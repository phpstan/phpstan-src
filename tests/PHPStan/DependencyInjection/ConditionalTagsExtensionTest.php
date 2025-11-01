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
		$this->assertNotContains(TestedConditionalServiceDisabled::class, $enabledServices);
		$this->assertContains(TestedConditionalServiceEnabled::class, $enabledServices);
		$this->assertContains(TestedConditionalServiceNotDisabled::class, $enabledServices);
		$this->assertNotContains(TestedConditionalServiceNotEnabled::class, $enabledServices);
		$this->assertNotContains(TestedConditionalServiceDisabledDisabled::class, $enabledServices);
		$this->assertNotContains(TestedConditionalServiceDisabledEnabled::class, $enabledServices);
		$this->assertNotContains(TestedConditionalServiceEnabledDisabled::class, $enabledServices);
		$this->assertContains(TestedConditionalServiceEnabledEnabled::class, $enabledServices);
		$this->assertContains(TestedConditionalServiceEnabledNotDisabled::class, $enabledServices);
		$this->assertNotContains(TestedConditionalServiceEnabledNotEnabled::class, $enabledServices);
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
