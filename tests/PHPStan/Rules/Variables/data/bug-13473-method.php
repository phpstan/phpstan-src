<?php

declare(strict_types = 1);

namespace Bug13473Method;

class Foo {
    private int $bar;

    public function __construct(int $bar)
    {
        $this->setBar($bar);
    }

	public function setBar(int $bar): void
	{
		if (isset($this->bar)) {
			throw new \Exception('bar is set');
		}
		$this->bar = $bar;
	}
}
