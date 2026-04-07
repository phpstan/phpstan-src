<?php

namespace OpcacheGetConfigurationPhp74;

use function opcache_get_configuration;
use function PHPStan\Testing\assertType;

$config = opcache_get_configuration();

assertType('array{directives: array{\'opcache.enable\': bool, \'opcache.enable_cli\': bool, \'opcache.use_cwd\': bool, \'opcache.validate_timestamps\': bool, \'opcache.validate_permission\': bool, \'opcache.validate_root\'?: bool, \'opcache.dups_fix\': bool, \'opcache.revalidate_path\': bool, \'opcache.log_verbosity_level\': int, \'opcache.memory_consumption\': int, \'opcache.interned_strings_buffer\': int, \'opcache.max_accelerated_files\': int, \'opcache.max_wasted_percentage\': float, \'opcache.consistency_checks\': int, \'opcache.force_restart_timeout\': int, \'opcache.revalidate_freq\': int, \'opcache.preferred_memory_model\': string, \'opcache.blacklist_filename\': string, \'opcache.max_file_size\': int, \'opcache.error_log\': string, \'opcache.protect_memory\': bool, \'opcache.save_comments\': bool, \'opcache.enable_file_override\': bool, \'opcache.optimization_level\': int, \'opcache.lockfile_path\'?: string, \'opcache.mmap_base\'?: string, \'opcache.file_cache\': string, \'opcache.file_cache_only\': bool, \'opcache.file_cache_consistency_checks\': bool, \'opcache.file_cache_fallback\'?: bool, \'opcache.file_update_protection\': int, \'opcache.opt_debug_level\': int, \'opcache.restrict_api\': string, \'opcache.huge_code_pages\'?: bool, \'opcache.preload\': string, \'opcache.preload_user\'?: string, \'opcache.cache_id\'?: string}, version: array{version: non-empty-string, opcache_product_name: non-empty-string}, blacklist: list<string>}|false', $config);
