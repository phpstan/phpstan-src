<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

#[AutowiredService]
final class ArrayRandFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private RandomArrayKeysReturnTypeHelper $randomArrayKeysReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_rand';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		$argsCount = count($args);
		if ($argsCount < 1) {
			return null;
		}

		$firstArgType = $scope->getType($args[0]->value);
		$keyType = $this->randomArrayKeysReturnTypeHelper->getPickedKeyType($firstArgType);

		if ($argsCount < 2) {
			return $keyType;
		}

		$secondArgType = $scope->getType($args[1]->value);

		$one = new ConstantIntegerType(1);
		if ($one->isSuperTypeOf($secondArgType)->yes()) {
			return $keyType;
		}

		$keysListType = $this->randomArrayKeysReturnTypeHelper->getPickedKeysListType($firstArgType);

		$bigger2 = IntegerRangeType::fromInterval(2, null);
		if ($bigger2->isSuperTypeOf($secondArgType)->yes()) {
			return $keysListType;
		}

		return TypeCombinator::union($keyType, $keysListType);
	}

}
