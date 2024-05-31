<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Type;

/** @api */
interface TemplateType extends CompoundType
{

	/** @return non-empty-string */
	public function getName(): string;

	public function getScope(): TemplateTypeScope;

	public function getBound(): Type;

	public function toArgument(): TemplateType;

	public function isArgument(): bool;

	public function isValidVariance(Type $a, Type $b): TrinaryLogic;

	public function isValidVarianceWithReason(Type $a, Type $b): AcceptsResult;

	public function getVariance(): TemplateTypeVariance;

	public function getStrategy(): TemplateTypeStrategy;

}
