<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\Attributes\DataProvider;

class SimpleImpurePointTest extends PHPStanTestCase
{

	public static function dataResolvePureUnlessCallableIsImpureVerdict(): iterable
	{
		yield 'flag No - no flagged parameters, verdict is null' => [
			TrinaryLogic::createNo(),
			null,
		];

		yield 'flag Yes - callback omitted, verdict is Yes' => [
			TrinaryLogic::createYes(),
			TrinaryLogic::createYes(),
		];

		// A Maybe flag (e.g. from a union method where only some members carry
		// @pure-unless-callable-is-impure) must be processed like Yes, not skipped.
		yield 'flag Maybe - callback omitted, verdict is Yes' => [
			TrinaryLogic::createMaybe(),
			TrinaryLogic::createYes(),
		];
	}

	#[DataProvider('dataResolvePureUnlessCallableIsImpureVerdict')]
	public function testResolvePureUnlessCallableIsImpureVerdict(TrinaryLogic $parameterFlag, ?TrinaryLogic $expectedVerdict): void
	{
		$parameter = new ExtendedDummyParameter(
			'cb',
			new MixedType(),
			true,
			PassedByReference::createNo(),
			false,
			null,
			new MixedType(),
			new MixedType(),
			null,
			TrinaryLogic::createMaybe(),
			null,
			[],
			null,
			$parameterFlag,
		);
		$variant = new FunctionVariant(
			TemplateTypeMap::createEmpty(),
			null,
			[$parameter],
			false,
			new MixedType(),
		);

		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('dummy.php'));

		$verdict = SimpleImpurePoint::resolvePureUnlessCallableIsImpureVerdict($variant, $scope, []);

		if ($expectedVerdict === null) {
			$this->assertNull($verdict);
		} else {
			$this->assertNotNull($verdict);
			$this->assertTrue($expectedVerdict->equals($verdict));
		}
	}

}
