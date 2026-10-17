<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function count;

/**
 * @implements Rule<InClassMethodNode>
 */
#[RegisteredRule(level: 2)]
#[ValidatesStubFiles]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/MethodAssertRule.cpp')]
final class MethodAssertRule implements Rule
{

	public function __construct(private AssertRuleHelper $helper)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$variants = $method->getVariants();
		if (count($variants) !== 1) {
			return [];
		}

		return $this->helper->check($scope, $node->getOriginalNode(), $method, $variants[0]);
	}

}
