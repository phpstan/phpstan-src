<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRefRule>
 */
class ReadOnlyPropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ReadOnlyPropertyAssignRefRule(new PropertyReflectionFinder());
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$errors = [
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$foo is assigned by reference.',
				14,
			],
			[
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				15,
			],
		];

		if (PHP_VERSION_ID < 80400) {
			// reported by PropertyAssignRefRule on 8.4+
			$errors[] = [
				'Readonly property ReadOnlyPropertyAssignRef\Foo::$bar is assigned by reference.',
				26,
			];
		}

		$this->analyse([__DIR__ . '/data/readonly-assign-ref.php'], $errors);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug14243(): void
	{
		$errors = [
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$promoted is assigned by reference.',
				19,
			],
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$params is assigned by reference.',
				26,
			],
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$params is assigned by reference.',
				27,
			],
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$nested is assigned by reference.',
				28,
			],
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$params is assigned by reference.',
				29,
			],
			[
				'Readonly property Bug14243\ReadonlyArrayProperties::$collection is assigned by reference.',
				31,
			],
			[
				'Readonly property Bug14243\ReadonlyClass::$params is assigned by reference.',
				46,
			],
		];

		if (PHP_VERSION_ID < 80400) {
			// reported by PropertyAssignRefRule on 8.4+
			$errors[] = [
				'Readonly property Bug14243\ReadonlyArrayProperties::$params is assigned by reference.',
				56,
			];
		}

		// maybe ArrayAccess: reported, as in PropertyAssignNode::isArrayAccessOffsetWrite()
		$errors[] = [
			'Readonly property Bug14243\ElementTargets::$union is assigned by reference.',
			91,
		];
		$errors[] = [
			'Readonly property Bug14243\ElementTargets::$roArray is assigned by reference.',
			92,
		];

		$this->analyse([__DIR__ . '/data/bug-14243.php'], $errors);
	}

}
