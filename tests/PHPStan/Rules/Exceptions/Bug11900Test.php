<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInMethodThrowsRule>
 */
class Bug11900Test extends RuleTestCase
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

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11900.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-11900.neon',
		];
	}

}
