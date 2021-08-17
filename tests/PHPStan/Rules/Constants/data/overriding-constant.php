<?php

namespace OverridingConstant;

class Foo
{

	const FOO = 1;

	/** @var int */
	const BAR = 1;

	/** @var int */
	private const BAZ = 1;

	/** @var string|int */
	const LOREM = 1;

	/** @var int */
	const IPSUM = 1;

}

class Bar extends Foo
{

	const FOO = 'foo';

	/** @var string */
	const BAR = 'bar';

	/** @var string */
	const BAZ = 'foo';

	/** @var string */
	const LOREM = 'foo';

	/** @var int|string */
	const IPSUM = 'foo';

}
