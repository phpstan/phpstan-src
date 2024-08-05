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
}
