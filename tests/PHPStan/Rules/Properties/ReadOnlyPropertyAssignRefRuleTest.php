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

	#[RequiresPhp('>= 8.1')]
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

}
