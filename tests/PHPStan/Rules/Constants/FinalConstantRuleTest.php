<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FinalConstantRule>
 */
class FinalConstantRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new FinalConstantRule(new PhpVersion($this->phpVersionId));
	}

	public function dataRule(): array
	{
		return [
			[
				80000,
				[
					[
						'Final class constants are supported only on PHP 8.1 and later.',
						9,
					],
				],
			],
			[
				80100,
				[],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param mixed[] $errors
	 */
	public function testRule(int $phpVersionId, array $errors): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires static reflection.');
		}

		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/final-constant.php'], $errors);
	}

}
