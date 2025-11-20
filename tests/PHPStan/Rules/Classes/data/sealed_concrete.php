<?php declare(strict_types = 1);

namespace SealedConcrete;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-sealed a|b|c
 */
class s {}

final class a extends s {}
final class b extends s {}
final class c extends s {}

function foo(s $sealedClass): void
{
	if ($sealedClass instanceof a) {
		return;
	}

	assertType('SealedConcrete\\s~SealedConcrete\\a', $sealedClass);

	if ($sealedClass instanceof b) {
		return;
	}

	assertType('SealedConcrete\\s~(SealedConcrete\\a|SealedConcrete\\b)', $sealedClass);

	if ($sealedClass instanceof c) {
		return;
	}

	assertType('SealedConcrete\\s', $sealedClass);
}
