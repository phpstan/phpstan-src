<?php

namespace Bug5903;

class Test
{
	/** @var \Traversable<string> */
	protected $traversable;
	/** @var \Iterator<string> */
	protected $iterator;
	/** @var iterable<string> */
	protected $iterable;
	/** @var array<string> */
	protected $array;

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
	}
}
