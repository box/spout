<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->exclude('vendor');

$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'align_multiline_comment' => false,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => null],
        'blank_line_before_statement' => ['statements' => ['return']],
        'combine_consecutive_unsets' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => ['space' => 'single'],
        'heredoc_to_nowdoc' => true,
        'increment_style' => ['style' => 'post'],
        'is_null' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'modernize_types_casting' => true,
        'no_break_comment' => ['comment_text' => 'do nothing'],
        'no_empty_phpdoc' => false,
        'no_null_property_initialization' => true,
        'echo_tag_syntax' => false,
        'no_superfluous_elseif' => true,
        'no_superfluous_phpdoc_tags' => false,
        'no_unneeded_control_parentheses' => ['statements' => ['break', 'clone', 'continue', 'echo_print', 'switch_case', 'yield']],
        'no_unneeded_curly_braces' => true,
        'no_unneeded_final_method' => true,
        'no_useless_else' => false,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_align' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_no_empty_return' => false,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'phpdoc_separation' => false,
        'protected_to_private' => true,
        'psr_autoloading' => true,
        'return_type_declaration' => ['space_before' => 'one'],
        'semicolon_after_instruction' => true,
        'simplified_null_return' => false,
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'strict_comparison' => true,
        'yoda_style' => ['equal' => false, 'identical' => false],
    ])
    ->setFinder($finder);
