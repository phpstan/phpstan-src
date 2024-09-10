<?php

namespace Bug11667;

final class HelloWorld {
	/** @var list<string>|null */
	private $matches = null;

	public function match(string $string): void {
		preg_match('/Hello (\w+)/', $string, $this->matches);
	}

	/** @return list<string>|null */
	public function get(): ?array {
		return $this->matches;
	}
}

final class HelloWorld2 {
	/** @var list<string>|null */
	private $matches = null;

	public function match(string $string): void {
		$this->paramOut($this->matches);
	}

	/**
	 * @param mixed $a
	 * @param-out list<string> $a
	 */
	public function paramOut(&$a): void
	{

	}

	/** @return list<string>|null */
	public function get(): ?array {
		return $this->matches;
	}
}
