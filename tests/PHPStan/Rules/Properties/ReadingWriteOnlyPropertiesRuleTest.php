<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReadingWriteOnlyPropertiesRule>
 */
class ReadingWriteOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new ReadingWriteOnlyPropertiesRule(new PropertyDescriptor(), new PropertyReflectionFinder(), new RuleLevelHelper(self::createReflectionProvider(), true, $this->checkThisOnly, true, false, false, false, true), $this->checkThisOnly);
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

	#[RequiresPhp('>= 8.4')]
	public function testPropertyHooks(): void
	{
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
