<?php

namespace Bug4565;

use function PHPStan\Testing\assertType;

function test(array $variables) {
	$attributes = ['href' => ''] + $variables['attributes'];
	assertType('non-empty-array', $attributes);
	if (!empty($variables['button'])) {
		assertType('non-empty-array', $attributes);
		$attributes['type'] = 'button';
		assertType("non-empty-array&hasOffsetValue('type', 'button')", $attributes);
		unset($attributes['href']);
		assertType("non-empty-array<mixed~'href', mixed>&hasOffsetValue('type', 'button')", $attributes);
	}
	assertType('non-empty-array', $attributes);
	return $attributes;
}
