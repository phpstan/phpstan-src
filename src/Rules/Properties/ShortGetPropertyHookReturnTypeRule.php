<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<InPropertyHookNode>
 */
final class ShortGetPropertyHookReturnTypeRule implements Rule
{

	public function __construct(private FunctionReturnTypeCheck $returnTypeCheck)
	{
	}

	public function getNodeType(): string
	{
		return InPropertyHookNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// return statements in long property hook bodies are checked by Methods\ReturnTypeRule
		// short set property hook type is checked by TypesAssignedToPropertiesRule
		$hookReflection = $node->getHookReflection();
		if ($hookReflection->getPropertyHookName() !== 'get') {
			return [];
		}

		$originalHookNode = $node->getOriginalNode();
		$hookBody = $originalHookNode->body;
		if (!$hookBody instanceof Node\Expr) {
			return [];
		}

		$methodDescription = sprintf(
			'Get hook for property %s::$%s',
			$hookReflection->getDeclaringClass()->getDisplayName(),
			$hookReflection->getHookedPropertyName(),
		);

		$returnType = $hookReflection->getReturnType();

		return $this->returnTypeCheck->checkReturnType(
			$scope,
			$returnType,
			$hookBody,
			$node,
			sprintf(
				'%s should return %%s but empty return statement found.',
				$methodDescription,
			),
			sprintf(
				'%s with return type void returns %%s but should not return anything.',
				$methodDescription,
			),
			sprintf(
				'%s should return %%s but returns %%s.',
				$methodDescription,
			),
			sprintf(
				'%s should never return but return statement found.',
				$methodDescription,
			),
			$hookReflection->isGenerator(),
		);
	}

}
