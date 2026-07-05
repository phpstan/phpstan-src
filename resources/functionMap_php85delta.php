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
		'session_get_cookie_params' => ['array{lifetime:0|positive-int,path:non-falsy-string,domain:string,secure:bool,httponly:bool,samesite:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\',partitioned:bool}'],
		'session_set_cookie_params\'1' => ['bool', 'options'=>'array{lifetime?:int,path?:string,domain?:string,secure?:bool,httponly?:bool,samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\',partitioned?:bool}'],
		'setcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array{ expires?:int, path?:string, domain?:string, secure?:bool, httponly?:bool, samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\', partitioned?:bool}'],
		'setrawcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array{ expires?:int, path?:string, domain?:string, secure?:bool, httponly?:bool, samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\', partitioned?:bool}'],
		'SQLite3Result::fetchAll' => ['($mode is SQLITE3_NUM ? list<non-empty-list<mixed>>|false : ($mode is SQLITE3_ASSOC ? list<non-empty-array<string,mixed>>|false : list<non-empty-array<int|string,mixed>>|false))', 'mode='=>'int'],
	],
	'old' => [
		'chr' => ['non-empty-string', 'ascii'=>'int'],
		'session_get_cookie_params' => ['array{lifetime:0|positive-int,path:non-falsy-string,domain:string,secure:bool,httponly:bool,samesite:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\'}'],
		'session_set_cookie_params\'1' => ['bool', 'options'=>'array{lifetime?:int,path?:string,domain?:string,secure?:bool,httponly?:bool,samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\'}'],
		'setcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array{ expires?:int, path?:string, domain?:string, secure?:bool, httponly?:bool, samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\'}'],
		'setrawcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array{ expires?:int, path?:string, domain?:string, secure?:bool, httponly?:bool, samesite?:\'None\'|\'Lax\'|\'Strict\'|\'none\'|\'lax\'|\'strict\'}'],
	]
];
