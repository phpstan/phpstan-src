<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
class IncompatibleRequireExtendsTypeTraitRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Trait_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			$node->namespacedName === null
			|| !$this->reflectionProvider->hasClass($node->namespacedName->toString())
		) {
			return [];
		}

		$traitReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());
		$extendsTags = $traitReflection->getRequireExtendsTags();

		$errors = [];
		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();
			if (!$type instanceof ObjectType) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))->build();
				continue;
			}

			$class = $type->getClassName();
			$referencedClassReflection = $type->getClassReflection();

			if ($referencedClassReflection === null) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains unknown class %s.', $class))->discoveringSymbolsTip()->build();
				continue;
			}

			if (!$referencedClassReflection->isClass()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain non-class type %s.', $class))->build();
			} elseif ($referencedClassReflection->isFinal()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain final class %s.', $class))->build();
			} elseif ($this->checkClassCaseSensitivity) {
				$errors = array_merge(
					$errors,
					$this->classCaseSensitivityCheck->checkClassNames([
						new ClassNameNodePair($class, $node),
					]),
				);
			}
		}

		return $errors;
	}

}
