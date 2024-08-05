<?php declare(strict_types=1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Testing\RuleTestCase;
use PHPStan\Rules\Keywords\RequireFileExistsRule;
use PHPStan\Rules\Rule;


/**
 * @extends RuleTestCase<RequireFileExistsRule>
 */
class RequireFileExistsRuleTest extends RuleTestCase
{
	protected function getRule(): Rule
	{
		return new RequireFileExistsRule($this->createReflectionProvider());
	}

	public function testItCannotReadConstantsDefinedInTheAnalysedFile(): void
	{
		$this->analyse([__DIR__ . '/data/file-does-not-exist-but-const-is-defined-in-the-same-file.php'], []);
	}

	public function testFileExistsButPathIsRelative(): void
	{
		$this->analyse([__DIR__ . '/data/file-exists-but-path-is-relative.php'], [
			[
				'Required file "include-me-to-prove-you-work.txt" does not exist.',
				5,
			],
			[
				'Required file "include-me-to-prove-you-work.txt" does not exist.',
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
		$this->analyse([__DIR__ . '/data/required-file-does-not-exist-using-class-const.php'], [
			[
				'Required file "a-file-that-does-not-exist.php" does not exist.',
				7,
			],
			[
				'Required file "a-file-that-does-not-exist.php" does not exist.',
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
		define('FILE_DOES_NOT_EXIST', 'a-file-that-does-not-exist.txt');

		$this->analyse([__DIR__ . '/data/required-file-does-not-exist-using-constant.php'], [
			[
				'Required file "a-file-that-does-not-exist.txt" does not exist.',
				7,
			],
			[
				'Required file "a-file-that-does-not-exist.txt" does not exist.',
				8,
			],
		]);
	}
}
