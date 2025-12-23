<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * @extends RuleTestCase<Rule<FuncCall>>
 */
class NonexistentAnalysedClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new /** @implements Rule<FuncCall> */class implements Rule {

			public function getNodeType(): string
			{
				return FuncCall::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node->name instanceof Node\Name && $node->name->toString() === 'error') {
					return [
						RuleErrorBuilder::message('Error call')
							->identifier('test.errorCall')
							->nonIgnorable()
							->build(),
					];
				}

				return [];
			}

		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/../../notAutoloaded/nonexistentClasses.php'], []);
	}

	public function testRuleWithError(): void
	{
		try {
			$this->analyse([__DIR__ . '/../../notAutoloaded/nonexistentClasses-error.php'], []);
			$this->fail('Should have failed');
		} catch (ExpectationFailedException $e) {
			if ($e->getComparisonFailure() === null) {
				throw $e;
			}
			self::assertStringContainsString('not found in ReflectionProvider', $e->getComparisonFailure()->getDiff());
		}
	}

}
