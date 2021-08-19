<?php

namespace MissingClassConstantTypehint;

class Foo
{

	private const FOO = '1';

	/** @var array */
	private const BAR = [];

	/** @var mixed[] */
	private const BARR = [];

	/** @var Bar */
	private const BAZ = 1;

	/** @var callable */
	private const LOREM = 1;

}

/** @template T */
class Bar
{

}
