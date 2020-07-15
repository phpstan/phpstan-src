<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivatePropertyRule>
 */
class UnusedPrivatePropertyRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivatePropertyRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 70400 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 7.4 or static reflection.');
		}

		$this->analyse([__DIR__ . '/data/unused-private-property.php'], [
			[
				'Class UnusedPrivateProperty\Foo has a write-only property $bar.',
				10,
			],
			[
				'Class UnusedPrivateProperty\Foo has an unused property $baz.',
				12,
			],
			[
				'Class UnusedPrivateProperty\Foo has a read-only property $lorem.',
				14,
			],
			[
				'Class UnusedPrivateProperty\Bar has a read-only property $baz.',
				57,
			],
		]);
	}

}
