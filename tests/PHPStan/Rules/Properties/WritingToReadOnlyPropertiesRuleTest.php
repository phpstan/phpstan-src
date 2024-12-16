<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<WritingToReadOnlyPropertiesRule>
 */
class WritingToReadOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new WritingToReadOnlyPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, false), new PropertyDescriptor(), new PropertyReflectionFinder(), $this->checkThisOnly);
	}

	public function testCheckThisOnlyProperties(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				20,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				21,
			],
		]);
	}

	public function testCheckAllProperties(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				20,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				21,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				30,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				31,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				43,
			],
		]);
	}

	public function testObjectShapes(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/properties-object-shapes.php'], [
			[
				'Property object{foo: int, bar?: string}::$foo is not writable.',
				18,
			],
			[
				'Property object{foo: int}|stdClass::$foo is not writable.',
				42,
			],
		]);
	}

	public function testConflictingAnnotationProperty(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/conflicting-annotation-property.php'], [
			/*[
				'Property ConflictingAnnotationProperty\PropertyWithAnnotation::$test is not writable.',
				27,
			],*/
		]);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-hooked-properties.php'], [
			[
				'Property WritingToReadOnlyHookedProperties\Foo::$i is not writable.',
				16,
			],
			[
				'Property WritingToReadOnlyHookedProperties\Bar::$i is not writable.',
				32,
			],
		]);
	}

}
