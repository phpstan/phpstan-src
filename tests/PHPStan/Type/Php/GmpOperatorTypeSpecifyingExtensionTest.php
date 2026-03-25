<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use InvalidArgumentException;
use Override;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
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
				return new ObjectType('object');
			case 'stdClass':
				return new ObjectType('stdClass');
			case 'GMP|int':
				return new UnionType([new ObjectType('GMP'), new IntegerType()]);
			default:
				throw new InvalidArgumentException(sprintf('Unknown type: %s', $type));
		}
	}

}
