<?php declare(strict_types = 1);

namespace Bug11610;

use function PHPStan\Testing\assertType;

function sayHello(string $value): void
{
	/** @var object{containers?: array<int>} $responseJson */
	$responseJson = json_decode($value);
	if (!is_array($responseJson->containers ?? null)) {
		throw new \Exception();
	}
	assertType('object{containers: array<int>}', $responseJson);
}

function sayHello2(string $value): void
{
	/** @var object{containers?: array<int>} $responseJson */
	$responseJson = json_decode($value);
	if (!isset($responseJson->containers) || !is_array($responseJson->containers)) {
		throw new \Exception();
	}
	assertType('object{containers: array<int>}', $responseJson);
}
