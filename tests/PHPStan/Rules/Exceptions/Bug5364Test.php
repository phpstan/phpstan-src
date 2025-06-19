<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class Bug5364Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInMethodThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				self::createReflectionProvider(),
				[],
				[],
				[],
				[],
			)),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5364.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-5364.neon',
		];
	}

}
