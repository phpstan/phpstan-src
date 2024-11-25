<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertiesInInterfaceRule>
 */
class PropertiesInInterfaceRuleTest extends RuleTestCase
{

	private int $phpVersion = PHP_VERSION_ID;

	protected function getRule(): Rule
	{
		return new PropertiesInInterfaceRule(new PhpVersion($this->phpVersion));
	}

	public function testPhp83AndPropertiesInInterface(): void
	{
		$this->phpVersion = 80300;

		$this->analyse([__DIR__ . '/data/properties-in-interface.php'], [
			[
				'Interfaces may not include properties.',
				7,
			],
			[
				'Interfaces may not include properties.',
				9,
			],
		]);
	}

	public function testPhp83AndPropertyHooksInInterface(): void
	{
		$this->phpVersion = 80300;

		$this->analyse([__DIR__ . '/data/property-hooks-in-interface.php'], [
			[
				'Interfaces may not include properties.',
				7,
			],
			[
				'Interfaces may not include properties.',
				9,
			],
		]);
	}

	public function testPhp84AndPropertiesInInterface(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/properties-in-interface.php'], [
			[
				'Interfaces may only include hooked properties.',
				7,
			],
			[
				'Interfaces may only include hooked properties.',
				9,
			],
		]);
	}

	public function testPhp84AndNonPublicPropertyHooksInInterface(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/property-hooks-visibility-in-interface.php'], [
			[
				'Interfaces may not include non-public properties.',
				7,
			],
			[
				'Interfaces may not include non-public properties.',
				9,
			],
		]);
	}

	public function testPhp84AndPropertyHooksWithBodiesInInterface(): void
	{
		$this->phpVersion = 80400;

		$this->analyse([__DIR__ . '/data/property-hooks-bodies-in-interface.php'], [
			[
				'Interfaces may not include property hooks with bodies.',
				7,
			],
			[
				'Interfaces may not include property hooks with bodies.',
				13,
			],
		]);
	}

}
