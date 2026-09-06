<?php declare(strict_types = 1);

namespace Bug9377;

/**
 * @template T
 */
class HelloWorld
{

}

/** @param HelloWorld<array{id: int|null}> $foo */
function foo($foo): void
{
}

/** @return HelloWorld<array{id: int|null}> */
function bar(): HelloWorld
{
	return new HelloWorld();
}

$a = bar();

foo($a);
