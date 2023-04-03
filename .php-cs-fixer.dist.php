<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        '@PHP82Migration' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/src')
    )
;
