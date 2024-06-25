<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\NullType;

class TypeSpecifierContextTest extends PHPStanTestCase
{

	public function dataContext(): array
	{
		return [
			[
				TypeSpecifierContext::createTrue(),
				[true, true, false, false, false, false],
			],
			[
				TypeSpecifierContext::createTruthy(),
				[true, true, false, false, false, false],
			],
			[
				TypeSpecifierContext::createFalse(),
				[false, false, true, true, false, false],
			],
			[
				TypeSpecifierContext::createFalsey(),
				[false, false, true, true, false, false],
			],
			[
				TypeSpecifierContext::createNull(),
				[false, false, false, false, true, false],
			],
			[
				$this->createComparisonContext(),
				[false, false, false, false, false, true],
			],
		];
	}

	/**
	 * @dataProvider dataContext
	 * @param bool[] $results
	 */
	public function testContext(TypeSpecifierContext $context, array $results): void
	{
		$this->assertSame($results[0], $context->true());
		$this->assertSame($results[1], $context->truthy());
		$this->assertSame($results[2], $context->false());
		$this->assertSame($results[3], $context->falsey());
		$this->assertSame($results[4], $context->null());

		if ($results[5]) {
			$this->assertNotNull($context->comparison());
		} else {
			$this->assertNull($context->comparison());
		}
	}

	public function dataNegate(): array
	{
		return [
			[
				TypeSpecifierContext::createTrue()->negate(),
				[false, true, true, true, false, false],
			],
			[
				TypeSpecifierContext::createTruthy()->negate(),
				[false, false, true, true, false, false],
			],
			[
				TypeSpecifierContext::createFalse()->negate(),
				[true, true, false, true, false, false],
			],
			[
				TypeSpecifierContext::createFalsey()->negate(),
				[true, true, false, false, false, false],
			],
			/*
			 // XXX should a comparison context be negatable?
			[
				$this->createComparisonContext()->negate(),
				[false, false, false, false, false, true],
			],
			*/
		];
	}

	/**
	 * @dataProvider dataNegate
	 * @param bool[] $results
	 */
	public function testNegate(TypeSpecifierContext $context, array $results): void
	{
		$this->assertSame($results[0], $context->true());
		$this->assertSame($results[1], $context->truthy());
		$this->assertSame($results[2], $context->false());
		$this->assertSame($results[3], $context->falsey());
		$this->assertSame($results[4], $context->null());

		if ($results[5]) {
			$this->assertNotNull($context->comparison());
		} else {
			$this->assertNull($context->comparison());
		}
	}

	public function testNegateNull(): void
	{
		$this->expectException(ShouldNotHappenException::class);
		TypeSpecifierContext::createNull()->negate();
	}

	private function createComparisonContext(): TypeSpecifierContext
	{
		return TypeSpecifierContext::createComparison(
			new TypeSpecifierComparisonContext(
				new Equal(new String_('dummy'), new String_('dummy2')),
				new FuncCall('dummyFunc'),
				new NullType(),
				TypeSpecifierContext::createNull(),
				null,
			),
		);
	}

}
