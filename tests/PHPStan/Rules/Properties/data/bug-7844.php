<?php

namespace Bug7844;

class C
{
	/** @var float */
	public $val = .0;

	/** @var array<float> */
	public $data = array();

	public function foo(): void
	{
		\PHPStan\dumpType($this->data);
		if (count($this->data) > 0) {
			\PHPStan\dumpType($this->data);
			$this->val = array_shift($this->data);
			\PHPStan\dumpType($this->val);
		}
	}
}

