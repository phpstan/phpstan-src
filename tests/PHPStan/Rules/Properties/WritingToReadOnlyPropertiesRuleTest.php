<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<WritingToReadOnlyPropertiesRule>
 */
class WritingToReadOnlyPropertiesRuleTest extends RuleTestCase
{

	private bool $checkThisOnly;

	protected function getRule(): Rule
	{
		return new WritingToReadOnlyPropertiesRule(new RuleLevelHelper(self::createReflectionProvider(), true, false, true, false, false, false, true), new PropertyDescriptor(), new PropertyReflectionFinder(), $this->checkThisOnly);
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
		$errors = [];
		if (PHP_VERSION_ID < 80200) {
			$errors = [
				[
					'Property ConflictingAnnotationProperty\PropertyWithAnnotation::$test is not writable.',
					27,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/conflicting-annotation-property.php'], $errors);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPropertyHooks(): void
	{
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

	#[RequiresPhp('>= 8.4')]
	public function testBug12553(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/../Variables/data/bug-12553.php'], []);
	}

	public function testPrivatePropertyTagRead(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/private-property-tag-read.php'], [
			[
				'Property PrivatePropertyTagRead\Foo::$foo is not writable.',
				22,
			],
		]);
	}

	public function testBug11241(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-11241.php'], [
			[
				'Property Bug11241\MagicProp::$id is not writable.',
				27,
			],
			[
				'Property Bug11241\ActualProp::$id is not writable.',
				30,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug13530(): void
	{
		$this->checkThisOnly = false;
		$this->analyse([__DIR__ . '/data/bug-13530.php'], []);
	}

}
