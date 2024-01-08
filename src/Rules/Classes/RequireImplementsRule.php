<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function in_array;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class RequireImplementsRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$interfaceNames = array_map(static fn (ClassReflection $interface): string => $interface->getName(), $classReflection->getInterfaces());

		$errors = [];
		foreach ($classReflection->getTraits() as $trait) {
			$implementsTags = $trait->getRequireImplementsTags();
			foreach ($implementsTags as $implementsTag) {
				$type = $implementsTag->getType();
				$typeName = $type->describe(VerbosityLevel::typeOnly());

				if (in_array($typeName, $interfaceNames, true)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'%s requires using class to implement %s, but %s does not.',
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
