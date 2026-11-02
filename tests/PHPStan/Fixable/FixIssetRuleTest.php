<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * @extends RuleTestCase<FixIssetRule>
 */
class FixIssetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FixIssetRule();
	}

	public function testRule(): void
	{
		$this->fix(__DIR__ . '/data/isset.php', __DIR__ . '/data/isset.php.fixed');
	}

	public function testFixFileChangedSinceLastFix(): void
	{
		$file = tempnam(sys_get_temp_dir(), 'phpstan-fix');
		$expectedFile = tempnam(sys_get_temp_dir(), 'phpstan-fix');
		try {
			FileWriter::write($file, FileReader::read(__DIR__ . '/data/isset.php'));
			$this->fix($file, __DIR__ . '/data/isset.php.fixed');

			// the diff has to be computed against the new contents, not the ones fixed before
			$addComment = static fn (string $code): string => str_replace('namespace FixIsset;', "namespace FixIsset;\n\n// changed", $code);
			FileWriter::write($file, $addComment(FileReader::read(__DIR__ . '/data/isset.php')));
			FileWriter::write($expectedFile, $addComment(FileReader::read(__DIR__ . '/data/isset.php.fixed')));
			$this->fix($file, $expectedFile);
		} finally {
			@unlink($file);
			@unlink($expectedFile);
		}
	}

	public function testFixInTraitInSameFile(): void
	{
		$this->fix(__DIR__ . '/data/trait/same-file-trait.php', __DIR__ . '/data/trait/same-file-trait.php.fixed');
	}

	public function testFixInTraitUsedByTwoClasses(): void
	{
		// each using class reports the error, both diffs make the same change
		$this->fixOtherFile(
			__DIR__ . '/data/trait/uses-trait.php',
			__DIR__ . '/data/trait/FixableTrait.php',
			__DIR__ . '/data/trait/FixableTrait.php.fixed',
		);
	}

}
