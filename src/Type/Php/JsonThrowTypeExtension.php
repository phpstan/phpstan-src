<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class JsonThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	/** @var array<string, int> */
	private array $argumentPositions = [
		'json_encode' => 1,
		'json_decode' => 3,
	];

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection
	): bool
	{
		return $this->reflectionProvider->hasConstant(new Name\FullyQualified('JSON_THROW_ON_ERROR'), null) && in_array(
			$functionReflection->getName(),
			[
				'json_encode',
				'json_decode',
			],
			true
		);
	}

	private function reorderNamedArguments(
		FunctionReflection $functionReflection,
		FuncCall $functionCall
	): FuncCall {
		$signatureParameters = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getParameters();
		$callArgs = $functionCall->getArgs();

		$argumentPositions = [];
		foreach($signatureParameters as $i => $parameter) {
			$argumentPositions[$parameter->getName()] = $i;
		}

		$reorderedArgs = [];
		foreach($callArgs as $i => $arg) {
			// add regular args as is
			if ($arg->name === null) {
				$reorderedArgs[$i] = $arg;
			}
			// order named args into the position the signature expects them
			if ($arg->name !== null) {
				array_splice($reorderedArgs, $argumentPositions[$arg->name], 0, $arg);
			}
		}

		return new FuncCall($functionCall->name, $reorderedArgs, $functionCall->getAttributes());
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): ?Type
	{
		$functionCall = $this->reorderNamedArguments($functionReflection, $functionCall);
		$argumentPosition = $this->argumentPositions[$functionReflection->getName()];

		if (!isset($functionCall->getArgs()[$argumentPosition])) {
			return null;
		}

		$optionsExpr = $functionCall->getArgs()[$argumentPosition]->value;
		if ($this->isBitwiseOrWithJsonThrowOnError($optionsExpr, $scope)) {
			return new ObjectType('JsonException');
		}

		$valueType = $scope->getType($optionsExpr);
		if (!$valueType instanceof ConstantIntegerType) {
			return null;
		}

		$value = $valueType->getValue();
		$throwOnErrorType = $this->reflectionProvider->getConstant(new Name\FullyQualified('JSON_THROW_ON_ERROR'), null)->getValueType();
		if (!$throwOnErrorType instanceof ConstantIntegerType) {
			return null;
		}

		$throwOnErrorValue = $throwOnErrorType->getValue();
		if (($value & $throwOnErrorValue) !== $throwOnErrorValue) {
			return null;
		}

		return new ObjectType('JsonException');
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
