<?php // @codingStandardsIgnoreFile (phpcs runs out of memory)

/**
 * Copied over from https://github.com/phan/phan/blob/da49cd287e3b315f5dfdb440522da797d980fe63/src/Phan/Language/Internal/FunctionSignatureMap_php74_delta.php
 * with more functions added from PHP 7.4 changelog
 * Copyright (c) 2015 Rasmus Lerdorf
 * Copyright (c) 2015 Andrew Morrison
 */

/**
 * This contains the information needed to convert the function signatures for php 7.4 to php 7.3 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.3 or have different signatures in php 7.4.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.3.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		'FFI::addr' => ['FFI\CData', 'ptr'=>'FFI\CData'],
		'FFI::alignof' => ['int', 'ptr'=>'mixed'],
		'FFI::arrayType' => ['FFI\CType', 'type'=>'string|FFI\CType', 'dims'=>'array<int,int>'],
		'FFI::cast' => ['FFI\CData', 'type'=>'string|FFI\CType', 'ptr'=>''],
		'FFI::cdef' => ['FFI', 'code='=>'string', 'lib='=>'?string'],
		'FFI::free' => ['void', 'ptr'=>'FFI\CData'],
		'FFI::load' => ['FFI', 'filename'=>'string'],
		'FFI::memcmp' => ['int', 'ptr1'=>'FFI\CData|string', 'ptr2'=>'FFI\CData|string', 'size'=>'int'],
		'FFI::memcpy' => ['void', 'dst'=>'FFI\CData', 'src'=>'string|FFI\CData', 'size'=>'int'],
		'FFI::memset' => ['void', 'ptr'=>'FFI\CData', 'ch'=>'int', 'size'=>'int'],
		'FFI::new' => ['FFI\CData', 'type'=>'string|FFI\CType', 'owned='=>'bool', 'persistent='=>'bool'],
		'FFI::scope' => ['FFI', 'scope_name'=>'string'],
		'FFI::sizeof' => ['int', 'ptr'=>'FFI\CData|FFI\CType'],
		'FFI::string' => ['string', 'ptr'=>'FFI\CData', 'size='=>'int'],
		'FFI::typeof' => ['FFI\CType', 'ptr'=>'FFI\CData'],
		'FFI::type' => ['FFI\CType', 'type'=>'string'],
		'fread' => ['string|false', 'fp'=>'resource', 'length'=>'positive-int'],
		'get_mangled_object_vars' => ['array', 'obj'=>'object'],
		'mb_str_split' => ['list<string>|false', 'str'=>'string', 'split_length='=>'int', 'encoding='=>'string'],
		'opcache_get_configuration' => ['array{directives: array{\'opcache.enable\': bool, \'opcache.enable_cli\': bool, \'opcache.use_cwd\': bool, \'opcache.validate_timestamps\': bool, \'opcache.validate_permission\': bool, \'opcache.validate_root\'?: bool, \'opcache.dups_fix\': bool, \'opcache.revalidate_path\': bool, \'opcache.log_verbosity_level\': int, \'opcache.memory_consumption\': int, \'opcache.interned_strings_buffer\': int, \'opcache.max_accelerated_files\': int, \'opcache.max_wasted_percentage\': float, \'opcache.consistency_checks\': int, \'opcache.force_restart_timeout\': int, \'opcache.revalidate_freq\': int, \'opcache.preferred_memory_model\': string, \'opcache.blacklist_filename\': string, \'opcache.max_file_size\': int, \'opcache.error_log\': string, \'opcache.protect_memory\': bool, \'opcache.save_comments\': bool, \'opcache.enable_file_override\': bool, \'opcache.optimization_level\': int, \'opcache.lockfile_path\'?: string, \'opcache.mmap_base\'?: string, \'opcache.file_cache\': string, \'opcache.file_cache_only\': bool, \'opcache.file_cache_consistency_checks\': bool, \'opcache.file_cache_fallback\'?: bool, \'opcache.file_update_protection\': int, \'opcache.opt_debug_level\': int, \'opcache.restrict_api\': string, \'opcache.huge_code_pages\'?: bool, \'opcache.preload\': string, \'opcache.preload_user\'?: string, \'opcache.cache_id\'?: string}, version: array{version: non-empty-string, opcache_product_name: non-empty-string}, blacklist: list<string>}|false'],
		'password_algos' => ['list<string>'],
		'password_needs_rehash' => ['bool', 'hash'=>'string', 'algo'=>'string|null', 'options='=>'array'],
		'preg_replace_callback' => ['string|array|null', 'regex'=>'string|array', 'callback'=>'callable(array<int|string, string|null>):string', 'subject'=>'string|array', 'limit='=>'int', '&w_count='=>'int', 'flags='=>'int'],
		'preg_replace_callback_array' => ['string|array|null', 'pattern'=>'array<string,callable>', 'subject'=>'string|array', 'limit='=>'int', '&w_count='=>'int', 'flags='=>'int'],
		'sapi_windows_set_ctrl_handler' => ['bool', 'callable'=>'callable(int):void', 'add='=>'bool'],
		'ReflectionProperty::getType' => ['?ReflectionType'],
		'ReflectionProperty::hasType' => ['bool'],
		'ReflectionProperty::isInitialized' => ['bool', 'object='=>'?object'],
		'ReflectionReference::fromArrayElement' => ['?ReflectionReference', 'array'=>'array', 'key'=>'int|string'],
		'ReflectionReference::getId' => ['string'],
		'SplFileObject::fwrite' => ['int|false', 'str'=>'string', 'length='=>'int'],
		'SQLite3Stmt::getSQL' => ['string', 'expanded='=>'bool'],
		'strip_tags' => ['string', 'str'=>'string', 'allowable_tags='=>'string|array<int, string>'],
		'WeakReference::create' => ['WeakReference', 'referent'=>'object'],
		'WeakReference::get' => ['?object'],
		'proc_open' => ['resource|false', 'command'=>'string|list<string>', 'descriptorspec'=>'array', '&w_pipes'=>'resource[]', 'cwd='=>'?string', 'env='=>'?array', 'other_options='=>'array'],
		'unserialize' => ['mixed', 'data'=>'string', 'options='=>'array{allowed_classes?:class-string[]|bool,max_depth?:int<0,max>}'],
	],
	'old' => [
		'implode\'2' => ['string', 'pieces'=>'array', 'glue'=>'string'],
		'join\'2' => ['string', 'pieces'=>'array', 'glue'=>'string'],
		'opcache_get_configuration' => ['array{directives: array{\'opcache.enable\': bool, \'opcache.enable_cli\': bool, \'opcache.use_cwd\': bool, \'opcache.validate_timestamps\': bool, \'opcache.validate_permission\': bool, \'opcache.validate_root\'?: bool, \'opcache.dups_fix\': bool, \'opcache.revalidate_path\': bool, \'opcache.log_verbosity_level\': int, \'opcache.memory_consumption\': int, \'opcache.interned_strings_buffer\': int, \'opcache.max_accelerated_files\': int, \'opcache.max_wasted_percentage\': float, \'opcache.consistency_checks\': int, \'opcache.force_restart_timeout\': int, \'opcache.revalidate_freq\': int, \'opcache.preferred_memory_model\': string, \'opcache.blacklist_filename\': string, \'opcache.max_file_size\': int, \'opcache.error_log\': string, \'opcache.protect_memory\': bool, \'opcache.save_comments\': bool, \'opcache.enable_file_override\': bool, \'opcache.optimization_level\': int, \'opcache.lockfile_path\'?: string, \'opcache.mmap_base\'?: string, \'opcache.file_cache\': string, \'opcache.file_cache_only\': bool, \'opcache.file_cache_consistency_checks\': bool, \'opcache.file_cache_fallback\'?: bool, \'opcache.file_update_protection\': int, \'opcache.opt_debug_level\': int, \'opcache.restrict_api\': string, \'opcache.huge_code_pages\'?: bool}, version: array{version: non-empty-string, opcache_product_name: non-empty-string}, blacklist: list<string>}|false'],
	],
];
