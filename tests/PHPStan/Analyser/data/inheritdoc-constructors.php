<?php

namespace InheritDocConstructors;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param string[] $data
	 */
	public function __construct($data)
	{
		assertType('array<string>', $data);
	}

}

class Bar extends Foo
{

	public function __construct($name, $data)
	{
		parent::__construct($data);
		assertType('mixed', $name);
		assertType('array<string>', $data);
	}

}
