<?php

namespace Bug6190;

class Food
{

	public static function bone(): self
	{
		return new self();
	}

}

/**
 * @return \Generator<int, Food>|null
 */
function nullableGenerator()
{
	yield Food::bone();
	yield 5;
}

/**
 * @return \Iterator<int, Food>|float
 */
function unionGenerator()
{
	yield Food::bone();
	yield 'foo';
}

/**
 * @return \Generator<int, Food>|null
 */
function nullableGeneratorWrongKey()
{
	yield 'key' => Food::bone();
}
