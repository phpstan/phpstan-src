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

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		return new PropertyInClassRule(new PhpVersion($this->phpVersion));
	}

	public function testPhp84AndNonAbstractHookedPropertiesInClass(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-class.php'], [
			[
				'Classes may not include hooked properties without bodies.',
				7,
			],
			[
				'Classes may not include hooked properties without bodies.',
				9,
			],
		]);
	}

	public function testPhp84AndAbstractHookedPropertiesInClass(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/abstract-hooked-properties-in-class.php'], [
			[
				'Classes may not include abstract hooked properties.',
				7,
			],
			[
				'Classes may not include abstract hooked properties.',
				9,
			],
		]);
	}

	public function testPhp84AndNonAbstractHookedPropertiesInAbstractClass(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/non-abstract-hooked-properties-in-abstract-class.php'], [
			[
				'Abstract classes may not include non-abstract hooked properties without bodies.',
				7,
			],
			[
				'Abstract classes may not include non-abstract hooked properties without bodies.',
				9,
			],
		]);
	}

	public function testPhp84AndAbstractNonHookedPropertiesInAbstractClass(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/abstract-non-hooked-properties-in-abstract-class.php'], [
			[
				'Only hooked properties may be declared abstract.',
				7,
			],
			[
				'Only hooked properties may be declared abstract.',
				9,
			],
		]);
	}

	public function testPhp84AndAbstractHookedPropertiesWithBodies(): void
	{
		$this->phpVersion = 80400;

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
