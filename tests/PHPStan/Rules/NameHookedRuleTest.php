<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class NameHookedRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node>
	 */
	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return Name::class;
			}

			/**
			 * @param Name $node
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				$error = RuleErrorBuilder::message('Found a name: ' . $node->toString())
					->identifier('test.name.hooked')
					->build();

				return [$error];
			}

		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/name-hooked.php'], [
			[
				'Found a name: NameHooked',
				3,
			],
		]);
	}

}
