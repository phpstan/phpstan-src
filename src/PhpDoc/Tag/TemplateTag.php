<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;

class TemplateTag
{

	private string $name;

	private \PHPStan\Type\Type $bound;

	private TemplateTypeVariance $variance;

	public function __construct(string $name, Type $bound, TemplateTypeVariance $variance)
	{
		$this->name = $name;
		$this->bound = $bound;
		$this->variance = $variance;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBound(): Type
	{
		return $this->bound;
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->variance;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['name'],
			$properties['bound'],
			$properties['variance']
		);
	}

}
