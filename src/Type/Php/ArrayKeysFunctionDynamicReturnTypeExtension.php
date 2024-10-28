<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

#[AutowiredService]
final class ArrayKeysFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_keys';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($arrayType->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		$keysArray = $arrayType->getKeysArray();
		if (count($functionCall->getArgs()) === 1) {
			return $keysArray;
		}

		$newArrayType = $keysArray;
		if (!$keysArray->isConstantArray()->no()) {
			$newArrayType = new ArrayType(
				$keysArray->getIterableKeyType()->generalize(GeneralizePrecision::lessSpecific()),
				$keysArray->getIterableValueType()->generalize(GeneralizePrecision::lessSpecific()),
			);
		}
		if ($keysArray->isList()->yes()) {
			$newArrayType = TypeCombinator::intersect($newArrayType, new AccessoryArrayListType());
		}
		return $newArrayType;
	}

}
