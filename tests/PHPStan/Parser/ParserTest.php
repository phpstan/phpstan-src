<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function count;

class ParserTest extends PHPStanTestCase
{

	public static function dataVariadicCallLikes(): iterable
	{
		yield [
			__DIR__ . '/data/variadic-functions.php',
			VariadicFunctionsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicFunctions\implicit_variadic_fn1' => true,
			],
		];

		yield [
			__DIR__ . '/data/variadic-methods.php',
			VariadicMethodsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicMethod\X' => [
					'implicit_variadic_fn1' => true,
				],
				'VariadicMethod\Z' => [
					'implicit_variadic_fnZ' => true,
				],
				'class@anonymous:20:30' => [
					'implicit_variadic_subZ' => true,
				],
				'class@anonymous:42:52' => [
					'implicit_variadic_fn' => true,
				],
				'class@anonymous:54:58' => [
					'implicit_variadic_fn' => true,
				],
				'class@anonymous:61:68' => [
					'implicit_variadic_fn' => true,
				],
			],
		];

		yield [
			__DIR__ . '/data/variadic-methods-in-enum.php',
			VariadicMethodsVisitor::ATTRIBUTE_NAME,
			[
				'VariadicMethodEnum\X' => [
					'implicit_variadic_fn1' => true,
				],
			],
		];
	}

	/**
	 * @param array<string, true>|array<string, array<string, true>> $expectedVariadics
	 * @throws ParserErrorsException
	 */
	#[DataProvider('dataVariadicCallLikes')]
	public function testSimpleParserVariadicCallLikes(string $file, string $attributeName, array $expectedVariadics): void
	{
		/** @var SimpleParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionSimpleParser');
		$ast = $parser->parseFile($file);
		$variadics = $ast[0]->getAttribute($attributeName);
		$this->assertIsArray($variadics);
		$this->assertCount(count($expectedVariadics), $variadics);
		foreach ($expectedVariadics as $key => $expectedVariadic) {
			$this->assertArrayHasKey($key, $variadics);
			$this->assertSame($expectedVariadic, $variadics[$key]);
		}
	}

	/**
	 * @param array<string, true>|array<string, array<string, true>> $expectedVariadics
	 * @throws ParserErrorsException
	 */
	#[DataProvider('dataVariadicCallLikes')]
	public function testRichParserVariadicCallLikes(string $file, string $attributeName, array $expectedVariadics): void
	{
		/** @var RichParser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');
		$ast = $parser->parseFile($file);
		$variadics = $ast[0]->getAttribute($attributeName);
		$this->assertIsArray($variadics);
		$this->assertCount(count($expectedVariadics), $variadics);
		foreach ($expectedVariadics as $key => $expectedVariadic) {
			$this->assertArrayHasKey($key, $variadics);
			$this->assertSame($expectedVariadic, $variadics[$key]);
		}
	}

}
