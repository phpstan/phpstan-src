<?php declare(strict_types = 1); // lint >= 8.4

namespace InitializerExprContextFixture\Inner;

const ANSWER = 42;

trait Greets
{

	public function greet(string $name = self::GREETING): string
	{
		$suffix = static fn (string $s = __FUNCTION__): string => $s . __METHOD__;
		return $name . $suffix();
	}

}

final class Holder
{

	use Greets;

	public const GREETING = 'hi';

	public string $label {
		get => $this->label . __METHOD__;
		set (string $value) {
			$this->label = $value . __FUNCTION__;
		}
	}

	public function __construct(public int $count = 1)
	{
		$this->label = 'x';
	}

	public static function make(int $count = self::GREETING === 'hi' ? 2 : 3): self
	{
		$factory = function (int $c) use ($count): self {
			return new self($c + $count);
		};
		return $factory($count);
	}

}

function helper(array $items = [ANSWER], string $name = __NAMESPACE__): string
{
	return implode(',', $items) . $name . __FUNCTION__;
}

namespace InitializerExprContextFixture;

function topLevel(int $x = 1): int
{
	return $x + \InitializerExprContextFixture\Inner\ANSWER;
}

$value = topLevel();
