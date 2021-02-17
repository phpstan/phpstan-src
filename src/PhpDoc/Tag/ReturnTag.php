<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

class ReturnTag implements TypedTag
{

	private \PHPStan\Type\Type $type;

	private bool $isExplicit;

	public function __construct(Type $type, bool $isExplicit)
	{
		$this->type = $type;
		$this->isExplicit = $isExplicit;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function isExplicit(): bool
	{
		return $this->isExplicit;
	}

	/**
	 * @param Type $type
	 * @return self
	 */
	public function withType(Type $type): TypedTag
	{
		return new self($type, $this->isExplicit);
	}

	public function toImplicit(): self
	{
		return new self($this->type, false);
	}

}
