<?php

namespace CommentTypes;

/**
 * @template T of FooInterface
 */
class Bar
{
	/*
	 * @var T $foo
	 */
	protected FooInterface $foo;

	/**
	 * @param T $foo
	 */
	public function __construct(FooInterface $foo) { $this->foo = $foo; }

	/*
	 * @return T
	 */
	public function getFoo(): FooInterface
	{
		return $this->foo;
	}

	/*
	 * some method
	 */
	public function getBar(): FooInterface
	{
		return $this->foo;
	}

	// this should not error: @var
	# this should not error: @var

	/*
	 * comments which look like phpdoc should be ignored
	 *
	 * x@x.cz
	 * 10 amps @ 1 volt
	 */
	public function ignoreComments(): FooInterface
	{
		return $this->foo;
	}
}
