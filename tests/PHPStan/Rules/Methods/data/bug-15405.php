<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15405;

use Closure;

final class Operators
{
	/** @return array<string, Closure(string, int, int): int> */
	public static function all(): array
	{
		return [
			'+' => self::plus(...),
			'-' => Closure::fromCallable([self::class, 'minus']),
		];
	}

	private static function plus(string $context, int $a, int $b): int
	{
		return $a + $b;
	}

	private static function minus(string $context, int $a, int $b): int
	{
		return $a - $b;
	}
}

final class OtherForms
{
	/** @return list<callable(string, int): int> */
	public function all(): array
	{
		return [
			$this->instanceFirstClass(...),
			[$this, 'instanceArray'],
			'self::stringCallable',
			[$this, 'MIXEDCASE'],
		];
	}

	private function instanceFirstClass(string $context, int $a): int
	{
		return $a;
	}

	private function instanceArray(string $context, int $a): int
	{
		return $a;
	}

	private static function stringCallable(string $context, int $a): int
	{
		return $a;
	}

	private function mixedCase(string $context, int $a): int
	{
		return $a;
	}
}

final class NotReferenced
{
	private function notReferenced(string $context, int $a): int
	{
		return $a;
	}

	private function calledDirectly(string $context, int $a): int
	{
		return $a;
	}

	public function run(): int
	{
		return $this->calledDirectly('x', 1);
	}
}

trait OperatorsTrait
{
	private function traitMethod(string $context, int $a): int
	{
		return $a;
	}
}

final class UsesTrait
{
	use OperatorsTrait;

	/** @return callable(string, int): int */
	public function get(): callable
	{
		return $this->traitMethod(...);
	}
}

$f = new class {
	/** @return callable(string, int): int */
	public function get(): callable
	{
		return $this->anonymous(...);
	}

	private function anonymous(string $context, int $a): int
	{
		return $a;
	}
};

trait SelfReferencingTrait
{
	/** @return callable(string, int): int */
	public function get(): callable
	{
		return $this->fromTrait(...);
	}

	private function fromTrait(string $context, int $a): int
	{
		return $a;
	}
}

final class UsesSelfReferencingTrait
{
	use SelfReferencingTrait;
}
