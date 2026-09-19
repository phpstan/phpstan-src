<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class Bug15271Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInMethodThrowsRule(
			self::getContainer()->getByType(MissingCheckedExceptionInThrowsCheck::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15271.php'], [
			[
				'Method Bug15271\MondayMorning::throwsException() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				68,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-15271.neon',
		];
	}

}
