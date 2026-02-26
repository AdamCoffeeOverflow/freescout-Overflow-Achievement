<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules', 'public/vendor'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'no_trailing_whitespace' => true,
        'single_quote' => true,
    ]);
