<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Testing\RuleTestCase;

class AttributeGroupRuleTest extends RuleTestCase
{

	public const ERROR_MESSAGE = 'Found AttributeGroup';

	protected function getRule(): Rule
	{
		return new class() implements Rule {

			public function getNodeType(): string
			{
				return Node\AttributeGroup::class;
			}

			/**
			 * @param Node\AttributeGroup $node
			 * @return RuleError[]
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				return [AttributeGroupRuleTest::ERROR_MESSAGE];
			}

		};
	}

	public function dataRule(): iterable
	{
		yield [
			__DIR__ . '/data/attributes.php',
			self::ERROR_MESSAGE,
			[8, 16,	20,	23,	26,	27,	34, 40],
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
