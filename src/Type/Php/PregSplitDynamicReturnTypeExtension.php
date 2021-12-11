<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function sprintf;
use function strtolower;

class PregSplitDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}


	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_split';
	}


	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$flagsArg = $functionCall->getArgs()[3] ?? null;

		if ($this->hasFlag($this->getConstant('PREG_SPLIT_OFFSET_CAPTURE'), $flagsArg, $scope)) {
			$type = new ArrayType(
				new IntegerType(),
				new ConstantArrayType([new ConstantIntegerType(0), new ConstantIntegerType(1)], [new StringType(), IntegerRangeType::fromInterval(0, null)]),
			);
			return TypeCombinator::union($type, new ConstantBooleanType(false));
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}


	private function hasFlag(int $flag, ?Arg $expression, Scope $scope): bool
	{
		if ($expression === null) {
			return false;
		}

		if ($expression->value instanceof BitwiseOr) {
			$left = $expression->value->left;
			$right = $expression->value->right;

			$leftType = $scope->getType($left);
			$rightType = $scope->getType($right);

			if ($leftType instanceof ConstantIntegerType && ($leftType->getValue() & $flag) === $flag) {
				return true;
			}
			if ($rightType instanceof ConstantIntegerType && ($rightType->getValue() & $flag) === $flag) {
				return true;
			}
		}

		$type = $scope->getType($expression->value);
		return $type instanceof ConstantIntegerType && ($type->getValue() & $flag) === $flag;
	}


	private function getConstant(string $constantName): int
	{
		$constant = $this->reflectionProvider->getConstant(new Name($constantName), null);
		$valueType = $constant->getValueType();
		if (!$valueType instanceof ConstantIntegerType) {
			throw new ShouldNotHappenException(sprintf('Constant %s does not have integer type.', $constantName));
		}

		return $valueType->getValue();
	}

}
