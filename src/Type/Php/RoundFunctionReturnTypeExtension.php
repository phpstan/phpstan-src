<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
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
use function array_map;
use function ceil;
use function count;
use function floor;
use function in_array;
use function is_float;
use function is_int;
use function round;

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

		$args = $functionCall->getArgs();

		if (count($args) < 1) {
			return $noArgsReturnType;
		}

		$argType = $scope->getType($args[0]->value);
		$functionName = $functionReflection->getName();
		$proc = $this->getProc($functionName, $args, $scope);

		if ($proc !== null) {
			$constantScalarValues = $argType->getConstantScalarValues();
			$returnValueTypes = [];

			foreach ($constantScalarValues as $constantScalarValue) {
				if (!is_int($constantScalarValue) && !is_float($constantScalarValue)) {
					$returnValueTypes = [];
					break;
				}

				$returnValueTypes[] = new ConstantFloatType($proc($constantScalarValue));
			}

			if (count($returnValueTypes) >= 1) {
				return TypeCombinator::union(...array_map(static fn ($l) => $l, $returnValueTypes));
			}
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

	/**
	 * @param Arg[] $args
	 */
	public function getProc(string $functionName, array $args, Scope $scope): ?Closure
	{
		if ($functionName === 'floor') {
			return static fn ($name) => floor($name);
		}

		if ($functionName === 'ceil') {
			return static fn ($name) => ceil($name);
		}

		if ($functionName === 'round') {
			if (count($args) === 1) {
				return static fn ($name) => round($name);
			}
			if (isset($args[1]->value)) {
				$precisionArg = $args[1]->value;
				$precisionType = $scope->getType($precisionArg);
				$precisions = $precisionType->getConstantScalarValues();
				if (count($precisions) !== 1 || !is_int($precisions[0])) {
					return null;
				}
				$precision = $precisions[0];
			} else {
				$precision = 0;
			}

			if (!isset($args[2]->value)) {
				return static fn ($name) => round($name, $precision);
			}

			$modeArg = $args[2]->value;
			$modeType = $scope->getType($modeArg);
			$mode = $modeType->getConstantScalarValues();

			if (count($mode) === 1 && is_int($mode[0])) {
				return static fn ($name) => round($name, $precision, $mode[0]);
			}
		}

		return null;
	}

}
