<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function chdir;
use function get_include_path;
use function getcwd;
use function implode;
use function realpath;
use function set_include_path;
use const PATH_SEPARATOR;

/**
 * @extends RuleTestCase<RequireFileExistsRule>
 */
class RequireFileExistsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RequireFileExistsRule();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../../Analyser/usePathConstantsAsConstantString.neon',
		];
	}

	public function testBasicCase(): void
	{
		$this->analyse([__DIR__ . '/data/require-file-simple-case.php'], [
			[
				'Path in include() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				11,
			],
			[
				'Path in include_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				12,
			],
			[
				'Path in require() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				13,
			],
			[
				'Path in require_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				14,
			],
		]);
	}

	public function testFileDoesNotExistConditionally(): void
	{
		$this->analyse([__DIR__ . '/data/require-file-conditionally.php'], [
			[
				'Path in include() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				9,
			],
			[
				'Path in include_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				10,
			],
			[
				'Path in require() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				11,
			],
			[
				'Path in require_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				12,
			],
		]);
	}

	public function testRelativePath(): void
	{
		$this->analyse([__DIR__ . '/data/require-file-relative-path.php'], [
			[
				'Path in include() "data/include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				8,
			],
			[
				'Path in include_once() "data/include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				9,
			],
			[
				'Path in require() "data/include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				10,
			],
			[
				'Path in require_once() "data/include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				11,
			],
		]);
	}

	public function testRelativePathWithIncludePath(): void
	{
		$includePaths = [realpath(__DIR__)];
		$includePaths[] = get_include_path();

		set_include_path(implode(PATH_SEPARATOR, $includePaths));

		try {
			$this->analyse([__DIR__ . '/data/require-file-relative-path.php'], []);
		} finally {
			set_include_path($includePaths[1]);
		}
	}

	public function testRelativePathWithSameWorkingDirectory(): void
	{
		$originalWorkingDirectory = getcwd();
		chdir(__DIR__);

		try {
			$this->analyse([__DIR__ . '/data/require-file-relative-path.php'], []);
		} finally {
			chdir((string) $originalWorkingDirectory);
		}
	}

}
