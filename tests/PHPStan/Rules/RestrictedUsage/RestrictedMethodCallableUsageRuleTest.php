<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedMethodCallableUsageRule>
 */
class RestrictedMethodCallableUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new RestrictedMethodCallableUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedMethodUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method-callable.php'], [
			[
				'Cannot call doFoo',
				13,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15681.php'], [
			[
				'Cannot call doFoo',
				64,
			],
			[
				'Cannot call doFoo',
				65,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/restricted-usage.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
