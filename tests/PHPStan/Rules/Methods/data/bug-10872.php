<?php

namespace Bug10872;

class HelloWorld
{
	/**
	 * @return array<string, string|null>
	 */
	public function getRow(): array
	{
		return [];
	}

	/**
	 * @template ExpectedType of array<mixed>
	 * @param ExpectedType $expected
	 * @param array<mixed> $actual
	 * @psalm-assert =ExpectedType $actual
	 */
	public static function assertSame(array $expected, array $actual): void
	{
		if ($actual !== $expected) {
			throw new \Exception();
		}
	}

	public function testEscapeIdentifier(): void
	{
		$names = [
			'foo',
			'2',
		];

		$expected = array_combine($names, array_fill(0, count($names), 'x'));

		self::assertSame(
			$expected,
			$this->getRow()
		);
	}
}
