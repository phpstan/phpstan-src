<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ReadingWriteOnlyPropertiesRule>
 */
class ReadingWriteOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new ReadingWriteOnlyPropertiesRule(new PropertyDescriptor(), new PropertyReflectionFinder(), new RuleLevelHelper($this->createReflectionProvider(), true, $this->checkThisOnly, true, false, false), $this->checkThisOnly);
	}

	public function testPropertyMustBeReadableInAssignOp(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				25,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				35,
			],
		]);
	}

	public function testPropertyMustBeReadableInAssignOpCheckThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				25,
			],
		]);
	}

	public function testReadingWriteOnlyProperties(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				20,
			],
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				25,
			],
		]);
	}

	public function testReadingWriteOnlyPropertiesCheckThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				20,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties-nullsafe.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				9,
			],
		]);
	}

}
