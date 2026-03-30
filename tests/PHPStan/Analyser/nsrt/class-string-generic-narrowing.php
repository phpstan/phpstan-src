<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClassStringGenericNarrowing;

use function PHPStan\Testing\assertType;

/** @template T */
abstract class Animal {
	/** @return T */
	abstract public function value(): mixed;
}

/**
 * @template T
 * @extends Animal<T>
 */
class Cat extends Animal {
	/** @param T $val */
	public function __construct(private mixed $val) {}
	/** @return T */
	public function value(): mixed { return $this->val; }
}

/**
 * @template T
 * @extends Animal<T>
 */
class Dog extends Animal {
	/** @return never */
	public function value(): never { throw new \RuntimeException(); }
}

/** @param Cat<string>|Dog<string> $a */
function unionMatchPreservesGeneric(Animal $a): void {
	match ($a::class) {
		Cat::class => assertType('string', $a->value()),
		Dog::class => assertType('never', $a->value()),
	};
}

/** @param Cat<int>|Dog<int> $a */
function ifElseClassPreservesGeneric(Animal $a): void {
	if ($a::class === Cat::class) {
		assertType('int', $a->value());
	} else {
		assertType('int', $a->value());
	}
}

/** @param Cat<float>|Dog<float> $a */
function mirrorCasePreservesGeneric(Animal $a): void {
	if (Cat::class === $a::class) {
		assertType('float', $a->value());
	}
}

/** @param Cat<array<string>>|Dog<array<string>> $a */
function matchWithMethodCall(Animal $a): void {
	$result = match ($a::class) {
		Cat::class => $a->value(),
		Dog::class => [],
	};
	assertType('array<string>', $result);
}

/** @param Cat<string>|Dog<string> $a */
function nonMatchingClass(Animal $a): void {
	if ($a::class === \stdClass::class) {
		assertType('*NEVER*', $a);
	} else {
		assertType('ClassStringGenericNarrowing\Cat<string>|ClassStringGenericNarrowing\Dog<string>', $a);
	}
}

/** @param Animal<string> $a */
function matchOnGenericParent(Animal $a): void {
	match ($a::class) {
		Cat::class => assertType('string', $a->value()),
		Dog::class => assertType('never', $a->value()),
	};
}

/** @param Animal<int> $a */
function ifElseOnGenericParent(Animal $a): void {
	if ($a::class === Cat::class) {
		assertType('int', $a->value());
	} else {
		assertType('int', $a->value());
	}
}

/** @param Animal<float> $a */
function mirrorCaseOnGenericParent(Animal $a): void {
	if (Cat::class === $a::class) {
		assertType('float', $a->value());
	}
}
