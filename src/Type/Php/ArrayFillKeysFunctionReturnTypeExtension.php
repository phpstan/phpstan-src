<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

#[AutowiredService]
final class ArrayFillKeysFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_fill_keys';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$keysType = $scope->getType($args[0]->value);
		if ($keysType->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		$filled = $keysType->fillKeysArray($scope->getType($args[1]->value));
		if ($keysType->isIterableAtLeastOnce()->yes() && $filled->isArray()->yes()) {
			return TypeCombinator::intersect($filled, new NonEmptyArrayType());
		}
		return $filled;
	}

}
