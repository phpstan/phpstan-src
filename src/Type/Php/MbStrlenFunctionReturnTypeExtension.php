<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function in_array;
use function max;
use function mb_internal_encoding;
use function mb_strlen;
use function min;
use function range;
use function sort;
use function sprintf;
use function var_export;

final class MbStrlenFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const UNSUPPORTED_ENCODING = 'unsupported';

	use MbFunctionsReturnTypeExtensionTrait;

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'mb_strlen';
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

		$encodings = [];

		if (count($functionCall->getArgs()) === 1) {
			// there is a chance to get an unsupported encoding 'pass' or 'none' here on PHP 7.3-7.4
			$encodings = [mb_internal_encoding()];
		} elseif (count($functionCall->getArgs()) === 2) { // custom encoding is specified
			$encodings = array_map(
				static fn (ConstantStringType $t) => $t->getValue(),
				$scope->getType($functionCall->getArgs()[1]->value)->getConstantStrings(),
			);
		}

		if (count($encodings) > 0) {
			for ($i = 0; $i < count($encodings); $i++) {
				if ($this->isSupportedEncoding($encodings[$i])) {
					continue;
				}
				$encodings[$i] = self::UNSUPPORTED_ENCODING;
			}

			$encodings = array_unique($encodings);

			if (in_array(self::UNSUPPORTED_ENCODING, $encodings, true) && count($encodings) === 1) {
				if ($this->phpVersion->throwsOnInvalidMbStringEncoding()) {
					return new NeverType();
				}
				return new ConstantBooleanType(false);
			}
		} else { // if there aren't encoding constants, use all available encodings
			$encodings = array_merge($this->getSupportedEncodings(), [self::UNSUPPORTED_ENCODING]);
		}

		$argType = $scope->getType($args[0]->value);

		if ($argType->isSuperTypeOf(new BooleanType())->yes()) {
			$constantScalars = TypeCombinator::remove($argType, new BooleanType())->getConstantScalarTypes();
			if (count($constantScalars) > 0) {
				$constantScalars[] = new ConstantBooleanType(true);
				$constantScalars[] = new ConstantBooleanType(false);
			}
		} else {
			$constantScalars = $argType->getConstantScalarTypes();
		}

		$lengths = [];
		foreach ($constantScalars as $constantScalar) {
			$stringScalar = $constantScalar->toString();
			if (!($stringScalar instanceof ConstantStringType)) {
				$lengths = [];
				break;
			}

			foreach ($encodings as $encoding) {
				if (!$this->isSupportedEncoding($encoding)) {
					continue;
				}

				$length = @mb_strlen($stringScalar->getValue(), $encoding);
				if ($length === false) {
					throw new ShouldNotHappenException(sprintf('Got false on a supported encoding %s and value %s', $encoding, var_export($stringScalar->getValue(), true)));
				}
				$lengths[] = $length;
			}
		}

		$isNonEmpty = $argType->isNonEmptyString();
		$numeric = TypeCombinator::union(new IntegerType(), new FloatType());
		if (count($lengths) > 0) {
			$lengths = array_unique($lengths);
			sort($lengths);
			if ($lengths === range(min($lengths), max($lengths))) {
				$range = IntegerRangeType::fromInterval(min($lengths), max($lengths));
			} else {
				$range = TypeCombinator::union(...array_map(static fn ($l) => new ConstantIntegerType($l), $lengths));
			}
		} elseif ($argType->isBoolean()->yes()) {
			$range = IntegerRangeType::fromInterval(0, 1);
		} elseif (
			$isNonEmpty->yes()
			|| $numeric->isSuperTypeOf($argType)->yes()
			|| TypeCombinator::remove($argType, $numeric)->isNonEmptyString()->yes()
		) {
			$range = IntegerRangeType::fromInterval(1, null);
		} elseif ($argType->isString()->yes() && $isNonEmpty->no()) {
			$range = new ConstantIntegerType(0);
		} else {
			$range = TypeCombinator::remove(
				ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$functionCall->getArgs(),
					$functionReflection->getVariants(),
				)->getReturnType(),
				new ConstantBooleanType(false),
			);
		}

		if (!$this->phpVersion->throwsOnInvalidMbStringEncoding() && in_array(self::UNSUPPORTED_ENCODING, $encodings, true)) {
			return TypeCombinator::union($range, new ConstantBooleanType(false));
		}
		return $range;
	}

}
