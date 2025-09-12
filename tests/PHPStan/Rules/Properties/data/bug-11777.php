<?php

namespace Bug11777;

class HelloWorld {
	/** @var array<int, resource> */
	private array $pipes;

	public function sayHello(string $cmd): void {
		proc_open($cmd, [0 => ['pipe', 'r']], $this->pipes);
	}
}
