<?php

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class WebToApp
{
    private $startTime;
    private $endTime;

    private string $appName;
    private string $shortName;
    private string $appUrl;
    private string $iconPath;

    private string $packageName;
    private int $versionCode;
    private string $versionName;

    private string $buildDir;

    private array $iconsFormat = [
        'mipmap-hdpi' => 72,
        'mipmap-mdpi' => 48,
        'mipmap-xhdpi' => 96,
        'mipmap-xxhdpi' => 144,
        'mipmap-xxxhdpi' => 192,
    ];

    private array $timings = [];

    public static function start(): void
    {
        (new self())->startConversion();

        return;
    }

    public function startConversion(): void
    {
        $this->giveIntro();

        if (confirm('Load from a previous build?', false)) {
            $this->setupFromReference();
        } else {
            $this->setupFromScratch();
        }

        spin(function () {
            info('Building the DEV APK file...');

            // Build the APK file
            $this->createDevApk();

            info('APK file created.');
            note('Location: ' . $this->buildDir . $this->shortName . '-dev.apk');
        }, 'Building the APK file');

        $this->endTime = microtime(true);

        note('');
        info('----------------------------------------');
        info('BUILD COMPLETED!');
        info('----------------------------------------');
        $this->printTimings();
        info('The results are stored in "' . $this->buildDir . '"');
        info('Dev APK file: ' . $this->buildDir . $this->shortName . '-dev.apk');

        if (confirm('Do you want to build the release APK file?')) {
            // Build the release APK file
            // $this->createReleaseApk();
        }

        if (confirm('Do you want to run the app in an emulator?')) {
            // Run the app in an emulator
            exec('cd ' . $this->buildDir . 'android/ && adb install -r ' . $this->shortName . '-dev.apk', $output, $return_var);

            if ($return_var === 0) {
                info('App installed successfully.');
            } else {
                error('Failed to install the app. Exiting...');
                exit(1);
            }
        }

        return;
    }

    #region Prompts

    private function giveIntro(): self
    {
        intro("Welcome to the Web to App CLI!");
        warning('This CLI will help you convert your website into Android app.');
        info('Please answer the following questions to get started.');

        return $this;
    }

    private function getAppName(): self
    {
        $this->appName = text('What is your web app name?');

        // remove spaces
        $this->shortName = strtolower(str_replace('-', '', $this->appName));

        // remove special characters
        $this->shortName = preg_replace('/[^A-Za-z0-9\-]/', '', $this->shortName);

        return $this;
    }

    private function getAppUrl(): self
    {
        $this->appUrl = text('What is your web app URL?');

        // Check if the URL is valid
        if (!filter_var($this->appUrl, FILTER_VALIDATE_URL)) {
            error('The URL is not valid. Exiting...');
            exit(1);
        }

        // Check if the URL is reachable
        if (!@fopen($this->appUrl, 'r')) {
            error('The URL is not reachable. Exiting...');
            exit(1);
        }

        return $this;
    }

    private function getIconPath(): self
    {
        $this->iconPath = text('What is the path to your web app icon?', 'Enter your complete local path and make sure it\'s 1024*1024 pixels and in PNG format.');

        // Check if the file exists and is a PNG
        if (!file_exists($this->iconPath) || pathinfo($this->iconPath, PATHINFO_EXTENSION) !== 'png') {
            error('The file does not exist. Exiting...');
            exit(1);
        }

        return $this;
    }

    private function getPackageName(): self
    {
        $this->packageName = suggest('What is the package name?', [
            'com.' . $this->shortName,
            'com.' . $this->shortName . '.app',
            'com.' . $this->shortName . '.android',
        ]);

        return $this;
    }

    private function getVersionCode(string $default = '1'): self
    {
        $this->versionCode = (int) text('What is the version code?', 'Enter a number.', $default);

        // Check if the version code is valid
        if ($this->versionCode < 1) {
            error('The version code is not valid. Exiting...');
            exit(1);
        }

        return $this;
    }

    private function getVersionName(string $default = '1.0'): self
    {
        $this->versionName = text('What is the version name?', 'Enter a string.', $default);

        return $this;
    }

    #endregion Prompts

    #region Functions

    private function setupFromReference(): self
    {
        $directoriesInBuild = array_filter(glob(BUILD_DIR . '*'), 'is_dir');

        if (empty($directoriesInBuild)) {
            warning('No previous build found. Exiting...');
            $this->buildDir = text('Enter the build directory name:', 'Enter the name of the directory where the build files are stored.');
        } else {
            $projects = [];

            foreach ($directoriesInBuild as $dir) {
                $projects[basename($dir)] = $dir;
            }

            $project = select('Select the build directory:', array_keys($projects));

            $this->buildDir = $projects[$project];
        }

        // Add trailing slash if not present
        if (substr($this->buildDir, -1) !== '/') {
            $this->buildDir .= '/';
        }

        // Check if the directory exists
        if (!file_exists($this->buildDir)) {
            error('The directory does not exist. Exiting...');
            exit(1);
        }

        // Check if the reference file exists
        if (!file_exists($this->buildDir . 'reference.json')) {
            error('The reference file does not exist. Exiting...');
            exit(1);
        }

        // Check if android directory exists
        if (!file_exists($this->buildDir . 'android/')) {
            error('The android directory does not exist. Exiting...');
            exit(1);
        }

        // Load the reference file
        $reference = json_decode(file_get_contents($this->buildDir . 'reference.json'), true);

        $this->appName = $reference['name'];
        $this->shortName = $reference['shortName'];
        $this->appUrl = $reference['url'];
        $this->packageName = $reference['packageName'];
        $this->versionCode = $reference['versionCode'];
        $this->versionName = $reference['versionName'];

        return $this;
    }

    private function setupFromScratch(): self
    {
        // Get required details
        $this->getAppName()->getAppUrl()->getIconPath()->getPackageName()->getVersionCode()->getVersionName();

        $this->startTime = microtime(true);

        info('Creating "' . $this->appName . '" app with URL "' . $this->appUrl . '"');
        warning('This might take a while...');

        spin(function () {
            info('Copying files and initializing the app...');

            // Initialize the build directory and copy the android files
            $this->createDirectories()->copyAndroidFiles()->createIcons();

            // Replace the variables and icons in the files
            $this->replaceFiles()->replaceIcons()->createReferenceFile();

            info('Android code setup completed.');
            note('Location: ' . $this->buildDir);

            $this->endTime = microtime(true);

            $this->timings['setup'] = round($this->endTime - $this->startTime, 2);
        }, 'Setting up the app');

        return $this;
    }

    private function createDirectories(): self
    {
        $this->buildDir = BUILD_DIR . $this->shortName . '/';

        // Create the output directory and make sure it's empty
        if (file_exists($this->buildDir)) {
            error('The output directory already exists. Exiting...');
            exit(1);
        }

        mkdir($this->buildDir, 0777, true);

        // Create the icons directory
        mkdir($this->buildDir . 'icons/', 0777, true);

        // Create android directory
        mkdir($this->buildDir . 'android/', 0777, true);

        return $this;
    }

    private function copyAndroidFiles(): self
    {
        // Copy the files from the android directory to the build directory
        $this->copyFiles(ANDROID_DIR, $this->buildDir . 'android/');

        // Give execute permission to gradlew
        chmod($this->buildDir . 'android/gradlew', 0755);

        return $this;
    }

    private function createIcons(): self
    {
        $icon = imagecreatefrompng($this->iconPath);

        foreach ($this->iconsFormat as $dir => $size) {
            $resizedIcon = imagescale($icon, $size, $size);
            imagepng($resizedIcon, $this->buildDir . 'icons/' . $dir . '.png', 0);
        }

        return $this;
    }

    private function replaceFiles(): self
    {
        // Replace the variables in the files
        $files = [
            'app/build.gradle' => [
                'APPLICATION_ID' => $this->packageName,
                'VERSION_CODE' => $this->versionCode,
                'VERSION_NAME' => $this->versionName,
            ],
            'app/src/main/AndroidManifest.xml' => [
                'APPLICATION_ID' => $this->packageName,
            ],
            'app/src/main/res/values/strings.xml' => [
                'APP_NAME' => $this->appName,
                'APP_URL' => $this->appUrl,
            ],
            'app/src/main/java/com.example.app/MainActivity.java' => [
                'APPLICATION_ID' => $this->packageName,
            ],
            'app/src/main/java/com.example.app/MyWebViewClient.java' => [
                'APPLICATION_ID' => $this->packageName,
            ],
        ];

        $androidDir = $this->buildDir . 'android/';

        foreach ($files as $file => $replacements) {
            $content = file_get_contents($androidDir . $file);

            foreach ($replacements as $search => $replace) {
                $content = str_replace('{{' . $search . '}}', $replace, $content);
            }

            file_put_contents($androidDir . $file, $content);
        }

        // Rename com.example.app directory to the package name
        rename(
            $androidDir . 'app/src/main/java/com.example.app/',
            // $androidDir . 'app/src/main/java/' . str_replace('.', '/', $this->packageName) . '/'
            $androidDir . 'app/src/main/java/' . $this->packageName . '/'
        );

        return $this;
    }

    private function replaceIcons(): self
    {
        $androidDir = $this->buildDir . 'android/';

        foreach ($this->iconsFormat as $dir => $size) {
            copy(
                // From
                $this->buildDir . 'icons/' . $dir . '.png',
                // To
                $androidDir . 'app/src/main/res/' . $dir . '/ic_launcher.png'
            );
        }

        return $this;
    }

    private function createReferenceFile(): self
    {
        // Create a json file with the details for future reference

        $data = [
            'name' => $this->appName,
            'shortName' => $this->shortName,
            'url' => $this->appUrl,
            'packageName' => $this->packageName,
            'versionCode' => $this->versionCode,
            'versionName' => $this->versionName,
        ];

        file_put_contents($this->buildDir . 'reference.json', json_encode($data, JSON_PRETTY_PRINT));

        return $this;
    }

    private function createDevApk(): self
    {
        $this->startTime = microtime(true);

        // Create the APK file
        $androidDir = $this->buildDir . 'android/';

        exec('cd ' . $androidDir . ' && ./gradlew assembleDebug', $output, $return_var);

        if ($return_var !== 0) {
            error('Failed to build the APK file. Exiting...');
            exit(1);
        }

        // Move the APK file to the build directory
        rename(
            $androidDir . 'app/build/outputs/apk/debug/app-debug.apk',
            $this->buildDir . $this->shortName . '-dev.apk'
        );

        $this->endTime = microtime(true);

        $this->timings['devApk'] = round($this->endTime - $this->startTime, 2);

        return $this;
    }

    private function printTimings(): void
    {
        if (empty($this->timings)) {
            return;
        }

        info('Time taken:');
        foreach ($this->timings as $name => $time) {
            info($name . ': ' . $time . ' seconds');
        }
    }

    #endregion Functions

    #region Helpers

    private function copyFiles(string $source, string $destination): void
    {
        $files = scandir($source);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir($source . $file)) {
                mkdir($destination . $file, 0777, true);
                $this->copyFiles($source . $file . '/', $destination . $file . '/');
            } else {
                copy($source . $file, $destination . $file);
            }
        }
    }

    #endregion Helpers
}
