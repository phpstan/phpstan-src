<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SymbolFinderInFilesTest extends PHPStanTestCase
{

	/**
	 * @return iterable<string, array{string, array{string[], string[], string[]}}>
	 */
	public static function dataFindSymbols(): iterable
	{
		yield 'class name expressions do not hide later global constants' => [
			__DIR__ . '/data/symbol-finder/class-name-before-global-constant.php',
			[
				[],
				['symbolfinderclassname', 'classname', 'symbolfinderanonymousclass', 'symbolfindernamedargument', 'symbolfindernamedargumentcall', 'symbolfinderspacedclassconstant'],
				['AFTER_CLASS_NAME', 'AFTER_CLASS_PREFIX', 'AFTER_ANONYMOUS_CLASS', 'AFTER_NAMED_CLASS_ARGUMENT', 'AFTER_SPACED_CLASS_CONSTANT'],
			],
		];

		yield 'namespaced constants do not leak a class entry' => [
			__DIR__ . '/data/symbol-finder/namespaced-constants.php',
			[
				['symbolfindertest\namespaced\thing'],
				[],
				['symbolfindertest\namespaced\ALPHA', 'symbolfindertest\namespaced\BETA'],
			],
		];

		yield 'global constants and defines' => [
			__DIR__ . '/data/symbol-finder/global-constants.php',
			[
				[],
				['symbolfindertestfunction'],
				['GLOBAL_ALPHA', 'symbolfindertest\DEFINED'],
			],
		];
	}

	/**
	 * @param array{string[], string[], string[]} $expected
	 */
	#[DataProvider('dataFindSymbols')]
	public function testFindSymbols(string $file, array $expected): void
	{
		$finder = new SymbolFinderInFiles(new PhpFileCleaner());
		$this->assertSame([$file => $expected], $finder->findSymbols([$file], true));
	}

}
