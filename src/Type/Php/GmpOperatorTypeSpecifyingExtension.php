<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\BooleanType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\Type;
use function in_array;

#[AutowiredService]
final class GmpOperatorTypeSpecifyingExtension implements OperatorTypeSpecifyingExtension
{

	public function isOperatorSupported(string $operatorSigil, Type $leftSide, Type $rightSide): bool
	{
		if ($leftSide instanceof NeverType || $rightSide instanceof NeverType) {
			return false;
		}

		if (!in_array($operatorSigil, ['+', '-', '*', '/', '**', '%', '&', '|', '^', '<<', '>>', '<', '<=', '>', '>=', '==', '!=', '<=>'], true)) {
			return false;
		}

		$gmpType = new ObjectType('GMP');
		$leftIsGmp = $gmpType->isSuperTypeOf($leftSide)->yes();
		$rightIsGmp = $gmpType->isSuperTypeOf($rightSide)->yes();

		// At least one side must be GMP
		if (!$leftIsGmp && !$rightIsGmp) {
			return false;
		}

		// The other side must be GMP-compatible (GMP, int, or numeric-string)
		// GMP operations with incompatible types (like stdClass) will error at runtime
		return $this->isGmpCompatible($leftSide, $gmpType) && $this->isGmpCompatible($rightSide, $gmpType);
	}

	private function isGmpCompatible(Type $type, ObjectType $gmpType): bool
	{
		if ($gmpType->isSuperTypeOf($type)->yes()) {
			return true;
		}
		if ($type->isInteger()->yes()) {
			return true;
		}
		if ($type->isNumericString()->yes()) {
			return true;
		}
		return false;
	}

	public function specifyType(string $operatorSigil, Type $leftSide, Type $rightSide): Type
	{
		$gmpType = new ObjectType('GMP');

		// Comparison operators return bool or int (for spaceship)
		if (in_array($operatorSigil, ['<', '<=', '>', '>=', '==', '!='], true)) {
			return new BooleanType();
		}

		if ($operatorSigil === '<=>') {
			return IntegerRangeType::fromInterval(-1, 1);
		}

		// All arithmetic and bitwise operations on GMP return GMP
		// GMP can operate with: GMP, int, or numeric-string
		return $gmpType;
	}

}
