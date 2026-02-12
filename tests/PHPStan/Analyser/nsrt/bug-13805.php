<?php // onlyForPhpVersions: 80100

declare(strict_types = 1);

namespace Bug13805;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type MinimalRowDefinition array{foo: string, muh: string}
 */
class HelloWorld
{
	/**
	 * @param array{test?: array<string, mixed>} $defaultItems
	 * @param MinimalRowDefinition $row
	 */
	public function sayHello(array $row, array $defaultItems): void
	{
		$result = [
			...($defaultItems['test'] ?? []),
			...$row,
		];

		assertType('non-empty-array<string, mixed>&hasOffsetValue(\'foo\', string)&hasOffsetValue(\'muh\', string)', $result);

		// $result will always contain the keys from MinimalRowDefinition, therefore also the needed muh
		$this->testStuff($result);
	}

	/** @param array{muh: string} $data */
	private function testStuff($data): void
	{

	}

	/**
	 * @param array<string, int> $a
	 * @param array{x: string, y: int} $b
	 */
	public function testSpreadOrder(array $a, array $b): void
	{
		$result = [...$a, ...$b];
		assertType('non-empty-array<string, int|string>&hasOffsetValue(\'x\', string)&hasOffsetValue(\'y\', int)', $result);
	}
}
