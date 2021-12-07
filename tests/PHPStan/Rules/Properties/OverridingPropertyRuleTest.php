<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<OverridingPropertyRule>
 */
class OverridingPropertyRuleTest extends RuleTestCase
{

	/** @var bool */
	private $reportMaybes;

	protected function getRule(): Rule
	{
		return new OverridingPropertyRule(true, $this->reportMaybes);
	}

	public function testRule(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->reportMaybes = true;
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
				'PHPDoc type 4 of property OverridingProperty\Typed2WithPhpDoc::$foo is not the same as PHPDoc type 1|2|3 of overridden property OverridingProperty\TypedWithPhpDoc::$foo.',
				158,
				sprintf(
					"You can fix 3rd party PHPDoc types with stub files:\n   %s",
					'<fg=cyan>https://phpstan.org/user-guide/stub-files</>'
				),
			],
		]);
	}

	public function dataRulePHPDocTypes(): array
	{
		$tip = sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>'
		);
		$tipWithOption = sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s\n   This error can be turned off by setting\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
			'<fg=cyan>reportMaybesInPropertyPhpDocTypes: false</> in your <fg=cyan>%configurationFile%</>.'
		);

		return [
			[
				false,
				[
					[
						'PHPDoc type array of property OverridingPropertyPhpDoc\Bar::$arrayClassStrings is not covariant with PHPDoc type array<class-string> of overridden property OverridingPropertyPhpDoc\Foo::$arrayClassStrings.',
						26,
						$tip,
					],
					[
						'PHPDoc type int of property OverridingPropertyPhpDoc\Bar::$string is not covariant with PHPDoc type string of overridden property OverridingPropertyPhpDoc\Foo::$string.',
						29,
						$tip,
					],
				],
			],
			[
				true,
				[
					[
						'PHPDoc type array<class-string> of property OverridingPropertyPhpDoc\Bar::$array is not the same as PHPDoc type array of overridden property OverridingPropertyPhpDoc\Foo::$array.',
						23,
						$tipWithOption,
					],
					[
						'PHPDoc type array of property OverridingPropertyPhpDoc\Bar::$arrayClassStrings is not the same as PHPDoc type array<class-string> of overridden property OverridingPropertyPhpDoc\Foo::$arrayClassStrings.',
						26,
						$tip,
					],
					[
						'PHPDoc type int of property OverridingPropertyPhpDoc\Bar::$string is not the same as PHPDoc type string of overridden property OverridingPropertyPhpDoc\Foo::$string.',
						29,
						$tip,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRulePHPDocTypes
	 * @param mixed[] $errors
	 */
	public function testRulePHPDocTypes(bool $reportMaybes, array $errors): void
	{
		$this->reportMaybes = $reportMaybes;
		$this->analyse([__DIR__ . '/data/overriding-property-phpdoc.php'], $errors);
	}

}
