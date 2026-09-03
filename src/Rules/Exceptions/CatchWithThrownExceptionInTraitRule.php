<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CatchWithThrownExceptionInTraitNode;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Rule;

/**
 * Records that a catch clause in a trait is alive in the context of the current class,
 * so that CatchWithUnthrownExceptionRule does not report it as dead based only on the
 * classes using the trait where it happens to be unreachable.
 *
 * @implements Rule<CatchWithThrownExceptionInTraitNode>
 */
#[RegisteredRule(level: 4)]
final class CatchWithThrownExceptionInTraitRule implements Rule
{

	public function __construct(private ConstantConditionInTraitHelper $constantConditionInTraitHelper)
	{
	}

	public function getNodeType(): string
	{
		return CatchWithThrownExceptionInTraitNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$this->constantConditionInTraitHelper->emitNoErrorForKey(
			CatchWithUnthrownExceptionRule::class,
			$scope,
			DeadCatchInTraitKey::create($node->getOriginalNode(), $node->getOriginalCaughtType()),
		);

		return [];
	}

}
