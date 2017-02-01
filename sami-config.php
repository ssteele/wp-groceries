<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in('src');

$options = [
    // 'theme'                => 'default',
    'title'                => 'SHS Groceries',
    'build_dir'            => __DIR__ . '/sami-documentation',
    'cache_dir'            => __DIR__ . '/sami-cache',
    'default_opened_level' => 2,
];

return new Sami($iterator, $options);
