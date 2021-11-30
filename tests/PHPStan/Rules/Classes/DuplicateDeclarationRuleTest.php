<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<\PHPStan\Rules\Classes\DuplicateDeclarationRule>
 */
class DuplicateDeclarationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new DuplicateDeclarationRule();
	}

	public function testDuplicateDeclarations(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

		$this->analyse(
			[
				__DIR__ . '/data/duplicate-declarations.php',
			],
			[
				[
					'Cannot redeclare constant DuplicateDeclarations\Foo::CONST1.',
					8,
				],
				[
					'Cannot redeclare constant DuplicateDeclarations\Foo::CONST2.',
					10,
				],
				[
					'Cannot redeclare property DuplicateDeclarations\Foo::$prop1.',
					17,
				],
				[
					'Cannot redeclare property DuplicateDeclarations\Foo::$prop2.',
					20,
				],
				[
					'Cannot redeclare method DuplicateDeclarations\Foo::func1().',
					27,
				],
				[
					'Cannot redeclare method DuplicateDeclarations\Foo::Func1().',
					35,
				],
			]
		);
	}

	public function testDuplicatePromotedProperty(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

		$this->analyse([__DIR__ . '/data/duplicate-promoted-property.php'], [
			[
				'Cannot redeclare property DuplicatedPromotedProperty\Foo::$foo.',
				11,
			],
			[
				'Cannot redeclare property DuplicatedPromotedProperty\Foo::$bar.',
				13,
			],
		]);
	}

	public function testDuplicateEnumCase(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

		$this->analyse([__DIR__ . '/data/duplicate-enum-cases.php'], [
			[
				'Cannot redeclare enum case DuplicatedEnumCase\Foo::BAR.',
				9,
			],
		]);
	}

}
