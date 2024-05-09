<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class ScopeTest extends PHPStanTestCase
{

	public function dataGeneralize(): array
	{
		return [
			[
				new ConstantStringType('a'),
				new ConstantStringType('a'),
				'\'a\'',
			],
			[
				new ConstantStringType('a'),
				new ConstantStringType('b'),
				'literal-string&non-falsy-string',
			],
			[
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
				'int<0, max>',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				]),
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				]),
				'int<0, max>',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				]),
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				]),
				'0|1|\'foo\'',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantStringType('foo'),
				]),
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
					new ConstantStringType('foo'),
				]),
				'\'foo\'|int<0, max>',
			],
			[
				new ConstantBooleanType(false),
				new UnionType([
					new ObjectType('Foo'),
					new ConstantBooleanType(false),
				]),
				'Foo|false',
			],
			[
				new UnionType([
					new ObjectType('Foo'),
					new ConstantBooleanType(false),
				]),
				new ConstantBooleanType(false),
				'Foo|false',
			],
			[
				new ObjectType('Foo'),
				new ConstantBooleanType(false),
				'Foo|false',
			],
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
				], [
					new ConstantIntegerType(1),
				]),
				new ConstantArrayType([
					new ConstantStringType('a'),
				], [
					new ConstantIntegerType(1),
				]),
				'array{a: 1}',
			],
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantIntegerType(1),
					new ConstantIntegerType(1),
				]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantIntegerType(2),
					new ConstantIntegerType(1),
				]),
				'array{a: int<1, max>, b: 1}',
			],
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
				], [
					new ConstantIntegerType(1),
				]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantIntegerType(1),
					new ConstantIntegerType(1),
				]),
				'non-empty-array<literal-string&non-falsy-string, 1>',
			],
			[
				new ConstantArrayType([
					new ConstantStringType('a'),
				], [
					new ConstantIntegerType(1),
				]),
				new ConstantArrayType([
					new ConstantStringType('a'),
					new ConstantStringType('b'),
				], [
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				]),
				'non-empty-array<literal-string&non-falsy-string, int<1, max>>',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				]),
				new UnionType([
					new ConstantIntegerType(-1),
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
				]),
				'int<min, 1>',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(2),
				]),
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				]),
				'0|1|2',
			],
			[
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(1),
					new ConstantIntegerType(2),
				]),
				new UnionType([
					new ConstantIntegerType(0),
					new ConstantIntegerType(2),
				]),
				'0|1|2',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(1, 17),
				'int<0, max>',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(-1, 15),
				'int<min, 16>',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(1, null),
				'int<0, max>',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(null, 15),
				'int<min, 16>',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(0, null),
				'int<0, max>',
			],
			[
				IntegerRangeType::fromInterval(0, 16),
				IntegerRangeType::fromInterval(null, 16),
				'int<min, 16>',
			],
		];
	}

	/**
	 * @dataProvider dataGeneralize
	 */
	public function testGeneralize(Type $a, Type $b, string $expectedTypeDescription): void
	{
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scopeA = $scopeFactory->create(ScopeContext::create('file.php'))->assignVariable('a', $a, $a);
		$scopeB = $scopeFactory->create(ScopeContext::create('file.php'))->assignVariable('a', $b, $b);
		$resultScope = $scopeA->generalizeWith($scopeB);
		$this->assertSame($expectedTypeDescription, $resultScope->getVariableType('a')->describe(VerbosityLevel::precise()));
	}

	public function testGetConstantType(): void
	{
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create(__DIR__ . '/data/compiler-halt-offset.php'));
		$node = new ConstFetch(new FullyQualified('__COMPILER_HALT_OFFSET__'));
		$type = $scope->getType($node);
		$this->assertSame('int<1, max>', $type->describe(VerbosityLevel::precise()));
	}

}
