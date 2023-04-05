<?php

namespace Bug8980;

function doFoo(): void {
	$undefined_curl_functions = array_filter(
		[
			'curl_multi_add_handle',
			'curl_multi_exec',
			'curl_multi_init',
		],
		static function ($function_name) {
			return !function_exists($function_name);
		}
	);
}
