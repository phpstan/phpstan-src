<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function get_include_path;
use function implode;
use function realpath;
use function set_include_path;
use const PATH_SEPARATOR;

/**
 * @extends RuleTestCase<RequireFileExistsRule>
 */
class RequireFileExistsRuleTest extends RuleTestCase
{

	private string $currentWorkingDirectory = __DIR__ . '/../';

	protected function getRule(): Rule
	{
		return new RequireFileExistsRule(
			$this->currentWorkingDirectory,
			self::getContainer()->getByType(ExprPrinter::class),
			true,
			self::getContainer()->getByType(FileHelper::class),
		);
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
		$this->currentWorkingDirectory = __DIR__;
		$this->analyse([__DIR__ . '/data/require-file-relative-path.php'], []);
	}

	public function testBug11738(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11738/bug-11738.php'], []);
	}

	public function testBug12203(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12203.php'], [
			[
				'Path in require_once() "../bug-12203-sure-does-not-exist.php" is not a file or it does not exist.',
				5,
			],
			[
				"Path in require_once() __DIR__ . '/../bug-12203-sure-does-not-exist.php' is not a file or it does not exist.",
				6,
			],
			[
				"Path in require_once() __DIR__ . '/' . \$path . '/' . \$file is not a file or it does not exist.",
				10,
			],
			[
				'Path in require_once() __DIR__ . "{$path}/{$file}" is not a file or it does not exist.',
				12,
			],
			[
				'Path in require_once() __DIR__ . DIRECTORY_SEPARATOR . $path . \'/\' . $file is not a file or it does not exist.',
				14,
			],
			[
				'Path in require_once() "../bug-12203-sure-does-not-exist.php" is not a file or it does not exist.',
				15,
			],
		]);
	}

	public function testRelativePathInTrait(): void
	{
		$this->analyse([
			__DIR__ . '/data/include-relative-in-trait/trait-dir/IncludeRelativeTrait.php',
			__DIR__ . '/data/include-relative-in-trait/class-dir/IncludeRelativeClass.php',
		], [
			[
				'Path in include() "only-in-class-dir.php" is not a file or it does not exist.',
				15,
			],
		]);
	}

	public function testBug15015(): void
	{
		$this->analyse([
			__DIR__ . '/data/bug-15015/trait-dir/Bug15015Trait.php',
			__DIR__ . '/data/bug-15015/class-dir/Bug15015Class.php',
		], [
			[
				"Path in include() __DIR__ . '/only-in-class-dir.php' is not a file or it does not exist.",
				15,
			],
		]);
	}

	public function testInFileExists(): void
	{
		$this->analyse([__DIR__ . '/data/include-in-file-exists.php'], []);
	}

}
