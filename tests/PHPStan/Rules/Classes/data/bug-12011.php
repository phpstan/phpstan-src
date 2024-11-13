<?php // lint >= 8.3

namespace Bug12011;

use Attribute;

#[Table(self::TABLE_NAME)]
class HelloWorld
{
	private const string TABLE_NAME = 'table';
}

#[Attribute(Attribute::TARGET_CLASS)]
final class Table
{
	public function __construct(
		public readonly string|null $name = null,
		public readonly string|null $schema = null,
	) {
	}
}

#[Table(self::TABLE_NAME)]
class HelloWorld2
{
	private const int TABLE_NAME = 1;
}
