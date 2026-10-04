<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DecimalIntegerStringHelper;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

#[AutowiredService]
final class NumberFormatFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'number_format';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();

		$accessoryTypes = [];
		if ($this->isNumericString($args, $scope)) {
			$accessoryTypes[] = new AccessoryNumericStringType();
		}
		if ($this->isNonDecimalIntegerString($args, $scope)) {
			$accessoryTypes[] = new AccessoryDecimalIntegerStringType(inverse: true);
		}

		if (count($accessoryTypes) === 0) {
			return new StringType();
		}

		$accessoryTypes[] = new StringType();

		return TypeCombinator::intersect(...$accessoryTypes);
	}

	/**
	 * @param array<Arg> $args
	 */
	private function isNumericString(array $args, Scope $scope): bool
	{
		if (!isset($args[3])) {
			return false;
		}

		$constantThousandsTypes = $scope->getType($args[3]->value)->getConstantStrings();
		if (count($constantThousandsTypes) !== 1 || $constantThousandsTypes[0]->getValue() !== '') {
			return false;
		}

		$constantScalarValues = $scope->getType($args[2]->value)->getConstantScalarValues();

		return count($constantScalarValues) === 1 && in_array($constantScalarValues[0], [null, '.', ''], true);
	}

	/**
	 * With at least one decimal the decimal separator always ends up between digits,
	 * so a separator that cannot occur in a decimal-int-string rules the whole result out.
	 *
	 * @param array<Arg> $args
	 */
	private function isNonDecimalIntegerString(array $args, Scope $scope): bool
	{
		if (!isset($args[1])) {
			return false;
		}

		if (!IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($scope->getType($args[1]->value))->yes()) {
			return false;
		}

		if (!isset($args[2])) {
			return true;
		}

		$decimalSeparatorType = $scope->getType($args[2]->value);
		if ($decimalSeparatorType->isNull()->yes()) {
			return true;
		}

		$constantSeparators = $decimalSeparatorType->getConstantStrings();
		if (count($constantSeparators) === 0) {
			return false;
		}

		foreach ($constantSeparators as $constantSeparator) {
			if (DecimalIntegerStringHelper::canBeInside($constantSeparator->getValue(), false)) {
				return false;
			}
		}

		return true;
	}

}
