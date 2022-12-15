<?php

namespace Bug4565;

use function PHPStan\Testing\assertType;

function test(array $variables) {
	$attributes = ['href' => ''] + $variables['attributes'];
	assertType('non-empty-array', $attributes);
	if (!empty($variables['button'])) {
		assertType('non-empty-array', $attributes);
		$attributes['type'] = 'button';
		assertType("hasOffsetValue('type', 'button')&non-empty-array", $attributes);
		unset($attributes['href']);
		assertType("array<mixed~'href', mixed>&hasOffsetValue('type', 'button')", $attributes);
	}
	assertType('array', $attributes);
	return $attributes;
}
