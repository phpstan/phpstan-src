<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
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

		$associativeType = count($args) >= 2 ? $scope->getType($args[1]->value)->toBoolean() : new ConstantBooleanType(false);

		if ($associativeType->isTrue()->yes()) {
			return $this->createAssociativeType();
		}

		if ($associativeType->isFalse()->yes()) {
			return $this->createListType();
		}

		return TypeCombinator::union($this->createListType(), $this->createAssociativeType());
	}

	private function createListType(): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->createFieldTypes() as [, $valueType]) {
			$builder->setOffsetValueType(null, $valueType);
		}

		return $builder->getArray();
	}

	private function createAssociativeType(): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->createFieldTypes() as [$key, $valueType]) {
			$builder->setOffsetValueType(new ConstantStringType($key), $valueType);
		}

		return $builder->getArray();
	}

	/**
	 * Fields of the C localtime struct in order, with the value ranges documented at
	 * https://www.php.net/manual/en/function.localtime.php
	 *
	 * @return list<array{string, Type}>
	 */
	private function createFieldTypes(): array
	{
		return [
			['tm_sec', IntegerRangeType::fromInterval(0, 59)],
			['tm_min', IntegerRangeType::fromInterval(0, 59)],
			['tm_hour', IntegerRangeType::fromInterval(0, 23)],
			['tm_mday', IntegerRangeType::fromInterval(1, 31)],
			['tm_mon', IntegerRangeType::fromInterval(0, 11)],
			['tm_year', new IntegerType()],
			['tm_wday', IntegerRangeType::fromInterval(0, 6)],
			['tm_yday', IntegerRangeType::fromInterval(0, 365)],
			['tm_isdst', new IntegerType()],
		];
	}

}
