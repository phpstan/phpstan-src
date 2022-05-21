<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;

class ArgumentsNormalizerTest extends PHPStanTestCase
{

	public function dataReorderValid(): iterable
	{
		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new IntegerType(), null],
				[new StringType(), null],
				[new FloatType(), null],
			],
			[
				new IntegerType(),
				new StringType(),
				new FloatType(),
			],
		];

		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new IntegerType(), 'one'],
				[new StringType(), 'two'],
				[new FloatType(), 'three'],
			],
			[
				new IntegerType(),
				new StringType(),
				new FloatType(),
			],
		];

		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new StringType(), 'two'],
				[new IntegerType(), 'one'],
				[new FloatType(), 'three'],
			],
			[
				new IntegerType(),
				new StringType(),
				new FloatType(),
			],
		];

		// could be invalid
		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new StringType(), 'two'],
				[new IntegerType(), 'one'],
			],
			[
				new IntegerType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'two'],
			],
			[
				new IntegerType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', true, new IntegerType()],
				['two', true, new StringType()],
				['three', true, new FloatType()],
			],
			[],
			[],
		];

		yield [
			[
				['one', true, new IntegerType()],
				['two', true, new StringType()],
				['three', true, new FloatType()],
			],
			[
				[new StringType(), 'two'],
			],
			[
				new IntegerType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', true, new IntegerType()],
				['two', true, new StringType()],
				['three', true, new FloatType()],
			],
			[
				[new StringType(), 'one'],
			],
			[
				new StringType(),
			],
		];

		yield [
			[
				['one', true, new IntegerType()],
				['two', true, new StringType()],
				['three', true, new FloatType()],
			],
			[
				[new StringType(), 'onee'],
			],
			[],
		];

		yield [
			[
				['one', true, new IntegerType()],
				['two', true, new StringType()],
				['three', true, new FloatType()],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'onee'],
			],
			[
				new IntegerType(),
			],
		];
	}

	/**
	 * @dataProvider dataReorderValid
	 * @param array<int, array{string, bool, ?Type}> $parameterSettings
	 * @param array<int, array{Type, ?string}> $argumentSettings
	 * @param array<int, Type> $expectedArgumentTypes
	 */
	public function testReorderValid(
		array $parameterSettings,
		array $argumentSettings,
		array $expectedArgumentTypes,
	): void
	{
		$parameters = [];
		foreach ($parameterSettings as [$name, $optional, $defaultValue]) {
			$parameters[] = new DummyParameter(
				$name,
				new MixedType(),
				$optional,
				null,
				false,
				$defaultValue,
			);
		}

		$arguments = [];
		foreach ($argumentSettings as [$type, $name]) {
			$arguments[] = new Arg(new TypeExpr($type), false, false, [], $name === null ? null : new Identifier($name));
		}

		$normalized = ArgumentsNormalizer::reorderFuncArguments(
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				$parameters,
				false,
				new MixedType(),
			),
			new FuncCall(new Name('foo'), $arguments),
		);
		$this->assertNotNull($normalized);

		$actualArguments = $normalized->getArgs();
		$this->assertCount(count($expectedArgumentTypes), $actualArguments);
		foreach ($actualArguments as $i => $actualArgument) {
			$this->assertNull($actualArgument->name);
			$value = $actualArgument->value;
			$this->assertInstanceOf(TypeExpr::class, $value);
			$this->assertSame(
				$expectedArgumentTypes[$i]->describe(VerbosityLevel::precise()),
				$value->getExprType()->describe(VerbosityLevel::precise()),
			);
		}
	}

	public function dataReorderInvalid(): iterable
	{
		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new StringType(), 'two'],
			],
		];

		yield [
			[
				['one', false, null],
				['two', false, null],
				['three', false, null],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'three'],
			],
		];
	}

	/**
	 * @dataProvider dataReorderInvalid
	 * @param array<int, array{string, bool, ?Type}> $parameterSettings
	 * @param array<int, array{Type, ?string}> $argumentSettings
	 */
	public function testReorderInvalid(
		array $parameterSettings,
		array $argumentSettings,
	): void
	{
		$parameters = [];
		foreach ($parameterSettings as [$name, $optional, $defaultValue]) {
			$parameters[] = new DummyParameter(
				$name,
				new MixedType(),
				$optional,
				null,
				false,
				$defaultValue,
			);
		}

		$arguments = [];
		foreach ($argumentSettings as [$type, $name]) {
			$arguments[] = new Arg(new TypeExpr($type), false, false, [], $name === null ? null : new Identifier($name));
		}

		$normalized = ArgumentsNormalizer::reorderFuncArguments(
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				$parameters,
				false,
				new MixedType(),
			),
			new FuncCall(new Name('foo'), $arguments),
		);
		$this->assertNull($normalized);
	}

}
