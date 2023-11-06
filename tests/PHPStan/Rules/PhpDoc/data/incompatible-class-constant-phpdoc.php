<?php

namespace IncompatibleClassConstantPhpDoc;

class Foo
{

	/** @var self&\stdClass */
	const FOO = 1;

	/** @var self<int> */
	const DOLOR = 1;

}
