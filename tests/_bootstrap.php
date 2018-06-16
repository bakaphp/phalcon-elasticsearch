<?php
// This is global bootstrap for autoloading

/**
 * Setup autoloading
 */
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/unit/PhalconUnitTestCase.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();
