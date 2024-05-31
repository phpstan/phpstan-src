<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\IntegerType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
final class TemplateIntegerType extends IntegerType implements TemplateType
{

	/** @use TemplateTypeTrait<IntegerType> */
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
		IntegerType $bound,
	)
	{
		parent::__construct();
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	protected function shouldGeneralizeInferredType(): bool
	{
		return false;
	}

}
