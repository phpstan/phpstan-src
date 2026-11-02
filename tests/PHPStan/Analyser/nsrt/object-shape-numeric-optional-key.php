<?php declare(strict_types = 1);

namespace ObjectShapeNumericOptionalKey;

use function PHPStan\Testing\assertType;

/**
 * @param object{'123'?: int, '0'?: string, 'a'?: bool} $o
 */
function test(object $o): void
{
	assertType('object{123?: int, 0?: string, a?: bool}', $o);
}
