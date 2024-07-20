<?php // lint >= 8.0

namespace PregQuotingPhp8;

function doFoo(string $s, callable $cb): void { // errors
	preg_split(subject: $s, pattern: '&' . preg_quote('&oops', '/') . 'pattern&');
	preg_split(subject: $s, pattern: '&' . preg_quote(delimiter: '/', str: '&oops') . 'pattern&');
}

function ok(string $s): void { // ok
	preg_split(subject: $s, pattern: '&' . preg_quote('&oops', '&') . 'pattern&');
	preg_split(subject: $s, pattern: '&' . preg_quote(delimiter: '&', str: '&oops') . 'pattern&');
}
