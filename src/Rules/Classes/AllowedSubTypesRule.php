<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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
				if (!$allowedSubType->isObject()->yes()) {
					continue;
				}

				if ($allowedSubType->getObjectClassNames() === [$className]) {
					continue 2;
				}
			}

			$identifierType = 'class';
			if ($classReflection->isInterface()) {
				$identifierType = 'interface';
			} elseif ($classReflection->isEnum()) {
				$identifierType = 'enum';
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Type %s is not allowed to be a subtype of %s.',
				$className,
				$parentReflection->getName(),
			))->identifier(sprintf('%s.disallowedSubtype', $identifierType))->build();
		}

		return $messages;
	}

}
