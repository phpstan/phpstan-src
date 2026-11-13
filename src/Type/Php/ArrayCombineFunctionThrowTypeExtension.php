<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArrayCombineFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(private ArrayCombineHelper $arrayCombineHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_combine';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		if (count($args) < 2) {
			return $functionReflection->getThrowType();
		}

		$firstArg = $args[0]->value;
		$secondArg = $args[1]->value;

		$hasValueError = $this->arrayCombineHelper->getReturnAndThrowType($firstArg, $secondArg, $scope)[1];
		if (!$hasValueError->no()) {
			return $functionReflection->getThrowType();
		}

		return null;
	}

}
