<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeScope;

/**
 * @implements \PHPStan\Rules\Rule<InClassNode>
 */
class ClassTemplateTypeRule implements Rule
{

	private \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck;

	public function __construct(
		TemplateTypeCheck $templateTypeCheck
	)
	{
		$this->templateTypeCheck = $templateTypeCheck;
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			return [];
		}
		$classReflection = $scope->getClassReflection();
		$className = $classReflection->getName();
		if ($classReflection->isAnonymous()) {
			$displayName = 'anonymous class';
		} else {
			$displayName = 'class ' . $classReflection->getDisplayName();
		}

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithClass($className),
			$classReflection->getTemplateTags(),
			sprintf('PHPDoc tag @template for %s cannot have existing class %%s as its name.', $displayName),
			sprintf('PHPDoc tag @template for %s cannot have existing type alias %%s as its name.', $displayName),
			sprintf('PHPDoc tag @template %%s for %s has invalid bound type %%s.', $displayName),
			sprintf('PHPDoc tag @template %%s for %s with bound type %%s is not supported.', $displayName)
		);
	}

}
