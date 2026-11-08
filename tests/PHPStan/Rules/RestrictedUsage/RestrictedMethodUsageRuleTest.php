<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedMethodUsageRule>
 */
class RestrictedMethodUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new RestrictedMethodUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedMethodUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-method.php'], [
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
				51,
			],
			[
				'Cannot call doFoo',
				52,
			],
			[
				'Cannot call doFoo',
				53,
			],
			[
				'Cannot call doFoo',
				54,
			],
			[
				'Cannot call doFoo',
				55,
			],
			[
				'Cannot call doFoo',
				56,
			],
			[
				'Cannot call doFoo',
				57,
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
