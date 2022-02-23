<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BitwiseOr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\UnionType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;

final class BitwiseFlagAnalyserTest extends PHPStanTestCase
{

	public function dataJsonExprContainsConst() {
		if (!defined('JSON_THROW_ON_ERROR')) {
			return [];
		}

		return [
			[
				new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createYes()
			],
			[
				new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new BitwiseOr(
					new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
					new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR'))
				),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createYes()
			],
			[
				new BitwiseOr(
					new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
					new ConstFetch(new FullyQualified('JSON_FORCE_OBJECT'))
				),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('mixedVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createMaybe()
			],
			[
				new Variable('stringVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('integerVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createMaybe(),
			],
			[
				new Variable('booleanVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('floatVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
		];
	}

	public function dataJsonExprContainsConstLegacy() {
		if (defined('JSON_THROW_ON_ERROR')) {
			return [];
		}

		// php < 7.3 does not define JSON_THROW_ON_ERROR
		// see https://3v4l.org/Co7df
		return [
			[
				new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new BitwiseOr(
					new ConstFetch(new FullyQualified('JSON_NUMERIC_CHECK')),
					new ConstFetch(new FullyQualified('JSON_THROW_ON_ERROR'))
				),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('mixedVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('stringVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('integerVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo(),
			],
			[
				new Variable('booleanVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
			[
				new Variable('floatVar'),
				'JSON_THROW_ON_ERROR',
				TrinaryLogic::createNo()
			],
		];
	}

	/**
	 * @dataProvider dataJsonExprContainsConst
	 * @dataProvider dataJsonExprContainsConstLegacy
	 */
	public function testExprContainsConst(Expr $expr, string $constName, TrinaryLogic $expected) {
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('file.php'))
		   ->assignVariable('mixedVar', new MixedType())
		   ->assignVariable('stringVar', new StringType())
		   ->assignVariable('integerVar', new IntegerType())
		   ->assignVariable('booleanVar', new BooleanType())
		   ->assignVariable('floatVar', new FloatType());

		$analyser = new BitwiseFlagAnalyser($this->createReflectionProvider());
		$actual = $analyser->exprContainsConstant($expr, $scope, $constName);
		$this->assertTrue($expected->equals($actual), sprintf('Expected Trinary::%s() but got Trinary::%s().', $expected->describe(), $actual->describe()));
	}
}
