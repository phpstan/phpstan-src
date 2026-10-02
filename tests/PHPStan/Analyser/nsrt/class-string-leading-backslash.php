<?php declare(strict_types = 1);

namespace ClassStringLeadingBackslash;

use function PHPStan\Testing\assertType;

class Foo
{

}

final class FinalFoo
{

}

/**
 * @template T of object
 * @param class-string<T> $className
 * @return T
 */
function create(string $className): object
{
	return new $className();
}

function (): void {
	assertType('ClassStringLeadingBackslash\Foo', create(Foo::class));
	assertType('ClassStringLeadingBackslash\Foo', create('ClassStringLeadingBackslash\Foo'));
	assertType('ClassStringLeadingBackslash\Foo', create('\ClassStringLeadingBackslash\Foo'));
};

function (object $object): void {
	$className = '\ClassStringLeadingBackslash\Foo';
	assertType('ClassStringLeadingBackslash\Foo', new $className());

	if ($object instanceof $className) {
		assertType('ClassStringLeadingBackslash\Foo', $object);
	}
};

function (object $object): void {
	if (is_a($object, '\ClassStringLeadingBackslash\Foo')) {
		assertType('ClassStringLeadingBackslash\Foo', $object);
	}
};

function (string $className): void {
	if (is_a($className, '\ClassStringLeadingBackslash\Foo', true)) {
		assertType('class-string<ClassStringLeadingBackslash\Foo>', $className);
	}
};

/**
 * @param class-string<FinalFoo> $className
 */
function removeWithoutLeadingBackslash(string $className): void
{
	if ($className !== 'ClassStringLeadingBackslash\FinalFoo') {
		assertType('*NEVER*', $className);
	}
}

/**
 * @param class-string<FinalFoo> $className
 */
function removeWithLeadingBackslash(string $className): void
{
	if ($className !== '\ClassStringLeadingBackslash\FinalFoo') {
		assertType('*NEVER*', $className);
	}
}
