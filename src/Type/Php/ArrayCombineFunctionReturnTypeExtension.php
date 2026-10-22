<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class ArrayCombineFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private ArrayCombineHelper $arrayCombineHelper,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_combine';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$firstArg = $args[0]->value;
		$secondArg = $args[1]->value;

		[$returnType, $hasValueError] = $this->arrayCombineHelper->getReturnAndThrowType($firstArg, $secondArg, $scope);
		if ($hasValueError->no()) {
			return $returnType;
		}

		$throwsValueError = $scope->getPhpVersion()->throwsValueErrorForInternalFunctions();
		if ($hasValueError->yes()) {
			if ($throwsValueError->yes()) {
				return new NeverType();
			}

			return new ConstantBooleanType(false);
		}

		if ($throwsValueError->yes()) {
			return $returnType;
		}

		return new UnionType([$returnType, new ConstantBooleanType(false)]);
	}

}
