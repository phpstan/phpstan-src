<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\CombinationsHelper;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_fill;
use function array_key_exists;
use function array_shift;
use function array_values;
use function count;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function substr;
use function vsprintf;

#[AutowiredService]
final class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sprintf', 'vsprintf'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$constantType = $this->getConstantType($args, $functionReflection, $scope);
		if ($constantType !== null) {
			return $constantType;
		}

		$formatType = $scope->getType($args[0]->value);
		$formatStrings = $formatType->getConstantStrings();

		$isLowercase = $formatType->isLowercaseString()->yes() && $this->allValuesSatisfies(
			$functionReflection,
			$scope,
			$args,
			static fn (Type $type): bool => $type->toString()->isLowercaseString()->yes()
		);

		$singlePlaceholderEarlyReturn = [];
		$allPatternsNonEmpty = count($formatStrings) !== 0;
		$allPatternsNonFalsy = count($formatStrings) !== 0;
		foreach ($formatStrings as $constantString) {
			$constantParts = $this->getFormatConstantParts(
				$constantString->getValue(),
				$functionReflection,
				$functionCall,
				$scope,
			);
			if ($constantParts !== null) {
				if ($constantParts->isNonFalsyString()->yes()) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
					// keep all bool flags as is
				} elseif ($constantParts->isNonEmptyString()->yes()) {
					$allPatternsNonFalsy = false;
				} else {
					$allPatternsNonEmpty = false;
					$allPatternsNonFalsy = false;
				}
			} else {
				$allPatternsNonEmpty = false;
				$allPatternsNonFalsy = false;
			}

			if (
				is_array($singlePlaceholderEarlyReturn)
				// The printf format is %[argnum$][flags][width][.precision]specifier.
				&& preg_match('/^%(?P<argnum>[0-9]*\$)?(?P<width>[0-9]*)\.?[0-9]*(?P<specifier>[sbdeEfFgGhHouxX])$/', $constantString->getValue(), $matches) === 1
			) {
				if ($matches['argnum'] !== '') {
					// invalid positional argument
					if ($matches['argnum'] === '0$') {
						return null;
					}
					$checkArg = intval(substr($matches['argnum'], 0, -1));
				} else {
					$checkArg = 1;
				}

				$checkArgType = $this->getValueType($functionReflection, $scope, $args, $checkArg);
				if ($checkArgType === null) {
					return null;
				}

				// if the format string is just a placeholder and specified an argument
				// of stringy type, then the return value will be of the same type
				if (
					$matches['specifier'] === 's'
					&& ($checkArgType->isString()->yes() || $checkArgType->isInteger()->yes())
				) {
					if ($checkArgType instanceof IntegerRangeType) {
						$constArgTypes = $checkArgType->getFiniteTypes();
					} else {
						$constArgTypes = $checkArgType->getConstantScalarTypes();
					}
					if ($constArgTypes !== []) {
						$printfArgs = array_fill(0, count($args) - 1, '');
						foreach ($constArgTypes as $constArgType) {
							$printfArgs[$checkArg - 1] = $constArgType->getValue();
							try {
								$singlePlaceholderEarlyReturn[] = new ConstantStringType(@sprintf($constantString->getValue(), ...$printfArgs));
							} catch (Throwable) {
								continue 2;
							}
						}

						continue;
					}

					$singlePlaceholderEarlyReturn[] = $checkArgType->toString();
				} elseif ($matches['specifier'] !== 's') {
					$singlePlaceholderEarlyReturn[] = $this->getStringReturnType(
						new AccessoryNumericStringType(),
						$isLowercase,
					);
				}

				continue;
			}

			$singlePlaceholderEarlyReturn = null;
		}

		if (is_array($singlePlaceholderEarlyReturn) && count($singlePlaceholderEarlyReturn) > 0) {
			return TypeCombinator::union(...$singlePlaceholderEarlyReturn);
		}

		if ($allPatternsNonFalsy) {
			return $this->getStringReturnType(new AccessoryNonFalsyStringType(), $isLowercase);
		}

		$isNonEmpty = $allPatternsNonEmpty;
		if (!$isNonEmpty && $formatType->isNonEmptyString()->yes()) {
			$isNonEmpty = $this->allValuesSatisfies(
				$functionReflection,
				$scope,
				$args,
				static fn (Type $type): bool => $type->toString()->isNonEmptyString()->yes()
			);
		}

		if ($isNonEmpty) {
			return $this->getStringReturnType(new AccessoryNonEmptyStringType(), $isLowercase);
		}

		return $this->getStringReturnType(null, $isLowercase);
	}

	/**
	 * @param array<Arg> $args
	 * @param callable(Type): bool $cb
	 */
	private function allValuesSatisfies(FunctionReflection $functionReflection, Scope $scope, array $args, callable $cb): bool
	{
		if ($functionReflection->getName() === 'sprintf' && count($args) >= 2) {
			foreach ($args as $key => $arg) {
				if ($key === 0) {
					continue;
				}

				if (!$cb($scope->getType($arg->value))) {
					return false;
				}
			}

			return true;
		}

		if ($functionReflection->getName() === 'vsprintf' && count($args) >= 2) {
			return $cb($scope->getType($args[1]->value)->getIterableValueType());
		}

		return false;
	}

	/**
	 * @param Arg[] $args
	 */
	private function getValueType(FunctionReflection $functionReflection, Scope $scope, array $args, int $argNumber): ?Type
	{
		if ($functionReflection->getName() === 'sprintf') {
			// constant string specifies a numbered argument that does not exist
			if (!array_key_exists($argNumber, $args)) {
				return null;
			}

			return $scope->getType($args[$argNumber]->value);
		}

		if ($functionReflection->getName() === 'vsprintf') {
			if (!array_key_exists(1, $args)) {
				return null;
			}

			$valuesType = $scope->getType($args[1]->value);
			$resultTypes = [];

			$valuesConstantArrays = $valuesType->getConstantArrays();
			foreach ($valuesConstantArrays as $valuesConstantArray) {
				// vsprintf does not care about the keys of the array, only the order
				$types = array_values($valuesConstantArray->getValueTypes());
				if (!array_key_exists($argNumber - 1, $types)) {
					return null;
				}

				$resultTypes[] = $types[$argNumber - 1];
			}
			if (count($resultTypes) === 0) {
				return $valuesType->getIterableValueType();
			}

			return TypeCombinator::union(...$resultTypes);
		}

		return null;
	}

	/**
	 * Detect constant strings in the format which neither depend on placeholders nor on given value arguments.
	 */
	private function getFormatConstantParts(
		string $format,
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?ConstantStringType
	{
		$args = $functionCall->getArgs();
		if ($functionReflection->getName() === 'sprintf') {
			$valuesCount = count($args) - 1;
		} elseif (
			$functionReflection->getName() === 'vsprintf'
			&& count($args) >= 2
		) {
			$arraySize = $scope->getType($args[1]->value)->getArraySize();
			if (!($arraySize instanceof ConstantIntegerType)) {
				return null;
			}

			$valuesCount = $arraySize->getValue();
		} else {
			return null;
		}

		if ($valuesCount <= 0) {
			return null;
		}
		$dummyValues = array_fill(0, $valuesCount, '');

		try {
			$formatted = @vsprintf($format, $dummyValues);
			if ($formatted === false) { // @phpstan-ignore identical.alwaysFalse (PHP7.2 compat)
				return null;
			}
			return new ConstantStringType($formatted);
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * @param Arg[] $args
	 */
	private function getConstantType(array $args, FunctionReflection $functionReflection, Scope $scope): ?Type
	{
		$values = [];
		$combinationsCount = 1;
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return null;
			}

			$argType = $scope->getType($arg->value);
			$constantScalarValues = $argType->getConstantScalarValues();

			if (count($constantScalarValues) === 0) {
				if ($argType instanceof IntegerRangeType) {
					foreach ($argType->getFiniteTypes() as $finiteType) {
						$constantScalarValues[] = $finiteType->getValue();
					}
				}
			}

			if (count($constantScalarValues) === 0) {
				return null;
			}

			$values[] = $constantScalarValues;
			$combinationsCount *= count($constantScalarValues);
		}

		if ($combinationsCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		$combinations = CombinationsHelper::combinations($values);
		$returnTypes = [];
		foreach ($combinations as $combination) {
			$format = array_shift($combination);
			if (!is_string($format)) {
				return null;
			}

			try {
				if ($functionReflection->getName() === 'sprintf') {
					$returnTypes[] = $scope->getTypeFromValue(@sprintf($format, ...$combination));
				} else {
					$returnTypes[] = $scope->getTypeFromValue(@vsprintf($format, $combination));
				}
			} catch (Throwable) {
				return null;
			}
		}

		if (count($returnTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		return TypeCombinator::union(...$returnTypes);
	}

	private function getStringReturnType(?AccessoryType $accessoryType, bool $isLowercase): Type
	{
		$accessoryTypes = [];
		if ($accessoryType !== null) {
			$accessoryTypes[] = $accessoryType;
		}
		if ($isLowercase) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}

		if (count($accessoryTypes) === 0) {
			return new StringType();
		}

		$accessoryTypes[] = new StringType();

		return new IntersectionType($accessoryTypes);
	}

}
