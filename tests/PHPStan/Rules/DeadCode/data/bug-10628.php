<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug10628;

use stdClass;

interface Bar
{

	public function bazName(): string;

}

final class Foo
{
	public function __construct(
		private Bar $bar,
	) {
	}

	public function __invoke(): stdClass
	{
		return $this->getMixed()->get(
			name: $this->bar->bazName(),
		);
	}

	public function getMixed(): mixed
	{

	}
}
