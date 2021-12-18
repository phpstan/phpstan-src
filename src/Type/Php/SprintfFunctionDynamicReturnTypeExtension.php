<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Throwable;
use function array_shift;
use function count;
use function is_string;
use function sprintf;

class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'sprintf';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$formatType = $scope->getType($args[0]->value);
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
			$value = @sprintf($format, ...$values);
		} catch (Throwable $e) {
			return $returnType;
		}

		return $scope->getTypeFromValue($value);
	}

}
