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
	public function testNullable(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-nullable.php'], [
			[
				'Cannot call doFoo',
				48,
			],
			[
				'Cannot call doFoo',
				49,
			],
			[
				'Cannot call doFoo',
				57,
			],
			[
				'Cannot call doFoo',
				57,
			],
			[
				'Cannot call doFoo',
				59,
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
