<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadingWriteOnlyPropertiesRule>
 */
class ReadingWriteOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new ReadingWriteOnlyPropertiesRule(new PropertyDescriptor(), new PropertyReflectionFinder(), new RuleLevelHelper($this->createReflectionProvider(), true, $this->checkThisOnly, true, false, false, false), $this->checkThisOnly);
	}

	public function testPropertyMustBeReadableInAssignOp(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				27,
			],
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				40,
			],
		]);
	}

	public function testPropertyMustBeReadableInAssignOpCheckThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/writing-to-read-only-properties.php'], [
			[
				'Property WritingToReadOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				27,
			],
		]);
	}

	public function testReadingWriteOnlyProperties(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				23,
			],
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				29,
			],
		]);
	}

	public function testReadingWriteOnlyPropertiesCheckThisOnly(): void
	{
		$this->checkThisOnly = true;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				23,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/reading-write-only-properties-nullsafe.php'], [
			[
				'Property ReadingWriteOnlyProperties\Foo::$writeOnlyProperty is not readable.',
				10,
			],
		]);
	}

	public function testConflictingAnnotationProperty(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/conflicting-annotation-property.php'], []);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/reading-write-only-hooked-properties.php'], [
			[
				'Property ReadingWriteOnlyHookedProperties\Foo::$i is not readable.',
				16,
			],
			[
				'Property ReadingWriteOnlyHookedProperties\Bar::$i is not readable.',
				34,
			],
		]);
	}

}
