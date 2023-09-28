<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleWithDisabledMultiCatchTest extends RuleTestCase
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

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[__DIR__ . '/disable-detect-multi-catch.neon'],
		);
	}

	public function testMultiCatchBackwardCompatible(): void
	{
		$this->analyse([__DIR__ . '/data/unthrown-exception-multi.php'], [
			[
				'Dead catch - InvalidArgumentException is already caught above.',
				145,
			],
			[
				'Dead catch - InvalidArgumentException is already caught above.',
				156,
			],
		]);
	}

}
