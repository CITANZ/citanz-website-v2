<?php

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_no_empty_return' => false,
        'phpdoc_to_comment' => false,
        'concat_space' => [
            'spacing' => 'one',
        ],
    ])
    ->setUsingCache(false)
;
