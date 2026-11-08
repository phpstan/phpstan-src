<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedPropertyUsageRule>
 */
class RestrictedPropertyUsageRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new RestrictedPropertyUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedPropertyUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-property.php'], [
			[
				'Cannot access $foo',
				17,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15681.php'], [
			[
				'Cannot access $foo',
				59,
			],
			[
				'Cannot access $foo',
				60,
			],
			[
				'Cannot access $foo',
				61,
			],
			[
				'Cannot access $foo',
				62,
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
