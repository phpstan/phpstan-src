<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 2)]
final class EnumTemplateTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->isEnum()) {
			return [];
		}

		$templateTagsCount = count($classReflection->getTemplateTags());
		if ($templateTagsCount === 0) {
			return [];
		}

		$className = $classReflection->getDisplayName();

		return [
			RuleErrorBuilder::message(sprintf('Enum %s has PHPDoc @template tag%s but enums cannot be generic.', $className, $templateTagsCount === 1 ? '' : 's'))
				->identifier('enum.generic')
				->build(),
		];
	}

}
