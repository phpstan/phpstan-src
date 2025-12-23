<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\Rules\LazyRegistry;
use PHPStan\Testing\PHPStanTestCase;
use function array_map;
use function get_class;

class ConditionalTagsExtensionTest extends PHPStanTestCase
{

	public function testConditionalTags(): void
	{
		$enabledServices = self::getContainer()->getServicesByTag(LazyRegistry::RULE_TAG);
		$enabledServices = array_map(static fn ($service) => get_class($service), $enabledServices);
		self::assertNotContains(TestedConditionalServiceDisabled::class, $enabledServices);
		self::assertContains(TestedConditionalServiceEnabled::class, $enabledServices);
		self::assertContains(TestedConditionalServiceNotDisabled::class, $enabledServices);
		self::assertNotContains(TestedConditionalServiceNotEnabled::class, $enabledServices);
		self::assertNotContains(TestedConditionalServiceDisabledDisabled::class, $enabledServices);
		self::assertNotContains(TestedConditionalServiceDisabledEnabled::class, $enabledServices);
		self::assertNotContains(TestedConditionalServiceEnabledDisabled::class, $enabledServices);
		self::assertContains(TestedConditionalServiceEnabledEnabled::class, $enabledServices);
		self::assertContains(TestedConditionalServiceEnabledNotDisabled::class, $enabledServices);
		self::assertNotContains(TestedConditionalServiceEnabledNotEnabled::class, $enabledServices);
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/conditionalTags.neon',
		];
	}

}
