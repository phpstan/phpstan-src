<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<Rule>
 */
class AttributeRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node\Attribute>
	 */
	protected function getRule(): Rule
	{
		return new AttributeRule();
	}

	public static function dataRule(): iterable
	{
		yield [
			__DIR__ . '/data/attributes.php',
			AttributeRule::ERROR_MESSAGE,
			[8, 16, 20, 23, 26, 27, 34, 40],
		];
	}

	/**
	 * @param int[] $lines
	 */
	#[DataProvider('dataRule')]
	public function testRule(string $file, string $expectedError, array $lines): void
	{
		$errors = [];
		foreach ($lines as $line) {
			$errors[] = [$expectedError, $line];
		}
		$this->analyse([$file], $errors);
	}

}
