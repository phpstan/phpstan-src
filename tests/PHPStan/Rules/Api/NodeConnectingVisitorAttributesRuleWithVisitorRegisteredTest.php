<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<NodeConnectingVisitorAttributesRule>
 */
class NodeConnectingVisitorAttributesRuleWithVisitorRegisteredTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NodeConnectingVisitorAttributesRule(self::getContainer());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/node-connecting-visitor.php'], []);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(parent::getAdditionalConfigFiles(), [
			__DIR__ . '/nodeConnectingVisitorCompatibility.neon',
		]);
	}

}
