<?php // lint >= 8.0

namespace Bug12925;

use function PHPStan\Testing\assertType;

/**
 * @template TIsZero of bool = bool
 */
final class Decimal
{
	/** @param numeric-string $value */
	public function __construct(private string $value) {}
	/**
	 * @phpstan-assert-if-true self<true> $this
	 * @phpstan-assert-if-false self<false> $this
	 */
	public function isZero(): bool { return bccomp($this->value, '0', 2) === 0; }
}
class C { public function __construct(public Decimal $p) {} }
$c = rand() ? new C(new Decimal((string)rand())) : null;

assertType('Bug12925\C|null', $c);
echo $c?->p->isZero() ? 'Free' : 'Buying';
assertType('Bug12925\C|null', $c);
