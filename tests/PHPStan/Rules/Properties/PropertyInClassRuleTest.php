<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyInClassRule>
 */
class PropertyInClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertyInClassRule(new PhpVersion(PHP_VERSION_ID));
	}

	public function testPhpLessThan84AndHookedPropertiesInClass(): void
	{
		if (PHP_VERSION_ID >= 80400) {
			$this->markTestSkipped('Test requires PHP 8.3 or earlier.');
		}
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Property hooks cause syntax error on PHP 7.4');
		}

		$this->analyse([__DIR__ . '/data/hooked-properties-in-class.php'], [
			[
				'Property hooks are supported only on PHP 8.4 and later.',
				7,
			],
		]);
	}

	public function testPhp84AndHookedPropertiesWithoutBodiesInClass(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/hooked-properties-without-bodies-in-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
		]);
	}

	public function testPhp84AndNonAbstractHookedPropertiesInClass(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
		]);
	}

	public function testPhp84AndAbstractHookedPropertiesInClass(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/abstract-hooked-properties-in-class.php'], [
			[
				'Non-abstract classes cannot include abstract properties.',
				7,
			],
			[
				'Non-abstract classes cannot include abstract properties.',
				9,
			],
		]);
	}

	public function testPhp84AndNonAbstractHookedPropertiesInAbstractClass(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-abstract-class.php'], [
			[
				'Non-abstract properties cannot include hooks without bodies.',
				7,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				9,
			],
			[
				'Non-abstract properties cannot include hooks without bodies.',
				25,
			],
		]);
	}

	public function testPhp84AndAbstractNonHookedPropertiesInAbstractClass(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/abstract-non-hooked-properties-in-abstract-class.php'], [
			[
				'Only hooked properties can be declared abstract.',
				7,
			],
			[
				'Only hooked properties can be declared abstract.',
				9,
			],
		]);
	}

	public function testPhp84AndAbstractHookedPropertiesWithBodies(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4 or later.');
		}

		$this->analyse([__DIR__ . '/data/abstract-hooked-properties-with-bodies.php'], [
			[
				'Abstract properties must specify at least one abstract hook.',
				7,
			],
			[
				'Abstract properties must specify at least one abstract hook.',
				12,
			],
		]);
	}

}
