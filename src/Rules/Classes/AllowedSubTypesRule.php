<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function array_values;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class AllowedSubTypesRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	/**
	 * @param InClassNode $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$className = $classReflection->getName();

		$parents = array_values($classReflection->getImmediateInterfaces());
		$parentClass = $classReflection->getParentClass();
		if ($parentClass !== null) {
			$parents[] = $parentClass;
		}

		$messages = [];

		foreach ($parents as $parentReflection) {
			$allowedSubTypes = $parentReflection->getAllowedSubTypes();
			if ($allowedSubTypes === null) {
				continue;
			}

			foreach ($allowedSubTypes as $allowedSubType) {
				if (!$allowedSubType instanceof ObjectType) {
					continue;
				}

				if ($className === $allowedSubType->getClassName()) {
					continue 2;
				}
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Type %s is not allowed to be a subtype of %s.',
				$className,
				$parentReflection->getName(),
			))->build();
		}

		return $messages;
	}

}
