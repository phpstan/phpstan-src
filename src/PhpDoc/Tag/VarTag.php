<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class VarTag implements TypedTag
{

	public function __construct(private Type $type, private bool $isExplicit)
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
		return new self($type, $this->isExplicit);
	}

	public function isExplicit(): bool
	{
		return $this->isExplicit;
	}

	public function toImplicit(): self
	{
		return new self($this->type, false);
	}

}
