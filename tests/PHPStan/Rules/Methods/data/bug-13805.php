<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13805;

/**
 * @phpstan-type MinimalRowDefinition array{foo: string, muh: string, ...}
 */
class HelloWorld
{
	/**
	 * @param array{test?: array<string, mixed>, ...} $defaultItems
	 * @param MinimalRowDefinition $row
	 */
	public function sayHello(array $row, array $defaultItems): void
	{
		$result = [
			...($defaultItems['test'] ?? []),
			...$row,
		];

		$this->testStuff($result);
	}

	/** @param array{muh: string, ...} $data */
	private function testStuff($data): void
	{

	}
}
