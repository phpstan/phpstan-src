<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

class ArrayReverseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reverse';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$type = $scope->getType($functionCall->getArgs()[0]->value);
		$preserveKeysType = isset($functionCall->getArgs()[1]) ? $scope->getType($functionCall->getArgs()[1]->value) : new NeverType();
		$preserveKeys = $preserveKeysType instanceof ConstantBooleanType ? $preserveKeysType->getValue() : false;

		if (!$type->isIterable()->yes()) {
			return null;
		}

		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($preserveKeys): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}
			if ($type instanceof ConstantArrayType) {
				return $type->reverse($preserveKeys);
			}
			return $type;
		});
	}

}
