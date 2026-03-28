<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14390;

readonly class Sample
{
	/**
	 * @param array<string, string> $fields
	 */
	public function __construct(
		public array $fields = [],
	) {
	}
}

class Foo
{
	public function bar(
		Sample $sample,
	): void {
		if ($sample->fields !== []) {
			echo $sample->fields[array_key_first($sample->fields)];
		}
	}

	/**
	 * @param array<string, string> $fields
	 */
	public function zoo(
		array $fields,
	): void {
		if ($fields !== []) {
			echo $fields[array_key_first($fields)];
		}
	}

	public function withKey(
		Sample $sample,
	): void {
		if ($sample->fields !== []) {
			$key = array_key_first($sample->fields);
			echo $sample->fields[$key];
		}
	}

	public function arrayKeyLast(
		Sample $sample,
	): void {
		if ($sample->fields !== []) {
			echo $sample->fields[array_key_last($sample->fields)];
		}
	}

	public function arrayRand(
		Sample $sample,
	): void {
		if ($sample->fields !== []) {
			echo $sample->fields[array_rand($sample->fields)];
		}
	}

	/**
	 * @param non-empty-list<string> $list
	 */
	public function countMinus1(
		array $list,
	): void {
		echo $list[count($list) - 1];
	}
}

readonly class SampleList
{
	/**
	 * @param list<string> $items
	 */
	public function __construct(
		public array $items = [],
	) {
	}
}

class Bar
{
	public function countMinus1Property(
		SampleList $sample,
	): void {
		if ($sample->items !== []) {
			echo $sample->items[count($sample->items) - 1];
		}
	}
}

class StaticProps
{
	/** @var array<string, string> */
	public static array $fields = [];

	/** @var list<string> */
	public static array $items = [];

	public function arrayKeyFirstStatic(): void
	{
		if (self::$fields !== []) {
			echo self::$fields[array_key_first(self::$fields)];
		}
	}

	public function arrayKeyLastStatic(): void
	{
		if (self::$fields !== []) {
			echo self::$fields[array_key_last(self::$fields)];
		}
	}

	public function arrayRandStatic(): void
	{
		if (self::$fields !== []) {
			echo self::$fields[array_rand(self::$fields)];
		}
	}

	public function countMinus1Static(): void
	{
		if (self::$items !== []) {
			echo self::$items[count(self::$items) - 1];
		}
	}
}

function doWithMethods(WithMethods $withMethods) {
	echo $withMethods->pureMethod()[array_key_first($withMethods->pureMethod())];
	echo $withMethods->impureMethod()[array_key_first($withMethods->impureMethod())];
}

class WithMethods {
	/**
	 * @phpstan-pure
	 * @return non-empty-array
	 */
	public function pureMethod():array {}
	/**
	 * @phpstan-impure
	 * @return non-empty-array
	 */
	public function impureMethod():array {}
}
