<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;

/**
 * @covers \PHPStan\Parser\RichParser
 * @covers \PHPStan\Parser\SimpleParser
 */
class ParserTest extends PHPStanTestCase
{

	public function dataVariadicCallLikes(): iterable
	{
		yield [
			__DIR__ . '/data/variadic-functions.php',
			VariadicFunctionsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicFunctions\variadic_fn1' => TrinaryLogic::createYes(),
				'VariadicFunctions\nonvariadic' => TrinaryLogic::createNo(),
				'VariadicFunctions\maybe_variadic_fn1' => TrinaryLogic::createNo(),
			],
		];

		yield [
			__DIR__ . '/data/variadic-methods.php',
			VariadicMethodsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicMethod\X' => [
					'non_variadic_fn1' => TrinaryLogic::createNo(),
					'variadic_fn1' => TrinaryLogic::createNo(), // variadicness later on detected via reflection
					'implicit_variadic_fn1' => TrinaryLogic::createYes(),
				],
				'VariadicMethod\Z' => [
					'non_variadic_fnZ' => TrinaryLogic::createNo(),
					'variadic_fnZ' => TrinaryLogic::createNo(), // variadicness later on detected via reflection
					'implicit_variadic_fnZ' => TrinaryLogic::createYes(),
				],
				'VariadicMethod\Z\class@anonymous' => [
					'non_variadic_fn_subZ' => TrinaryLogic::createNo(),
					'variadic_fn_subZ' => TrinaryLogic::createNo(), // variadicness later on detected via reflection
					'implicit_variadic_subZ' => TrinaryLogic::createYes(),
				],
				'VariadicMethod\class@anonymous' => [
					'non_variadic_fn' => TrinaryLogic::createNo(),
					'variadic_fn' => TrinaryLogic::createNo(), // variadicness later on detected via reflection
					'implicit_variadic_fn' => TrinaryLogic::createYes(),
				],
			],
		];

		yield [
			__DIR__ . '/data/variadic-methods-in-enum.php',
			VariadicMethodsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicMethodEnum\X' => [
					'non_variadic_fn1' => TrinaryLogic::createNo(),
					'variadic_fn1' => TrinaryLogic::createNo(), // variadicness later on detected via reflection
					'implicit_variadic_fn1' => TrinaryLogic::createYes(),
				],
			]
		];
	}

	/**
	 * @dataProvider dataVariadicCallLikes
	 * @param array<string, TrinaryLogic>|array<string, array<string, TrinaryLogic>> $expectedVariadics
	 * @throws ParserErrorsException
	 */
	public function testSimpleParserVariadicCallLikes(string $file, string $attributeName, array $expectedVariadics): void
	{
		/** @var RichParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionSimpleParser');
		$ast = $parser->parseFile($file);
		$variadics = $ast[0]->getAttribute($attributeName);
		$this->assertIsArray($variadics);
		$this->assertSame($expectedVariadics, $variadics);
	}

	/**
	 * @dataProvider dataVariadicCallLikes
	 * @param array<string, TrinaryLogic>|array<string, array<string, TrinaryLogic>> $expectedVariadics
	 * @throws ParserErrorsException
	 */
	public function testRichParserVariadicCallLikes(string $file, string $attributeName, array $expectedVariadics): void
	{
		/** @var RichParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');
		$ast = $parser->parseFile($file);
		$variadics = $ast[0]->getAttribute($attributeName);
		$this->assertIsArray($variadics);
		$this->assertSame($expectedVariadics, $variadics);
	}

}
