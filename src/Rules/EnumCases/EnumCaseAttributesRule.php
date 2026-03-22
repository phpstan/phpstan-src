<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Stmt\EnumCase>
 */
#[RegisteredRule(level: 0)]
final class EnumCaseAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\EnumCase::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		return $this->attributesCheck->check(
			$scope,
			$node->attrGroups,
			Attribute::TARGET_CLASS_CONSTANT,
			'class constant',
		);
	}

}
