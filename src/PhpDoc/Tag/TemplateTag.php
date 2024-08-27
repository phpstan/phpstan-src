<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class TemplateTag
{

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(private string $name, private Type $bound, private TemplateTypeVariance $variance)
	{
	}

	/**
	 * @return non-empty-string
	 */
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

}
