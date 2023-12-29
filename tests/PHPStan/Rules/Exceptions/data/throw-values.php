<?php // lint >= 8.0

namespace ThrowExprValues;

class InvalidException {};
interface InvalidInterfaceException {};
interface ValidInterfaceException extends \Throwable {};

/**
 * @template T of \Exception
 * @param class-string<T> $genericExceptionClassName
 * @param T $genericException
 */
function test($genericExceptionClassName, $genericException) {
	/** @var ValidInterfaceException $validInterface */
	$validInterface = new \Exception();
	/** @var InvalidInterfaceException $invalidInterface */
	$invalidInterface = new \Exception();
	/** @var \Exception|null $nullableException */
	$nullableException = new \Exception();

	if (rand(0, 1)) {
		throw new \Exception();
	}
	if (rand(0, 1)) {
		throw $validInterface;
	}
	if (rand(0, 1)) {
		throw 123;
	}
	if (rand(0, 1)) {
		throw new InvalidException();
	}
	if (rand(0, 1)) {
		throw $invalidInterface;
	}
	if (rand(0, 1)) {
		throw $nullableException;
	}
	if (rand(0, 1)) {
		throw foo();
	}
	if (rand(0, 1)) {
		throw new NonexistentClass();
	}
	if (rand(0, 1)) {
		throw new $genericExceptionClassName;
	}
	if (rand(0, 1)) {
		throw $genericException;
	}
}

function (\stdClass $foo) {
	/** @var \Exception $foo */
	throw $foo;
};

function (\stdClass $foo) {
	/** @var \Exception */
	throw $foo;
};

function (?\stdClass $foo) {
	echo $foo ?? throw 1;
};
