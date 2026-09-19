<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInFunctionThrowsRule>
 */
class Bug15271FunctionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInFunctionThrowsRule(
			self::getContainer()->getByType(MissingCheckedExceptionInThrowsCheck::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15271.php'], [
			[
				'Function Bug15271\throwsExceptionFunction() throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				86,
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
