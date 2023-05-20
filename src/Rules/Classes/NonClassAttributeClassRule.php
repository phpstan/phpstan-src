<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class NonClassAttributeClassRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getOriginalNode();
		foreach ($originalNode->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$name = $attr->name->toLowerString();
				if ($name === 'attribute') {
					return $this->check($scope);
				}
			}
		}

		return [];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function check(Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		if (!$classReflection->isClass()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'%s cannot be an Attribute class.',
					$classReflection->isInterface() ? 'Interface' : 'Enum',
				))
					->identifier(sprintf('attribute.%s', $classReflection->isInterface() ? 'interface' : 'enum'))
					->build(),
			];
		}
		if ($classReflection->isAbstract()) {
			return [
				RuleErrorBuilder::message(sprintf('Abstract class %s cannot be an Attribute class.', $classReflection->getDisplayName()))
					->identifier('attribute.abstract')
					->build(),
			];
		}

		if (!$classReflection->hasConstructor()) {
			return [];
		}

		if (!$classReflection->getConstructor()->isPublic()) {
			return [
				RuleErrorBuilder::message(sprintf('Attribute class %s constructor must be public.', $classReflection->getDisplayName()))
					->identifier('attribute.constructorNotPublic')
					->build(),
			];
		}

		return [];
	}

}
