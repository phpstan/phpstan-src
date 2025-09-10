<?php declare(strict_types = 1);

namespace Bug12224Method;

class Assert
{
	/**
	 * @phpstan-pure
	 * @phpstan-assert string $value
	 */
	public static function staticString(mixed $value): string
	{
		if (\is_string($value)) {
			return $value;
		}
		throw new \RuntimeException();
	}

	/**
	 * @phpstan-pure
	 * @phpstan-assert string $value
	 */
	public function string(mixed $value): string
	{
		if (\is_string($value)) {
			return $value;
		}
		throw new \RuntimeException();
	}
}

class AssertConstructor
{
	/**
	 * @phpstan-pure
	 * @phpstan-assert string $value
	 */
	public function __construct(mixed $value)
	{
		if (!\is_string($value)) {
			throw new \RuntimeException();
		}
	}
}

/** @var string|null $a */
$a = '';
Assert::staticString($a);
(new Assert())->string($a);
new AssertConstructor($a);
