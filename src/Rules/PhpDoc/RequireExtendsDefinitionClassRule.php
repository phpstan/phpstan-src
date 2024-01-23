<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class RequireExtendsDefinitionClassRule implements Rule
{

	public function __construct(
		private RequireExtendsCheck $requireExtendsCheck,
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
		$extendsTags = $classReflection->getRequireExtendsTags();

		if (count($extendsTags) === 0) {
			return [];
		}

		if (!$classReflection->isInterface()) {
			return [
				RuleErrorBuilder::message('PHPDoc tag @phpstan-require-extends is only valid on trait or interface.')
					->identifier(sprintf('requireExtends.on%s', $classReflection->getClassTypeDescription()))
					->build(),
			];
		}

		return $this->requireExtendsCheck->checkExtendsTags($node, $extendsTags);
	}

}
