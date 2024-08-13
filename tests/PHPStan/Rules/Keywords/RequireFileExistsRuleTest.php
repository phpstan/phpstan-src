<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function define;

/**
 * @extends RuleTestCase<RequireFileExistsRule>
 */
class RequireFileExistsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RequireFileExistsRule();
	}

	public function testItCannotReadConstantsDefinedInTheAnalysedFile(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-const-is-defined-in-the-same-file.php'], []);
	}

	public function testFileExistsButPathIsRelative(): void
	{
		$this->analyse([__DIR__ . '/data/file-exists-but-path-is-relative.php'], [
			[
				'Path in include() "include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				3,
			],
			[
				'Path in include_once() "include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				4,
			],
			[
				'Path in require() "include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				5,
			],
			[
				'Path in require_once() "include-me-to-prove-you-work.txt" is not a file or it does not exist.',
				6,
			],
		]);
	}

	public function testFileExistsUsingClassConst(): void
	{
		$this->analyse([__DIR__ . '/data/file-exists-using-class-const.php'], []);
	}

	public function testFileDoesNotExistUsingClassConst(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-using-class-const.php'], [
			[
				'Path in include() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				5,
			],
			[
				'Path in include_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				6,
			],
			[
				'Path in require() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				7,
			],
			[
				'Path in require_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				8,
			],
		]);
	}

	public function testFileExistsUsingConst(): void
	{
		define('FILE_EXISTS', __DIR__ . '/data/include-me-to-prove-you-work.txt');

		$this->analyse([__DIR__ . '/data/file-exists-using-constant.php'], []);
	}

	public function testFileDoesNotExistUsingConst(): void
	{
		define('FILE_DOES_NOT_EXIST', 'a-file-that-does-not-exist.php');

		$this->analyse([__DIR__ . '/data/file-does-not-exist-using-constant.php'], [
			[
				'Path in include() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				5,
			],
			[
				'Path in include_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				6,
			],
			[
				'Path in require() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				7,
			],
			[
				'Path in require_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				8,
			],
		]);
	}

	public function testFileExistsUsingDIR(): void
	{
		$this->analyse([__DIR__ . '/data/file-exists-using-DIR.php'], []);
	}

	public function testFileExistsUsingVariables(): void
	{
		$this->analyse([__DIR__ . '/data/file-exists-using-a-variable.php'], []);
	}

	public function testFileDoesNotExistButUsesVariables(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-uses-a-variable.php'], [
			[
				'Path in include() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				5,
			],
			[
				'Path in include_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				6,
			],
			[
				'Path in require() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				7,
			],
			[
				'Path in require_once() "a-file-that-does-not-exist.php" is not a file or it does not exist.',
				8,
			],
		]);
	}

	public function testFileDoesNotExistButUsesClassProperties(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-uses-a-class-property.php'], []);
	}

	public function testFileDoesNotExistButUsesClassMethods(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-uses-a-class-method.php'], []);
	}

	public function testFileDoesNotExistButUsesAFunction(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-uses-a-function.php'], []);
	}

}
