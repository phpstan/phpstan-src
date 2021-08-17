<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OverridingPropertyRule>
 */
class OverridingPropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverridingPropertyRule(true);
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->analyse([__DIR__ . '/data/overriding-property.php'], [
			[
				'Static property OverridingProperty\Bar::$protectedFoo overrides non-static property OverridingProperty\Foo::$protectedFoo.',
				25,
			],
			[
				'Non-static property OverridingProperty\Bar::$protectedStaticFoo overrides static property OverridingProperty\Foo::$protectedStaticFoo.',
				26,
			],
			[
				'Static property OverridingProperty\Bar::$publicFoo overrides non-static property OverridingProperty\Foo::$publicFoo.',
				28,
			],
			[
				'Non-static property OverridingProperty\Bar::$publicStaticFoo overrides static property OverridingProperty\Foo::$publicStaticFoo.',
				29,
			],
			[
				'Readonly property OverridingProperty\ReadonlyChild::$readWrite overrides readwrite property OverridingProperty\ReadonlyParent::$readWrite.',
				45,
			],
			[
				'Readwrite property OverridingProperty\ReadonlyChild::$readOnly overrides readonly property OverridingProperty\ReadonlyParent::$readOnly.',
				46,
			],
			[
				'Readonly property OverridingProperty\ReadonlyChild2::$readWrite overrides readwrite property OverridingProperty\ReadonlyParent::$readWrite.',
				55,
			],
			[
				'Readwrite property OverridingProperty\ReadonlyChild2::$readOnly overrides readonly property OverridingProperty\ReadonlyParent::$readOnly.',
				56,
			],
			[
				'Private property OverridingProperty\PrivateDolor::$protectedFoo overriding protected property OverridingProperty\Dolor::$protectedFoo should be protected or public.',
				76,
			],
			[
				'Private property OverridingProperty\PrivateDolor::$publicFoo overriding public property OverridingProperty\Dolor::$publicFoo should also be public.',
				77,
			],
			[
				'Private property OverridingProperty\PrivateDolor::$anotherPublicFoo overriding public property OverridingProperty\Dolor::$anotherPublicFoo should also be public.',
				78,
			],
			[
				'Protected property OverridingProperty\ProtectedDolor::$publicFoo overriding public property OverridingProperty\Dolor::$publicFoo should also be public.',
				87,
			],
			[
				'Protected property OverridingProperty\ProtectedDolor::$anotherPublicFoo overriding public property OverridingProperty\Dolor::$anotherPublicFoo should also be public.',
				88,
			],
			[
				'Property OverridingProperty\TypeChild::$withType overriding property OverridingProperty\Typed::$withType (int) should also have native type int.',
				125,
			],
			[
				'Property OverridingProperty\TypeChild::$withoutType (int) overriding property OverridingProperty\Typed::$withoutType should not have a native type.',
				126,
			],
			[
				'Type string of property OverridingProperty\Typed2Child::$foo is not the same as type int of overridden property OverridingProperty\Typed2::$foo.',
				142,
			],
			[
				'Type 4 of property OverridingProperty\Typed2WithPhpDoc::$foo is not the same as type 1|2|3 of overridden property OverridingProperty\TypedWithPhpDoc::$foo.',
				158,
			],
		]);
	}

}
