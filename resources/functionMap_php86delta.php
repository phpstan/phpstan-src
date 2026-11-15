<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.6 to php 8.5 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php8.5 or have different signatures in php 8.6.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 8.5.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		// https://wiki.php.net/rfc/deprecations_php_8_6#passing_objects_which_are_interpreted_as_arrays
		'array_walk' => ['bool', '&rw_input'=>'array', 'callback'=>'callable', 'userdata='=>'mixed'],
		'array_walk_recursive' => ['bool', '&rw_input'=>'array', 'callback'=>'callable', 'userdata='=>'mixed'],
		'deflate_init' => ['resource|false', 'encoding'=>'int', 'options='=>'array'],
		'http_build_query' => ['string', 'querydata'=>'array', 'prefix='=>'string', 'arg_separator='=>'string', 'enc_type='=>'int'],
		'inflate_init' => ['resource|false', 'encoding'=>'int', 'options='=>'array'],
		'mb_convert_variables' => ['string|false', 'to_encoding'=>'string', 'from_encoding'=>'array|string', '&rw_vars'=>'string|array', '&...rw_vars='=>'string|array'],
	],
	'old' => [
		'array_walk' => ['bool', '&rw_input'=>'array|object', 'callback'=>'callable', 'userdata='=>'mixed'],
		'array_walk_recursive' => ['bool', '&rw_input'=>'array|object', 'callback'=>'callable', 'userdata='=>'mixed'],
		'deflate_init' => ['resource|false', 'encoding'=>'int', 'options='=>'array|object'],
		'http_build_query' => ['string', 'querydata'=>'array|object', 'prefix='=>'string', 'arg_separator='=>'string', 'enc_type='=>'int'],
		'inflate_init' => ['resource|false', 'encoding'=>'int', 'options='=>'array|object'],
		'mb_convert_variables' => ['string|false', 'to_encoding'=>'string', 'from_encoding'=>'array|string', '&rw_vars'=>'string|array|object', '&...rw_vars='=>'string|array|object'],
	],
];
