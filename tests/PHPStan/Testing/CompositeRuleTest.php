<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function ctype_digit;

/**
 * @extends RuleTestCase<CompositeRule>
 */
final class CompositeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CompositeRule([
			/**
			 * @implements Rule<String_>
			 */
			new class implements Rule {

				public function getNodeType(): string
				{
					return String_::class;
				}

				/**
				 * @param String_ $node
				 */
				public function processNode(Node $node, Scope&NodeCallbackInvoker $scope): array
				{
					if (ctype_digit($node->value)) {
						$scope->invokeNodeCallback(
							new Int_((int) $node->value, ['startLine' => $node->getStartLine()]),
						);

						return [];
					}

					return [
						RuleErrorBuilder::message('Error from String_ Rule.')->identifier('CompositeRuleString')->build(),
					];
				}

			},
			/**
			 * @implements Rule<Int_>
			 */
			new class implements Rule {

				public function getNodeType(): string
				{
					return Int_::class;
				}

				/**
				 * @param Int_ $node
				 */
				public function processNode(Node $node, Scope $scope): array
				{
					return [
						RuleErrorBuilder::message('Error from Int_ Rule.')->identifier('CompositeRuleInt')->build(),
					];
				}

			},
		]);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/composite-rule.php'], [
			[
				'Error from String_ Rule.',
				6,
			],
			[
				'Error from Int_ Rule.',
				7,
			],
			[
				'Error from Int_ Rule.',
				9,
			],
			[
				'Error from Int_ Rule.',
				13,
			],
		]);
	}

}
