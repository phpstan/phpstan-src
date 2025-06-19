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
		if (count($this->data) > 0) {
			$this->val = array_shift($this->data);
		}
	}
}

