<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
		$this->analyse([__DIR__ . '/data/file-does-not-exist-conditionally.php'], [
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

}
