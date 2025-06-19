<?php declare(strict_types = 1);

namespace PHPStan\Type\Constant;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class OversizedArrayBuilderTest extends PHPStanTestCase
{

	public static function dataBuild(): iterable
	{
		yield [
			'[1, 2, 3]',
			'non-empty-list<int>&oversized-array',
		];

		yield [
			'[1, 2, 3, ...[1, 2, 3]]',
			'non-empty-list<int>&oversized-array',
		];

		yield [
			'[1, 2, 3, ...[1, \'foo\' => 2, 3]]',
			'non-empty-array<int|(literal-string&lowercase-string&non-falsy-string), int>&oversized-array',
		];

		yield [
			'[1, 2, 3, ...[1, \'FOO\' => 2, 3]]',
			'non-empty-array<int|(literal-string&non-falsy-string&uppercase-string), int>&oversized-array',
		];

		yield [
			'[1, 2, 2 => 3]',
			'non-empty-list<int>&oversized-array',
		];
		yield [
			'[1, 2, 3 => 3]',
			'non-empty-array<int, int>&oversized-array',
		];
		yield [
			'[1, 1 => 2, 3]',
			'non-empty-list<int>&oversized-array',
		];
		yield [
			'[1, 2 => 2, 3]',
			'non-empty-array<int, int>&oversized-array',
		];
		yield [
			'[1, \'foo\' => 2, 3]',
			'non-empty-array<int|(literal-string&lowercase-string&non-falsy-string), int>&oversized-array',
		];
		yield [
			'[1, \'FOO\' => 2, 3]',
			'non-empty-array<int|(literal-string&non-falsy-string&uppercase-string), int>&oversized-array',
		];
	}

	#[DataProvider('dataBuild')]
	public function testBuild(string $sourceCode, string $expectedTypeDescription): void
	{
		$parser = self::getParser();
		$ast = $parser->parseString('<?php ' . $sourceCode . ';');
		$expr = $ast[0];
		$this->assertInstanceOf(Expression::class, $expr);

		$array = $expr->expr;
		$this->assertInstanceOf(Array_::class, $array);

		$builder = new OversizedArrayBuilder();
		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		$arrayType = $builder->build($array, static fn (Expr $expr): Type => $initializerExprTypeResolver->getType($expr, InitializerExprContext::createEmpty()));
		$this->assertSame($expectedTypeDescription, $arrayType->describe(VerbosityLevel::precise()));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../../../conf/bleedingEdge.neon',
		];
	}

}
