<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\FileNotFoundException;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use function array_column;
use function array_map;
use function realpath;

class GlobIncludesTest extends PHPStanTestCase
{

	public function testMatchedFilesAreIncluded(): void
	{
		/** @var list<array{identifier?: string}> $ignoreErrors */
		$ignoreErrors = self::getContainer()->getParameter('ignoreErrors');
		$identifiers = array_column($ignoreErrors, 'identifier');
		$this->assertContains('a.identifier', $identifiers);
		$this->assertContains('b.identifier', $identifiers);
		$this->assertContains('literal.identifier', $identifiers);
		$this->assertContains('sibling.identifier', $identifiers);
		$this->assertContains('bracketed.identifier', $identifiers);
	}

	public function testMatchedFilesAreIncludedInAlphabeticalOrder(): void
	{
		// a later include overrides an earlier one, so b.neon wins over a.neon
		$this->assertSame('b', self::getContainer()->getParameter('globIncludesOrder'));
	}

	public function testMatchedFilesAreListedAsConfigFiles(): void
	{
		/** @var list<string> $allConfigFiles */
		$allConfigFiles = self::getContainer()->getParameter('allConfigFiles');
		$allConfigFiles = array_map(static fn (string $file): string|false => realpath($file), $allConfigFiles);

		$this->assertContains(realpath(__DIR__ . '/globIncludes/baselines/a.neon'), $allConfigFiles);
		$this->assertContains(realpath(__DIR__ . '/globIncludes/baselines/b.neon'), $allConfigFiles);
		$this->assertContains(realpath(__DIR__ . '/globIncludes/literal/[literal].neon'), $allConfigFiles);
	}

	public function testTopLevelConfigFileIsNotAPattern(): void
	{
		$loader = new NeonLoader(new FileHelper(__DIR__), null);

		$this->expectException(FileNotFoundException::class);
		$loader->load(__DIR__ . '/globIncludes/baselines/*.neon');
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/globIncludes/main.neon',
			__DIR__ . '/globIncludes/empty.neon',
		];
	}

}
