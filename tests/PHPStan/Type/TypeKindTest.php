<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeKindTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{string, string, bool}>
	 */
	public static function dataAreDisjoint(): iterable
	{
		yield ['int', 'string', true];
		yield ['true', 'false', true];
		yield ['true', 'bool', false];
		yield ['int|string', 'float|null', true];
		yield ['int|string', 'string|null', false];
		yield ['array-key', 'float', true];
		yield ['scalar', 'array', true];
		yield ['scalar', 'bool', false];
		yield ['object', 'array', true];
		yield ['non-empty-string', 'numeric-string', false];
		yield ['mixed', 'resource', false];
		yield ['never', 'int', false];
		yield ['never', 'never', false];
	}

	#[DataProvider('dataAreDisjoint')]
	public function testAreDisjoint(string $a, string $b, bool $expected): void
	{
		$resolver = self::getContainer()->getByType(TypeStringResolver::class);
		$maskA = $resolver->resolve($a)->getKindMask();
		$maskB = $resolver->resolve($b)->getKindMask();

		$this->assertSame($expected, TypeKind::areDisjoint($maskA, $maskB));
		$this->assertSame($expected, TypeKind::areDisjoint($maskB, $maskA));
	}

}
