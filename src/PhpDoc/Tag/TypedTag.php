<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

interface TypedTag
{

	public function getType(): Type;

	/**
	 * @param Type $type
	 * @return static
	 */
	public function withType(Type $type): self;

}
