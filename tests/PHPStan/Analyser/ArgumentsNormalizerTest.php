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
use PHPUnit\Framework\Attributes\DataProvider;
use function count;

class ArgumentsNormalizerTest extends PHPStanTestCase
{

	public static function dataReorderValid(): iterable
	{
		yield [
			[
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
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
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
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
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
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
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
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
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
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
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
			],
			[],
			[],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
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
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
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
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
				['rest', true, true, new StringType()],
			],
			[
				[new StringType(), 'onee'],
			],
			[
				new IntegerType(),
				new StringType(),
				new FloatType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
				['rest', true, true, new StringType()],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'onee'],
			],
			[
				new IntegerType(),
				new StringType(),
				new FloatType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['rest', true, true, new StringType()],
			],
			[
				[new StringType(), 'rest'],
				[new StringType(), 'another'],
			],
			[
				new IntegerType(),
				new StringType(),
				new StringType(),
			],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
			],
			[],
			[],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
			],
			[
				[new StringType(), 'onee'],
			],
			[
				new StringType(),
			],
		];

		yield [
			[
				['one', true, false, new IntegerType()],
				['two', true, false, new StringType()],
				['three', true, false, new FloatType()],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'onee'],
			],
			[
				new IntegerType(),
				new StringType(),
			],
		];
	}

	/**
	 * @param array<int, array{non-empty-string, bool, bool, ?Type}> $parameterSettings
	 * @param array<int, array{Type, ?non-empty-string}> $argumentSettings
	 * @param array<int, Type> $expectedArgumentTypes
	 */
	#[DataProvider('dataReorderValid')]
	public function testReorderValid(
		array $parameterSettings,
		array $argumentSettings,
		array $expectedArgumentTypes,
	): void
	{
		$parameters = [];
		foreach ($parameterSettings as [$name, $optional, $variadic, $defaultValue]) {
			$parameters[] = new DummyParameter(
				$name,
				new MixedType(),
				$optional,
				null,
				$variadic,
				$defaultValue,
			);
		}

		$arguments = [];
		foreach ($argumentSettings as [$type, $name]) {
			$arguments[] = new Arg(new TypeExpr($type), name: $name === null ? null : new Identifier($name));
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

	public static function dataReorderInvalid(): iterable
	{
		yield [
			[
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
			],
			[
				[new StringType(), 'two'],
			],
		];

		yield [
			[
				['one', false, false, null],
				['two', false, false, null],
				['three', false, false, null],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'three'],
			],
		];

		// positional arg after named arg with variadic parameter
		yield [
			[
				['value', false, false, null],
				['values', true, true, new StringType()],
			],
			[
				[new IntegerType(), null],
				[new IntegerType(), null],
				[new IntegerType(), null],
				[new StringType(), 'd'],
				[new IntegerType(), null],
			],
		];

		// positional arg after named arg without variadic parameter
		yield [
			[
				['one', false, false, null],
			],
			[
				[new IntegerType(), null],
				[new StringType(), 'd'],
				[new IntegerType(), null],
			],
		];
	}

	/**
	 * @param array<int, array{non-empty-string, bool, bool, ?Type}> $parameterSettings
	 * @param array<int, array{Type, ?non-empty-string}> $argumentSettings
	 */
	#[DataProvider('dataReorderInvalid')]
	public function testReorderInvalid(
		array $parameterSettings,
		array $argumentSettings,
	): void
	{
		$parameters = [];
		foreach ($parameterSettings as [$name, $optional, $variadic, $defaultValue]) {
			$parameters[] = new DummyParameter(
				$name,
				new MixedType(),
				$optional,
				null,
				$variadic,
				$defaultValue,
			);
		}

		$arguments = [];
		foreach ($argumentSettings as [$type, $name]) {
			$arguments[] = new Arg(new TypeExpr($type), name: $name === null ? null : new Identifier($name));
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

	public static function dataGetArgByPositionOrName(): iterable
	{
		// no arguments
		yield [[], 0, 'array', null];

		// positional argument at the position
		yield [[null, null], 0, 'array', 0];
		yield [[null, null], 1, 'callback', 1];

		// fewer positional arguments than the position
		yield [[null], 1, 'callback', null];

		// named argument written in signature order
		yield [['array', 'callback'], 0, 'array', 0];

		// named argument written in a different order
		yield [['callback', 'array'], 0, 'array', 1];
		yield [['mode', 'callback', 'array'], 0, 'array', 2];

		// positional arguments followed by the named one
		yield [[null, 'mode', 'callback'], 1, 'callback', 2];

		// named argument at the position is bound to a different parameter
		yield [['callback'], 0, 'array', null];
		yield [[null, 'mode'], 1, 'callback', null];

		// argument names are case-sensitive
		yield [['Array'], 0, 'array', null];

		// negative position never matches a positional argument
		yield [[null, null], -1, 'array', null];
		yield [[null, 'array'], -1, 'array', 1];
	}

	/**
	 * @param list<?non-empty-string> $argumentNames
	 */
	#[DataProvider('dataGetArgByPositionOrName')]
	public function testGetArgByPositionOrName(
		array $argumentNames,
		int $position,
		string $name,
		?int $expectedIndex,
	): void
	{
		$arguments = [];
		foreach ($argumentNames as $argumentName) {
			$arguments[] = new Arg(new TypeExpr(new MixedType()), name: $argumentName === null ? null : new Identifier($argumentName));
		}

		$arg = ArgumentsNormalizer::getArgByPositionOrName($arguments, $position, $name);
		if ($expectedIndex === null) {
			$this->assertNull($arg);
			return;
		}

		$this->assertSame($arguments[$expectedIndex], $arg);
	}

}
