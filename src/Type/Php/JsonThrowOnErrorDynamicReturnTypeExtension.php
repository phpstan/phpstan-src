<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function is_bool;
use function json_decode;

#[AutowiredService]
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
		if ($firstValueType->getConstantStrings() !== []) {
			$types = [];

			foreach ($firstValueType->getConstantStrings() as $constantString) {
				$types[] = $this->resolveConstantStringType($constantString, $isForceArray);
			}

			return TypeCombinator::union(...$types);
		}

		if ($isForceArray->yes()) {
			return TypeCombinator::remove($fallbackType, new ObjectWithoutClassType());
		}

		return $fallbackType;
	}

	/**
	 * Is "json_decode(..., true)"?
	 */
	private function isForceArray(FuncCall $funcCall, Scope $scope): TrinaryLogic
	{
		$args = $funcCall->getArgs();
		$flagValue = $this->getFlagValue($funcCall, $scope);
		if (!isset($args[1])) {
			return TrinaryLogic::createNo();
		}

		$secondArgType = $scope->getType($args[1]->value);
		$secondArgValues = [];
		foreach ($secondArgType->getConstantScalarValues() as $value) {
			if ($value === null) {
				$secondArgValues[] = $flagValue;
				continue;
			}
			if (!is_bool($value)) {
				return TrinaryLogic::createNo();
			}
			$secondArgValues[] = TrinaryLogic::createFromBoolean($value);
		}

		if ($secondArgValues === []) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::extremeIdentity(...$secondArgValues);
	}

	private function resolveConstantStringType(ConstantStringType $constantStringType, TrinaryLogic $isForceArray): Type
	{
		$types = [];
		/** @var bool $asArray */
		foreach ($isForceArray->toBooleanType()->getConstantScalarValues() as $asArray) {
			$decodedValue = json_decode($constantStringType->getValue(), $asArray);
			$types[] = ConstantTypeHelper::getTypeFromValue($decodedValue);
		}

		return TypeCombinator::union(...$types);
	}

	private function getFlagValue(FuncCall $funcCall, Scope $scope): TrinaryLogic
	{
		$args = $funcCall->getArgs();
		if (!isset($args[3])) {
			return TrinaryLogic::createNo();
		}

		// depends on used constants, @see https://www.php.net/manual/en/json.constants.php#constant.json-object-as-array
		return $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($args[3]->value, $scope, 'JSON_OBJECT_AS_ARRAY');
	}

}
