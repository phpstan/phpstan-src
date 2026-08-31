<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPUnit\Framework\Attributes\DataProvider;

class LateResolvableArrayShapeTypeTest extends PHPStanTestCase
{

	/**
	 * @param array{Type|null, Type}|null $unsealed
	 */
	private static function createShape(?array $unsealed): Type
	{
		return LateResolvableArrayShapeType::create(
			[[self::templateType(), new IntegerType(), false]],
			$unsealed,
			ArrayShapeNode::KIND_ARRAY,
		);
	}

	private static function templateType(Type $bound = new StringType()): Type
	{
		return TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('doFoo'),
			'TKey',
			$bound,
			TemplateTypeVariance::createInvariant(),
		);
	}

	/**
	 * @return iterable<string, array{Type, string}>
	 */
	public static function dataDescribe(): iterable
	{
		$templateType = self::templateType();

		yield 'sealed' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{TKey: int}',
		];

		yield 'optional key' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), true]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{TKey?: int}',
		];

		yield 'next to a constant key' => [
			LateResolvableArrayShapeType::create(
				[
					[new ConstantStringType('a'), new IntegerType(), false],
					[$templateType, new BooleanType(), false],
				],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			"array{'a': int, TKey: bool}",
		];

		yield 'auto index' => [
			LateResolvableArrayShapeType::create(
				[
					[null, new IntegerType(), false],
					[$templateType, new BooleanType(), false],
				],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{int, TKey: bool}',
		];

		yield 'non-empty-array kind' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_NON_EMPTY_ARRAY,
			),
			'non-empty-array{TKey: int}',
		];

		yield 'unsealed without a written down type' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				[null, new MixedType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{TKey: int, ...}',
		];

		yield 'unsealed with a value type only' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				[null, new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{TKey: int, ...<bool>}',
		];

		yield 'unsealed with a key and a value type' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				[new StringType(), new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			'array{TKey: int, ...<string, bool>}',
		];

		yield 'unsealed key is the template type' => [
			LateResolvableArrayShapeType::create(
				[[new ConstantStringType('a'), new IntegerType(), false]],
				[$templateType, new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			"array{'a': int, ...<TKey, bool>}",
		];
	}

	#[DataProvider('dataDescribe')]
	public function testDescribe(Type $type, string $expectedDescription): void
	{
		$this->assertInstanceOf(LateResolvableArrayShapeType::class, $type);
		$this->assertFalse($type->isResolvable());
		$this->assertSame($expectedDescription, $type->describe(VerbosityLevel::precise()));
		$this->assertSame($expectedDescription, (new Printer())->print($type->toPhpDocNode()));
	}

	/**
	 * @return iterable<string, array{Type, Type, string}>
	 */
	public static function dataResolve(): iterable
	{
		$templateType = self::templateType();

		yield 'constant key' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			new ConstantStringType('a'),
			'array{a: int}',
		];

		yield 'numeric string key is cast to an integer' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			new ConstantStringType('5'),
			'array{5: int}',
		];

		yield 'non-constant key degrades the shape' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			new StringType(),
			'non-empty-array<string, int>',
		];

		yield 'unsealed key' => [
			LateResolvableArrayShapeType::create(
				[[new ConstantStringType('a'), new IntegerType(), false]],
				[$templateType, new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			new StringType(),
			'array{a: int, ...<string, bool>}',
		];

		yield 'unsealed key resolved to a constant not in the shape' => [
			LateResolvableArrayShapeType::create(
				[[new ConstantStringType('a'), new IntegerType(), false]],
				[$templateType, new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			new ConstantStringType('b'),
			'array{a: int, b?: bool}',
		];

		yield 'unsealed key resolved to an explicit key of the shape' => [
			LateResolvableArrayShapeType::create(
				[[new ConstantStringType('a'), new IntegerType(), false]],
				[$templateType, new BooleanType()],
				ArrayShapeNode::KIND_ARRAY,
			),
			new ConstantStringType('a'),
			'array{a: int}',
		];

		yield 'key that cannot be an array key at all' => [
			LateResolvableArrayShapeType::create(
				[[$templateType, new IntegerType(), false]],
				null,
				ArrayShapeNode::KIND_ARRAY,
			),
			new ObjectType('stdClass'),
			'*ERROR*',
		];
	}

	#[DataProvider('dataResolve')]
	public function testResolve(Type $type, Type $resolvedTemplateType, string $expectedDescription): void
	{
		$resolved = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($resolvedTemplateType): Type {
			if ($type instanceof Generic\TemplateType) {
				return $resolvedTemplateType;
			}

			return $traverse($type);
		});

		$this->assertNotInstanceOf(LateResolvableArrayShapeType::class, $resolved);
		$this->assertSame($expectedDescription, $resolved->describe(VerbosityLevel::precise()));
	}

	public function testResolvesToTemplateTypeBoundsWhenTheTemplateTypeStays(): void
	{
		$type = LateResolvableArrayShapeType::create(
			[[self::templateType(), new IntegerType(), false]],
			[self::templateType(), new BooleanType()],
			ArrayShapeNode::KIND_ARRAY,
		);
		$this->assertInstanceOf(LateResolvableArrayShapeType::class, $type);
		$this->assertFalse($type->isResolvable());

		// the shape is asked about itself before the template types get
		// substituted - it degrades to the bounds instead of leaking template
		// types into the resulting array type
		$this->assertSame('non-empty-array<string, bool|int>', $type->resolve()->describe(VerbosityLevel::precise()));
	}

	public function testTraverseSimultaneouslyVisitsKeysAndValues(): void
	{
		$left = LateResolvableArrayShapeType::create(
			[[self::templateType(), new IntegerType(), false]],
			[self::templateType(), new BooleanType()],
			ArrayShapeNode::KIND_ARRAY,
		);
		$right = LateResolvableArrayShapeType::create(
			[[self::templateType(new IntegerType()), new StringType(), false]],
			[self::templateType(new IntegerType()), new FloatType()],
			ArrayShapeNode::KIND_ARRAY,
		);

		$visited = [];
		$left->traverseSimultaneously($right, static function (Type $left, Type $right) use (&$visited): Type {
			$visited[] = [
				$left->describe(VerbosityLevel::typeOnly()),
				$right->describe(VerbosityLevel::typeOnly()),
			];

			return $left;
		});

		$this->assertSame(
			[
				['TKey of string', 'TKey of int'],
				['int', 'string'],
				['TKey of string', 'TKey of int'],
				['bool', 'float'],
			],
			$visited,
		);
	}

	public function testEquals(): void
	{
		$sealed = self::createShape(null);
		$unsealedDefaultKey = self::createShape([null, new BooleanType()]);
		$unsealedExplicitKey = self::createShape([new StringType(), new BooleanType()]);

		$this->assertTrue($sealed->equals(self::createShape(null)));
		$this->assertFalse($sealed->equals($unsealedDefaultKey));
		$this->assertFalse($unsealedDefaultKey->equals($unsealedExplicitKey));
		$this->assertTrue($unsealedExplicitKey->equals(self::createShape([new StringType(), new BooleanType()])));
		$this->assertFalse($unsealedExplicitKey->equals(self::createShape([new IntegerType(), new BooleanType()])));
	}

}
