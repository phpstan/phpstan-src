<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use InvalidArgumentException;
use Override;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\Attributes\DataProvider;
use function sprintf;

class GmpOperatorTypeSpecifyingExtensionTest extends PHPStanTestCase
{

	private GmpOperatorTypeSpecifyingExtension $extension;

	#[Override]
	protected function setUp(): void
	{
		$this->extension = new GmpOperatorTypeSpecifyingExtension();
	}

	#[DataProvider('dataSupportedOperations')]
	public function testSupportsValidGmpOperations(string $sigil, string $leftType, string $rightType): void
	{
		$left = $this->createType($leftType);
		$right = $this->createType($rightType);

		self::assertTrue($this->extension->isOperatorSupported($sigil, $left, $right));
	}

	public static function dataSupportedOperations(): iterable
	{
		// GMP + GMP
		yield 'GMP + GMP' => ['+', 'GMP', 'GMP'];
		yield 'GMP - GMP' => ['-', 'GMP', 'GMP'];
		yield 'GMP * GMP' => ['*', 'GMP', 'GMP'];

		// GMP + int (activates, specifyType handles compatibility)
		yield 'GMP + int' => ['+', 'GMP', 'int'];
		yield 'int + GMP' => ['+', 'int', 'GMP'];

		// GMP + incompatible (activates, specifyType returns ErrorType)
		yield 'GMP + stdClass' => ['+', 'GMP', 'stdClass'];
		yield 'stdClass + GMP' => ['+', 'stdClass', 'GMP'];

		// Comparison
		yield 'GMP < GMP' => ['<', 'GMP', 'GMP'];
		yield 'GMP <=> int' => ['<=>', 'GMP', 'int'];
	}

	#[DataProvider('dataUnsupportedOperations')]
	public function testDoesNotSupportInvalidOperations(string $sigil, string $leftType, string $rightType): void
	{
		$left = $this->createType($leftType);
		$right = $this->createType($rightType);

		self::assertFalse($this->extension->isOperatorSupported($sigil, $left, $right));
	}

	public static function dataUnsupportedOperations(): iterable
	{
		// Neither side is GMP
		yield 'int + int' => ['+', 'int', 'int'];

		// object is a supertype of GMP, but is not GMP itself
		// This catches mutations that swap isSuperTypeOf callee/argument
		yield 'object + int' => ['+', 'object', 'int'];
		yield 'int + object' => ['+', 'int', 'object'];

		// GMP|int union should not be treated as definitely GMP
		// This catches mutations that change .yes() to !.no()
		yield 'GMP|int + int' => ['+', 'GMP|int', 'int'];
		yield 'int + GMP|int' => ['+', 'int', 'GMP|int'];
	}

	#[DataProvider('dataSpecifyTypeReturnsError')]
	public function testSpecifyTypeReturnsErrorForIncompatibleTypes(string $sigil, string $leftType, string $rightType): void
	{
		$left = $this->createType($leftType);
		$right = $this->createType($rightType);

		self::assertInstanceOf(ErrorType::class, $this->extension->specifyType($sigil, $left, $right));
	}

	public static function dataSpecifyTypeReturnsError(): iterable
	{
		yield 'GMP + stdClass' => ['+', 'GMP', 'stdClass'];
		yield 'stdClass + GMP' => ['+', 'stdClass', 'GMP'];
		yield 'GMP + float' => ['+', 'GMP', 'float'];

		// object is a supertype of GMP - these catch line 37 IsSuperTypeOfCalleeAndArgumentMutator
		// When mutation swaps callee/argument, $otherSide incorrectly becomes GMP instead of object
		yield 'object + GMP' => ['+', 'object', 'GMP'];
		yield 'GMP + object' => ['+', 'GMP', 'object'];

		// GMP|int is Maybe-GMP - catches line 37 TrinaryLogicMutator
		// When mutation changes .yes() to !.no(), $otherSide incorrectly becomes int instead of GMP|int
		// Note: int + GMP|int returns GMP (other=int which is valid), only GMP|int + int returns error
		yield 'GMP|int + int (specifyType)' => ['+', 'GMP|int', 'int'];

		// int|stdClass has isInteger()=Maybe - catches line 52 TrinaryLogicMutator
		// When mutation changes .yes() to !.no(), isInteger() incorrectly returns true
		yield 'GMP + int|stdClass' => ['+', 'GMP', 'int|stdClass'];
		yield 'int|stdClass + GMP' => ['+', 'int|stdClass', 'GMP'];

		// string has isNumericString()=Maybe - catches line 53 TrinaryLogicMutator
		// When mutation changes .yes() to !.no(), isNumericString() incorrectly returns true
		yield 'GMP + string' => ['+', 'GMP', 'string'];
		yield 'string + GMP' => ['+', 'string', 'GMP'];
	}

	#[DataProvider('dataSpecifyTypeReturnsGmp')]
	public function testSpecifyTypeReturnsGmpForCompatibleTypes(string $sigil, string $leftType, string $rightType): void
	{
		$left = $this->createType($leftType);
		$right = $this->createType($rightType);

		$result = $this->extension->specifyType($sigil, $left, $right);
		self::assertInstanceOf(ObjectType::class, $result);
		self::assertSame('GMP', $result->getClassName());
	}

	public static function dataSpecifyTypeReturnsGmp(): iterable
	{
		yield 'GMP + GMP' => ['+', 'GMP', 'GMP'];
		yield 'GMP + int' => ['+', 'GMP', 'int'];
		yield 'int + GMP' => ['+', 'int', 'GMP'];

		// When left is int and right is GMP|int, other=int which is valid
		yield 'int + GMP|int' => ['+', 'int', 'GMP|int'];
	}

	private function createType(string $type): Type
	{
		switch ($type) {
			case 'GMP':
				return new ObjectType('GMP');
			case 'int':
				return new IntegerType();
			case 'float':
				return new FloatType();
			case 'object':
				return new ObjectWithoutClassType();
			case 'stdClass':
				return new ObjectType('stdClass');
			case 'GMP|int':
				return new UnionType([new ObjectType('GMP'), new IntegerType()]);
			case 'int|stdClass':
				return new UnionType([new IntegerType(), new ObjectType('stdClass')]);
			case 'string':
				return new StringType();
			default:
				throw new InvalidArgumentException(sprintf('Unknown type: %s', $type));
		}
	}

}
