<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnsetCastRule>
 */
class UnsetCastRuleTest extends RuleTestCase
{

	private int $phpVersion;

	protected function getRule(): Rule
	{
		return new UnsetCastRule(new PhpVersion($this->phpVersion));
	}

	public function dataRule(): array
	{
		return [
			[
				70400,
				[],
			],
			[
				80000,
				[
					[
						'The (unset) cast is no longer supported in PHP 8.0 and later.',
						6,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param mixed[] $errors
	 */
	public function testRule(int $phpVersion, array $errors): void
	{
		$this->phpVersion = $phpVersion;
		$this->analyse([__DIR__ . '/data/unset-cast.php'], $errors);
	}

}
