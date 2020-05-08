<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

class ReturnTag implements TypedTag
{

	/** @var \PHPStan\Type\Type */
	private $type;

	/** @var bool */
	private $isExplicit;

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

	public function cloneImplicit(): self
	{
		return new self($this->type, false);
	}

	/**
	 * @param mixed[] $properties
	 * @return ReturnTag
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['type'],
			$properties['isExplicit']
		);
	}

}
