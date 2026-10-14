<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ClosureType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateClosureType extends ClosureType implements TemplateType
{

	/** @use TemplateTypeTrait<ClosureType> */
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
		ClosureType $bound,
		?Type $default,
	)
	{
		$isCommonCallable = $bound->isCommonCallable();
		parent::__construct(
			$isCommonCallable ? null : $bound->getParameters(),
			$isCommonCallable ? null : $bound->getReturnType(),
			$bound->isVariadic(),
			$bound->getTemplateTypeMap(),
			$bound->getResolvedTemplateTypeMap(),
			$bound->getCallSiteVarianceMap(),
			$bound->getTemplateTags(),
			$bound->getThrowPoints(),
			$bound->getImpurePoints(),
			$bound->getInvalidateExpressions(),
			$bound->getUsedVariables(),
			$bound->acceptsNamedArguments(),
			$bound->mustUseReturnValue(),
			$bound->getAsserts(),
			$bound->isStaticClosure(),
		);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

}
