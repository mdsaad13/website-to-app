<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/WebToApp.php';

// Check if script is running from the command line.
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

/**
 * Start the conversion process.
 * 
 * This will ask for the required details and start the conversion process.
 * 
 * - Create a new directory for the build files.
 * - Copy the android files to the build directory.
 * - Replace the necessary files. (Brand the app with the given details)
 * - Create the icons for the app.
 * - Build the APK file.
 * - Sign the APK file.
 * - Zipalign the APK file.
 * - Move the APK file to the build directory.
 */
WebToApp::start();
