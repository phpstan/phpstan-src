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


function doFoo(string $s)
{
	if (rand(0, 1) === 0) {
		$prefix = '/\d*';
		$suffix = '/';
	} else {
		$prefix = '{\d*';
		$suffix = '}';
	}
	return preg_split($prefix . preg_quote($s, '/') . 'pattern' . $suffix, $s); // ok
}

function doFooBar(string $s)
{
	if (rand(0, 1) === 0) {
		$prefix = '/\d*';
		$suffix = '/';
		$quote = '/';
	} else {
		$prefix = '{\d*';
		$suffix = '}';
		$quote = '}';
	}
	return preg_split($prefix . preg_quote($s, $quote) . 'pattern' . $suffix, $s); // ok
}

function doFooBarBaz(string $s)
{
	if (rand(0, 1) === 0) {
		$prefix = '/\d*';
		$suffix = '/';
		$quote = '/';
	} else {
		$prefix = '@\d*';
		$suffix = '@';
		$quote = '@';
	}
	return preg_split($prefix . preg_quote($s, $quote) . 'pattern' . $suffix, $s); // ok
}

function doFooBarBazFoo(string $s)
{
	if (rand(0, 1) === 0) {
		$prefix = '/\d*';
		$suffix = '/';
	} else {
		$prefix = '@\d*';
		$suffix = '@';
	}
	return preg_split($prefix . preg_quote($s) . 'pattern' . $suffix, $s);
}


function ok(string $s): void { // ok
	preg_match('&' . preg_quote('&oops', '&') . 'pattern&', $s);
	preg_match('{' . preg_quote('&oops') . 'pattern}', $s);
	preg_match($s, "string");

	preg_match(
		'{' .
		preg_quote($s, '&') // unnecessary arg but not dangerous when delimiter is {
		. 'pattern}',
		$s
	);
}

function notAnalyzable(string $s): void { // ok
	preg_match($s. preg_quote('&oops') . 'pattern}', $s);
}
