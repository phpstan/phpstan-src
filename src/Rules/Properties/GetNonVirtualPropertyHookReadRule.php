<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\Property\PropertyRead;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;

/**
 * @implements Rule<ClassPropertiesNode>
 */
final class GetNonVirtualPropertyHookReadRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$reads = [];
		$classReflection = $node->getClassReflection();
		foreach ($node->getPropertyUsages() as $propertyUsage) {
			if (!$propertyUsage instanceof PropertyRead) {
				continue;
			}

			$fetch = $propertyUsage->getFetch();
			if (!$fetch instanceof Node\Expr\PropertyFetch) {
				continue;
			}

			if (!$fetch->name instanceof Node\Identifier) {
				continue;
			}

			$propertyName = $fetch->name->toString();
			if (!$fetch->var instanceof Node\Expr\Variable || $fetch->var->name !== 'this') {
				continue;
			}

			$usageScope = $propertyUsage->getScope();
			$inFunction = $usageScope->getFunction();
			if (!$inFunction instanceof PhpMethodFromParserNodeReflection) {
				continue;
			}

			if (!$inFunction->isPropertyHook()) {
				continue;
			}

			if ($inFunction->getPropertyHookName() !== 'get') {
				continue;
			}

			if ($propertyName !== $inFunction->getHookedPropertyName()) {
				continue;
			}

			$reads[$propertyName] = true;
		}

		$errors = [];
		foreach ($node->getProperties() as $propertyNode) {
			if (!$propertyNode->hasHooks()) {
				continue;
			}

			if (array_key_exists($propertyNode->getName(), $reads)) {
				continue;
			}

			$propertyReflection = $classReflection->getNativeProperty($propertyNode->getName());
			if ($propertyReflection->isVirtual()->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Get hook for non-virtual property %s::$%s does not read its value.',
				$classReflection->getDisplayName(),
				$propertyNode->getName(),
			))
				->line($this->getGetHookLine($propertyNode))
				->identifier('propertyGetHook.noRead')
				->build();
		}

		return $errors;
	}

	private function getGetHookLine(ClassPropertyNode $propertyNode): int
	{
		$getHook = null;
		foreach ($propertyNode->getHooks() as $hook) {
			if ($hook->name->toLowerString() !== 'get') {
				continue;
			}

			$getHook = $hook;
			break;
		}

		if ($getHook === null) {
			return $propertyNode->getStartLine();
		}

		return $getHook->getStartLine();
	}

}
