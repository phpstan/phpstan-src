<?php // phpcs:ignoreFile

/**
 * Copied over from https://github.com/phan/phan/blob/8866d6b98be94b37996390da226e8c4befea29aa/src/Phan/Language/Internal/FunctionSignatureMap_php80_delta.php
 * Copyright (c) 2015 Rasmus Lerdorf
 * Copyright (c) 2015 Andrew Morrison
 */

/**
 * This contains the information needed to convert the function signatures for php 8.0 to php 7.4 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.4 or have different signatures in php 8.0.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.4.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		'ReflectionFunction::getReturnType' => ['ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null'],
		'ReflectionMethod::getReturnType' => ['ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null'],
		'ReflectionParameter::getType' => ['ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null'],
		'ReflectionProperty::getType' => ['ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null'],
	],
	'old' => [
		'pg_escape_bytea' => ['string', 'connection'=>'resource', 'data'=>'string'],
		'pg_escape_bytea\'1' => ['string', 'data'=>'string'],
		'pg_escape_identifier' => ['string|false', 'connection'=>'resource', 'data'=>'string'],
		'pg_escape_identifier\'1' => ['string', 'data'=>'string'],
		'pg_escape_literal' => ['string|false', 'connection'=>'resource', 'data'=>'string'],
		'pg_escape_literal\'1' => ['string', 'data'=>'string'],
		'pg_escape_string' => ['string', 'connection'=>'resource', 'data'=>'string'],
		'pg_escape_string\'1' => ['string', 'data'=>'string'],
		'pg_execute' => ['resource|false', 'connection'=>'resource', 'stmtname'=>'string', 'params'=>'array'],
		'pg_execute\'1' => ['resource|false', 'stmtname'=>'string', 'params'=>'array'],
		'pg_fetch_object' => ['object|false', 'result'=>'', 'row='=>'?int', 'result_type='=>'int'],
		'pg_fetch_object\'1' => ['object', 'result'=>'', 'row='=>'?int', 'class_name='=>'string', 'ctor_params='=>'array'],
		'pg_fetch_result' => ['', 'result'=>'', 'field_name'=>'string|int'],
		'pg_fetch_result\'1' => ['', 'result'=>'', 'row_number'=>'int', 'field_name'=>'string|int'],
		'pg_field_is_null' => ['int|false', 'result'=>'', 'field_name_or_number'=>'string|int'],
		'pg_field_is_null\'1' => ['int', 'result'=>'', 'row'=>'int', 'field_name_or_number'=>'string|int'],
		'pg_field_prtlen' => ['int|false', 'result'=>'', 'field_name_or_number'=>''],
		'pg_field_prtlen\'1' => ['int', 'result'=>'', 'row'=>'int', 'field_name_or_number'=>'string|int'],
		'pg_lo_export' => ['bool', 'connection'=>'resource', 'oid'=>'int', 'filename'=>'string'],
		'pg_lo_export\'1' => ['bool', 'oid'=>'int', 'pathname'=>'string'],
		'pg_lo_import' => ['int|false', 'connection'=>'resource', 'pathname'=>'string', 'oid'=>''],
		'pg_lo_import\'1' => ['int', 'pathname'=>'string', 'oid'=>''],
		'pg_parameter_status' => ['string|false', 'connection'=>'resource', 'param_name'=>'string'],
		'pg_parameter_status\'1' => ['string|false', 'param_name'=>'string'],
		'pg_prepare' => ['resource|false', 'connection'=>'resource', 'stmtname'=>'string', 'query'=>'string'],
		'pg_prepare\'1' => ['resource|false', 'stmtname'=>'string', 'query'=>'string'],
		'pg_put_line' => ['bool', 'connection'=>'resource', 'data'=>'string'],
		'pg_put_line\'1' => ['bool', 'data'=>'string'],
		'pg_query' => ['resource|false', 'connection'=>'resource', 'query'=>'string'],
		'pg_query\'1' => ['resource|false', 'query'=>'string'],
		'pg_query_params' => ['resource|false', 'connection'=>'resource', 'query'=>'string', 'params'=>'array'],
		'pg_query_params\'1' => ['resource|false', 'query'=>'string', 'params'=>'array'],
		'pg_set_client_encoding' => ['int', 'connection'=>'resource', 'encoding'=>'string'],
		'pg_set_client_encoding\'1' => ['int', 'encoding'=>'string'],
		'pg_set_error_verbosity' => ['int|false', 'connection'=>'resource', 'verbosity'=>'int'],
		'pg_set_error_verbosity\'1' => ['int', 'verbosity'=>'int'],
		'pg_tty' => ['string', 'connection='=>'resource'],
		'pg_tty\'1' => ['string'],
		'pg_untrace' => ['bool', 'connection='=>'resource'],
		'pg_untrace\'1' => ['bool'],
		'ReflectionFunction::getReturnType' => ['ReflectionNamedType|ReflectionUnionType|null'],
		'ReflectionMethod::getReturnType' => ['ReflectionNamedType|ReflectionUnionType|null'],
		'ReflectionParameter::getType' => ['ReflectionNamedType|ReflectionUnionType|null'],
		'ReflectionProperty::getType' => ['ReflectionNamedType|ReflectionUnionType|null'],
	]
];
