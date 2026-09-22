<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function count;
use function is_float;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * range() throws ValueError since PHP 8.0 for an invalid step, a non-finite boundary,
 * and a range with more items than an array can hold.
 */
#[AutowiredService]
final class RangeFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	/** HT_MAX_SIZE of a 32-bit PHP, the smallest array size range() refuses to create */
	private const RANGE_SIZE_LIMIT = 0x02000000;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'range';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		// PHP 7 reported these with a warning and a false return value
		$phpVersions = $scope->getPhpVersion();
		if ($phpVersions->throwsValueErrorForInternalFunctions()->no()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) < 2) {
			return $functionReflection->getThrowType();
		}

		$starts = self::getConstantValues($scope->getNativeType($args[0]->value), true);
		$ends = self::getConstantValues($scope->getNativeType($args[1]->value), true);
		$steps = count($args) >= 3 ? self::getConstantValues($scope->getNativeType($args[2]->value), false) : [1];
		if ($starts === null || $ends === null || $steps === null) {
			return $functionReflection->getThrowType();
		}

		$hasStricterRange = $phpVersions->hasStricterRangeFunction();
		foreach ($starts as $start) {
			foreach ($ends as $end) {
				foreach ($steps as $step) {
					if (self::mightThrow($hasStricterRange, $start, $end, $step)) {
						return $functionReflection->getThrowType();
					}
				}
			}
		}

		return new VoidType();
	}

	/**
	 * @return ($allowString is true ? list<int|float|string> : list<int|float>)|null
	 */
	private static function getConstantValues(Type $type, bool $allowString): ?array
	{
		if (!$type->isConstantScalarValue()->yes()) {
			return null;
		}

		$values = [];
		foreach ($type->getConstantScalarValues() as $value) {
			if (!is_int($value) && !is_float($value) && (!$allowString || !is_string($value))) {
				return null;
			}

			$values[] = $value;
		}

		return $values;
	}

	private static function mightThrow(TrinaryLogic $hasStricterRange, int|float|string $start, int|float|string $end, int|float $step): bool
	{
		$length = RangeFunctionReturnTypeExtension::getRangeLength($start, $end, $step);
		if ($length === null) {
			// only a character range has no number of items, any other one did not fit into a float
			if (!is_string($start) || !is_string($end) || is_numeric($start) || is_numeric($end)) {
				return true;
			}
		} elseif ($length >= self::RANGE_SIZE_LIMIT) {
			return true;
		}

		// calling range() tells about the step and the boundaries of a short range
		$runtimeRejects = null;
		if ($length === null || $length <= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
			$runtimeRejects = RangeFunctionArgumentsHelper::callRange($start, $end, $step) === false;
		}

		return !RangeFunctionArgumentsHelper::rejects($hasStricterRange, $start, $end, $step, $runtimeRejects)->no();
	}

}
