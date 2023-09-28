<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class AbilityToDisableImplicitThrowsTest extends RuleTestCase
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
		$this->analyse([__DIR__ . '/data/ability-to-disable-implicit-throws.php'], [
			[
				'Dead catch - Throwable is never thrown in the try block.',
				17,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/data/ability-to-disable-implicit-throws.neon'],
		);
	}

}
