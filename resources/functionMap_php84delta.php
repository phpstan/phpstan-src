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
		'http_get_last_response_header ' => ['list<string>'],
		'http_clear_last_response_header' => ['void'],
		'mb_lcfirst' => ['string', 'str'=>'string', 'encoding='=>'string'],
		'mb_ucfirst' => ['string', 'str'=>'string', 'encoding='=>'string'],
	],
	'old' => [

	]
];
