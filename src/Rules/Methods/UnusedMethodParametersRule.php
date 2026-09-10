<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;
use function str_starts_with;

/**
 * Reports unused parameters of private methods: a private method has no
 * callers outside the class, so its signature is not a contract - unlike a
 * public or protected one, whose parameters may be dictated by an interface,
 * a parent or an override.
 *
 * @implements Rule<VariableWritesNode>
 */
final class UnusedMethodParametersRule implements Rule
{

	public function __construct(private UnusedParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getFunctionLike();
		if (!$originalNode instanceof Node\Stmt\ClassMethod) {
			return [];
		}
		if (!$originalNode->isPrivate() || $originalNode->stmts === null || $this->check->isNoOpBody($originalNode->stmts)) {
			return [];
		}
		if (str_starts_with($originalNode->name->toString(), '__')) {
			// a magic method's signature is dictated by the engine, and the
			// constructor has its own rule
			return [];
		}
		if (count($originalNode->params) === 0) {
			return [];
		}
		if (!$scope->isInClass()) {
			return [];
		}
		$method = $scope->getFunction();
		if ($method === null) {
			return [];
		}

		return $this->check->getUnusedParameterErrors(
			$node,
			$method,
			$originalNode->params,
			sprintf(
				'Method %s::%s() has an unused parameter $%%s.',
				SprintfHelper::escapeFormatString($scope->getClassReflection()->getDisplayName()),
				SprintfHelper::escapeFormatString($originalNode->name->toString()),
			),
			'method.unusedParameter',
			true,
		);
	}

}
