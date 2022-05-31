<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class TraitExampleRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node\Stmt\Return_>
	 */
	protected function getRule(): Rule
	{
		return new TraitExampleRule();
	}

	public function dataRule(): iterable
	{
		yield [
			__DIR__ . '/data/trait-use.php',
			TraitExampleRule::ERROR_MESSAGE,
			[6],
		];
	}

	/**
	 * @param int[] $lines
	 * @dataProvider dataRule
	 */
	public function testRule(string $file, string $expectedError, array $lines): void
	{
		$errors = [];
		foreach ($lines as $line) {
			$errors[] = [$expectedError, $line];
		}
		$this->analyse([$file], $errors);
	}

}
