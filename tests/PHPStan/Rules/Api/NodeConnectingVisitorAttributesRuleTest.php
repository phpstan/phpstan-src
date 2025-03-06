<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NodeConnectingVisitorAttributesRule>
 */
class NodeConnectingVisitorAttributesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeConnectingVisitorAttributesRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/node-connecting-visitor.php'], [
			[
				'Node attribute \'parent\' is no longer available.',
				22,
				'See: https://phpstan.org/blog/preprocessing-ast-for-custom-rules',
			],
			[
				'Node attribute \'parent\' is no longer available.',
				24,
				'See: https://phpstan.org/blog/preprocessing-ast-for-custom-rules',
			],
		]);
	}

}
