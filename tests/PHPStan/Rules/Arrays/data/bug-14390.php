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
}
