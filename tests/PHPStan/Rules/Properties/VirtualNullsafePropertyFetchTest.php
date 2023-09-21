<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class VirtualNullsafePropertyFetchTest extends RuleTestCase
{

	/**
	 * @return Rule<PropertyFetch>
	 */
	protected function getRule(): Rule
	{
		return new /** @implements Rule<PropertyFetch> */ class implements Rule {

			public function getNodeType(): string
			{
				return PropertyFetch::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node->getAttribute('virtualNullsafePropertyFetch') === true) {
					return [RuleErrorBuilder::message('Nullable property fetch detected')->identifier('ruleTest.VirtualNullsafeProperty')->build()];
				}

				return [RuleErrorBuilder::message('Regular property fetch detected')->identifier('ruleTest.VirtualNullsafeProperty')->build()];
			}

		};
	}

	public function testAttribute(): void
	{
		$this->analyse([ __DIR__ . '/data/virtual-nullsafe-property-fetch.php'], [
			[
				'Regular property fetch detected',
				3,
			],
			[
				'Nullable property fetch detected',
				4,
			],
		]);
	}

}
