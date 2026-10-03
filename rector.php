<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

// The sets of zairakai/laravel-dev-tools for PHP 8.3, without the Laravel ones.
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withCache(__DIR__ . '/build/rector')
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withParallel();
