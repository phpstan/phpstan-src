<?php

namespace ValueAssignedToClassConstant;

class Foo
{

	/** @var int */
	const BAR = 1;

	const NO_TYPE = 'string';

	/** @var string */
	const BAZ = 1;

	/** @var string|int */
	const LOREM = 1;

	/** @var int */
	const IPSUM = self::LOREM;

	/** @var self<int> */
	const DOLOR = 1;

}

class Bar extends Foo
{

	const BAR = 2;

	const BAZ = 2;

}

class Baz
{

	/** @var string */
	private const BAZ = 'foo';

}

class Lorem extends Baz
{

	private const BAZ = 1;

}
