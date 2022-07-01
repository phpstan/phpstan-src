<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FinalPrivateMethodRule> */
class FinalPrivateMethodRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new FinalPrivateMethodRule(
			new PhpVersion($this->phpVersionId),
		);
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
						'Private method FinalPrivateMethod\Foo::foo() cannot be final as it is never overridden by other classes.',
						8,
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
		$this->phpVersionId = $phpVersion;
		$this->analyse([__DIR__ . '/data/final-private-method.php'], $errors);
	}

}
