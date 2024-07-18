<?php

namespace PregQuoting;

function doFoo(string $s, callable $cb): void { // errors
	preg_match('&' . preg_quote('&oops') . 'pattern&', $s);
	preg_match('&' . preg_quote('&oops', '/') . 'pattern&', $s);

	preg_match(
		'&' .
		preg_quote('&oops', '/') .
		preg_quote('&oops') .
		preg_quote('&oops', '&') .
		'pattern&',
		$s
	);

	preg_match_all('&' . preg_quote('&oops', '/') . 'pattern&', $s);

	preg_filter('&' . preg_quote('&oops', '/') . 'pattern&', $s);
	preg_grep('&' . preg_quote('&oops', '/') . 'pattern&', $s);
	preg_replace('&' . preg_quote('&oops', '/') . 'pattern&', $s);
	preg_replace_callback('&' . preg_quote('&oops', '/') . 'pattern&', $cb, $s);
	preg_split('&' . preg_quote('&oops', '/') . 'pattern&', $s);
}

function ok(string $s): void { // ok
	preg_match('&' . preg_quote('&oops', '&') . 'pattern&', $s);
	preg_match('{' . preg_quote('&oops') . 'pattern}', $s);
	preg_match($s, "string");
}

function notAnalyzable(string $s): void { // ok
	preg_match($s. preg_quote('&oops') . 'pattern}', $s);
}
