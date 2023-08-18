<?php

namespace Readonly\Uninit\Bug;

trait Foo
{
	protected readonly int $x;

	public function foo(): void
	{
		echo $this->x;
	}

	public function init(): void
	{
		$this->x = rand();
	}
}
