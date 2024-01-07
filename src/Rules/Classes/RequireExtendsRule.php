<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function in_array;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class RequireExtendsRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$parentNames = $classReflection->getParentClassesNames();

		$errors = [];
		foreach ($classReflection->getInterfaces() as $interface) {
			$extendsTags = $interface->getRequireExtendsTags();
			foreach ($extendsTags as $extendsTag) {
				$type = $extendsTag->getType();
				$typeName = $type->describe(VerbosityLevel::typeOnly());

				if (in_array($typeName, $parentNames, true)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'%s requires implementing class to extend %s, but %s does not.',
						$interface->getName(),
						$type->describe(VerbosityLevel::typeOnly()),
						$classReflection->getName(),
					),
				)->build();
			}
		}

		foreach ($classReflection->getTraits() as $trait) {
			$extendsTags = $trait->getRequireExtendsTags();
			foreach ($extendsTags as $extendsTag) {
				$type = $extendsTag->getType();
				$typeName = $type->describe(VerbosityLevel::typeOnly());

				if (in_array($typeName, $parentNames, true)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'%s requires using class to extend %s, but %s does not.',
						$trait->getName(),
						$type->describe(VerbosityLevel::typeOnly()),
						$classReflection->getName(),
					),
				)->build();
			}
		}

		return $errors;
	}

}
