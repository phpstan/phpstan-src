<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
final class ClassAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classLikeNode = $node->getOriginalNode();

		$errors = $this->attributesCheck->check(
			$scope,
			$classLikeNode->attrGroups,
			Attribute::TARGET_CLASS,
			'class',
		);

		$classReflection = $node->getClassReflection();
		if (
			$classReflection->isReadOnly()
			|| $classReflection->isEnum()
			|| $classReflection->isInterface()
		) {
			$typeName = 'readonly class';
			$identifier = 'class.allowDynamicPropertiesReadonly';
			if ($classReflection->isEnum()) {
				$typeName = 'enum';
				$identifier = 'enum.allowDynamicProperties';
			}
			if ($classReflection->isInterface()) {
				$typeName = 'interface';
				$identifier = 'interface.allowDynamicProperties';
			}

			if (count($classReflection->getNativeReflection()->getAttributes('AllowDynamicProperties')) > 0) {
				$errors[] = RuleErrorBuilder::message(sprintf('Attribute class AllowDynamicProperties cannot be used with %s.', $typeName))
					->identifier($identifier)
					->nonIgnorable()
					->build();
			}
		}

		return $errors;
	}

}
