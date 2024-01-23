<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;

final class RequireExtendsCheck
{

	public function __construct(
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	/**
	 * @param  array<RequireExtendsTag> $extendsTags
	 * @return list<IdentifierRuleError>
	 */
	public function checkExtendsTags(Node $node, array $extendsTags): array
	{
		$errors = [];

		if (count($extendsTags) > 1) {
			$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends can only be used once.'))
				->identifier('requireExtends.duplicate')
				->build();
		}

		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();
			if (!$type instanceof ObjectType) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))
					->identifier('requireExtends.nonObject')
					->build();
				continue;
			}

			$class = $type->getClassName();
			$referencedClassReflection = $type->getClassReflection();

			if ($referencedClassReflection === null) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends contains unknown class %s.', $class))
					->discoveringSymbolsTip()
					->identifier('class.notFound')
					->build();
				continue;
			}

			if (!$referencedClassReflection->isClass()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain non-class type %s.', $class))
					->identifier(sprintf('requireExtends.%s', strtolower($referencedClassReflection->getClassTypeDescription())))
					->build();
			} elseif ($referencedClassReflection->isFinal()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @phpstan-require-extends cannot contain final class %s.', $class))
					->identifier('requireExtends.finalClass')
					->build();
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
