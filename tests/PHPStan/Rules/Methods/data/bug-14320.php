<?php declare(strict_types = 1);

namespace Bug14320;

trait MyTrait
{
	/**
	* @param array<string, mixed> $data
	* @return array<string, mixed>
	*/
	abstract protected function myFunction(array $data): array;
}

trait MyFirstTrait
{
	use MyTrait;
}

abstract class MyAbstractClass
{
	use MyFirstTrait;

	/**
	* @param array<string, mixed> $data
	* @return array<string, mixed>
	*/
	protected function myFunction(array $data): array
	{
		return [
			'hello' => 'bug',
		];
	}
}
