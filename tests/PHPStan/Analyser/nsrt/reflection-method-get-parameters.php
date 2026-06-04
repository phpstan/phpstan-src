<?php declare(strict_types = 1);

namespace ReflectionMethodGetParameters;

use function PHPStan\Testing\assertType;

class User
{
	public function __construct(
		public int $id,
		public ?string $email,
	) {
	}

	public function noParams(): void
	{
	}

	public function variadic(int $first, string ...$rest): void
	{
	}
}

function testConstructorEncodesClassAndName(): void
{
	$m = new \ReflectionMethod(User::class, '__construct');
	assertType('ReflectionMethod<ReflectionMethodGetParameters\\User, \'__construct\'>', $m);
}

function testGetParametersIsTuple(): void
{
	$m = new \ReflectionMethod(User::class, '__construct');
	$params = $m->getParameters();
	assertType('array{ReflectionParameter<\'id\'>, ReflectionParameter<\'email\'>}', $params);
}

function testGetNameReturnsLiteralInLoop(): void
{
	$m = new \ReflectionMethod(User::class, '__construct');
	foreach ($m->getParameters() as $param) {
		assertType('\'email\'|\'id\'', $param->getName());
	}
}

function testRebuildArrayShape(User $user): void
{
	$props = [];
	$m = new \ReflectionMethod(User::class, '__construct');
	foreach ($m->getParameters() as $param) {
		$props[$param->getName()] = $user->{$param->getName()};
	}
	assertType('array{id: int, email: string|null}', $props);
}

/** @param class-string<User> $className */
function testClassStringArgument(string $className): void
{
	$m = new \ReflectionMethod($className, '__construct');
	assertType('array{ReflectionParameter<\'id\'>, ReflectionParameter<\'email\'>}', $m->getParameters());
}

function testObjectArgument(User $user): void
{
	$m = new \ReflectionMethod($user, '__construct');
	assertType('array{ReflectionParameter<\'id\'>, ReflectionParameter<\'email\'>}', $m->getParameters());
}

function testCombinedStringArgument(): void
{
	$m = new \ReflectionMethod('ReflectionMethodGetParameters\\User::__construct');
	assertType('array{ReflectionParameter<\'id\'>, ReflectionParameter<\'email\'>}', $m->getParameters());
}

function testNoParameters(): void
{
	$m = new \ReflectionMethod(User::class, 'noParams');
	assertType('array{}', $m->getParameters());
}

function testVariadic(): void
{
	$m = new \ReflectionMethod(User::class, 'variadic');
	assertType('array{ReflectionParameter<\'first\'>, ReflectionParameter<\'rest\'>}', $m->getParameters());
}

function testDynamicMethodNameFallsBack(string $method): void
{
	$m = new \ReflectionMethod(User::class, $method);
	assertType('list<ReflectionParameter>', $m->getParameters());
}

function testUnknownMethodFallsBack(): void
{
	$m = new \ReflectionMethod(User::class, 'doesNotExist');
	assertType('list<ReflectionParameter>', $m->getParameters());
}

function testUnknownObjectFallsBack(object $object): void
{
	$m = new \ReflectionMethod($object, 'foo');
	assertType('list<ReflectionParameter>', $m->getParameters());
}
