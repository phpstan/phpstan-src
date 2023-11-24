<?php declare(strict_types=1);

namespace PropertyAssignArrayShape;

class Foo
{

	/** @var array{opt?: int, req: int} */
	public array $prop;

	public function test(): void
	{
		$this->prop = ['req' => 1, 'foo' => 1];
		$this->prop = rand()
			? ['req' => 1, 'opt' => 1]
			: ['req' => 1, 'foo' => 1];
	}

}
