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
		if (count($funcCall->getArgs()) < 2) {
			return $functionReflection->getThrowType();
		}

		$firstArg = $funcCall->getArgs()[0]->value;
		$secondArg = $funcCall->getArgs()[1]->value;

		$hasValueError = $this->arrayCombineHelper->getReturnAndThrowType($firstArg, $secondArg, $scope)[1];
		if (!$hasValueError->no()) {
			return $functionReflection->getThrowType();
		}

		return null;
	}

}
