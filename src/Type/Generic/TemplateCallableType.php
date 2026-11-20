<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\CallableType;
use PHPStan\Type\Type;

/** @api */
final class TemplateCallableType extends CallableType implements TemplateType
{

	/** @use TemplateTypeTrait<CallableType> */
	use TemplateTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		CallableType $bound,
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
			$bound->getTemplateTags(),
			$bound->isPure(),
			$bound->getAsserts(),
		);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

}
