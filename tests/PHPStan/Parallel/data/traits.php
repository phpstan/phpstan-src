<?php

namespace ParallelAnalyserIntegrationTest;

class Foo
{

	use FooTrait;

}

class Bar
{

	use FooTrait;

	/** @var int */
	private $test;

}
