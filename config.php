<?php

/**
 * The directory where the build files will be stored.
 * 
 * @var string
 */
define('BUILD_DIR', __DIR__ . '/build/');

/**
 * Android directory path.
 * This is where the Default Android project is located.
 * We will copy the files from this directory to the build directory.
 * And replace the necessary files.
 * 
 * @var string
 */
define('ANDROID_DIR', __DIR__ . '/android-template/');
