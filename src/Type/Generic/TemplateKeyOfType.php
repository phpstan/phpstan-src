<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\KeyOfType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateKeyOfType extends KeyOfType implements TemplateType
{

	/** @use TemplateTypeTrait<KeyOfType> */
	use TemplateTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		KeyOfType $bound,
	)
	{
		parent::__construct($bound->getType());
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	protected function getResult(): Type
	{
		$result = $this->getBound()->getResult();

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$result,
			$this->getVariance(),
			$this->getStrategy(),
		);
	}

	protected function shouldGeneralizeInferredType(): bool
	{
		return false;
	}

}
