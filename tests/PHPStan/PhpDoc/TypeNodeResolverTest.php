<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class TypeNodeResolverTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{string, non-empty-string|null, array<string, string>, string}>
	 */
	public static function dataGlobalConstantPattern(): iterable
	{
		yield 'built-in' => ['PHP_ROUND_*', null, [], '1|2|3|4'];
		yield 'asterisk in the middle' => ['E_*_ERROR', null, [], '16|64|256|4096'];
		yield 'fully qualified in a namespace' => ['\\PHP_ROUND_*', 'GlobalConstantWildcard', [], '1|2|3|4'];
		yield 'falls back to the global namespace' => ['PHP_ROUND_*', 'GlobalConstantWildcard', [], '1|2|3|4'];
		yield 'current namespace' => ['FLAG_*', 'GlobalConstantWildcard', [], "'read'|'write'"];
		yield 'current namespace before the global one' => ['PHP_ROUND_*', 'GlobalConstantWildcard\\Sub', [], "'shadowed'"];
		yield 'fully qualified namespaced' => ['\\GlobalConstantWildcard\\FLAG_*', null, [], "'read'|'write'"];
		yield 'qualified, relative to the namespace' => ['Sub\\FLAG_*', 'GlobalConstantWildcard', [], "'execute'"];
		yield 'qualified, through a use' => ['S\\FLAG_*', 'Other', ['s' => 'GlobalConstantWildcard\\Sub'], "'execute'"];
		yield 'defined' => ['GLOBAL_CONSTANT_WILDCARD_*', null, [], '10|100'];
		yield 'no match' => ['NOPE_*', null, [], '*ERROR*'];
		yield 'no global match for a namespaced constant' => ['\\FLAG_*', 'GlobalConstantWildcard', [], '*ERROR*'];
	}

	/**
	 * @param non-empty-string|null $namespace
	 * @param array<string, string> $uses
	 */
	#[DataProvider('dataGlobalConstantPattern')]
	public function testGlobalConstantPattern(string $pattern, ?string $namespace, array $uses, string $expectedType): void
	{
		$typeNodeResolver = self::getContainer()->getByType(TypeNodeResolver::class);

		// the node phpstan/phpdoc-parser reads @param PHP_ROUND_* $mode into
		$type = $typeNodeResolver->resolve(
			new ConstTypeNode(new ConstFetchNode('', $pattern)),
			new NameScope($namespace, $uses),
		);

		$this->assertSame($expectedType, $type->describe(VerbosityLevel::precise()));
	}

}
