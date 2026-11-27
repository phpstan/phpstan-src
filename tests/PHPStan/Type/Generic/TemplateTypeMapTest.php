<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use Exception;
use InvalidArgumentException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class TemplateTypeMapTest extends PHPStanTestCase
{

	public function testResolveDependentBoundsAndDefaults(): void
	{
		$scope = TemplateTypeScope::createWithClass('DependentBounds');
		$variance = TemplateTypeVariance::createInvariant();
		$default = new ObjectType(stdClass::class);
		$t = TemplateTypeFactory::create($scope, 'T', new ObjectWithoutClassType(), $variance, default: $default);
		$u = TemplateTypeFactory::create($scope, 'U', $t, $variance);
		$v = TemplateTypeFactory::create($scope, 'V', $u, $variance);
		$resolved = (new TemplateTypeMap(['T' => $t, 'U' => $u, 'V' => $v]))->resolveToBounds();

		foreach (['T', 'U', 'V'] as $name) {
			$type = $resolved->getType($name);
			$this->assertNotNull($type);
			$this->assertTrue($default->equals($type));
		}
	}

	public static function dataUnionWithUpperBoundTypes(): iterable
	{
		$map = (new TemplateTypeMap([
			'T' => new ObjectType(Exception::class),
		]))->swapBounds();

		yield [
			$map,
			Exception::class,
		];

		yield [
			$map->union(new TemplateTypeMap([
				'T' => new ObjectType(InvalidArgumentException::class),
			])),
			InvalidArgumentException::class,
		];

		yield [
			$map->union((new TemplateTypeMap([
				'T' => new ObjectType(InvalidArgumentException::class),
			]))->swapBounds()),
			InvalidArgumentException::class,
		];

		yield [
			(new TemplateTypeMap([
				'T' => new ObjectType(Exception::class),
			], [
				'T' => new ObjectType(InvalidArgumentException::class),
			]))->swapBounds(),
			InvalidArgumentException::class,
		];

		yield [
			(new TemplateTypeMap([
				'T' => new ObjectType(InvalidArgumentException::class),
			], [
				'T' => new ObjectType(Exception::class),
			]))->swapBounds(),
			Exception::class,
		];

		// swapped twice, Exception is a lower bound again and joins the other lower bound
		yield [
			$map->swapBounds()->union(new TemplateTypeMap([
				'T' => new ObjectType(InvalidArgumentException::class),
			])),
			Exception::class,
		];
	}

	#[DataProvider('dataUnionWithUpperBoundTypes')]
	public function testUnionWithUpperBoundTypes(TemplateTypeMap $map, string $expectedTDescription): void
	{
		$this->assertFalse($map->isEmpty());
		$t = $map->getType('T');
		$this->assertNotNull($t);
		$this->assertSame($expectedTDescription, $t->describe(VerbosityLevel::precise()));
	}

}
