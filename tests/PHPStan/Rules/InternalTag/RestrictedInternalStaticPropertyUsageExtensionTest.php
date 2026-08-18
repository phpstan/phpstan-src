<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Rules\RestrictedUsage\RestrictedStaticPropertyUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedStaticPropertyUsageRule>
 */
class RestrictedInternalStaticPropertyUsageExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(RestrictedStaticPropertyUsageRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-property-internal-tag.php'], [
			[
				'Access to internal static property StaticPropertyInternalTagOne\Foo::$internal from outside its root namespace StaticPropertyInternalTagOne.',
				49,
			],
			[
				'Access to static property $foo of internal class StaticPropertyInternalTagOne\FooInternal from outside its root namespace StaticPropertyInternalTagOne.',
				54,
			],
			[
				'Access to internal static property StaticPropertyInternalTagOne\Foo::$internal from outside its root namespace StaticPropertyInternalTagOne.',
				62,
			],

			[
				'Access to static property $foo of internal class StaticPropertyInternalTagOne\FooInternal from outside its root namespace StaticPropertyInternalTagOne.',
				67,
			],
			[
				'Access to internal static property FooWithInternalStaticPropertyWithoutNamespace::$internal.',
				89,
			],
			[
				'Access to static property $foo of internal class FooInternalWithStaticPropertyWithoutNamespace.',
				94,
			],
			[
				'Access to internal static property FooWithInternalStaticPropertyWithoutNamespace::$internal.',
				102,
			],
			[
				'Access to static property $foo of internal class FooInternalWithStaticPropertyWithoutNamespace.',
				107,
			],
		]);
	}

	public function testStaticPropertyAccessOnInternalSubclass(): void
	{
		$this->analyse([__DIR__ . '/data/static-property-access-on-internal-subclass.php'], [
			[
				'Access to static property $bar of internal class StaticPropertyAccessOnInternalSubclassOne\Bar from outside its root namespace StaticPropertyAccessOnInternalSubclassOne.',
				28,
			],
		]);
	}

}
