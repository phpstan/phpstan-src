<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Methods\OverridingMethodCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OverridingPropertyRule>
 */
class OverridingPropertyRuleTest extends RuleTestCase
{

	private bool $reportMaybes;

	private ?bool $checkMissingOverridePropertyAttribute = null;

	private bool $checkMissingOverrideMethodAttribute = false;

	protected function getRule(): Rule
	{
		$phpVersion = self::getContainer()->getByType(PhpVersion::class);

		return new OverridingPropertyRule(
			$phpVersion,
			new OverridingMethodCheck(),
			new MethodParameterComparisonHelper($phpVersion),
			true,
			$this->reportMaybes,
			$this->checkMissingOverridePropertyAttribute,
			$this->checkMissingOverrideMethodAttribute,
		);
	}

	public function testRule(): void
	{
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
					'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
				),
			],
		]);
	}

	public static function dataRulePHPDocTypes(): array
	{
		$tip = sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
		);
		$tipWithOption = sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s\n   This error can be turned off by setting\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
			'<fg=cyan>reportMaybesInPropertyPhpDocTypes: false</> in your <fg=cyan>%configurationFile%</>.',
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
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRulePHPDocTypes')]
	public function testRulePHPDocTypes(bool $reportMaybes, array $errors): void
	{
		$this->reportMaybes = $reportMaybes;
		$this->analyse([__DIR__ . '/data/overriding-property-phpdoc.php'], $errors);
	}

	public function testBug7839(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-7839.php'], []);
	}

	public function testBug7692(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-7692.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testFinal(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-final-property.php'], [
			[
				'Property OverridingFinalProperty\Bar::$a overrides final property OverridingFinalProperty\Foo::$a.',
				27,
			],
			[
				'Property OverridingFinalProperty\Bar::$b overrides final property OverridingFinalProperty\Foo::$b.',
				29,
			],
			[
				'Property OverridingFinalProperty\Bar::$c overrides final property OverridingFinalProperty\Foo::$c.',
				31,
			],
			[
				'Property OverridingFinalProperty\Bar::$d overrides final property OverridingFinalProperty\Foo::$d.',
				33,
			],
			[
				'Property OverridingFinalProperty\Bar::$e overrides @final property OverridingFinalProperty\Foo::$e.',
				35,
			],
			[
				'Property OverridingFinalProperty\Bar::$f overrides @final property OverridingFinalProperty\Foo::$f.',
				37,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyPrototypeFromInterface(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/property-prototype-from-interface.php'], [
			[
				'Type string of property Bug12466\Bar::$a is not the same as type int of overridden property Bug12466\Foo::$a.',
				15,
			],
			[
				'Property Bug12466\TestMoreProps::$a overriding writable property Bug12466\MoreProps::$a also has to be writable.',
				34,
			],
			[
				'Property Bug12466\TestMoreProps::$b overriding readable property Bug12466\MoreProps::$b also has to be readable.',
				41,
			],
			[
				'Property Bug12466\TestMoreProps::$c overriding writable property Bug12466\MoreProps::$c also has to be writable.',
				48,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug12466(): void
	{
		$tip = sprintf(
			"You can fix 3rd party PHPDoc types with stub files:\n   %s",
			'<fg=cyan>https://phpstan.org/user-guide/stub-files</>',
		);

		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-12466.php'], [
			[
				'Type int|string|null of property Bug12466OverridenProperty\Baz::$onlyGet is not covariant with type int|string of overridden property Bug12466OverridenProperty\Foo::$onlyGet.',
				34,
			],
			[
				'Type int of property Bug12466OverridenProperty\Baz::$onlySet is not contravariant with type int|string of overridden property Bug12466OverridenProperty\Foo::$onlySet.',
				40,
			],
			[
				'PHPDoc type array<int|string|null> of property Bug12466OverridenProperty\BazWithPhpDocs::$onlyGet is not covariant with PHPDoc type array<int|string> of overridden property Bug12466OverridenProperty\FooWithPhpDocs::$onlyGet.',
				82,
				$tip,
			],
			[
				'PHPDoc type array<int> of property Bug12466OverridenProperty\BazWithPhpDocs::$onlySet is not contravariant with PHPDoc type array<int|string> of overridden property Bug12466OverridenProperty\FooWithPhpDocs::$onlySet.',
				89,
				$tip,
			],
		]);
	}

	public function testBug11761(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-11761.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug12586(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-12586.php'], [
			[
				'Readonly property Bug12586\FooImpl::$baz overrides readwrite property Bug12586\Foo::$baz.',
				23,
			],
		]);
	}

	public static function dataOverrideAttribute(): iterable
	{
		$errors = [
			[
				'Property PropertyOverrideAttr\Lorem::$foo overrides property PropertyOverrideAttr\Foo::$foo but is missing the #[\Override] attribute.',
				44,
			],
		];
		// the default follows checkMissingOverrideMethodAttribute only when the analysed PHP version supports the attribute,
		// OverridingPropertyRuleConfigPhpTest covers PHP version ranges
		yield [null, false, []];
		yield [null, true, PHP_VERSION_ID >= 80500 ? $errors : []];
		yield [true, false, $errors];
		yield [true, true, $errors];
		yield [false, false, []];
		yield [false, true, []];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	#[DataProvider('dataOverrideAttribute')]
	public function testOverrideAttribute(?bool $checkMissingOverridePropertyAttribute, bool $checkMissingOverrideMethodAttribute, array $expectedErrors): void
	{
		$this->checkMissingOverridePropertyAttribute = $checkMissingOverridePropertyAttribute;
		$this->checkMissingOverrideMethodAttribute = $checkMissingOverrideMethodAttribute;
		$this->reportMaybes = true;
		$errors = [
			[
				'Property PropertyOverrideAttr\Bar::$bar has #[\Override] attribute but does not override any property.',
				19,
			],
			[
				'Property PropertyOverrideAttr\Baz::$bar has #[\Override] attribute but does not override any property.',
				30,
			],
		];
		$this->analyse([__DIR__ . '/data/property-override-attr.php'], array_merge($errors, $expectedErrors));
	}

	public function testFixMissingOverrideAttribute(): void
	{
		$this->checkMissingOverridePropertyAttribute = true;
		$this->reportMaybes = true;
		$this->fix(__DIR__ . '/data/property-override-attr-missing.php', __DIR__ . '/data/property-override-attr-missing.php.fixed');
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug14457(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/bug-14457.php'], [
			[
				'Cannot make non-abstract get hook for property Bug14457Property\ParentClass::$bar abstract in class Bug14457Property\ChildClass.',
				10,
			],
			[
				'Cannot make non-abstract set hook for property Bug14457Property\SetParent::$setProp abstract in class Bug14457Property\SetChild.',
				42,
			],
			[
				'Cannot make non-abstract get hook for property Bug14457Property\BothParent::$bothProp abstract in class Bug14457Property\BothChild.',
				54,
			],
			[
				'Cannot make non-abstract set hook for property Bug14457Property\BothParent::$bothProp abstract in class Bug14457Property\BothChild.',
				54,
			],
		]);
	}

	public function testEquivalentTypes(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-property-equivalent-types.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testMultiplePrototypes(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-property-multiple-prototypes.php'], [
			[
				'Property OverridingPropertyMultiplePrototypes\GetOnly::$prop overriding writable property OverridingPropertyMultiplePrototypes\HasSet::$prop also has to be writable.',
				29,
			],
			[
				'Type int of property OverridingPropertyMultiplePrototypes\WrongType::$prop is not covariant with type string of overridden property OverridingPropertyMultiplePrototypes\HasStringGet::$prop.',
				38,
			],
			[
				'Property OverridingPropertyMultiplePrototypes\ChildImplementingInterface::$prop overriding writable property OverridingPropertyMultiplePrototypes\HasSet::$prop also has to be writable.',
				54,
			],
			[
				'Property OverridingPropertyMultiplePrototypes\ImplementsExtendsBoth::$prop overriding readable property OverridingPropertyMultiplePrototypes\HasGet::$prop also has to be readable.',
				82,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testSetVisibility(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-property-set-visibility.php'], [
			[
				'Private(set) property OverridingPropertySetVisibility\B::$protectedSetToPrivateSet overriding protected(set) property OverridingPropertySetVisibility\A::$protectedSetToPrivateSet should be protected(set) or public(set).',
				37,
			],
			[
				'Private(set) property OverridingPropertySetVisibility\B::$publicToPrivateSet overriding public(set) property OverridingPropertySetVisibility\A::$publicToPrivateSet should also be public(set).',
				39,
			],
			[
				'Protected(set) property OverridingPropertySetVisibility\B::$publicToProtectedSet overriding public(set) property OverridingPropertySetVisibility\A::$publicToProtectedSet should also be public(set).',
				41,
			],
			[
				'Private(set) property OverridingPropertySetVisibility\B::$protectedToProtectedPrivateSet overriding protected(set) property OverridingPropertySetVisibility\A::$protectedToProtectedPrivateSet should be protected(set) or public(set).',
				45,
			],
			[
				'Private(set) property OverridingPropertySetVisibility\B::$protectedToPublicPrivateSet overriding protected(set) property OverridingPropertySetVisibility\A::$protectedToPublicPrivateSet should be protected(set) or public(set).',
				49,
			],
			[
				'Private(set) property OverridingPropertySetVisibility\B::$readonlyToPrivateSetReadonly overriding protected(set) property OverridingPropertySetVisibility\A::$readonlyToPrivateSetReadonly should be protected(set) or public(set).',
				53,
			],
			[
				'Private(set) property OverridingPropertySetVisibility\ImplementsGetOnly::$getAndSet overriding public(set) property OverridingPropertySetVisibility\GetOnly::$getAndSet should also be public(set).',
				77,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testHooks(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-property-hooks.php'], [
			[
				'Get hook for property OverridingPropertyHooks\FinalHookChild::$prop overrides final get hook for property OverridingPropertyHooks\FinalHookParent::$prop.',
				26,
			],
			[
				'Set hook for property OverridingPropertyHooks\FinalHookChild::$setProp overrides final set hook for property OverridingPropertyHooks\FinalHookParent::$setProp.',
				30,
			],
			[
				'Get hook for property OverridingPropertyHooks\ImplementsByRefGetByValue::$prop overriding get hook for property OverridingPropertyHooks\ByRefGet::$prop that returns by reference should also return by reference.',
				52,
			],
			[
				'Get hook of backed property OverridingPropertyHooks\BackedChild::$prop with set hook cannot return by reference.',
				100,
			],
			[
				'Type string of property OverridingPropertyHooks\ImplementsSetWider::$prop is not contravariant with set hook parameter type int|string of overridden property OverridingPropertyHooks\SetWider::$prop.',
				128,
			],
			[
				'Parameter #1 $prop (OverridingPropertyHooks\Y) of set hook for property OverridingPropertyHooks\SetParameterChild::$prop is not contravariant with parameter #1 $prop (OverridingPropertyHooks\X) of set hook for property OverridingPropertyHooks\SetParameterParent::$prop.',
				178,
			],
			[
				'Get hook for property OverridingPropertyHooks\OverrideChild::$setOnly has #[\Override] attribute but does not override any get hook.',
				208,
			],
			[
				'Set hook for property OverridingPropertyHooks\OverrideChild::$getOnly has #[\Override] attribute but does not override any set hook.',
				215,
			],
			[
				'Get hook for property OverridingPropertyHooks\OverrideChild::$notInParent has #[\Override] attribute but does not override any get hook.',
				227,
			],
			[
				'Readonly property OverridingPropertyHooks\ReadonlyOverridesVirtualGet::$prop overrides readwrite property OverridingPropertyHooks\VirtualGetParent::$prop.',
				245,
			],
			[
				'Readonly property OverridingPropertyHooks\ReadonlyOverridesVirtualGetInAbstractClass::$prop overrides readwrite property OverridingPropertyHooks\AbstractClassVirtualGetParent::$prop.',
				261,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testHookDeclarationErrorsAreNotRepeated(): void
	{
		$this->reportMaybes = true;
		$this->analyse([__DIR__ . '/data/overriding-property-hooks-invalid-declaration.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testHooksMissingOverride(): void
	{
		$this->reportMaybes = true;
		$this->checkMissingOverridePropertyAttribute = false;
		$this->checkMissingOverrideMethodAttribute = true;
		$this->analyse([__DIR__ . '/data/overriding-property-hooks-missing-override.php'], [
			[
				'Set hook for property OverridingPropertyHooksMissingOverride\Child::$both overrides set hook for property OverridingPropertyHooksMissingOverride\ParentClass::$both but is missing the #[\Override] attribute.',
				27,
			],
			[
				'Get hook for property OverridingPropertyHooksMissingOverride\Child::$getOnly overrides get hook for property OverridingPropertyHooksMissingOverride\ParentClass::$getOnly but is missing the #[\Override] attribute.',
				31,
			],
			[
				'Get hook for property OverridingPropertyHooksMissingOverride\ImplementsInterface::$prop overrides get hook for property OverridingPropertyHooksMissingOverride\HasHook::$prop but is missing the #[\Override] attribute.',
				51,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testFixHookOverrideAttribute(): void
	{
		$this->reportMaybes = true;
		$this->checkMissingOverridePropertyAttribute = false;
		$this->checkMissingOverrideMethodAttribute = true;
		$this->fix(__DIR__ . '/data/property-hook-override-attr.php', __DIR__ . '/data/property-hook-override-attr.php.fixed');
	}

}
