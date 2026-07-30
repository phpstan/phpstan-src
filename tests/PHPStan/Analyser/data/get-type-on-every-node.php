<?php declare(strict_types = 1);

namespace GetTypeOnEveryNode;

class HelloWorld
{

	/** @var array<string, string> */
	private array $data = [];

	public function sayHello(?string $name): string
	{
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}

		if (empty($this->data)) {
			return 'empty';
		}

		$fallback = $name ?? 'anonymous';
		$this->data[$fallback] ??= 'hello';

		return $this->data[$fallback];
	}

}
