<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use Attribute;
use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\AttributesCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<ClassPropertyNode>
 */
#[RegisteredRule(level: 0)]
final class PropertyAttributesRule implements Rule
{

	public function __construct(
		private AttributesCheck $attributesCheck,
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$this->phpVersion->supportsOverrideAttributeOnProperty()) {
			$propertyReflection = $node->getClassReflection()->getNativeProperty($node->getName());
			if (count($propertyReflection->getNativeReflection()->getAttributes('Override')) > 0) {
				return [
					RuleErrorBuilder::message('Attribute class Override can be used with properties only on PHP 8.5 and later.')
						->identifier('property.overrideAttribute')
						->nonIgnorable()
						->build(),
				];
			}
		}

		if ($node->isPromoted()) {
			return [];
		}

		return $this->attributesCheck->check(
			$scope,
			$node->getAttrGroups(),
			Attribute::TARGET_PROPERTY,
			'property',
		);
	}

}
