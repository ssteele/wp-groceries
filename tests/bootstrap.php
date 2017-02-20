<?php

// First we need to load the composer autoloader so we can use WP Mock
require_once 'vendor/autoload.php';
require_once '../shs-sanitize/vendor/autoload.php';

// Now call the bootstrap method of WP Mock
WP_Mock::bootstrap();
