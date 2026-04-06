<?php // lint >= 8.1

namespace MethodCallReturnTypeFallback;

use function PHPStan\Testing\assertType;

enum Suit: string {
	case Hearts = 'hearts';
	case Diamonds = 'diamonds';
}

class MyClass {
	/** @return self */
	public static function from(string $value): self {
		return new self();
	}
}

/** @param class-string<Suit>|class-string<MyClass> $class */
function testStaticCallOnUnionWithConstant(string $class): void {
	assertType('MethodCallReturnTypeFallback\MyClass|MethodCallReturnTypeFallback\Suit::Hearts', $class::from('hearts'));
}

/** @param class-string<Suit>|class-string<MyClass> $class */
function testStaticCallOnUnionWithVariable(string $class, string $value): void {
	assertType('MethodCallReturnTypeFallback\MyClass|MethodCallReturnTypeFallback\Suit', $class::from($value));
}
