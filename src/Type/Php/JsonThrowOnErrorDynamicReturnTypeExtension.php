<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

<<<<<<< HEAD
<<<<<<< HEAD
=======
use PhpParser\Node\Arg;
=======
use PhpParser\ConstExprEvaluator;
>>>>>>> check for JSON_OBJECT_AS_ARRAY, in case of null and array
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
>>>>>>> Extend JsonThrowOnErrorDynamicReturnTypeExtension to detect knonw type from contssant string value
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
<<<<<<< HEAD
<<<<<<< HEAD
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
=======
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
=======
>>>>>>> return mixed type
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
>>>>>>> Extend JsonThrowOnErrorDynamicReturnTypeExtension to detect knonw type from contssant string value
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use stdClass;
use function constant;
use function json_decode;
use const JSON_OBJECT_AS_ARRAY;

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
		$isArrayWithoutStdClass = $this->isForceArrayWithoutStdClass($funcCall);

		$firstArgValue = $args[0]->value;
		$firstValueType = $scope->getType($firstArgValue);

		if ($firstValueType instanceof ConstantStringType) {
			return $this->resolveConstantStringType($firstValueType, $isArrayWithoutStdClass);
		}

		// fallback type
		if ($isArrayWithoutStdClass) {
			return new MixedType(true, new ObjectType(stdClass::class));
		}

		return new MixedType(true);
	}

	/**
	 * Is "json_decode(..., true)"?
	 */
	private function isForceArrayWithoutStdClass(FuncCall $funcCall): bool
	{
		$args = $funcCall->getArgs();

		$constExprEvaluator = new ConstExprEvaluator(static function (Expr $expr) {
			if ($expr instanceof ConstFetch) {
				return constant($expr->name->toString());
			}

			return null;
		});

		if (isset($args[1])) {
			$secondArgValue = $args[1]->value;

			$constValue = $constExprEvaluator->evaluateSilently($secondArgValue);
			if ($constValue === true) {
				return true;
			}

			if ($constValue === false) {
				return false;
			}

			// depends on used constants
			if ($constValue === null) {
				if (! isset($args[3])) {
					return false;
				}

				// @see https://www.php.net/manual/en/json.constants.php#constant.json-object-as-array
				$thirdArgValue = $constExprEvaluator->evaluateSilently($args[3]->value);
				if ($thirdArgValue & JSON_OBJECT_AS_ARRAY) {
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

}
