<?php

declare(strict_types = 1);

namespace Bug14545;

use function PHPStan\Testing\assertType;

interface SomeInterface {
	public function test(): void;
}

class ObjectClass {
}

class OtherClass {
}

/**
 * @template T of object
 * @param class-string<T> $class_name
 * @return T
 */
function getObject1(string $class_name): object {
	return new $class_name;
}

function testStoredInstanceofWithGenericMethodCall(): void {
	$obj = getObject1(ObjectClass::class);
	$is_interface = $obj instanceof SomeInterface;
	if($is_interface) {
		assertType('Bug14545\ObjectClass&Bug14545\SomeInterface', $obj);
		$obj->test();
	}

	if($is_interface) {
		assertType('Bug14545\ObjectClass&Bug14545\SomeInterface', $obj);
		$obj->test();
	}
}

function testStoredInstanceofWithGenericFuncCall(): void {
	$obj = getObject1(ObjectClass::class);
	$is_interface = $obj instanceof SomeInterface;
	if($is_interface) {
		var_dump($obj);
	}

	if($is_interface) {
		assertType('Bug14545\ObjectClass&Bug14545\SomeInterface', $obj);
	}
}

function testStoredInstanceofWithConcreteClass(): void {
	$obj = getObject1(OtherClass::class);
	$is_interface = $obj instanceof SomeInterface;
	if($is_interface) {
		assertType('Bug14545\OtherClass&Bug14545\SomeInterface', $obj);
		$obj->test();
	}

	if($is_interface) {
		assertType('Bug14545\OtherClass&Bug14545\SomeInterface', $obj);
	}
}

function getObject2(): object {
	return new \stdClass();
}

function testStoredInstanceofWithAbstractObject(): void {
	$obj = getObject2();
	$is_interface = $obj instanceof SomeInterface;
	if($is_interface) {
		assertType('Bug14545\SomeInterface', $obj);
		$obj->test();
	}

	if($is_interface) {
		assertType('Bug14545\SomeInterface', $obj);
		$obj->test();
	}
}

function testThreeConsecutiveChecks(): void {
	$obj = getObject1(ObjectClass::class);
	$is_interface = $obj instanceof SomeInterface;
	if($is_interface) {
		$obj->test();
	}
	if($is_interface) {
		$obj->test();
	}
	if($is_interface) {
		assertType('Bug14545\ObjectClass&Bug14545\SomeInterface', $obj);
	}
}

/**
 * @param array<mixed, mixed> $data
 */
function testStoredIsArray(array $data): void {
	$value = $data['key'] ?? null;
	$isArray = is_array($value);
	if ($isArray) {
		assertType('array<mixed, mixed>', $value);
		var_dump($value);
	}
	if ($isArray) {
		assertType('array<mixed, mixed>', $value);
	}
}
