<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.4 to php 8.3 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php8.3 or have different signatures in php 8.4.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 8.3.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		'bcround' => ['numeric-string', 'num'=>'numeric-string', 'precision='=>'int', 'mode='=>'RoundingMode'],
		'http_get_last_response_headers' => ['list<string>|null'],
		'http_clear_last_response_headers' => ['void'],
		'mb_lcfirst' => ['string', 'string'=>'string', 'encoding='=>'string'],
		'mb_ucfirst' => ['string', 'string'=>'string', 'encoding='=>'string'],
		'dba_close' => ['void', 'handle'=>'Dba\Connection'],
		'dba_delete' => ['bool', 'key'=>'string', 'handle'=>'Dba\Connection'],
		'dba_exists' => ['bool', 'key'=>'string', 'handle'=>'Dba\Connection'],
		'dba_fetch' => ['string|false', 'key'=>'string', 'skip'=>'int', 'handle'=>'Dba\Connection'],
		'dba_fetch\'1' => ['string|false', 'key'=>'string', 'handle'=>'Dba\Connection'],
		'dba_firstkey' => ['string|false', 'handle'=>'Dba\Connection'],
		'dba_insert' => ['bool', 'key'=>'string', 'value'=>'string', 'handle'=>'Dba\Connection'],
		'dba_nextkey' => ['string|false', 'handle'=>'Dba\Connection'],
		'dba_open' => ['Dba\Connection|false', 'path'=>'string', 'mode'=>'string', 'handlername='=>'string', '...args='=>'string'],
		'dba_optimize' => ['bool', 'handle'=>'Dba\Connection'],
		'dba_popen' => ['Dba\Connection|false', 'path'=>'string', 'mode'=>'string', 'handlername='=>'string', '...args='=>'string'],
		'dba_replace' => ['bool', 'key'=>'string', 'value'=>'string', 'handle'=>'Dba\Connection'],
		'dba_sync' => ['bool', 'handle'=>'Dba\Connection'],
	],
	'old' => [

	]
];
