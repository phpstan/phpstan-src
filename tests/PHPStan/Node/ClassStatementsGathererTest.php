<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<Rule<ClassMethodsNode>>
 */
class ClassStatementsGathererTest extends RuleTestCase
{

	/**
	 * @return Rule<ClassMethodsNode>
	 */
	protected function getRule(): Rule
	{
		return new /** @implements Rule<ClassMethodsNode> */ class implements Rule {

			public function getNodeType(): string
			{
				return ClassMethodsNode::class;
			}

			/**
			 * @param ClassMethodsNode $node
			 * @return list<IdentifierRuleError>
			 */
			public function processNode(Node $node, Scope $scope): array
			{
				$errors = [];
				foreach ($node->getMethodCalls() as $methodCall) {
					$callNode = $methodCall->getNode();
					if (!$callNode instanceof Array_) {
						continue;
					}

					$errors[] = RuleErrorBuilder::message(
						sprintf('Gathered callable array on line %d.', $callNode->getStartLine()),
					)->line($callNode->getStartLine())
						->identifier('tests.gatheredCallableArray')
						->build();
				}

				return $errors;
			}

		};
	}

	public function testGatherCallableArrays(): void
	{
		$this->analyse([__DIR__ . '/data/class-statements-gatherer-callable-array.php'], [
			[
				'Gathered callable array on line 10.',
				10,
			],
			[
				'Gathered callable array on line 11.',
				11,
			],
			[
				'Gathered callable array on line 12.',
				12,
			],
			[
				'Gathered callable array on line 13.',
				13,
			],
		]);
	}

}
