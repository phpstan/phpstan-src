<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function ucfirst;

/**
 * @implements Rule<InPropertyHookNode>
 */
final class ExistingClassesInPropertyHookTypehintsRule implements Rule
{

	public function __construct(private FunctionDefinitionCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InPropertyHookNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$hookReflection = $node->getHookReflection();
		if (!$hookReflection->isPropertyHook()) {
			throw new ShouldNotHappenException();
		}
		$className = SprintfHelper::escapeFormatString($node->getClassReflection()->getDisplayName());
		$hookName = $hookReflection->getPropertyHookName();
		$propertyName = SprintfHelper::escapeFormatString($hookReflection->getHookedPropertyName());

		$originalHookNode = $node->getOriginalNode();
		if ($hookReflection->getPropertyHookName() === 'set' && $originalHookNode->params === []) {
			$originalHookNode = clone $originalHookNode;
			$originalHookNode->params = [
				new Node\Param(new Variable('value'), null, null),
			];
		}

		return $this->check->checkClassMethod(
			$hookReflection,
			$originalHookNode,
			sprintf(
				'Parameter $%%s of %s hook for property %s::$%s has invalid type %%s.',
				$hookName,
				$className,
				$propertyName,
			),
			sprintf(
				'%s hook for property %s::$%s has invalid return type %%s.',
				ucfirst($hookName),
				$className,
				$propertyName,
			),
			sprintf('%s hook for property %s::$%s uses native union types but they\'re supported only on PHP 8.0 and later.', $hookName, $className, $propertyName),
			sprintf('Template type %%s of %s hook for property %s::$%s is not referenced in a parameter.', $hookName, $className, $propertyName),
			sprintf(
				'Parameter $%%s of %s hook for property %s::$%s has unresolvable native type.',
				$hookName,
				$className,
				$propertyName,
			),
			sprintf(
				'%s hook for property %s::$%s has unresolvable native return type.',
				ucfirst($hookName),
				$className,
				$propertyName,
			),
			sprintf(
				'%s hook for property %s::$%s has invalid @phpstan-self-out type %%s.',
				ucfirst($hookName),
				$className,
				$propertyName,
			),
		);
	}

}
