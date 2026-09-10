<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;

/**
 * @implements Rule<VariableWritesNode>
 */
#[RegisteredRule(level: 1)]
final class UnusedConstructorParametersRule implements Rule
{

	public function __construct(
		private UnusedParametersCheck $check,
		#[AutowiredParameter(ref: '%featureToggles.reportPreciseLineForUnusedFunctionParameter%')]
		private bool $reportExactLine,
	)
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
		if ($originalNode->name->toLowerString() !== '__construct' || $originalNode->stmts === null) {
			return [];
		}
		if (count($originalNode->params) === 0) {
			return [];
		}
		$method = $scope->getFunction();
		if ($method === null) {
			return [];
		}
		if ($node->isOpaque()) {
			return [];
		}
		if (!$scope->isInClass()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if ($classReflection->isAttributeClass()) {
			return [];
		}

		foreach ($classReflection->getInterfaces() as $interface) {
			if ($interface->hasConstructor()) {
				return [];
			}
		}

		$message = sprintf(
			'Constructor of class %s has an unused parameter $%%s.',
			SprintfHelper::escapeFormatString($classReflection->getDisplayName()),
		);
		if ($classReflection->isAnonymous()) {
			$message = 'Constructor of an anonymous class has an unused parameter $%s.';
		}

		return $this->check->getUnusedParameterErrors(
			$node,
			$method,
			$originalNode->params,
			$message,
			'constructor.unusedParameter',
			$this->reportExactLine,
		);
	}

}
