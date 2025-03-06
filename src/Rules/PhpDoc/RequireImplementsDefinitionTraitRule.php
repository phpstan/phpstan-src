<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_column;
use function array_map;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Trait_>
 */
final class RequireImplementsDefinitionTraitRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private bool $checkClassCaseSensitivity,
		private bool $discoveringSymbolsTip,
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
			$classNames = $type->getObjectClassNames();
			if (count($classNames) === 0) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('requireImplements.nonObject')
					->build();
				continue;
			}

			$referencedClassReflections = array_map(static fn ($reflection) => [$reflection, $reflection->getName()], $type->getObjectClassReflections());
			$referencedClassReflectionsMap = array_column($referencedClassReflections, 0, 1);
			foreach ($classNames as $class) {
				$referencedClassReflection = $referencedClassReflectionsMap[$class] ?? null;
				if ($referencedClassReflection === null) {
					$errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements contains unknown class %s.', $class))
					->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$errors[] = $errorBuilder->build();
					continue;
				}

				if (!$referencedClassReflection->isInterface()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-implements cannot contain non-interface type %s.', $class))
						->identifier(sprintf('requireImplements.%s', strtolower($referencedClassReflection->getClassTypeDescription())))
						->build();
				} else {
					$errors = array_merge(
						$errors,
						$this->classCheck->checkClassNames($scope, [
							new ClassNameNodePair($class, $node),
						], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_REQUIRE_IMPLEMENTS), $this->checkClassCaseSensitivity),
					);
				}
			}
		}

		return $errors;
	}

}
