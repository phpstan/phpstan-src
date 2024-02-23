<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
class RequireImplementsDefinitionTraitRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
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
		$implementsTags = $traitReflection->getRequireImplementsTags();

		$errors = [];
		foreach ($implementsTags as $implementsTag) {
			$type = $implementsTag->getType();
			if (!$type instanceof ObjectType) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('requireImplements.nonObject')
					->build();
				continue;
			}

			$class = $type->getClassName();
			$referencedClassReflection = $type->getClassReflection();
			if ($referencedClassReflection === null) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements contains unknown class %s.', $class))
					->discoveringSymbolsTip()
					->identifier('class.notFound')
					->build();
				continue;
			}

			if (!$referencedClassReflection->isInterface()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements cannot contain non-interface type %s.', $class))
					->identifier(sprintf('requireImplements.%s', strtolower($referencedClassReflection->getClassTypeDescription())))
					->build();
			} else {
				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames([
						new ClassNameNodePair($class, $node),
					], $this->checkClassCaseSensitivity),
				);
			}
		}

		return $errors;
	}

}
