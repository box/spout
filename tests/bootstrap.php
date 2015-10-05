<?php
require_once(dirname(__DIR__) . '/vendor/autoload.php');

require_once(dirname(__DIR__) . '/tests/Spout/TestUsingResource.php');
require_once(dirname(__DIR__) . '/tests/Spout/ReflectionHelper.php');

// Make sure a timezone is set to be able to work with dates
date_default_timezone_set('UTC');
