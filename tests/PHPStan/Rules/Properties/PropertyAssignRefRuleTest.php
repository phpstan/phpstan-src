<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyAssignRefRule>
 */
class PropertyAssignRefRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertyAssignRefRule(new PhpVersion(PHP_VERSION_ID), new PropertyReflectionFinder());
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/property-assign-ref.php'], [
			[
				'Property PropertyAssignRef\Foo::$foo with private visibility is assigned by reference.',
				25,
			],
			[
				'Property PropertyAssignRef\Foo::$bar with protected(set) visibility is assigned by reference.',
				26,
			],
			[
				'Property PropertyAssignRef\Baz::$a with protected visibility is assigned by reference.',
				41,
			],
			[
				'Property PropertyAssignRef\Baz::$b with private visibility is assigned by reference.',
				42,
			],
		]);
	}

	public function testAsymmetricVisibility(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/property-assign-ref-asymmetric.php'], [
			[
				'Property PropertyAssignRefAsymmetric\Foo::$a with private(set) visibility is assigned by reference.',
				28,
			],
			[
				'Property PropertyAssignRefAsymmetric\Foo::$a with private(set) visibility is assigned by reference.',
				36,
			],
			[
				'Property PropertyAssignRefAsymmetric\Foo::$b with protected(set) visibility is assigned by reference.',
				37,
			],
		]);
	}

}
