<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class VirtualNullsafeMethodCallTest extends RuleTestCase
{

	/**
	 * @return Rule<MethodCall>
	 */
	protected function getRule(): Rule
	{
		return new /** @implements Rule<MethodCall> */ class implements Rule {

			public function getNodeType(): string
			{
				return MethodCall::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node->getAttribute('virtualNullsafeMethodCall') === true) {
					return [RuleErrorBuilder::message('Nullable method call detected')->identifier('')->build()];
				}

				return [RuleErrorBuilder::message('Regular method call detected')->identifier('')->build()];
			}

		};
	}

	public function testAttribute(): void
	{
		$this->analyse([ __DIR__ . '/data/virtual-nullsafe-method-call.php'], [
			[
				'Regular method call detected',
				3,
			],
			[
				'Nullable method call detected',
				4,
			],
		]);
	}

}
