<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class SelfOutTypeTag implements TypedTag
{

	public function __construct(private Type $type)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	/**
	 * @return self
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type);
	}

}
