<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Throwable;
use function array_key_exists;
use function array_shift;
use function count;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;
use function vsprintf;

class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sprintf', 'vsprintf'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$formatType = $scope->getType($args[0]->value);
		if ($formatType instanceof ConstantStringType) {
			// The printf format is %[argnum$][flags][width][.precision]
			if (preg_match('/^%([0-9]*\$)?[0-9]*\.?[0-9]*[bdeEfFgGhHouxX]$/', $formatType->getValue(), $matches) === 1) {
				// invalid positional argument
				if (array_key_exists(1, $matches) && $matches[1] === '0$') {
					return null;
				}

				return new IntersectionType([
					new StringType(),
					new AccessoryNumericStringType(),
				]);
			}
		}

		if ($formatType->isNonEmptyString()->yes()) {
			$returnType = new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		} else {
			$returnType = new StringType();
		}

		$values = [];
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if (!$argType instanceof ConstantScalarType) {
				return $returnType;
			}

			$values[] = $argType->getValue();
		}

		$format = array_shift($values);
		if (!is_string($format)) {
			return $returnType;
		}

		try {
			if ($functionReflection->getName() === 'sprtinf') {
				$value = @sprintf($format, ...$values);
			} else {
				$value = @vsprintf($format, $values);
			}
		} catch (Throwable) {
			return $returnType;
		}

		return $scope->getTypeFromValue($value);
	}

}
