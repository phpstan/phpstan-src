<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;

/** @api */
final class TemplateBenevolentUnionType extends BenevolentUnionType implements TemplateType
{

	/** @use TemplateTypeTrait<BenevolentUnionType> */
	use TemplateTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		BenevolentUnionType $bound,
		?Type $default,
	)
	{
		parent::__construct($bound->getTypes());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

	/** @param Type[] $types */
	public function withTypes(array $types): self
	{
		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			new BenevolentUnionType($types),
			$this->default,
		);
	}

	public function filterTypes(callable $filterCb): Type
	{
		$result = parent::filterTypes($filterCb);
		if (!$result instanceof TemplateType) {
			return TemplateTypeFactory::create(
				$this->getScope(),
				$this->getName(),
				$result,
				$this->getVariance(),
				$this->getStrategy(),
				$this->getDefault(),
			);
		}

		return $result;
	}

}
