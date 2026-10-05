<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ClampFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'clamp';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) !== 3) {
			return null;
		}

		foreach ($args as $arg) {
			if ($arg->unpack) {
				return null;
			}
		}

		$valueExpr = $args[0]->value;
		$minExpr = $args[1]->value;
		$maxExpr = $args[2]->value;
		$valueType = $scope->getType($valueExpr);
		$minType = $scope->getType($minExpr);
		$maxType = $scope->getType($maxExpr);

		// arrays are compared by size first, keep the native return type for them
		if (!$valueType->isArray()->no() || !$minType->isArray()->no() || !$maxType->isArray()->no()) {
			return null;
		}

		$value = new AlwaysRememberedExpr($valueExpr, $valueType, $scope->getNativeType($valueExpr));
		$min = new AlwaysRememberedExpr($minExpr, $minType, $scope->getNativeType($minExpr));
		$max = new AlwaysRememberedExpr($maxExpr, $maxType, $scope->getNativeType($maxExpr));

		// same comparisons as php_math_clamp(): $max < $value ? $max : ($value < $min ? $min : $value)
		return $scope->getType(new Ternary(
			new Smaller($max, $value),
			$max,
			new Ternary(
				new Smaller($value, $min),
				$min,
				$value,
			),
		));
	}

}
