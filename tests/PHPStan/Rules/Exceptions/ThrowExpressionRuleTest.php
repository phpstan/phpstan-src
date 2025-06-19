<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<ThrowExpressionRule>
 */
class ThrowExpressionRuleTest extends RuleTestCase
{

	private PhpVersion $phpVersion;

	protected function getRule(): Rule
	{
		return new ThrowExpressionRule($this->phpVersion);
	}

	public static function dataRule(): array
	{
		return [
			[
				70400,
				[
					[
						'Throw expression is supported only on PHP 8.0 and later.',
						10,
					],
				],
			],
			[
				80000,
				[],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataRule')]
	public function testRule(int $phpVersion, array $expectedErrors): void
	{
		$this->phpVersion = new PhpVersion($phpVersion);
		$this->analyse([__DIR__ . '/data/throw-expr.php'], $expectedErrors);
	}

}
