<?php

namespace Bug5903;

final class Test
{
	/** @var \Traversable<string> */
	protected $traversable;
	/** @var \Iterator<string> */
	protected $iterator;
	/** @var iterable<string> */
	protected $iterable;
	/** @var array<string> */
	protected $array;
	/** @var array<string>|null */
	protected $maybeArray;
	/** @var \Iterator<string>|null */
	protected $maybeIterable;

	public function foo()
	{
		try {
			foreach ($this->traversable as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->iterator as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->iterable as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->array as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->maybeArray as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->maybeIterable as $val) {
				echo $val;
			}
		} catch (\Throwable $e) {
		}
	}
}
