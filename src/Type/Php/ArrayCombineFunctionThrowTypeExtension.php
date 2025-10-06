<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
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

		$hasError = $this->arrayCombineHelper->getArrayAndThrowType($firstArg, $secondArg, $scope)[1];
		if (!$hasError->no()) {
			return $functionReflection->getThrowType();
		}

		return null;
	}

}
