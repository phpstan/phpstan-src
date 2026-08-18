<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedPropertyUsageRule>
 */
class RestrictedInternalPropertyUsageExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(RestrictedPropertyUsageRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/property-internal-tag.php'], [
			[
				'Access to internal property PropertyInternalTagOne\Foo::$internal from outside its root namespace PropertyInternalTagOne.',
				49,
			],
			[
				'Access to property $foo of internal class PropertyInternalTagOne\FooInternal from outside its root namespace PropertyInternalTagOne.',
				54,
			],
			[
				'Access to internal property PropertyInternalTagOne\Foo::$internal from outside its root namespace PropertyInternalTagOne.',
				62,
			],

			[
				'Access to property $foo of internal class PropertyInternalTagOne\FooInternal from outside its root namespace PropertyInternalTagOne.',
				67,
			],
			[
				'Access to internal property FooWithInternalPropertyWithoutNamespace::$internal.',
				89,
			],
			[
				'Access to property $foo of internal class FooInternalWithPropertyWithoutNamespace.',
				94,
			],
			[
				'Access to internal property FooWithInternalPropertyWithoutNamespace::$internal.',
				102,
			],
			[
				'Access to property $foo of internal class FooInternalWithPropertyWithoutNamespace.',
				107,
			],
		]);
	}

}
