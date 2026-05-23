<?php declare(strict_types = 1);

namespace Bug8980;

use function function_exists;

// actual bug report snippet: function_exists inside array_filter callback
$undefined_curl_functions = array_filter(
	[
		'curl_multi_add_handle',
		'curl_multi_exec',
		'curl_multi_init',
	],
	static function( $function_name ) {
		return ! function_exists( $function_name );
	}
);
