<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<WritingToReadOnlyPropertiesRule>
 */
class WritingToReadOnlyPropertiesRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkThisOnly;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new WritingToReadOnlyPropertiesRule(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false), new PropertyDescriptor(), new PropertyReflectionFinder(), $this->checkThisOnly);
	}

	public function testCheckThisOnlyProperties(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				15,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				16,
			],
		]);
	}

	public function testCheckAllProperties(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				15,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				16,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				25,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				26,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$readOnlyProperty is not writable.',
				35,
			],
		]);
	}

}
