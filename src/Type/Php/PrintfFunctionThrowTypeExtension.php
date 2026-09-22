<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArgumentCountError;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use ValueError;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function max;

/**
 * The printf family throws ArgumentCountError (the v* functions ValueError)
 * when the format uses more arguments than passed, and ValueError for an
 * invalid format or an out-of-range `*` / `.*` argument.
 */
#[AutowiredService]
final class PrintfFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const INT_MAX = 2147483647;

	private const FUNCTIONS = ['sprintf', 'printf', 'fprintf', 'vsprintf', 'vprintf', 'vfprintf'];

	private const ARRAY_FUNCTIONS = ['vsprintf', 'vprintf', 'vfprintf'];

	private const STREAM_FUNCTIONS = ['fprintf', 'vfprintf'];

	private const NEGATIVE_PRECISION_SPECIFIERS = ['g', 'G', 'h', 'H'];

	public function __construct(
		private PhpVersion $phpVersion,
		private PrintfFormatParser $formatParser,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), self::FUNCTIONS, true);
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new VoidType();
		}

		$functionName = $functionReflection->getName();
		$formatPosition = in_array($functionName, self::STREAM_FUNCTIONS, true) ? 1 : 0;
		$args = $funcCall->getArgs();
		foreach ($args as $i => $arg) {
			$originalArg = $arg->getAttribute(ArgumentsNormalizer::ORIGINAL_ARG_ATTRIBUTE);
			if ($arg->name !== null || ($originalArg instanceof Arg && $originalArg->name !== null)) {
				return $functionReflection->getThrowType();
			}
			if ($i <= $formatPosition && $arg->unpack) {
				return $functionReflection->getThrowType();
			}
		}

		if (!isset($args[$formatPosition])) {
			return $functionReflection->getThrowType();
		}

		$formatType = $scope->getNativeType($args[$formatPosition]->value);
		$formats = $formatType->getConstantStrings();
		if (!$formatType->isString()->yes() || count($formats) === 0) {
			return $functionReflection->getThrowType();
		}

		$uses = [];
		$requiredArgumentsCount = 0;
		foreach ($formats as $format) {
			$formatUses = $this->formatParser->parse($format->getValue());
			if ($formatUses === null) {
				return $functionReflection->getThrowType();
			}

			foreach ($formatUses as $use) {
				$uses[] = $use;
			}
			$requiredArgumentsCount = max($requiredArgumentsCount, $this->formatParser->getRequiredArgumentsCount($formatUses));
		}

		$valueArgs = array_slice($args, $formatPosition + 1);
		if (in_array($functionName, self::ARRAY_FUNCTIONS, true)) {
			if (
				$this->hasEnoughValuesInArray($valueArgs, $requiredArgumentsCount, $scope)
				&& $this->areArrayStarArgumentsValid($valueArgs, $uses, $scope)
			) {
				return new VoidType();
			}

			return new ObjectType(ValueError::class);
		}

		[$minArgumentsCount, $knownArgumentTypes, $hasStringKeys] = $this->getVariadicArguments($valueArgs, $scope);

		$throwTypes = [];
		if ($hasStringKeys || $minArgumentsCount < $requiredArgumentsCount) {
			$throwTypes[] = new ObjectType(ArgumentCountError::class);
		}

		foreach ($uses as $use) {
			if ($use['kind'] === 'value') {
				continue;
			}

			if (
				!isset($knownArgumentTypes[$use['index']])
				|| !$this->isStarArgumentValid($knownArgumentTypes[$use['index']], $use['kind'], $use['specifier'])
			) {
				$throwTypes[] = new ObjectType(ValueError::class);
				break;
			}
		}

		if (count($throwTypes) === 0) {
			return new VoidType();
		}

		return TypeCombinator::union(...$throwTypes);
	}

	/**
	 * Types of the arguments after the format at positions known from the call,
	 * and how many arguments are passed at least. An unpacked constant list
	 * contributes its items, any other unpacked array ends the known positions.
	 *
	 * @param list<Arg> $args
	 * @return array{int, list<Type>, bool}
	 */
	private function getVariadicArguments(array $args, Scope $scope): array
	{
		$minCount = 0;
		$knownTypes = [];
		$positionsKnown = true;
		$hasStringKeys = false;
		foreach ($args as $arg) {
			$argType = $scope->getNativeType($arg->value);
			if (!$arg->unpack) {
				$minCount++;
				if ($positionsKnown) {
					$knownTypes[] = $argType;
				}
				continue;
			}

			if (!$argType->getIterableKeyType()->isInteger()->yes()) {
				// string keys are passed as unknown named parameters
				$hasStringKeys = true;
			}

			$constantArrays = $argType->getConstantArrays();
			if (count($constantArrays) === 1 && $constantArrays[0]->getOptionalKeys() === []) {
				$valueTypes = $constantArrays[0]->getValueTypes();
				$minCount += count($valueTypes);
				if ($positionsKnown) {
					foreach ($valueTypes as $valueType) {
						$knownTypes[] = $valueType;
					}
				}
				continue;
			}

			$positionsKnown = false;
		}

		return [$minCount, $knownTypes, $hasStringKeys];
	}

	/**
	 * @param list<Arg> $args
	 */
	private function hasEnoughValuesInArray(array $args, int $requiredCount, Scope $scope): bool
	{
		if (count($args) !== 1 || $args[0]->unpack) {
			return false;
		}

		if ($requiredCount === 0) {
			return true;
		}

		$valuesType = $scope->getNativeType($args[0]->value);
		if (!$valuesType->isArray()->yes()) {
			return false;
		}

		return IntegerRangeType::fromInterval($requiredCount, null)->isSuperTypeOf($valuesType->getArraySize())->yes();
	}

	/**
	 * @param list<Arg> $args
	 * @param list<array{index: int, kind: 'value'|'width'|'precision', specifier: string}> $uses
	 */
	private function areArrayStarArgumentsValid(array $args, array $uses, Scope $scope): bool
	{
		$valuesType = null;
		foreach ($uses as $use) {
			if ($use['kind'] === 'value') {
				continue;
			}

			if ($valuesType === null) {
				$valuesType = $scope->getNativeType($args[0]->value);
			}

			// values are taken in iteration order, the keys do not matter
			$constantArrays = $valuesType->getConstantArrays();
			if (count($constantArrays) === 1 && $constantArrays[0]->getOptionalKeys() === []) {
				$valueTypes = array_values($constantArrays[0]->getValueTypes());
				if (!isset($valueTypes[$use['index']])) {
					return false;
				}
				$argumentType = $valueTypes[$use['index']];
			} else {
				$argumentType = $valuesType->getIterableValueType();
			}

			if (!$this->isStarArgumentValid($argumentType, $use['kind'], $use['specifier'])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A `*` width must be an int between 0 and INT_MAX, a `.*` precision
	 * between 0 (-1 for g, G, h and H) and INT_MAX. Other types are not
	 * coerced, not even in coercive typing mode.
	 */
	private function isStarArgumentValid(Type $argumentType, string $kind, string $specifier): bool
	{
		$min = $kind === 'precision' && in_array($specifier, self::NEGATIVE_PRECISION_SPECIFIERS, true) ? -1 : 0;

		return IntegerRangeType::fromInterval($min, self::INT_MAX)->isSuperTypeOf($argumentType)->yes();
	}

}
