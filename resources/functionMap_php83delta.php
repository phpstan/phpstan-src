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
		'DateInterval::createFromDateString' => ['static', 'modify'=>'string'],
		'DateTime::modify' => ['static', 'modify'=>'string'],
		'DateTimeImmutable::modify' => ['static', 'modify'=>'string'],
		'str_decrement' => ['non-empty-string', 'string'=>'non-empty-string'],
		'str_increment' => ['non-falsy-string', 'string'=>'non-empty-string'],
		'gc_status' => ['array{running:bool,protected:bool,full:bool,runs:int,collected:int,threshold:int,buffer_size:int,roots:int,application_time:float,collector_time:float,destructor_time:float,free_time:float}'],
		'opcache_get_configuration' => ['array{directives: array{\'opcache.enable\': bool, \'opcache.enable_cli\': bool, \'opcache.use_cwd\': bool, \'opcache.validate_timestamps\': bool, \'opcache.validate_permission\': bool, \'opcache.validate_root\'?: bool, \'opcache.dups_fix\': bool, \'opcache.revalidate_path\': bool, \'opcache.log_verbosity_level\': int, \'opcache.memory_consumption\': int, \'opcache.interned_strings_buffer\': int, \'opcache.max_accelerated_files\': int, \'opcache.max_wasted_percentage\': float, \'opcache.force_restart_timeout\': int, \'opcache.revalidate_freq\': int, \'opcache.preferred_memory_model\': string, \'opcache.blacklist_filename\': string, \'opcache.max_file_size\': int, \'opcache.error_log\': string, \'opcache.protect_memory\': bool, \'opcache.save_comments\': bool, \'opcache.record_warnings\': bool, \'opcache.enable_file_override\': bool, \'opcache.optimization_level\': int, \'opcache.lockfile_path\'?: string, \'opcache.mmap_base\'?: string, \'opcache.file_cache\': string, \'opcache.file_cache_only\': bool, \'opcache.file_cache_consistency_checks\': bool, \'opcache.file_cache_fallback\'?: bool, \'opcache.file_update_protection\': int, \'opcache.opt_debug_level\': int, \'opcache.restrict_api\': string, \'opcache.huge_code_pages\'?: bool, \'opcache.preload\': string, \'opcache.preload_user\'?: string, \'opcache.cache_id\'?: string, \'opcache.jit\'?: string, \'opcache.jit_buffer_size\'?: int, \'opcache.jit_debug\'?: int, \'opcache.jit_bisect_limit\'?: int, \'opcache.jit_blacklist_root_trace\'?: int, \'opcache.jit_blacklist_side_trace\'?: int, \'opcache.jit_hot_func\'?: int, \'opcache.jit_hot_loop\'?: int, \'opcache.jit_hot_return\'?: int, \'opcache.jit_hot_side_exit\'?: int, \'opcache.jit_max_exit_counters\'?: int, \'opcache.jit_max_loop_unrolls\'?: int, \'opcache.jit_max_polymorphic_calls\'?: int, \'opcache.jit_max_recursive_calls\'?: int, \'opcache.jit_max_recursive_returns\'?: int, \'opcache.jit_max_root_traces\'?: int, \'opcache.jit_max_side_traces\'?: int, \'opcache.jit_prof_threshold\'?: float, \'opcache.jit_max_trace_length\'?: int}, version: array{version: non-empty-string, opcache_product_name: non-empty-string}, blacklist: list<string>}|false'],
	],
	'old' => [
		'dba_fetch\'1' => ['string|false', 'key'=>'string', 'handle'=>'resource'],
		'opcache_get_configuration' => ['array{directives: array{\'opcache.enable\': bool, \'opcache.enable_cli\': bool, \'opcache.use_cwd\': bool, \'opcache.validate_timestamps\': bool, \'opcache.validate_permission\': bool, \'opcache.validate_root\'?: bool, \'opcache.dups_fix\': bool, \'opcache.revalidate_path\': bool, \'opcache.log_verbosity_level\': int, \'opcache.memory_consumption\': int, \'opcache.interned_strings_buffer\': int, \'opcache.max_accelerated_files\': int, \'opcache.max_wasted_percentage\': float, \'opcache.consistency_checks\': int, \'opcache.force_restart_timeout\': int, \'opcache.revalidate_freq\': int, \'opcache.preferred_memory_model\': string, \'opcache.blacklist_filename\': string, \'opcache.max_file_size\': int, \'opcache.error_log\': string, \'opcache.protect_memory\': bool, \'opcache.save_comments\': bool, \'opcache.record_warnings\': bool, \'opcache.enable_file_override\': bool, \'opcache.optimization_level\': int, \'opcache.lockfile_path\'?: string, \'opcache.mmap_base\'?: string, \'opcache.file_cache\': string, \'opcache.file_cache_only\': bool, \'opcache.file_cache_consistency_checks\': bool, \'opcache.file_cache_fallback\'?: bool, \'opcache.file_update_protection\': int, \'opcache.opt_debug_level\': int, \'opcache.restrict_api\': string, \'opcache.huge_code_pages\'?: bool, \'opcache.preload\': string, \'opcache.preload_user\'?: string, \'opcache.cache_id\'?: string, \'opcache.jit\'?: string, \'opcache.jit_buffer_size\'?: int, \'opcache.jit_debug\'?: int, \'opcache.jit_bisect_limit\'?: int, \'opcache.jit_blacklist_root_trace\'?: int, \'opcache.jit_blacklist_side_trace\'?: int, \'opcache.jit_hot_func\'?: int, \'opcache.jit_hot_loop\'?: int, \'opcache.jit_hot_return\'?: int, \'opcache.jit_hot_side_exit\'?: int, \'opcache.jit_max_exit_counters\'?: int, \'opcache.jit_max_loop_unrolls\'?: int, \'opcache.jit_max_polymorphic_calls\'?: int, \'opcache.jit_max_recursive_calls\'?: int, \'opcache.jit_max_recursive_returns\'?: int, \'opcache.jit_max_root_traces\'?: int, \'opcache.jit_max_side_traces\'?: int, \'opcache.jit_prof_threshold\'?: int}, version: array{version: non-empty-string, opcache_product_name: non-empty-string}, blacklist: list<string>}|false'],
	]
];
