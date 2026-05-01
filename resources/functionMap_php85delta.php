<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.5 to php 8.4 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php8.4 or have different signatures in php 8.5.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 8.4.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		'chr' => ['non-empty-string', 'ascii'=>'int<0,255>'],
		'SQLite3Result::fetchAll' => ['($mode is SQLITE3_NUM ? list<non-empty-list<mixed>>|false : ($mode is SQLITE3_ASSOC ? list<non-empty-array<string,mixed>>|false : list<non-empty-array<int|string,mixed>>|false))', 'mode='=>'int'],
	],
	'old' => [
		'chr' => ['non-empty-string', 'ascii'=>'int'],
	]
];
