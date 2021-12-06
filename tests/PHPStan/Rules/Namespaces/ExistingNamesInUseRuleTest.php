<?php declare(strict_types = 1);

namespace PHPStan\Rules\Namespaces;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExistingNamesInUseRule>
 */
class ExistingNamesInUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new ExistingNamesInUseRule($broker, new ClassCaseSensitivityCheck($broker, true), true);
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/uses-defined.php';
		$this->analyse([__DIR__ . '/data/uses.php'], [
			[
				'Used function Uses\bar not found.',
				7,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Used constant Uses\OTHER_CONSTANT not found.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function Uses\foo used with incorrect case: Uses\Foo.',
				9,
			],
			[
				'Interface Uses\Lorem referenced with incorrect case: Uses\LOREM.',
				10,
			],
			[
				'Class DateTime referenced with incorrect case: DATETIME.',
				11,
			],
		]);
	}

}
