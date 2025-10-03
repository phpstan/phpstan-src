<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

#[AutowiredService]
final class TypeCoercionRuleHelper
{

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.checkTypeCoercions%')]
		private readonly bool $checkTypeCoercions,
		#[AutowiredParameter(ref: '%allowedTypeCoercions.boolToString%')]
		private readonly bool $allowBoolToString,
	)
	{
	}

	public function coerceToString(Type $type): Type
	{
		if (!$this->checkTypeCoercions) {
			return $type->toString();
		}
		if (!$this->allowBoolToString && !$type->isBoolean()->no()) {
			return new ErrorType();
		}
		return $type->toString();
	}

}
