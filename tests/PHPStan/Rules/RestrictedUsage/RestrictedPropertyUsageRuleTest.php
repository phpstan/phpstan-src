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
	public function testNullable(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-nullable.php'], [
			[
				'Cannot access $foo',
				51,
			],
			[
				'Cannot access $foo',
				52,
			],
			[
				'Cannot access $foo',
				58,
			],
			[
				'Cannot access $foo',
				58,
			],
			[
				'Cannot access $foo',
				60,
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
