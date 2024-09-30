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
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function is_bool;
use function json_decode;

final class JsonThrowOnErrorDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		if ($functionReflection->getName() === 'json_decode') {
			return true;
		}

		return $functionReflection->getName() === 'json_encode' && $this->reflectionProvider->hasConstant(new FullyQualified('JSON_THROW_ON_ERROR'), null);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$argumentPosition = $this->argumentPositions[$functionReflection->getName()];
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		if ($functionReflection->getName() === 'json_decode') {
			$defaultReturnType = $this->narrowTypeForJsonDecode($functionCall, $scope, $defaultReturnType);
		}

		if (!isset($functionCall->getArgs()[$argumentPosition])) {
			return $defaultReturnType;
		}

		$optionsExpr = $functionCall->getArgs()[$argumentPosition]->value;
		if ($functionReflection->getName() === 'json_encode' && $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($optionsExpr, $scope, 'JSON_THROW_ON_ERROR')->yes()) {
			return TypeCombinator::remove($defaultReturnType, new ConstantBooleanType(false));
		}

		return $defaultReturnType;
	}

	private function narrowTypeForJsonDecode(FuncCall $funcCall, Scope $scope, Type $fallbackType): Type
	{
		$args = $funcCall->getArgs();
		$isForceArray = $this->isForceArray($funcCall, $scope);
		if (!isset($args[0])) {
			return $fallbackType;
		}

		$firstValueType = $scope->getType($args[0]->value);
		if ($firstValueType instanceof ConstantStringType) {
			return $this->resolveConstantStringType($firstValueType, $isForceArray);
		}

		if ($isForceArray) {
			return TypeCombinator::remove($fallbackType, new ObjectWithoutClassType());
		}

		return $fallbackType;
	}

	/**
	 * Is "json_decode(..., true)"?
	 */
	private function isForceArray(FuncCall $funcCall, Scope $scope): bool
	{
		$args = $funcCall->getArgs();
		if (!isset($args[1])) {
			return false;
		}

		$secondArgType = $scope->getType($args[1]->value);
		$secondArgValue = $secondArgType instanceof ConstantScalarType ? $secondArgType->getValue() : null;

		if (is_bool($secondArgValue)) {
			return $secondArgValue;
		}

		if ($secondArgValue !== null || !isset($args[3])) {
			return false;
		}

		// depends on used constants, @see https://www.php.net/manual/en/json.constants.php#constant.json-object-as-array
		return $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($args[3]->value, $scope, 'JSON_OBJECT_AS_ARRAY')->yes();
	}

	private function resolveConstantStringType(ConstantStringType $constantStringType, bool $isForceArray): Type
	{
		$decodedValue = json_decode($constantStringType->getValue(), $isForceArray);

		return ConstantTypeHelper::getTypeFromValue($decodedValue);
	}

}
