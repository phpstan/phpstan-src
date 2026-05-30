<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRule>
 */
class ReadOnlyPropertyAssignRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyAssignRule(
			new PropertyReflectionFinder(),
			new ConstructorsHelper(
				self::getContainer(),
				[
					'ReadonlyPropertyAssign\\TestCase::setUp',
				],
			),
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$errors = [
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
		];

		if (PHP_VERSION_ID < 80400) {
			// Since PHP 8.4, readonly is implicitly protected(set),
			// so child classes may initialize the property.
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				33,
			];
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				34,
			];
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				39,
			];
			// reported by AccessPropertiesInAssignRule on 8.4+
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				46,
			];
		} else {
			// On PHP 8.4+ the assignment is allowed by visibility rules,
			// but still has to happen in a constructor of the child class.
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of the constructor.',
				39,
			];
		}

		$errors = array_merge($errors, [
			[
				'Readonly property ReadonlyPropertyAssign\FooArrays::$details is assigned outside of the constructor.',
				64,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooArrays::$details is assigned outside of the constructor.',
				65,
			],
			[
				'Readonly property ReadonlyPropertyAssign\NotThis::$foo is not assigned on $this.',
				90,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				102,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				103,
			],
			[
				'Readonly property ReadonlyPropertyAssign\PostInc::$foo is assigned outside of the constructor.',
				105,
			],
			[
				'Readonly property ReadonlyPropertyAssign\ListAssign::$foo is assigned outside of the constructor.',
				122,
			],
			[
				'Readonly property ReadonlyPropertyAssign\ListAssign::$foo is assigned outside of the constructor.',
				127,
			],
			/*[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$name is assigned outside of the constructor.',
				140,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$value is assigned outside of the constructor.',
				141,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$name is assigned outside of its declaring class.',
				151,
			],
			[
				'Readonly property ReadonlyPropertyAssign\FooEnum::$value is assigned outside of its declaring class.',
				152,
			],*/
		]);

		if (PHP_VERSION_ID < 80400) {
			// reported by AccessPropertiesInAssignRule on 8.4+
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				162,
			];
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				163,
			];
		}

		$errors[] = [
			'Readonly property ReadonlyPropertyAssign\ArrayAccessPropertyFetch::$storage is assigned outside of the constructor.',
			212,
		];

		$this->analyse([__DIR__ . '/data/readonly-assign.php'], $errors);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testFeature7648(): void
	{
		$this->analyse([__DIR__ . '/data/feature-7648.php'], [
			[
				'Readonly property Feature7648\Request::$offset is assigned outside of the constructor.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testReadOnlyClasses(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-class-assign.php'], [
			[
				'Readonly property ReadonlyClassPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6773(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6773.php'], [
			[
				'Readonly property Bug6773\Repository::$data is assigned outside of the constructor.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug8929(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8929.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug12537(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12537.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testCloneWith(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-property-assign-clone-with.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug11495(): void
	{
		if (PHP_VERSION_ID < 80300) {
			$errors = [
				[
					'Readonly property Bug11495\HelloWorld::$foo is assigned outside of the constructor.',
					17,
				],
				[
					'Readonly property Bug11495\HelloWorld::$foo is assigned outside of the constructor.',
					20,
				],
				[
					'Readonly property Bug11495\DoubleAssign::$foo is assigned outside of the constructor.',
					40,
				],
				[
					'Readonly property Bug11495\DoubleAssign::$foo is assigned outside of the constructor.',
					41,
				],
				[
					'Readonly property Bug11495\BranchedAssign::$foo is assigned outside of the constructor.',
					57,
				],
				[
					'Readonly property Bug11495\BranchedAssign::$foo is assigned outside of the constructor.',
					59,
				],
				[
					'Readonly property Bug11495\ConditionalThenAssign::$foo is assigned outside of the constructor.',
					76,
				],
				[
					'Readonly property Bug11495\ConditionalThenAssign::$foo is assigned outside of the constructor.',
					78,
				],
			];
		} else {
			$errors = [
				[
					'Readonly property Bug11495\HelloWorld::$foo is not assigned on $this.',
					20,
				],
				[
					'Readonly property Bug11495\DoubleAssign::$foo is already assigned.',
					41,
				],
				[
					'Readonly property Bug11495\ConditionalThenAssign::$foo is already assigned.',
					78,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/bug-11495.php'], $errors);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug12871(): void
	{
		// The private(set) assignment in a subclass is reported by AccessPropertiesInAssignRule,
		// so this rule only reports the write outside of a constructor.
		$this->analyse([__DIR__ . '/data/bug-12871.php'], [
			[
				'Readonly property Bug12871\A::$foo is assigned outside of the constructor.',
				54,
			],
		]);
	}

}
