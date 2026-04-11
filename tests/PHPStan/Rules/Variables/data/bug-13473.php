<?php // lint >= 8.4

declare(strict_types = 1);

namespace Bug13473;

class Foo {
	private(set) int $bar {
		get => $this->bar;
		set(int $bar) {
			if (isset($this->bar)) {
				throw new \Exception('bar is set');
			}
			$this->bar = $bar;
		}
	}

	public function __construct(int $bar)
	{
		$this->bar = $bar;
	}
}
