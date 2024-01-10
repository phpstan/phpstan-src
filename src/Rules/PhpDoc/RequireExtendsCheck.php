<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\PhpDoc\Tag\RequireExtendsTag;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

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
	 * @return RuleError[]
	 */
	public function checkExtendsTags(Node $node, array $extendsTags): array
	{
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
