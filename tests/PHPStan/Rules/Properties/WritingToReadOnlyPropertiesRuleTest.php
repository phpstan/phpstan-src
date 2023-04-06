<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<WritingToReadOnlyPropertiesRule>
 */
class WritingToReadOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new WritingToReadOnlyPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false), new PropertyDescriptor(), new PropertyReflectionFinder(), $this->checkThisOnly);
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
				'Property stdClass::$foo is not writable.',
				18,
			],
		]);
	}

}
