<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PDO;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\BinaryOp\BitwiseAnd;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function count;
use function get_defined_functions;

class ParserNodeTypeToPHPStanTypeTest extends PHPStanTestCase
{

	public function dataParameterDefaults(): array
	{
		return [
			[
				new ClassConstFetch(new FullyQualified('PDO'), 'PARAM_STR'),
				new ConstantIntegerType(PDO::PARAM_STR),
			],
			[
				new ConstFetch(new Name('true')),
				new ConstantBooleanType(true),
			],
			[
				new ConstFetch(new Name('false')),
				new ConstantBooleanType(false),
			],
			[
				new ConstFetch(new Name('null')),
				new NullType(),
			],
			[
				new String_('123'),
				new ConstantStringType('123'),
			],
			[
				new LNumber(123),
				new ConstantIntegerType(123),
			],
			[
				new DNumber(123.12),
				new ConstantFloatType(123.12),
			],
			[
				new UnaryMinus(new LNumber(123)),
				new ConstantIntegerType(-123),
			],
			[
				new UnaryMinus(new DNumber(123.12)),
				new ConstantFloatType(-123.12),
			],
			[
				new BitwiseOr(
					new ClassConstFetch(new FullyQualified('PDO'), 'PARAM_STR'),
					new ClassConstFetch(new FullyQualified('PDO'), 'PARAM_INT'),
				),
				new ConstantIntegerType(PDO::PARAM_STR | PDO::PARAM_INT),
			],
			[
				new BitwiseAnd(
					new ClassConstFetch(new FullyQualified('PDO'), 'PARAM_STR'),
					new ClassConstFetch(new FullyQualified('PDO'), 'PARAM_INT'),
				),
				new ConstantIntegerType(PDO::PARAM_STR & PDO::PARAM_INT),
			],
			[
				new Array_([
					new ArrayItem(new LNumber(3)),
					new ArrayItem(new LNumber(7)),
				]),
				new ConstantArrayType(
					[
						new ConstantIntegerType(0),
						new ConstantIntegerType(1),
					],
					[
						new ConstantIntegerType(3),
						new ConstantIntegerType(7),
					],
				),
			],
		];
	}

	/**
	 * @dataProvider dataParameterDefaults
	 */
	public function testParameterDefaultType(Expr $expr, Type $expectedType): void
	{
		$actualType = ParserNodeTypeToPHPStanType::resolveParameterDefaultType($expr);

		$this->assertSame(
			$expectedType->describe(VerbosityLevel::precise()),
			$actualType->describe(VerbosityLevel::precise()),
		);
	}

	public function testAllDefinedParameterDefaultsAreParseble(): void
	{
		$reflectionProvider = $this->createReflectionProvider();

		$allFunctions = get_defined_functions();
		foreach (['internal', 'user'] as $functionType) {
			foreach ($allFunctions[$functionType] as $functionName) {
				$function = new Name($functionName);
				if (!$reflectionProvider->hasFunction($function, null)) {
					continue;
				}

				$reflectionProvider->getFunction($function, null);
			}
		}

		// when we reach this assertion, all defined functions could be reflected without exceptions
		$this->assertTrue(count($allFunctions) > 0);
	}

}
