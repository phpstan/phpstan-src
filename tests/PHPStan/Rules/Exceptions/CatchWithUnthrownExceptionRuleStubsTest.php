<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleStubsTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule(new DefaultExceptionTypeResolver(
			$this->createReflectionProvider(),
			[],
			[],
			[],
			[],
		), true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/catch-with-unthrown-exception-stubs.php'], [
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				44,
			],
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				55,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/catch-with-unthrown-exception-stubs.neon',
		];
	}

}
