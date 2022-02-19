<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
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
		$types = [new StringType()];
		if ($formatType->isNonEmptyString()->yes()) {
			$types[] = new AccessoryNonEmptyStringType();
		}

		$values = [];
		$isConstant = true;
		$isLiteral = true;
		$maxLiteral = new UnionType([
			new IntegerType(),
			new FloatType(),
			new BooleanType(),
			new AccessoryLiteralStringType(),
		]);
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if (!$maxLiteral->isSuperTypeOf($argType)->yes()) {
				$isLiteral = false;
			}

			if (!$argType instanceof ConstantScalarType) {
				$isConstant = false;
				continue;
			}

			$values[] = $argType->getValue();
		}
		if ($isLiteral) {
			$types[] = new AccessoryLiteralStringType();
		}
		$returnType = count($types) > 1 ? new IntersectionType($types) : $types[0];
		if (!$isConstant) {
			return $returnType;
		}

		$format = array_shift($values);
		if (!is_string($format)) {
			return $returnType;
		}

		try {
			$value = @sprintf($format, ...$values);
		} catch (Throwable) {
			return $returnType;
		}

		return $scope->getTypeFromValue($value);
	}

}
