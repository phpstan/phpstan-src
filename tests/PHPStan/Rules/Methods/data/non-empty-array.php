<?php

namespace AcceptNonEmptyArray;

class Foo
{

	/**
	 * @param array<int> $mightBeEmpty
	 * @param non-empty-array<int> $nonEmpty
	 * @return void
	 */
	public function doFoo(array $mightBeEmpty, array $nonEmpty)
	{
		$this->requireNonEmpty($mightBeEmpty);
		$this->requireNonEmpty($nonEmpty);
		$this->requireNonEmpty([]);
		$this->requireNonEmpty([123]);
	}

	/**
	 * @param non-empty-array<int> $nonEmpty
	 */
	public function requireNonEmpty(array $nonEmpty)
	{

	}

}
