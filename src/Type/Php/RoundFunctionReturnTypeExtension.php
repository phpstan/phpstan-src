<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class RoundFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'round',
				'ceil',
				'floor',
			],
			true,
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		// PHP 7 can return either a float or false.
		// PHP 8 can either return a float or fatal.
		$defaultReturnType = null;

		if ($this->phpVersion->hasStricterRoundFunctions()) {
			// PHP 8 fatals with a missing parameter.
			$noArgsReturnType = new NeverType(true);
		} else {
			// PHP 7 returns null with a missing parameter.
			$noArgsReturnType = new NullType();
		}

		if (count($functionCall->getArgs()) < 1) {
			return $noArgsReturnType;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);

		if ($firstArgType instanceof MixedType) {
			return $defaultReturnType;
		}

		if ($this->phpVersion->hasStricterRoundFunctions()) {
			$allowed = TypeCombinator::union(
				new IntegerType(),
				new FloatType(),
			);

			if (!$scope->isDeclareStrictTypes()) {
				$allowed = TypeCombinator::union(
					$allowed,
					new IntersectionType([
						new StringType(),
						new AccessoryNumericStringType(),
					]),
					new NullType(),
					new BooleanType(),
				);
			}

			if ($allowed->isSuperTypeOf($firstArgType)->no()) {
				// PHP 8 fatals if the parameter is not an integer or float.
				return new NeverType(true);
			}
		} elseif ($firstArgType->isArray()->yes()) {
			// PHP 7 returns false if the parameter is an array.
			return new ConstantBooleanType(false);
		}

		return new FloatType();
	}

}
