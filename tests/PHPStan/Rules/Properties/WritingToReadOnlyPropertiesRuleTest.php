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
		return new WritingToReadOnlyPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false), new PropertyDescriptor(), new PropertyReflectionFinder(), $this->checkThisOnly);
	}

	public function testCheckThisOnlyProperties(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				18,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				19,
			],
		]);
	}

	public function testCheckAllProperties(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				18,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				19,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				28,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				29,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				38,
			],
		]);
	}

}
