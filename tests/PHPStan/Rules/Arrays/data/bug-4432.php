<?php declare(strict_types = 1);

namespace Bug4432;

class HelloWorld
{
	/** @param array{client?: array{id: string}} $config */
	public function sayHello(array $config): void
	{
		echo $config['client']['id'] ?? '';
	}

	/** @param array{client?: array{id?: string}} $config */
	public function sayHello2(array $config): void
	{
		echo $config['client']['id'] ?? '';
	}
}
