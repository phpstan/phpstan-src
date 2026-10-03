<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * Applies the signature checks of a purity declaration to methods without a
 * body (abstract and interface methods), which PureMethodRule never sees.
 *
 * @implements Rule<InClassMethodNode>
 */
final class PureAbstractMethodRule implements Rule
{

	public function __construct(private FunctionPurityCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->getOriginalNode()->stmts !== null) {
			return [];
		}

		$method = $node->getMethodReflection();

		return $this->check->checkSignature(
			$scope,
			sprintf('Method %s::%s()', $method->getDeclaringClass()->getDisplayName(), $method->getName()),
			'Method',
			$method,
			$method->getParameters(),
			$method->getReturnType(),
			$method->isConstructor(),
		);
	}

}
