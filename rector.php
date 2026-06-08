<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Deferred: enabling strict_types is a behavioral change; revisit with full test coverage.
        SafeDeclareStrictTypesRector::class,
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        codeQuality: true,
        deadCode: true,
    );
