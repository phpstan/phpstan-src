<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
interface TypedTag
{

	public function getType(): Type;

	/**
	 * @return static
	 */
	public function withType(Type $type): self;

}
