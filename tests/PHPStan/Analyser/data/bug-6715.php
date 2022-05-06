<?php

namespace Bug6715;

use function PHPStan\Testing\assertType;

class Test
{
	/** @var bool */
	public $bool;

	function test(): void
	{
		assertType('array{test: true}', array_filter([
			'test' => true,
		]));

		assertType('array{}', array_filter([
			'test' => false,
		]));

		assertType('array{test?: 1}', array_filter([
			'test' => rand(0, 1),
		]));

		assertType('array{test?: true}', array_filter([
			'test' => $this->bool,
		]));
	}

	function test2(): void
	{
		assertType('\'1\'', implode(', ', array_filter([
			'test' => true,
		])));

		assertType('\'\'', implode(', ', array_filter([
			'test' => false,
		])));

		assertType('\'\'|\'1\'', implode(', ', array_filter([
			'test' => rand(0, 1),
		])));

		assertType('\'\'|\'1\'', implode(', ', array_filter([
			'test' => $this->bool,
		])));
	}
}
