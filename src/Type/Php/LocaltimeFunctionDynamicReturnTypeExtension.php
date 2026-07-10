<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

#[AutowiredService]
final class LocaltimeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'localtime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();

		$associativeType = count($args) >= 2 ? $scope->getType($args[1]->value)->toBoolean() : null;

		if ($associativeType !== null && $associativeType->isTrue()->yes()) {
			return $this->createAssociativeType();
		}

		if ($associativeType === null || $associativeType->isFalse()->yes()) {
			return $this->createListType();
		}

		return TypeCombinator::union($this->createListType(), $this->createAssociativeType());
	}

	private function createListType(): Type
	{
		$integerType = new IntegerType();
		$builder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < 9; $i++) {
			$builder->setOffsetValueType(null, $integerType);
		}

		return $builder->getArray();
	}

	private function createAssociativeType(): Type
	{
		$integerType = new IntegerType();
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach (['tm_sec', 'tm_min', 'tm_hour', 'tm_mday', 'tm_mon', 'tm_year', 'tm_wday', 'tm_yday', 'tm_isdst'] as $key) {
			$builder->setOffsetValueType(new ConstantStringType($key), $integerType);
		}

		return $builder->getArray();
	}

}
