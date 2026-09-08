<?php declare(strict_types = 1); // lint >= 8.4

namespace ReturnTypeAfterFinallyPropertyHooks;

class Foo
{

	/** @var int|string */
	private $backing = 1;

	public int $byRefHook {
		&get {
			if (!is_int($this->backing)) {
				throw new \Exception();
			}
			try {
				return $this->backing;
			} finally {
				$this->backing = 'test';
			}
		}
	}

	public int $byValueHook {
		get {
			if (!is_int($this->backing)) {
				throw new \Exception();
			}
			try {
				return $this->backing;
			} finally {
				$this->backing = 'test';
			}
		}
	}

}
