<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;

/** @api */
final class TemplateIntersectionType extends IntersectionType implements TemplateType
{

	/** @use TemplateTypeTrait<IntersectionType> */
	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		IntersectionType $bound,
	)
	{
		parent::__construct($bound->getTypes());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	public function traverse(callable $cb): Type
	{
		$newBound = $cb($this->getBound());
		if ($this->getBound() !== $newBound && $newBound instanceof IntersectionType) {
			return new self(
				$this->scope,
				$this->strategy,
				$this->variance,
				$this->name,
				$newBound,
			);
		}

		return $this;
	}

}
