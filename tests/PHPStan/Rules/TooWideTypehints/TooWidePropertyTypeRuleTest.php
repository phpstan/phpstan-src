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

	private bool $reportTooWideBool = false;

	protected function getRule(): Rule
	{
		return new TooWidePropertyTypeRule(
			new DirectReadWritePropertiesExtensionProvider([]),
			new PropertyReflectionFinder(),
			new TooWideTypeCheck($this->reportTooWideBool),
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

	#[RequiresPhp('>= 8.2')]
	public function testBug13384(): void
	{
		$this->reportTooWideBool = true;
		$this->analyse([__DIR__ . '/data/bug-13384.php'], [
			[
				'Static property Bug13384\ShutdownHandlerFalseDefault::$registered (bool) is never assigned true so the property type can be changed to false.',
				9,
			],
			[
				'Static property Bug13384\ShutdownHandlerTrueDefault::$registered (bool) is never assigned false so the property type can be changed to true.',
				34,
			],
		]);
	}

	#[RequiresPhp('< 8.2')]
	public function testBug13384PrePhp82(): void
	{
		$this->reportTooWideBool = true;
		$this->analyse([__DIR__ . '/data/bug-13384.php'], []);
	}

	public function testBug13384Phpdoc(): void
	{
		$this->reportTooWideBool = true;
		$this->analyse([__DIR__ . '/data/bug-13384-phpdoc.php'], [
			[
				'Static property Bug13384Phpdoc\ShutdownHandlerPhpdocTypes::$registered (bool) is never assigned true so the property type can be changed to false.',
				12,
			],
		]);
	}

	public function testBug13384b(): void
	{
		$this->reportTooWideBool = true;
		$this->analyse([__DIR__ . '/data/bug-13384b.php'], []);
	}

	public function testBug13384bOff(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13384b.php'], []);
	}

	public function testBugPR4318(): void
	{
		$this->reportTooWideBool = true;
		$this->analyse([__DIR__ . '/data/bug-pr-4318.php'], []);
	}

}
