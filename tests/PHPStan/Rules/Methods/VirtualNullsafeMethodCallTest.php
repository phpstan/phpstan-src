<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class VirtualNullsafeMethodCallTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return MethodCall::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node->getAttribute('virtualNullsafeMethodCall') === true) {
					return ['Nullable method call detected'];
				}

				return ['Regular method call detected'];
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
