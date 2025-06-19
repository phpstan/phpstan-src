<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPUnit\Framework\Attributes\DataProvider;
use function defined;
use function sprintf;

final class BitwiseFlagHelperTest extends PHPStanTestCase
{

	public static function dataUnknownConstants(): array
	{
		return [
			[
				new ConstFetch(new Name('SOME_CONST')),
				'SOME_CONST',
				TrinaryLogic::createYes(),
			],
			[
				new BitwiseOr(
					new ConstFetch(new Name('SOME_CONST1')),
					new ConstFetch(new Name('SOME_CONST2')),
				),
				'SOME_CONST2',
				TrinaryLogic::createYes(),
			],
			[
				new BitwiseOr(
					new ConstFetch(new Name('SOME_CONST1')),
					new ConstFetch(new Name('SOME_CONST2')),
				),
				'SOME_CONST3',
				TrinaryLogic::createNo(),
			],
		];
	}

	public static function dataJsonExprContainsConst(): array
	{
		if (!defined('JSON_THROW_ON_ERROR')) {
			return [];
		}

		return [
			[
				new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createYes(),
			],
			[
				new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new BitwiseOr(
					new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
					new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR')),
				),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createYes(),
			],
			[
				new BitwiseOr(
					new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
					new ConstFetch(new FullyQualified('JSON_FORCE_OBJECT')),
				),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new Variable('mixedVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createMaybe(),
			],
			[
				new Variable('stringVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new Variable('integerVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createMaybe(),
			],
			[
				new Variable('booleanVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new Variable('floatVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new Variable('unionIntFloatVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createMaybe(),
			],
			[
				new Variable('unionStringFloatVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
		];
	}

	/**
	 *
	 * @param non-empty-string $constName
	 */
	#[DataProvider('dataUnknownConstants')]
	#[DataProvider('dataJsonExprContainsConst')]
	public function testExprContainsConst(Expr $expr, string $constName, TrinaryLogic $expected): void
	{
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('file.php'))
			->assignVariable('mixedVar', new MixedType(), new MixedType(), TrinaryLogic::createYes())
			->assignVariable('stringVar', new StringType(), new StringType(), TrinaryLogic::createYes())
			->assignVariable('integerVar', new IntegerType(), new IntegerType(), TrinaryLogic::createYes())
			->assignVariable('booleanVar', new BooleanType(), new BooleanType(), TrinaryLogic::createYes())
			->assignVariable('floatVar', new FloatType(), new FloatType(), TrinaryLogic::createYes())
			->assignVariable('unionIntFloatVar', new UnionType([new IntegerType(), new FloatType()]), new UnionType([new IntegerType(), new FloatType()]), TrinaryLogic::createYes())
			->assignVariable('unionStringFloatVar', new UnionType([new StringType(), new FloatType()]), new UnionType([new StringType(), new FloatType()]), TrinaryLogic::createYes());

		$analyser = new BitwiseFlagHelper(self::createReflectionProvider());
		$actual = $analyser->bitwiseOrContainsConstant($expr, $scope, $constName);
		$this->assertTrue($expected->equals($actual), sprintf('Expected Trinary::%s but got Trinary::%s.', $expected->describe(), $actual->describe()));
	}

}
