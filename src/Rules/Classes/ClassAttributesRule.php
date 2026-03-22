<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class ClassAttributesRule implements Rule
{

	public function __construct(private AttributesCheck $attributesCheck)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$classReflection = $node->getClassReflection();

		if (count($classReflection->getNativeReflection()->getAttributes('Deprecated')) > 0) {
			$typeName = strtolower($classReflection->getClassTypeDescription());
			return [
				RuleErrorBuilder::message(sprintf('Attribute class Deprecated cannot be used with %s %s.', $typeName, $classReflection->getDisplayName()))
					->identifier(sprintf('%s.deprecatedAttribute', $typeName))
					->nonIgnorable()
					->build(),
			];
		}

		$classLikeNode = $node->getOriginalNode();
		$errors = $this->attributesCheck->check(
			$scope,
			$classLikeNode->attrGroups,
			Attribute::TARGET_CLASS,
			'class',
		);

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
