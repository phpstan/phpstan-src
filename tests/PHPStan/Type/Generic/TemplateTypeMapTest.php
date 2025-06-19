<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use Exception;
use InvalidArgumentException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TemplateTypeMapTest extends TestCase
{

	public static function dataUnionWithLowerBoundTypes(): iterable
	{
		$map = (new TemplateTypeMap([
			'T' => new ObjectType(Exception::class),
		]))->convertToLowerBoundTypes();

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
			]))->convertToLowerBoundTypes()),
			InvalidArgumentException::class,
		];

		yield [
			(new TemplateTypeMap([
				'T' => new ObjectType(Exception::class),
			], [
				'T' => new ObjectType(InvalidArgumentException::class),
			]))->convertToLowerBoundTypes(),
			InvalidArgumentException::class,
		];

		yield [
			(new TemplateTypeMap([
				'T' => new ObjectType(InvalidArgumentException::class),
			], [
				'T' => new ObjectType(Exception::class),
			]))->convertToLowerBoundTypes(),
			InvalidArgumentException::class,
		];
	}

	#[DataProvider('dataUnionWithLowerBoundTypes')]
	public function testUnionWithLowerBoundTypes(TemplateTypeMap $map, string $expectedTDescription): void
	{
		$this->assertFalse($map->isEmpty());
		$t = $map->getType('T');
		$this->assertNotNull($t);
		$this->assertSame($expectedTDescription, $t->describe(VerbosityLevel::precise()));
	}

}
