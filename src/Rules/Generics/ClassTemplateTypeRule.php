<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeScope;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class ClassTemplateTypeRule implements Rule
{

	public function __construct(
		private TemplateTypeCheck $templateTypeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->isClass()) {
			return [];
		}
		$className = $classReflection->getName();
		if ($classReflection->isAnonymous()) {
			$displayName = 'anonymous class';
		} else {
			$displayName = 'class ' . SprintfHelper::escapeFormatString($classReflection->getDisplayName());
		}

		return $this->templateTypeCheck->check(
			$node,
			TemplateTypeScope::createWithClass($className),
			$classReflection->getTemplateTags(),
			sprintf('PHPDoc tag @template for %s cannot have existing class %%s as its name.', $displayName),
			sprintf('PHPDoc tag @template for %s cannot have existing type alias %%s as its name.', $displayName),
			sprintf('PHPDoc tag @template %%s for %s has invalid bound type %%s.', $displayName),
			sprintf('PHPDoc tag @template %%s for %s with bound type %%s is not supported.', $displayName),
		);
	}

}
