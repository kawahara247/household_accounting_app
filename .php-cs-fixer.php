<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database/factories',
        __DIR__ . '/database/seeders',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->exclude('MultiAuth')
    ->exclude('/database/migrations');

$config = new PhpCsFixer\Config;

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer:risky'            => true, // 危険なルールを含むルールセットを有効にする
        'array_syntax'                 => ['syntax' => 'short'], // 短い配列構文[]を使用する
        'blank_line_after_opening_tag' => true, // <?PHPタグの後に空行を入れる
        'blank_lines_before_namespace' => true, // 名前空間の前に空行を入れる
        'binary_operator_spaces'       => [ // 二項演算子の前後にスペースを入れる
            'default'   => 'single_space',
            'operators' => [
                '=>'  => 'align',
                '=='  => 'align',
                '===' => 'align',
                '!='  => 'align',
                '!==' => 'align',
                '='   => 'align',
            ],
        ],
        'cast_spaces'                 => ['space' => 'single'], // キャスト演算子の前後にスペースを入れる
        'class_attributes_separation' => [ // クラス属性の改行
            'elements' => [
                'const'    => 'one', // 定数
                'method'   => 'one', // メソッド
                'property' => 'one', // プロパティ
            ],
        ],
        'compact_nullable_type_declaration'               => true, // null許容型の型ヒントを短縮する
        'concat_space'                                    => ['spacing' => 'one'], // 文字列結合演算子の前後にスペースを入れる
        'constant_case'                                   => ['case' => 'lower'], // false, true, nullの定数を小文字にする
        'control_structure_continuation_position'         => true, // 制御構造の継続行
        'control_structure_braces'                        => true, // 制御構造の中括弧を必ず使う
        'braces'                                          => [
            'allow_single_line_closure'                   => true,
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_control_structures'           => 'same',
            'position_after_anonymous_constructs'         => 'same',
        ],
        'braces_position'                         => [ // 中括弧の位置
            'control_structures_opening_brace'          => 'same_line',
            'functions_opening_brace'                   => 'next_line_unless_newline_at_signature_end',
            'anonymous_functions_opening_brace'         => 'same_line',
            'classes_opening_brace'                     => 'next_line_unless_newline_at_signature_end',
            'anonymous_classes_opening_brace'           => 'next_line_unless_newline_at_signature_end',
            'allow_single_line_empty_anonymous_classes' => false,
            'allow_single_line_anonymous_functions'     => false,
        ],
        'statement_indentation'                   => true, // ステートメントのインデント
        'declare_strict_types'                    => true, // declare(strict_types=1);を追加する
        'explicit_string_variable'                => true, // 文字列変数を明示的に指定する
        'function_declaration'                    => ['closure_function_spacing' => 'one'], // 関数宣言の前後にスペースを入れる
        'global_namespace_import'                 => [ // グローバル名前空間のインポート
            'import_classes'   => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'indentation_type'                                 => true, // インデントのタイプをスペースにする
        'lowercase_cast'                                   => true, // キャスト演算子を小文字にする
        'lowercase_keywords'                               => true, // キーワードを小文字にする
        'linebreak_after_opening_tag'                      => false, // <?PHPタグの後に改行を入れない
        'method_chaining_indentation'                      => true, // メソッドチェーンのインデント
        'multiline_comment_opening_closing'                => true, // 複数行コメントの開始と終了
        'multiline_whitespace_before_semicolons'           => ['strategy' => 'no_multi_line'], // セミコロンの前にスペースを入れない
        'native_function_invocation'                       => false, // 組み込み関数の呼び出し
        'native_type_declaration_casing'                   => true, // 型宣言の大文字小文字を統一する
        'new_with_parentheses'                             => ['named_class' => false], // newの後にカッコを付ける
        'nullable_type_declaration_for_default_null_value' => true, // null許容型の型ヒントを追加する
        'no_multiline_whitespace_around_double_arrow'      => true, // =>の前後に改行を入れない
        'no_blank_lines_after_class_opening'               => true, // クラス宣言の開始後に空行を入れない
        'no_blank_lines_after_phpdoc'                      => true, // PHPDocの後に空行を入れない
        'no_empty_phpdoc'                                  => true, // 空のPHPDocを削除する
        'no_extra_blank_lines'                             => ['tokens' => ['extra']], // 余分な空行を削除する
        'no_spaces_after_function_name'                    => true, // 関数名の後にスペースを入れない
        'no_superfluous_phpdoc_tags'                       => false, // 不要なPHPDocタグを削除する
        'no_useless_else'                                  => true, // 不要なelseを削除する
        'no_whitespace_before_comma_in_array'              => ['after_heredoc' => false], // 配列のカンマの前にスペースを入れない
        'object_operator_without_whitespace'               => true, // オブジェクト演算子の前後にスペースを入れない
        'ordered_traits'                                   => true, // トレイトをアルファベット順に並べる
        'ordered_imports'                                  => true, // インポートをアルファベット順に並べる
        'php_unit_test_case_static_method_calls'           => [ // PHPUnitのテストケースの静的メソッド呼び出し
            'call_type' => 'this',
        ],
        'phpdoc_align' => [ // PHPDocの整列
            'align' => 'left',
        ],
        'phpdoc_types_order' => [ // PHPDocの型の順序
            'null_adjustment' => 'always_last',
            'sort_algorithm'  => 'none',
        ],
        'phpdoc_order'                    => true, // PHPDocの順序
        'phpdoc_separation'               => true, // PHPDocの区切り
        'phpdoc_single_line_var_spacing'  => true,   // PHPDocの変数の前後にスペースを入れる
        'phpdoc_trim'                     => true, // PHPDocのトリム
        'return_type_declaration'         => ['space_before' => 'none'], // 戻り値の型ヒントの前にスペースを入れない
        'static_lambda'                   => false, // 静的ラムダ式を使用しない
        'single_space_around_construct'   => true, // 構築子の前後にスペースを入れる
        'strict_comparison'               => true, // 厳密な比較演算子を使用する
        'ternary_operator_spaces'         => true, // 三項演算子の前後にスペースを入れる
        'type_declaration_spaces'         => true, // 型ヒントの前後にスペースを入れる
        'whitespace_after_comma_in_array' => true, // 配列のカンマの後にスペースを入れる

        // 追加の推奨ルール
        'no_unused_imports'                 => true, // 未使用のuseの削除
        'single_import_per_statement'       => true, // 1行1つのuse文
        'no_trailing_whitespace'            => true, // 行末の空白を削除
        'no_trailing_whitespace_in_comment' => true, // コメント行末の空白を削除
        'trailing_comma_in_multiline'       => true, // 複数行配列の末尾カンマ
        'single_line_after_imports'         => true, // use文の後に1行空ける
        'no_whitespace_in_blank_line'       => true, // 空行内の空白を削除
        'class_definition'                  => true, // クラス定義の整形（PSR-12準拠）
        'method_argument_space'             => [ // メソッド引数のスペース
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'function_typehint_space'        => true, // 型ヒントの後のスペース
        'no_spaces_around_offset'        => true, // 配列アクセス時のスペース削除
        'no_spaces_inside_parenthesis'   => true, // 括弧内のスペース削除
        'elseif'                         => true, // else ifをelseifに統一
        'switch_case_semicolon_to_colon' => true, // switch文のセミコロンをコロンに
        'switch_case_space'              => true, // switch文のcaseのスペース
        'no_break_comment'               => true, // switch文のbreakコメント
        'lowercase_static_reference'     => true, // static参照を小文字に
        'magic_constant_casing'          => true, // マジック定数を小文字に
        'magic_method_casing'            => true, // マジックメソッドを小文字に
        'visibility_required'            => true, // visibility修飾子を必須に
        'ordered_class_elements'         => [ // クラス要素の順序
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
        ],
    ])
    ->setFinder($finder);
