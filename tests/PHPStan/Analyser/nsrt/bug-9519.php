<?php declare(strict_types = 1);

namespace Bug9519;

use function PHPStan\Testing\assertType;

class ClassA {
	public function sayHello(): void
	{
		echo 'Hello';
	}
}

class ClassB {
	public function sayHello(): void
	{
		echo 'Hello';
	}
}

function test1(mixed $obj): void {
	$isA = $obj instanceof ClassA;
	$isB = $obj instanceof ClassB;

	if ($isA || $isB) {
		assertType('Bug9519\ClassA|Bug9519\ClassB', $obj);
	}
}

function test2(mixed $obj): void {
	// Direct instanceof in condition should work (and already does)
	if (($obj instanceof ClassA) || ($obj instanceof ClassB)) {
		assertType('Bug9519\ClassA|Bug9519\ClassB', $obj);
	}
}

function test3(mixed $obj): void {
	$isA = $obj instanceof ClassA;
	$isB = $obj instanceof ClassB;

	if ($isA) {
		assertType('Bug9519\ClassA', $obj);
	}

	if ($isB) {
		assertType('Bug9519\ClassB', $obj);
	}
}

interface SomeInterface {
	public function test(): void;
}

class ObjectClass {
}

class OtherClass extends ObjectClass {
}

/**
 * @template T of object
 * @param class-string<T> $class_name
 * @return T
 */
function getObject(string $class_name): object {
	return new $class_name;
}

function test4(): void {
	$obj = getObject(ObjectClass::class);
	$is_other = $obj instanceof OtherClass;
	$is_interface = $obj instanceof SomeInterface;

	if ($is_interface) {
		assertType('Bug9519\ObjectClass&Bug9519\SomeInterface', $obj);
	}
}

function test5(): void {
	$obj = getObject(ObjectClass::class);
	$is_interface = $obj instanceof SomeInterface;
	$is_other = $obj instanceof OtherClass;

	if ($is_interface) {
		assertType('Bug9519\ObjectClass&Bug9519\SomeInterface', $obj);
	}
}
