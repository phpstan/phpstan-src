<?php

namespace EmptyArrayInProperty;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var string[] */
	private $comments;

	public function doFoo(): void
	{
		assertType('array<string>', $this->comments);
		$this->comments = [];
		assertType('array{}', $this->comments);
		if ($this->comments === []) {
			assertType('array{}', $this->comments);
			return;
		} else {
			assertType('*NEVER*', $this->comments);
		}

		assertType('*NEVER*', $this->comments);
	}

	public function doBar(): void
	{
		assertType('array<string>', $this->comments);
		$this->comments = [];
		assertType('array{}', $this->comments);
		if ([] === $this->comments) {
			assertType('array{}', $this->comments);
			return;
		} else {
			assertType('*NEVER*', $this->comments);
		}

		assertType('*NEVER*', $this->comments);
	}

}
