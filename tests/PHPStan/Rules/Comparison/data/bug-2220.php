<?php

namespace Bug2220;

class HelloWorld
{
	/**
	 * @var string
	 */
	private $privateModule;

	public function sayHello(): void
	{
		$resource = $this->getResource();

		if ($resource === "{$this->privateModule}:abcdef") {
			$this->abc();
		} elseif ($resource === "{$this->privateModule}:xyz") {
			$this->abc();
		}
	}

	private function abc(): void {}

	private function getResource(): string { return 'string'; }
}
