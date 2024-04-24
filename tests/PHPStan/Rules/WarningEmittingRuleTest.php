<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @extends RuleTestCase<Rule>
 */
class WarningEmittingRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node>
	 */
	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return Node::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				echo $undefined; // @phpstan-ignore variable.undefined
				return [];
			}

		};
	}

	public function testRule(): void
	{
		try {
			$this->analyse([__DIR__ . '/data/empty-file.php'], []);
			self::fail('Should throw an exception');

		} catch (AssertionFailedError $e) {
			self::assertStringContainsString('Undefined variable $undefined', $e->getMessage());
		}
	}

}
