<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Bug9499\FooEnum;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Printer\Printer;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function implode;
use function sprintf;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class TypeSpecifierTest extends PHPStanTestCase
{

	private const FALSEY_TYPE_DESCRIPTION = '0|0.0|\'\'|\'0\'|array{}|false|null';
	private const TRUTHY_TYPE_DESCRIPTION = 'mixed~' . self::FALSEY_TYPE_DESCRIPTION;
	private const SURE_NOT_FALSEY = '~' . self::FALSEY_TYPE_DESCRIPTION;
	private const SURE_NOT_TRUTHY = '~' . self::TRUTHY_TYPE_DESCRIPTION;

	/** @var Standard () */
	private Standard $printer;

	private TypeSpecifier $typeSpecifier;

	private Scope $scope;

	protected function setUp(): void
	{
		$reflectionProvider = $this->createReflectionProvider();
		$this->printer = new Printer();
		$this->typeSpecifier = self::getContainer()->getService('typeSpecifier');
		$this->scope = $this->createScopeFactory($reflectionProvider, $this->typeSpecifier)->create(ScopeContext::create(''));
		$this->scope = $this->scope->enterClass($reflectionProvider->getClass('DateTime'));
		$this->scope = $this->scope->assignVariable('bar', new ObjectType('Bar'), new ObjectType('Bar'));
		$this->scope = $this->scope->assignVariable('stringOrNull', new UnionType([new StringType(), new NullType()]), new UnionType([new StringType(), new NullType()]));
		$this->scope = $this->scope->assignVariable('string', new StringType(), new StringType());
		$this->scope = $this->scope->assignVariable('barOrNull', new UnionType([new ObjectType('Bar'), new NullType()]), new UnionType([new ObjectType('Bar'), new NullType()]));
		$this->scope = $this->scope->assignVariable('barOrFalse', new UnionType([new ObjectType('Bar'), new ConstantBooleanType(false)]), new UnionType([new ObjectType('Bar'), new ConstantBooleanType(false)]));
		$this->scope = $this->scope->assignVariable('stringOrFalse', new UnionType([new StringType(), new ConstantBooleanType(false)]), new UnionType([new StringType(), new ConstantBooleanType(false)]));
		$this->scope = $this->scope->assignVariable('array', new ArrayType(new MixedType(), new MixedType()), new ArrayType(new MixedType(), new MixedType()));
		$this->scope = $this->scope->assignVariable('foo', new MixedType(), new MixedType());
		$this->scope = $this->scope->assignVariable('classString', new ClassStringType(), new ClassStringType());
		$this->scope = $this->scope->assignVariable('genericClassString', new GenericClassStringType(new ObjectType('Bar')), new GenericClassStringType(new ObjectType('Bar')));
		$this->scope = $this->scope->assignVariable('object', new ObjectWithoutClassType(), new ObjectWithoutClassType());
		$this->scope = $this->scope->assignVariable('int', new IntegerType(), new IntegerType());
		$this->scope = $this->scope->assignVariable('float', new FloatType(), new FloatType());
	}

	/**
	 * @dataProvider dataCondition
	 * @param mixed[] $expectedPositiveResult
	 * @param mixed[] $expectedNegatedResult
	 */
	public function testCondition(Expr $expr, array $expectedPositiveResult, array $expectedNegatedResult): void
	{
		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this->scope, $expr, TypeSpecifierContext::createTruthy());
		$actualResult = $this->toReadableResult($specifiedTypes);
		$this->assertSame($expectedPositiveResult, $actualResult, sprintf('if (%s)', $this->printer->prettyPrintExpr($expr)));

		$specifiedTypes = $this->typeSpecifier->specifyTypesInCondition($this->scope, $expr, TypeSpecifierContext::createFalsey());
		$actualResult = $this->toReadableResult($specifiedTypes);
		$this->assertSame($expectedNegatedResult, $actualResult, sprintf('if not (%s)', $this->printer->prettyPrintExpr($expr)));
	}

	public function dataCondition(): array
	{
		return [
			[
				$this->createFunctionCall('is_int'),
				['$foo' => 'int'],
				['$foo' => '~int'],
			],
			[
				$this->createFunctionCall('is_numeric'),
				['$foo' => 'float|int|numeric-string'],
				['$foo' => '~float|int|numeric-string'],
			],
			[
				$this->createFunctionCall('is_scalar'),
				['$foo' => 'bool|float|int|string'],
				['$foo' => '~bool|float|int|string'],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_int'),
					$this->createFunctionCall('random'),
				),
				['$foo' => 'int'],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					$this->createFunctionCall('is_int'),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => '~int'],
			],
			[
				new Expr\BinaryOp\LogicalAnd(
					$this->createFunctionCall('is_int'),
					$this->createFunctionCall('random'),
				),
				['$foo' => 'int'],
				[],
			],
			[
				new Expr\BinaryOp\LogicalOr(
					$this->createFunctionCall('is_int'),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => '~int'],
			],
			[
				new Expr\BooleanNot($this->createFunctionCall('is_int')),
				['$foo' => '~int'],
				['$foo' => 'int'],
			],

			[
				new Expr\BinaryOp\BooleanAnd(
					new Expr\BooleanNot($this->createFunctionCall('is_int')),
					$this->createFunctionCall('random'),
				),
				['$foo' => '~int'],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BooleanNot($this->createFunctionCall('is_int')),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => 'int'],
			],
			[
				new Expr\BooleanNot(new Expr\BooleanNot($this->createFunctionCall('is_int'))),
				['$foo' => 'int'],
				['$foo' => '~int'],
			],
			[
				$this->createInstanceOf('Foo'),
				['$foo' => 'Foo'],
				['$foo' => '~Foo'],
			],
			[
				new Expr\BooleanNot($this->createInstanceOf('Foo')),
				['$foo' => '~Foo'],
				['$foo' => 'Foo'],
			],
			[
				new Expr\Instanceof_(
					new Variable('foo'),
					new Variable('className'),
				),
				['$foo' => 'object'],
				[],
			],
			[
				new Equal(
					new FuncCall(new Name('get_class'), [
						new Arg(new Variable('foo')),
					]),
					new String_('Foo'),
				),
				['$foo' => 'Foo', 'get_class($foo)' => '\'Foo\''],
				['get_class($foo)' => '~\'Foo\''],
			],
			[
				new Equal(
					new String_('Foo'),
					new FuncCall(new Name('get_class'), [
						new Arg(new Variable('foo')),
					]),
				),
				['$foo' => 'Foo', 'get_class($foo)' => '\'Foo\''],
				['get_class($foo)' => '~\'Foo\''],
			],
			[
				new BooleanNot(
					new Expr\Instanceof_(
						new Variable('foo'),
						new Variable('className'),
					),
				),
				[],
				['$foo' => 'object'],
			],
			[
				new Variable('foo'),
				['$foo' => self::SURE_NOT_FALSEY],
				['$foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new Variable('foo'),
					$this->createFunctionCall('random'),
				),
				['$foo' => self::SURE_NOT_FALSEY],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Variable('foo'),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\BooleanNot(new Variable('bar')),
				['$bar' => self::SURE_NOT_TRUTHY],
				['$bar' => self::SURE_NOT_FALSEY],
			],

			[
				new PropertyFetch(new Variable('this'), 'foo'),
				['$this->foo' => self::SURE_NOT_FALSEY],
				['$this->foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new PropertyFetch(new Variable('this'), 'foo'),
					$this->createFunctionCall('random'),
				),
				['$this->foo' => self::SURE_NOT_FALSEY],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new PropertyFetch(new Variable('this'), 'foo'),
					$this->createFunctionCall('random'),
				),
				[],
				['$this->foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\BooleanNot(new PropertyFetch(new Variable('this'), 'foo')),
				['$this->foo' => self::SURE_NOT_TRUTHY],
				['$this->foo' => self::SURE_NOT_FALSEY],
			],

			[
				new Expr\BinaryOp\BooleanOr(
					$this->createFunctionCall('is_int'),
					$this->createFunctionCall('is_string'),
				),
				['$foo' => 'int|string'],
				['$foo' => '~int|string'],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					$this->createFunctionCall('is_int'),
					new Expr\BinaryOp\BooleanOr(
						$this->createFunctionCall('is_string'),
						$this->createFunctionCall('is_bool'),
					),
				),
				['$foo' => 'bool|int|string'],
				['$foo' => '~bool|int|string'],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					$this->createFunctionCall('is_int', 'foo'),
					$this->createFunctionCall('is_string', 'bar'),
				),
				[],
				['$foo' => '~int', '$bar' => '~string'],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new Expr\BinaryOp\BooleanOr(
						$this->createFunctionCall('is_int', 'foo'),
						$this->createFunctionCall('is_string', 'foo'),
					),
					$this->createFunctionCall('random'),
				),
				['$foo' => 'int|string'],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						$this->createFunctionCall('is_int', 'foo'),
						$this->createFunctionCall('is_string', 'foo'),
					),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => 'mixed'],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						$this->createFunctionCall('is_int', 'foo'),
						$this->createFunctionCall('is_string', 'bar'),
					),
					$this->createFunctionCall('random'),
				),
				[],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						new Expr\BooleanNot($this->createFunctionCall('is_int', 'foo')),
						new Expr\BooleanNot($this->createFunctionCall('is_string', 'foo')),
					),
					$this->createFunctionCall('random'),
				),
				[],
				['$foo' => 'int|string'],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new Expr\BinaryOp\BooleanOr(
						new Expr\BooleanNot($this->createFunctionCall('is_int', 'foo')),
						new Expr\BooleanNot($this->createFunctionCall('is_string', 'foo')),
					),
					$this->createFunctionCall('random'),
				),
				['$foo' => 'mixed'],
				[],
			],

			[
				new Identical(
					new Variable('foo'),
					new Expr\ConstFetch(new Name('true')),
				),
				['$foo' => 'true & ~' . self::FALSEY_TYPE_DESCRIPTION],
				['$foo' => '~true'],
			],
			[
				new Identical(
					new Variable('foo'),
					new Expr\ConstFetch(new Name('false')),
				),
				['$foo' => 'false & ~' . self::TRUTHY_TYPE_DESCRIPTION],
				['$foo' => '~false'],
			],
			[
				new Identical(
					$this->createFunctionCall('is_int'),
					new Expr\ConstFetch(new Name('true')),
				),
				['is_int($foo)' => 'true', '$foo' => 'int'],
				['is_int($foo)' => '~true', '$foo' => '~int'],
			],
			[
				new Identical(
					$this->createFunctionCall('is_string'),
					new Expr\ConstFetch(new Name('true')),
				),
				['is_string($foo)' => 'true', '$foo' => 'string'],
				['is_string($foo)' => '~true', '$foo' => '~string'],
			],
			[
				new Identical(
					$this->createFunctionCall('is_int'),
					new Expr\ConstFetch(new Name('false')),
				),
				['is_int($foo)' => 'false', '$foo' => '~int'],
				['$foo' => 'int', 'is_int($foo)' => '~false'],
			],
			[
				new Equal(
					$this->createFunctionCall('is_int'),
					new Expr\ConstFetch(new Name('true')),
				),
				['$foo' => 'int'],
				['$foo' => '~int'],
			],
			[
				new Equal(
					$this->createFunctionCall('is_int'),
					new Expr\ConstFetch(new Name('false')),
				),
				['$foo' => '~int'],
				['$foo' => 'int'],
			],
			[
				new Equal(
					new Variable('foo'),
					new Expr\ConstFetch(new Name('false')),
				),
				['$foo' => self::SURE_NOT_TRUTHY],
				['$foo' => self::SURE_NOT_FALSEY],
			],
			[
				new Equal(
					new Variable('foo'),
					new Expr\ConstFetch(new Name('null')),
				),
				['$foo' => self::SURE_NOT_TRUTHY],
				['$foo' => self::SURE_NOT_FALSEY],
			],
			[
				new Expr\BinaryOp\Identical(
					new Variable('foo'),
					new Variable('bar'),
				),
				['$foo' => 'Bar', '$bar' => 'mixed'], // could be '$bar' => 'Bar'
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new String_('Foo')),
				]),
				['$foo' => 'Foo'],
				['$foo' => '~Foo'],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Variable('className')),
				]),
				['$foo' => 'object'],
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Expr\ClassConstFetch(
						new Name('static'),
						'class',
					)),
				]),
				['$foo' => 'static(DateTime)'],
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Variable('classString')),
				]),
				['$foo' => 'object'],
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Variable('genericClassString')),
				]),
				['$foo' => 'Bar'],
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new String_('Foo')),
					new Arg(new Expr\ConstFetch(new Name('true'))),
				]),
				['$foo' => 'class-string<Foo>|Foo'],
				['$foo' => '~class-string<Foo>|Foo'],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Variable('className')),
					new Arg(new Expr\ConstFetch(new Name('true'))),
				]),
				['$foo' => 'class-string|object'],
				[],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new String_('Foo')),
					new Arg(new Variable('unknown')),
				]),
				['$foo' => 'class-string<Foo>|Foo'],
				['$foo' => '~class-string<Foo>|Foo'],
			],
			[
				new FuncCall(new Name('is_a'), [
					new Arg(new Variable('foo')),
					new Arg(new Variable('className')),
					new Arg(new Variable('unknown')),
				]),
				['$foo' => 'class-string|object'],
				[],
			],
			[
				new Expr\Assign(
					new Variable('foo'),
					new Variable('stringOrNull'),
				),
				['$foo' => self::SURE_NOT_FALSEY],
				['$foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\Assign(
					new Variable('foo'),
					new Variable('stringOrFalse'),
				),
				['$foo' => self::SURE_NOT_FALSEY],
				['$foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\Assign(
					new Variable('foo'),
					new Variable('bar'),
				),
				['$foo' => self::SURE_NOT_FALSEY],
				['$foo' => self::SURE_NOT_TRUTHY],
			],
			[
				new Expr\Isset_([
					new Variable('stringOrNull'),
					new Variable('barOrNull'),
				]),
				[
					'$stringOrNull' => '~null',
					'$barOrNull' => '~null',
				],
				[
					'$stringOrNull' => self::SURE_NOT_TRUTHY,
					'$barOrNull' => self::SURE_NOT_TRUTHY,
				],
			],
			[
				new Expr\BooleanNot(new Expr\Empty_(new Variable('stringOrNull'))),
				[
					'$stringOrNull' => '~0|0.0|\'\'|\'0\'|array{}|false|null',
				],
				[
					'$stringOrNull' => '\'\'|\'0\'|null',
				],
			],
			[
				new Expr\BinaryOp\Identical(
					new Variable('foo'),
					new LNumber(123),
				),
				[
					'$foo' => '123',
				],
				['$foo' => '~123'],
			],
			[
				new Expr\Empty_(new Variable('array')),
				[
					'$array' => 'array{}',
				],
				[
					'$array' => '~0|0.0|\'\'|\'0\'|array{}|false|null',
				],
			],
			[
				new BooleanNot(new Expr\Empty_(new Variable('array'))),
				[
					'$array' => '~0|0.0|\'\'|\'0\'|array{}|false|null',
				],
				[
					'$array' => 'array{}',
				],
			],
			[
				new FuncCall(new Name('count'), [
					new Arg(new Variable('array')),
				]),
				[
					'$array' => 'non-empty-array',
				],
				[
					'$array' => '~non-empty-array',
				],
			],
			[
				new BooleanNot(new FuncCall(new Name('count'), [
					new Arg(new Variable('array')),
				])),
				[
					'$array' => '~non-empty-array',
				],
				[
					'$array' => 'non-empty-array',
				],
			],
			[
				new FuncCall(new Name('sizeof'), [
					new Arg(new Variable('array')),
				]),
				[
					'$array' => 'non-empty-array',
				],
				[
					'$array' => '~non-empty-array',
				],
			],
			[
				new BooleanNot(new FuncCall(new Name('sizeof'), [
					new Arg(new Variable('array')),
				])),
				[
					'$array' => '~non-empty-array',
				],
				[
					'$array' => 'non-empty-array',
				],
			],
			[
				new Variable('foo'),
				[
					'$foo' => self::SURE_NOT_FALSEY,
				],
				[
					'$foo' => self::SURE_NOT_TRUTHY,
				],
			],
			[
				new Variable('array'),
				[
					'$array' => self::SURE_NOT_FALSEY,
				],
				[
					'$array' => self::SURE_NOT_TRUTHY,
				],
			],
			[
				new Equal(
					new Expr\Instanceof_(
						new Variable('foo'),
						new Variable('className'),
					),
					new LNumber(1),
				),
				['$foo' => 'object'],
				[],
			],
			[
				new Equal(
					new Expr\Instanceof_(
						new Variable('foo'),
						new Variable('className'),
					),
					new LNumber(0),
				),
				[],
				[
					'$foo' => 'object',
				],
			],
			[
				new Expr\Isset_(
					[
						new PropertyFetch(new Variable('foo'), new Identifier('bar')),
					],
				),
				[
					'$foo' => 'object&hasProperty(bar) & ~null',
					'$foo->bar' => '~null',
				],
				[],
			],
			[
				new Expr\Isset_(
					[
						new Expr\StaticPropertyFetch(new Name('Foo'), new VarLikeIdentifier('bar')),
					],
				),
				[
					'Foo::$bar' => '~null',
				],
				[],
			],
			[
				new Identical(
					new Variable('barOrNull'),
					new Expr\ConstFetch(new Name('null')),
				),
				[
					'$barOrNull' => 'null',
				],
				[
					'$barOrNull' => '~null',
				],
			],
			[
				new Identical(
					new Expr\Assign(
						new Variable('notNullBar'),
						new Variable('barOrNull'),
					),
					new Expr\ConstFetch(new Name('null')),
				),
				[
					'$notNullBar' => 'null',
				],
				[
					'$notNullBar' => '~null',
				],
			],
			[
				new NotIdentical(
					new Variable('barOrNull'),
					new Expr\ConstFetch(new Name('null')),
				),
				[
					'$barOrNull' => '~null',
				],
				[
					'$barOrNull' => 'null',
				],
			],
			[
				new Expr\BinaryOp\Smaller(
					new Variable('n'),
					new LNumber(3),
				),
				[
					'$n' => 'mixed~int<3, max>|true',
				],
				[
					'$n' => 'mixed~int<min, 2>|false|null',
				],
			],
			[
				new Expr\BinaryOp\Smaller(
					new Variable('n'),
					new LNumber(PHP_INT_MIN),
				),
				[
					'$n' => 'mixed~int<' . PHP_INT_MIN . ', max>|true',
				],
				[
					'$n' => 'mixed~false|null',
				],
			],
			[
				new Expr\BinaryOp\Greater(
					new Variable('n'),
					new LNumber(PHP_INT_MAX),
				),
				[
					'$n' => 'mixed~bool|int<min, ' . PHP_INT_MAX . '>|null',
				],
				[
					'$n' => 'mixed',
				],
			],
			[
				new Expr\BinaryOp\SmallerOrEqual(
					new Variable('n'),
					new LNumber(PHP_INT_MIN),
				),
				[
					'$n' => 'mixed~int<' . (PHP_INT_MIN + 1) . ', max>',
				],
				[
					'$n' => 'mixed~bool|int<min, ' . PHP_INT_MIN . '>|null',
				],
			],
			[
				new Expr\BinaryOp\GreaterOrEqual(
					new Variable('n'),
					new LNumber(PHP_INT_MAX),
				),
				[
					'$n' => 'mixed~int<min, ' . (PHP_INT_MAX - 1) . '>|false|null',
				],
				[
					'$n' => 'mixed~int<' . PHP_INT_MAX . ', max>|true',
				],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new Expr\BinaryOp\GreaterOrEqual(
						new Variable('n'),
						new LNumber(3),
					),
					new Expr\BinaryOp\SmallerOrEqual(
						new Variable('n'),
						new LNumber(5),
					),
				),
				[
					'$n' => 'mixed~int<min, 2>|int<6, max>|false|null',
				],
				[
					'$n' => 'mixed~int<3, 5>|true',
				],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					new Expr\Assign(
						new Variable('foo'),
						new LNumber(1),
					),
					new Expr\BinaryOp\SmallerOrEqual(
						new Variable('n'),
						new LNumber(5),
					),
				),
				[
					'$n' => 'mixed~int<6, max>',
					'$foo' => self::SURE_NOT_FALSEY,
				],
				[],
			],
			[
				new NotIdentical(
					new Expr\Assign(
						new Variable('notNullBar'),
						new Variable('barOrNull'),
					),
					new Expr\ConstFetch(new Name('null')),
				),
				[
					'$notNullBar' => '~null',
				],
				[
					'$notNullBar' => 'null',
				],
			],
			[
				new Identical(
					new Variable('barOrFalse'),
					new Expr\ConstFetch(new Name('false')),
				),
				[
					'$barOrFalse' => 'false & ' . self::SURE_NOT_TRUTHY,
				],
				[
					'$barOrFalse' => '~false',
				],
			],
			[
				new Identical(
					new Expr\Assign(
						new Variable('notFalseBar'),
						new Variable('barOrFalse'),
					),
					new Expr\ConstFetch(new Name('false')),
				),
				[
					'$notFalseBar' => 'false & ' . self::SURE_NOT_TRUTHY,
				],
				[
					'$notFalseBar' => '~false',
				],
			],
			[
				new NotIdentical(
					new Variable('barOrFalse'),
					new Expr\ConstFetch(new Name('false')),
				),
				[
					'$barOrFalse' => '~false',
				],
				[
					'$barOrFalse' => 'false & ' . self::SURE_NOT_TRUTHY,
				],
			],
			[
				new NotIdentical(
					new Expr\Assign(
						new Variable('notFalseBar'),
						new Variable('barOrFalse'),
					),
					new Expr\ConstFetch(new Name('false')),
				),
				[
					'$notFalseBar' => '~false',
				],
				[
					'$notFalseBar' => 'false & ' . self::SURE_NOT_TRUTHY,
				],
			],
			[
				new Expr\Instanceof_(
					new Expr\Assign(
						new Variable('notFalseBar'),
						new Variable('barOrFalse'),
					),
					new Name('Bar'),
				),
				[
					'$notFalseBar' => 'Bar',
				],
				[
					'$notFalseBar' => '~Bar',
				],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new FuncCall(new Name('array_key_exists'), [
						new Arg(new String_('foo')),
						new Arg(new Variable('array')),
					]),
					new FuncCall(new Name('array_key_exists'), [
						new Arg(new String_('bar')),
						new Arg(new Variable('array')),
					]),
				),
				[
					'$array' => 'array',
				],
				[
					'$array' => '~hasOffset(\'bar\')|hasOffset(\'foo\')',
				],
			],
			[
				new BooleanNot(new Expr\BinaryOp\BooleanOr(
					new FuncCall(new Name('array_key_exists'), [
						new Arg(new String_('foo')),
						new Arg(new Variable('array')),
					]),
					new FuncCall(new Name('array_key_exists'), [
						new Arg(new String_('bar')),
						new Arg(new Variable('array')),
					]),
				)),
				[
					'$array' => '~hasOffset(\'bar\')|hasOffset(\'foo\')',
				],
				[
					'$array' => 'array',
				],
			],
			[
				new FuncCall(new Name('array_key_exists'), [
					new Arg(new String_('foo')),
					new Arg(new Variable('array')),
				]),
				[
					'$array' => 'array&hasOffset(\'foo\')',
				],
				[
					'$array' => '~hasOffset(\'foo\')',
				],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new FuncCall(new Name('key_exists'), [
						new Arg(new String_('foo')),
						new Arg(new Variable('array')),
					]),
					new FuncCall(new Name('key_exists'), [
						new Arg(new String_('bar')),
						new Arg(new Variable('array')),
					]),
				),
				[
					'$array' => 'array',
				],
				[
					'$array' => '~hasOffset(\'bar\')|hasOffset(\'foo\')',
				],
			],
			[
				new BooleanNot(new Expr\BinaryOp\BooleanOr(
					new FuncCall(new Name('key_exists'), [
						new Arg(new String_('foo')),
						new Arg(new Variable('array')),
					]),
					new FuncCall(new Name('key_exists'), [
						new Arg(new String_('bar')),
						new Arg(new Variable('array')),
					]),
				)),
				[
					'$array' => '~hasOffset(\'bar\')|hasOffset(\'foo\')',
				],
				[
					'$array' => 'array',
				],
			],
			[
				new FuncCall(new Name('key_exists'), [
					new Arg(new String_('foo')),
					new Arg(new Variable('array')),
				]),
				[
					'$array' => 'array&hasOffset(\'foo\')',
				],
				[
					'$array' => '~hasOffset(\'foo\')',
				],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('string')),
					new Arg(new Variable('stringOrNull')),
				]),
				[
					'$string' => 'class-string|object',
				],
				[],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('object')),
					new Arg(new Variable('stringOrNull')),
					new Arg(new Expr\ConstFetch(new Name('false'))),
				]),
				[
					'$object' => 'object',
				],
				[],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('string')),
					new Arg(new Variable('stringOrNull')),
					new Arg(new Expr\ConstFetch(new Name('false'))),
				]),
				[
					'$string' => 'object',
				],
				[],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('string')),
					new Arg(new Variable('genericClassString')),
				]),
				[
					'$string' => 'Bar|class-string<Bar>',
				],
				[],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('object')),
					new Arg(new Variable('genericClassString')),
					new Arg(new Expr\ConstFetch(new Name('false'))),
				]),
				[
					'$object' => 'Bar',
				],
				[],
			],
			[
				new FuncCall(new Name('is_subclass_of'), [
					new Arg(new Variable('string')),
					new Arg(new Variable('genericClassString')),
					new Arg(new Expr\ConstFetch(new Name('false'))),
				]),
				[
					'$string' => 'Bar',
				],
				[],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						$this->createFunctionCall('is_string', 'a'),
						new NotIdentical(new String_(''), new Variable('a')),
					),
					new Identical(new Expr\ConstFetch(new Name('null')), new Variable('a')),
				),
				['$a' => 'non-empty-string|null'],
				['$a' => 'mixed~non-empty-string & ~null'],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						$this->createFunctionCall('is_string', 'a'),
						new Expr\BinaryOp\Greater(
							$this->createFunctionCall('strlen', 'a'),
							new LNumber(0),
						),
					),
					new Identical(new Expr\ConstFetch(new Name('null')), new Variable('a')),
				),
				['$a' => 'non-empty-string|null'],
				['$a' => 'mixed~non-empty-string & ~null'],
			],
			[
				new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						$this->createFunctionCall('is_array', 'a'),
						new Expr\BinaryOp\Greater(
							$this->createFunctionCall('count', 'a'),
							new LNumber(0),
						),
					),
					new Identical(new Expr\ConstFetch(new Name('null')), new Variable('a')),
				),
				['$a' => 'non-empty-array|null'],
				['$a' => 'mixed~non-empty-array & ~null'],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_array', 'foo'),
					new Identical(
						new FuncCall(
							new Name('array_filter'),
							[new Arg(new Variable('foo')), new Arg(new String_('is_string')), new Arg(new ConstFetch(new Name('ARRAY_FILTER_USE_KEY')))],
						),
						new Variable('foo'),
					),
				),
				[
					'$foo' => 'array<string, mixed>',
					'array_filter($foo, \'is_string\', ARRAY_FILTER_USE_KEY)' => 'array', // could be 'array<string, mixed>'
				],
				[],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_array', 'foo'),
					new Expr\BinaryOp\GreaterOrEqual(
						new FuncCall(
							new Name('count'),
							[new Arg(new Variable('foo'))],
						),
						new LNumber(2),
					),
				),
				[
					'$foo' => 'non-empty-array',
					'count($foo)' => 'mixed~int<min, 1>|false|null',
				],
				[],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_array', 'foo'),
					new Identical(
						new FuncCall(
							new Name('count'),
							[new Arg(new Variable('foo'))],
						),
						new LNumber(2),
					),
				),
				[
					'$foo' => 'non-empty-array',
					'count($foo)' => '2',
				],
				[],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_string', 'foo'),
					new NotIdentical(
						new FuncCall(
							new Name('strlen'),
							[new Arg(new Variable('foo'))],
						),
						new LNumber(0),
					),
				),
				[
					'$foo' => 'non-empty-string',
					'strlen($foo)' => '~0',
				],
				[
					'$foo' => 'mixed~non-empty-string',
				],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_numeric', 'int'),
					new Expr\BinaryOp\Equal(
						new Variable('int'),
						new Expr\Cast\Int_(new Variable('int')),
					),
				),
				[
					'$int' => 'int',
					'(int) $int' => 'int',
				],
				[],
			],
			[
				new Expr\BinaryOp\BooleanAnd(
					$this->createFunctionCall('is_numeric', 'float'),
					new Expr\BinaryOp\Equal(
						new Variable('float'),
						new Expr\Cast\Int_(new Variable('float')),
					),
				),
				[
					'$float' => 'float',
					'(int) $float' => 'int',
				],
				[],
			],
			[
				new Identical(
					new PropertyFetch(new Variable('foo'), 'bar'),
					new Expr\ClassConstFetch(new Name(FooEnum::class), 'A'),
				),
				[
					'$foo->bar' => 'Bug9499\FooEnum::A',
				],
				[
					'$foo->bar' => '~Bug9499\FooEnum::A',
				],
			],
			[
				new Identical(
					new AlwaysRememberedExpr(
						new PropertyFetch(new Variable('foo'), 'bar'),
						new ObjectType(FooEnum::class),
						new ObjectType(FooEnum::class),
					),
					new Expr\ClassConstFetch(new Name(FooEnum::class), 'A'),
				),
				[
					'__phpstanRembered($foo->bar)' => 'Bug9499\FooEnum::A',
					'$foo->bar' => 'Bug9499\FooEnum::A',
				],
				[
					'__phpstanRembered($foo->bar)' => '~Bug9499\FooEnum::A',
					'$foo->bar' => '~Bug9499\FooEnum::A',
				],
			],
		];
	}

	/**
	 * @return mixed[]
	 */
	private function toReadableResult(SpecifiedTypes $specifiedTypes): array
	{
		$typesDescription = [];

		foreach ($specifiedTypes->getSureTypes() as $exprString => [$exprNode, $exprType]) {
			$typesDescription[$exprString][] = $exprType->describe(VerbosityLevel::precise());
		}

		foreach ($specifiedTypes->getSureNotTypes() as $exprString => [$exprNode, $exprType]) {
			$typesDescription[$exprString][] = '~' . $exprType->describe(VerbosityLevel::precise());
		}

		$descriptions = [];
		foreach ($typesDescription as $exprString => $exprTypes) {
			$descriptions[$exprString] = implode(' & ', $exprTypes);
		}

		return $descriptions;
	}

	private function createInstanceOf(string $className, string $variableName = 'foo'): Expr\Instanceof_
	{
		return new Expr\Instanceof_(new Variable($variableName), new Name($className));
	}

	private function createFunctionCall(string $functionName, string $variableName = 'foo'): FuncCall
	{
		return new FuncCall(new Name($functionName), [new Arg(new Variable($variableName))]);
	}

}
