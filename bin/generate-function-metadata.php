<?php declare(strict_types = 1);

// inspired by Psalm's Function::isCallMapFunctionPure()

use PHPStan\Type\Type;

function isFunctionPure(\PHPStan\Reflection\FunctionReflection $functionReflection): bool
{
	$impureFunctions = [
		'assert',
		// file io
		'chdir', 'chgrp', 'chmod', 'chown', 'chroot', 'closedir', 'copy', 'file_put_contents',
		'fopen', 'fread', 'fwrite', 'fclose', 'touch', 'fpassthru', 'fputs', 'fscanf', 'fseek',
		'ftruncate', 'fprintf', 'symlink', 'mkdir', 'unlink', 'rename', 'rmdir', 'popen', 'pclose',
		'fputcsv', 'umask', 'finfo_close', 'readline_add_history', 'stream_set_timeout',

		// stream/socket io
		'stream_context_set_option', 'socket_write', 'stream_set_blocking', 'socket_close',
		'socket_set_option', 'stream_set_write_buffer',

		// meta calls
		'call_user_func', 'call_user_func_array', 'define', 'create_function',

		// http
		'header', 'header_remove', 'http_response_code', 'setcookie',

		// output buffer
		'ob_start', 'ob_end_clean', 'readfile', 'printf', 'var_dump', 'phpinfo',
		'ob_implicit_flush',

		// mcrypt
		'mcrypt_generic_init', 'mcrypt_generic_deinit', 'mcrypt_module_close',

		// internal optimisation
		'opcache_compile_file', 'clearstatcache',

		// process-related
		'pcntl_signal', 'posix_kill', 'cli_set_process_title', 'pcntl_async_signals', 'proc_close',
		'proc_nice', 'proc_open', 'proc_terminate',

		// curl
		'curl_setopt', 'curl_close', 'curl_multi_add_handle', 'curl_multi_remove_handle',
		'curl_multi_select', 'curl_multi_close', 'curl_setopt_array',

		// apc, apcu
		'apc_store', 'apc_delete', 'apc_clear_cache', 'apc_add', 'apc_inc', 'apc_dec', 'apc_cas',
		'apcu_store', 'apcu_delete', 'apcu_clear_cache', 'apcu_add', 'apcu_inc', 'apcu_dec', 'apcu_cas',

		// gz
		'gzwrite', 'gzrewind', 'gzseek', 'gzclose',

		// newrelic
		'newrelic_start_transaction', 'newrelic_name_transaction', 'newrelic_add_custom_parameter',
		'newrelic_add_custom_tracer', 'newrelic_background_job', 'newrelic_end_transaction',
		'newrelic_set_appname',

		// execution
		'shell_exec', 'exec', 'system', 'passthru', 'pcntl_exec',

		// well-known functions
		'libxml_use_internal_errors', 'libxml_disable_entity_loader', 'curl_exec',
		'mt_srand', 'openssl_pkcs7_sign',
		'mt_rand', 'rand', 'wincache_ucache_delete', 'wincache_ucache_set', 'wincache_ucache_inc',
		'class_alias',

		// php environment
		'ini_set', 'sleep', 'usleep', 'register_shutdown_function',
		'error_reporting', 'register_tick_function', 'unregister_tick_function',
		'set_error_handler', 'user_error', 'trigger_error', 'restore_error_handler',
		'date_default_timezone_set', 'assert_options', 'setlocale',
		'set_exception_handler', 'set_time_limit', 'putenv', 'spl_autoload_register',
		'microtime', 'array_rand',

		// logging
		'openlog', 'syslog', 'error_log', 'define_syslog_variables',

		// session
		'session_id', 'session_name', 'session_set_cookie_params', 'session_set_save_handler',
		'session_regenerate_id', 'mb_internal_encoding',

		// ldap
		'ldap_set_option',

		// iterators
		'rewind', 'iterator_apply',

		// mysqli
		'mysqli_select_db', 'mysqli_dump_debug_info', 'mysqli_kill', 'mysqli_multi_query',
		'mysqli_next_result', 'mysqli_options', 'mysqli_ping', 'mysqli_query', 'mysqli_report',
		'mysqli_rollback', 'mysqli_savepoint', 'mysqli_set_charset', 'mysqli_ssl_set',
	];

	$functionName = $functionReflection->getName();

	if (strpos($functionName, 'set_') !== false) {
		return false;
	}

	if (strpos($functionName, 'commit') !== false) {
		return false;
	}

	if (strpos($functionName, 'rollback') !== false) {
		return false;
	}

	if (\in_array(strtolower($functionName), $impureFunctions, true)) {
		return false;
	}

	if (strpos($functionName, 'image') === 0) {
		return false;
	}

	if (($functionName === 'var_export' || $functionName === 'print_r')) {
		return false;
	}

	foreach ($functionReflection->getVariants() as $variant) {
		if ($variant->getReturnType() instanceof \PHPStan\Type\VoidType) {
			return false;
		}

		if (isImpureType($variant->getReturnType())) {
			return false;
		}

		/** @var \PHPStan\Reflection\ParameterReflection $parameter */
		foreach ($variant->getParameters() as $parameter) {
			if (isImpureType($parameter->getType())) {
				return false;
			}
			if ($parameter->passedByReference()->yes()) {
				return false;
			}
		}
	}

	return true;
}

function isImpureType(Type $type): bool
{
	if ($type->isCallable()->yes()) {
		return true;
	}

	if ($type instanceof \PHPStan\Type\MixedType) {
		return true;
	}

	if (!(new \PHPStan\Type\ResourceType())->isSuperTypeOf($type)->no()) {
		return true;
	}

	return false;
}

(function () {

	require_once __DIR__ . '/../vendor/autoload.php';

	$signatureMap = require __DIR__ . '/../src/Reflection/SignatureMap/functionMap.php';

	$containerFactory = new \PHPStan\DependencyInjection\ContainerFactory(__DIR__);
	$container = $containerFactory->create(__DIR__ . '/../tmp', [], []);

	/** @var \PHPStan\Broker\Broker $broker */
	$broker = $container->getByType(\PHPStan\Broker\Broker::class);

	$results = [];

	foreach ($signatureMap as $functionName => $map) {
		if (strpos($functionName, '\'') !== false) {
			continue;
		}
		if (strpos($functionName, '::') !== false) {
			continue;
		}

		$functionReflection = $broker->getFunction(new \PhpParser\Node\Name($functionName), null);

		$results[$functionName] = ['hasSideEffects' => !isFunctionPure($functionReflection)];
	}

	$template = <<<'php'
<?php declare(strict_types = 1);

return [
%s];

php;
	;

	$body = '';
	foreach ($results as $functionName => $data) {
		$body .= sprintf(
			"\t%s => ['hasSideEffects' => %s],%s",
			var_export($functionName, true),
			var_export($data['hasSideEffects'], true),
			"\n"
		);
	}

	file_put_contents(__DIR__ . '/functionMetadata_tmp.php', sprintf($template, $body));

})();
