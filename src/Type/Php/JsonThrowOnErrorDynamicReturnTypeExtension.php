<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use stdClass;
use function constant;
use function is_bool;
use function json_decode;
use const JSON_OBJECT_AS_ARRAY;

class JsonThrowOnErrorDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const UNABLE_TO_RESOLVE = '__UNABLE_TO_RESOLVE__';

	private ConstExprEvaluator $constExprEvaluator;

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
		$this->constExprEvaluator = new ConstExprEvaluator(static function (Expr $expr) {
			if ($expr instanceof ConstFetch) {
				return constant($expr->name->toString());
			}

			return null;
		});
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		if ($functionReflection->getName() === 'json_decode') {
			return true;
		}

		return $this->reflectionProvider->hasConstant(new FullyQualified('JSON_THROW_ON_ERROR'), null) && $functionReflection->getName() === 'json_encode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		// update type based on JSON_THROW_ON_ERROR
		$argumentPosition = $this->argumentPositions[$functionReflection->getName()];
		$defaultReturnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		// narrow type for json_decode()
		if ($functionReflection->getName() === 'json_decode') {
			$defaultReturnType = $this->narrowTypeForJsonDecode($functionCall, $scope);
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

	private function narrowTypeForJsonDecode(FuncCall $funcCall, Scope $scope): Type
	{
		$args = $funcCall->getArgs();
		$isArrayWithoutStdClass = $this->isForceArrayWithoutStdClass($funcCall, $scope);

		$firstArgValue = $args[0]->value;
		$firstValueType = $scope->getType($firstArgValue);

		if ($firstValueType instanceof ConstantStringType) {
			return $this->resolveConstantStringType($firstValueType, $isArrayWithoutStdClass);
		}

		// fallback type
		if ($isArrayWithoutStdClass === true) {
			return new MixedType(true, new ObjectType(stdClass::class));
		}

		return new MixedType(true);
	}

	/**
	 * Is "json_decode(..., true)"?
	 */
	private function isForceArrayWithoutStdClass(FuncCall $funcCall, Scope $scope): bool
	{
		$args = $funcCall->getArgs();

		if (isset($args[1])) {
			$secondArgValue = $this->resolveMaskValue($args[1]->value, $scope);
			if ($secondArgValue === self::UNABLE_TO_RESOLVE) {
				return false;
			}

			if (is_bool($secondArgValue)) {
				return $secondArgValue;
			}

			// depends on used constants
			if ($secondArgValue === null) {
				if (! isset($args[3])) {
					return false;
				}

				// @see https://www.php.net/manual/en/json.constants.php#constant.json-object-as-array
				$thirdArgValue = $args[3]->value;
				$resolvedThirdArgValue = $this->resolveMaskValue($thirdArgValue, $scope);
				if (($resolvedThirdArgValue & JSON_OBJECT_AS_ARRAY) !== 0) {
					return true;
				}
			}
		}

		return false;
	}

	private function resolveConstantStringType(ConstantStringType $constantStringType, bool $isForceArray): Type
	{
		$decodedValue = json_decode($constantStringType->getValue(), $isForceArray);

		return ConstantTypeHelper::getTypeFromValue($decodedValue);
	}

	/**
	 * @return mixed
	 */
	private function resolveMaskValue(Expr $expr, Scope $scope)
	{
		$thirdArgValueType = $scope->getType($expr);
		if ($thirdArgValueType instanceof ConstantIntegerType) {
			return $thirdArgValueType->getValue();
		}

		// fallback to value resolver
		try {
			return $this->constExprEvaluator->evaluateSilently($expr);
		} catch (ConstExprEvaluationException) {
			// unable to resolve
			return self::UNABLE_TO_RESOLVE;
		}
	}

}
