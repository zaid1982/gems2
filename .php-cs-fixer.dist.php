<?php

declare(strict_types=1);

/**
 * Code style is enforced only on the new, refactored code (api/src/Gfm and
 * tests). Legacy files are intentionally excluded so we never produce a
 * 1,000-file reformatting diff; folders graduate into scope as they are
 * migrated.
 */
$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/api/src/Gfm',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'native_function_invocation' => false,
    ])
    ->setFinder($finder);
