<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
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

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
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
		$functionName = $functionReflection->getName();
		$argumentPosition = $this->argumentPositions[$functionName];
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if (!isset($functionCall->getArgs()[$argumentPosition])) {
			return $defaultReturnType;
		}

		$optionsExpr = $functionCall->getArgs()[$argumentPosition]->value;
		if ($functionName === 'json_encode' && $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($optionsExpr, $scope, 'JSON_THROW_ON_ERROR')->yes()) {
			return TypeCombinator::remove($defaultReturnType, new ConstantBooleanType(false));
		}

		return $defaultReturnType;
	}

}
