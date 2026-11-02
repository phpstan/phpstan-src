<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_map;
use function strtr;

class ComposerConfigIncludesTest extends TestCase
{

	/**
	 * @return iterable<string, array{string, string|null, list<string>}>
	 */
	public static function dataFindMissing(): iterable
	{
		yield 'one already included by the project config' => ['included', 'phpstan.neon', ['rules.neon']];
		yield 'none included by the project config' => ['not-included', 'phpstan.neon', ['extension.neon', 'rules.neon']];
		yield 'without a project config' => ['included', null, ['extension.neon', 'rules.neon']];
		yield 'without extra.phpstan.includes' => ['no-extra', null, []];
		yield 'without composer.json' => ['no-composer', null, []];
	}

	/**
	 * @param list<string> $expected
	 */
	#[DataProvider('dataFindMissing')]
	public function testFindMissing(string $case, ?string $projectConfigFile, array $expected): void
	{
		$directory = strtr(__DIR__, '\\', '/') . '/composer-includes/' . $case;
		$this->assertSame(
			array_map(static fn (string $file): string => $directory . '/' . $file, $expected),
			array_map(static fn (string $file): string => strtr($file, '\\', '/'), ComposerConfigIncludes::findMissing(
				$directory,
				$projectConfigFile === null ? null : $directory . '/' . $projectConfigFile,
			)),
		);
	}

}
