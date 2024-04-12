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
use function defined;
use function sprintf;

final class BitwiseFlagHelperTest extends PHPStanTestCase
{

	public function dataUnknownConstants(): array
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

	public function dataJsonExprContainsConst(): array
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
	 * @dataProvider dataUnknownConstants
	 * @dataProvider dataJsonExprContainsConst
	 *
	 * @param non-empty-string $constName
	 */
	public function testExprContainsConst(Expr $expr, string $constName, TrinaryLogic $expected): void
	{
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('file.php'))
			->assignVariable('mixedVar', new MixedType(), new MixedType())
			->assignVariable('stringVar', new StringType(), new StringType())
			->assignVariable('integerVar', new IntegerType(), new IntegerType())
			->assignVariable('booleanVar', new BooleanType(), new BooleanType())
			->assignVariable('floatVar', new FloatType(), new FloatType())
			->assignVariable('unionIntFloatVar', new UnionType([new IntegerType(), new FloatType()]), new UnionType([new IntegerType(), new FloatType()]))
			->assignVariable('unionStringFloatVar', new UnionType([new StringType(), new FloatType()]), new UnionType([new StringType(), new FloatType()]));

		$analyser = new BitwiseFlagHelper($this->createReflectionProvider());
		$actual = $analyser->bitwiseOrContainsConstant($expr, $scope, $constName);
		$this->assertTrue($expected->equals($actual), sprintf('Expected Trinary::%s but got Trinary::%s.', $expected->describe(), $actual->describe()));
	}

}
