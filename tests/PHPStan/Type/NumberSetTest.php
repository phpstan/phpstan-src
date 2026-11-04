<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use const INF;

class NumberSetTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{string, array{list<array{int|null, int|null}>, list<array{float, float}>, bool}|null}>
	 */
	public static function dataToNumberSet(): iterable
	{
		yield ['int', [[[null, null]], [], false]];
		yield ['int<min, 0>', [[[null, 0]], [], false]];
		yield ['7', [[[7, 7]], [], false]];
		yield ['int<1, 5>|7', [[[1, 5], [7, 7]], [], false]];
		yield ['float', [[], [[-INF, INF]], true]];
		yield ['float<min, max>', [[], [[-INF, INF]], false]];
		yield ['NAN', [[], [], true]];
		yield ['-1.5', [[], [[-1.5, -1.5]], false]];
		yield ['float<1.5, 2>|NAN|3', [[[3, 3]], [[1.5, 2.0]], true]];
		yield ['int<min, 2>|float<0, max>', [[[null, 2]], [[0.0, INF]], false]];
		yield ['string', null];
		yield ['int|string', null];
		yield ['1|null', null];
		yield ['mixed', null];
	}

	/**
	 * @param array{list<array{int|null, int|null}>, list<array{float, float}>, bool}|null $expected
	 */
	#[DataProvider('dataToNumberSet')]
	public function testToNumberSet(string $typeString, ?array $expected): void
	{
		$type = self::getContainer()->getByType(TypeStringResolver::class)->resolve($typeString);
		$numberSet = $type->toNumberSet();
		if ($expected === null) {
			$this->assertNull($numberSet, $type->describe(VerbosityLevel::precise()));
			return;
		}

		$this->assertNotNull($numberSet, $type->describe(VerbosityLevel::precise()));
		$this->assertSame($expected, [
			$numberSet->getIntegerIntervals(),
			$numberSet->getFloatIntervals(),
			$numberSet->hasNan(),
		]);
	}

}
