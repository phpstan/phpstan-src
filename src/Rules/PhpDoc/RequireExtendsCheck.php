<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_column;
use function array_map;
use function array_merge;
use function count;
use function sort;
use function sprintf;
use function strtolower;

final class RequireExtendsCheck
{

	public function __construct(
		private ClassNameCheck $classCheck,
		private bool $checkClassCaseSensitivity,
		private bool $discoveringSymbolsTip,
	)
	{
	}

	/**
	 * @param  array<RequireExtendsTag> $extendsTags
	 * @return list<IdentifierRuleError>
	 */
	public function checkExtendsTags(Scope $scope, Node $node, array $extendsTags): array
	{
		$errors = [];

		if (count($extendsTags) > 1) {
			$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends can only be used once.'))
				->identifier('requireExtends.duplicate')
				->build();
		}

		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();
			$classNames = $type->getObjectClassNames();
			if (count($classNames) === 0) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('requireExtends.nonObject')
					->build();
				continue;
			}

			sort($classNames);
			$referencedClassReflections = array_map(static fn ($reflection) => [$reflection, $reflection->getName()], $type->getObjectClassReflections());
			$referencedClassReflectionsMap = array_column($referencedClassReflections, 0, 1);
			foreach ($classNames as $class) {
				$referencedClassReflection = $referencedClassReflectionsMap[$class] ?? null;
				if ($referencedClassReflection === null) {
					$errorBuilder = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains unknown class %s.', $class))
						->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$errors[] = $errorBuilder->build();
					continue;
				}

				if ($referencedClassReflection->isInterface()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain an interface %s, expected a class.', $class))
						->tip('If you meant an interface, use @phpstan-require-implements instead.')
						->identifier('requireExtends.interface')
						->build();
				} elseif (!$referencedClassReflection->isClass()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain non-class type %s.', $class))
						->identifier(sprintf('requireExtends.%s', strtolower($referencedClassReflection->getClassTypeDescription())))
						->build();
				} elseif ($referencedClassReflection->isFinal()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain final class %s.', $class))
						->identifier('requireExtends.finalClass')
						->build();
				} else {
					$errors = array_merge(
						$errors,
						$this->classCheck->checkClassNames($scope, [
							new ClassNameNodePair($class, $node),
						], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_REQUIRE_EXTENDS), $this->checkClassCaseSensitivity),
					);
				}
			}
		}

		return $errors;
	}

}
