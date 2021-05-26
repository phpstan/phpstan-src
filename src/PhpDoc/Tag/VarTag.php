<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
class VarTag implements TypedTag
{

	private \PHPStan\Type\Type $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	/**
	 * @param Type $type
	 * @return self
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type);
	}

}
