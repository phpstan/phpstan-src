<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function in_array;

/**
 * Narrows the `false` part out of `ob_get_*()` return types when output
 * buffering is known to be active (a previous `ob_start()` call in the same
 * scope has not been balanced by a closing call).
 */
#[AutowiredService]
final class OutputBufferingDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'ob_get_contents',
			'ob_get_clean',
			'ob_get_flush',
			'ob_get_length',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		$outputBufferLevelType = $scope->getType(new FuncCall(new Name('ob_get_level'), []));
		if (IntegerRangeType::createAllGreaterThanOrEqualTo(1)->isSuperTypeOf($outputBufferLevelType)->yes()) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		return $defaultReturnType;
	}

}
