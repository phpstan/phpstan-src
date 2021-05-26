<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

/** @api */
class ParamTag implements TypedTag
{

	private \PHPStan\Type\Type $type;

	private bool $isVariadic;

	public function __construct(Type $type, bool $isVariadic)
	{
		$this->type = $type;
		$this->isVariadic = $isVariadic;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function isVariadic(): bool
	{
		return $this->isVariadic;
	}

	/**
	 * @param Type $type
	 * @return self
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type, $this->isVariadic);
	}

}
