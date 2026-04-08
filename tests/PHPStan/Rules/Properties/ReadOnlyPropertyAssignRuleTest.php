<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

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
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$errors = [
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				33,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				34,
			],
			[
				'Readonly property ReadonlyPropertyAssign\Foo::$bar is assigned outside of its declaring class.',
				39,
			],
		];

		if (PHP_VERSION_ID < 80400) {
			// reported by AccessPropertiesInAssignRule on 8.4+
			$errors[] = [
				'Readonly property ReadonlyPropertyAssign\Foo::$baz is assigned outside of its declaring class.',
				46,
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

	#[RequiresPhp('>= 8.1')]
	public function testFeature7648(): void
	{
		$this->analyse([__DIR__ . '/data/feature-7648.php'], [
			[
				'Readonly property Feature7648\Request::$offset is assigned outside of the constructor.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testReadOnlyClasses(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-class-assign.php'], [
			[
				'Readonly property ReadonlyClassPropertyAssign\Foo::$foo is assigned outside of the constructor.',
				21,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug6773(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6773.php'], [
			[
				'Readonly property Bug6773\Repository::$data is assigned outside of the constructor.',
				16,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8929(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8929.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug12537(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12537.php'], []);
	}

	#[RequiresPhp('>= 8.5')]
	public function testCloneWith(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-property-assign-clone-with.php'], []);
	}

}
