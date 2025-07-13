<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_values;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class SealedRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if ($classReflection->isEnum()) {
			return [];
		}

		$className = $classReflection->getName();

		$parents = array_values($classReflection->getImmediateInterfaces());
		$parentClass = $classReflection->getParentClass();
		if ($parentClass !== null) {
			$parents[] = $parentClass;
		}

		$errors = [];
		foreach ($parents as $parent) {
			$sealedTags = $parent->getSealedTags();
			foreach ($sealedTags as $sealedTag) {
				$type = $sealedTag->getType();
				if ($type->isSuperTypeOf(new ObjectType($className))->yes()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'%s %s is sealed and only permits %s as subtypes, %s given.',
						$parent->isInterface() ? 'Interface' : 'Class',
						$parent->getDisplayName(),
						$type->describe(VerbosityLevel::typeOnly()),
						$classReflection->getDisplayName(),
					),
				)
					->identifier('class.sealed')
					->build();
			}
		}

		return $errors;
	}

}
