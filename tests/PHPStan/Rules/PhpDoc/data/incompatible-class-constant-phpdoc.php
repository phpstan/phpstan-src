<?php

namespace IncompatibleClassConstantPhpDoc;

class Foo
{

	/** @var self&\stdClass */
	const FOO = 1;

	/** @var int */
	const BAR = 1;

	const NO_TYPE = 'string';

	/** @var string */
	const BAZ = 1;

	/** @var string|int */
	const LOREM = 1;

	/** @var int */
	const IPSUM = self::LOREM; // resolved to 1, I'd prefer string|int

	/** @var self<int> */
	const DOLOR = 1;

}
