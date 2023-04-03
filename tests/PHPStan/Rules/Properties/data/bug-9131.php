<?php

namespace Bug9131;

class A
{
	/** @var array<int<0, max>, string> */
	public array $l = [];

	public function add(string $s): void {
		$this->l[] = $s;
	}
}
