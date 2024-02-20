<?php declare(strict_types = 1);

namespace Bug9594;

class HelloWorld
{
	public function sayHello(): void
	{
		$data = [
			[
				'elements' => [1, 2, 3],
				'greet' => fn (int $value) => 'I am '.$value,
			],
			[
				'elements' => ['hello', 'world'],
				'greet' => fn (string $value) => 'I am '.$value,
			],
		];

		foreach ($data as $entry) {
			foreach ($entry['elements'] as $element) {
				$entry['greet']($element);
			}
		}
	}
}
