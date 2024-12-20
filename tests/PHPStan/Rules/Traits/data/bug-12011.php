<?php // lint >= 8.3

namespace Bug12011Trait;

use Attribute;


#[Table(self::TABLE_NAME)]
trait MyTrait
{
	private const int TABLE_NAME = 1;
}

class X {
	use MyTrait;
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
