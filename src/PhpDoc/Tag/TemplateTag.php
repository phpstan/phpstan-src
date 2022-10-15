<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;

/** @api */
class TemplateTag
{

	public function __construct(private string $name, private Type $bound, private ?Type $default, private TemplateTypeVariance $variance)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBound(): Type
	{
		return $this->bound;
	}

	public function getDefault(): ?Type
	{
		return $this->default;
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->variance;
	}

}
