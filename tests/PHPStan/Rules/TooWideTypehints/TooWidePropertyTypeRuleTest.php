<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\DirectReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWidePropertyTypeRule>
 */
class TooWidePropertyTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWidePropertyTypeRule(
			new DirectReadWritePropertiesExtensionProvider([]),
			new PropertyReflectionFinder(),
			new TooWideTypeCheck(),
		);
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-property-type.php'], [
			[
				'Property TooWidePropertyType\Foo::$foo (int|string) is never assigned string so it can be removed from the property type.',
				9,
			],
			/*[
				'Property TooWidePropertyType\Foo::$barr (int|null) is never assigned null so it can be removed from the property type.',
				15,
			],
			[
				'Property TooWidePropertyType\Foo::$barrr (int|null) is never assigned null so it can be removed from the property type.',
				18,
			],*/
			[
				'Property TooWidePropertyType\Foo::$baz (int|null) is never assigned null so it can be removed from the property type.',
				20,
			],
			[
				'Property TooWidePropertyType\Bar::$c (int|null) is never assigned int so it can be removed from the property type.',
				45,
			],
			[
				'Property TooWidePropertyType\Bar::$d (int|null) is never assigned null so it can be removed from the property type.',
				47,
			],
		]);
	}

	public function testBug11667(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11667.php'], []);
	}

}
