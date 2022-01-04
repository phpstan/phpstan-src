<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;

class JsonThrowOnErrorDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var array<string, int> */
	private array $argumentPositions = [
		'json_encode' => 1,
		'json_decode' => 3,
	];

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return $this->reflectionProvider->hasConstant(new FullyQualified('JSON_THROW_ON_ERROR'), null) && in_array(
			$functionReflection->getName(),
			[
				'json_encode',
				'json_decode',
			],
			true,
		);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$argumentPosition = $this->argumentPositions[$functionReflection->getName()];
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if (!isset($functionCall->getArgs()[$argumentPosition])) {
			return $defaultReturnType;
		}

		$optionsExpr = $functionCall->getArgs()[$argumentPosition]->value;
		$constrictedReturnType = TypeCombinator::remove($defaultReturnType, new ConstantBooleanType(false));
		if ($this->isBitwiseOrWithJsonThrowOnError($optionsExpr, $scope)) {
			return $constrictedReturnType;
		}

		$valueType = $scope->getType($optionsExpr);
		if (!$valueType instanceof ConstantIntegerType) {
			return $defaultReturnType;
		}

		$value = $valueType->getValue();
		$throwOnErrorType = $this->reflectionProvider->getConstant(new FullyQualified('JSON_THROW_ON_ERROR'), null)->getValueType();
		if (!$throwOnErrorType instanceof ConstantIntegerType) {
			return $defaultReturnType;
		}

		$throwOnErrorValue = $throwOnErrorType->getValue();
		if (($value & $throwOnErrorValue) !== $throwOnErrorValue) {
			return $defaultReturnType;
		}

		return $constrictedReturnType;
	}

	private function isBitwiseOrWithJsonThrowOnError(Expr $expr, Scope $scope): bool
	{
		if ($expr instanceof ConstFetch) {
			$constant = $this->reflectionProvider->resolveConstantName($expr->name, $scope);
			if ($constant === 'JSON_THROW_ON_ERROR') {
				return true;
			}
		}

		if (!$expr instanceof BitwiseOr) {
			return false;
		}

		return $this->isBitwiseOrWithJsonThrowOnError($expr->left, $scope) ||
			$this->isBitwiseOrWithJsonThrowOnError($expr->right, $scope);
	}

}
