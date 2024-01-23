<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
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

		$errors = [];
		foreach ($classReflection->getTraits(true) as $trait) {
			$implementsTags = $trait->getRequireImplementsTags();
			foreach ($implementsTags as $implementsTag) {
				$type = $implementsTag->getType();
				if (!$type instanceof ObjectType) {
					continue;
				}

				if ($classReflection->implementsInterface($type->getClassName())) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Trait %s requires using class to implement %s, but %s does not.',
						$trait->getDisplayName(),
						$type->describe(VerbosityLevel::typeOnly()),
						$classReflection->getDisplayName(),
					),
				)
					->identifier('class.missingImplements')
					->build();
			}
		}

		return $errors;
	}

}
