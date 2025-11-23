<?php declare(strict_types = 1);

namespace PHPStan\Rules\Namespaces;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ExistingNamesInUseRule>
 */
class ExistingNamesInUseRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new ExistingNamesInUseRule(
			$reflectionProvider,
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			true,
			true,
		);
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

	public function testPhpstanInternalClass(): void
	{
		$tip = 'This is most likely unintentional. Did you mean to type \PrefixedRuntimeException?';

		$this->analyse([__DIR__ . '/../Classes/data/phpstan-internal-class.php'], [
			[
				'Referencing prefixed PHPStan class: _PHPStan_156ee64ba\PrefixedRuntimeException.',
				14,
				$tip,
			],
		]);
	}

}
