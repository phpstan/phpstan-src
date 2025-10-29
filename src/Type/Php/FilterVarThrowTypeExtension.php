<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

#[AutowiredService]
final class FilterVarThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return $functionReflection->getName() === 'filter_var'
			&& $this->reflectionProvider->hasConstant(new Name\FullyQualified('FILTER_THROW_ON_FAILURE'), null);
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $funcCall,
		Scope $scope,
	): ?Type
	{
		if (!isset($funcCall->getArgs()[3])) {
			return null;
		}

		$flagsExpr = $funcCall->getArgs()[3]->value;
		$flagsType = $scope->getType($flagsExpr);

		if ($flagsType->isConstantArray()->yes()) {
			$flagsType = $flagsType->getOffsetValueType(new ConstantStringType('flags'));
		}

		$flag = $this->getConstant();

		if ($flag !== null && $flagsType instanceof ConstantIntegerType && ($flagsType->getValue() & $flag) === $flag) {
			return new ObjectType('Filter\FilterFailedException');
		}

		return null;
	}

	private function getConstant(): ?int
	{
		$constant = $this->reflectionProvider->getConstant(new Name('FILTER_THROW_ON_FAILURE'), null);
		$valueType = $constant->getValueType();
		if (!$valueType instanceof ConstantIntegerType) {
			return null;
		}

		return $valueType->getValue();
	}

}
