<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<ClassLike>
 */
class IncompatibleRequireExtendsTypeRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			$node->namespacedName === null
			|| !$this->reflectionProvider->hasClass($node->namespacedName->toString())
		) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());
		$extendsTags = $classReflection->getRequireExtendsTags();

		if (
			!$classReflection->isTrait()
			&& ! $classReflection->isInterface()
			&& count($extendsTags) > 0
		) {
			return [
				RuleErrorBuilder::message('PHPDoc tag @require-extends is only valid on trait or interface.')->build(),
			];
		}

		$errors = [];
		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();
			if (!$type->canCallMethods()->yes() || !$type->canAccessProperties()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @require-extends contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))->build();
				continue;
			}

			if (
				$this->unresolvableTypeHelper->containsUnresolvableType($type)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @require-extends contains unresolvable type.')->build();
				continue;
			}

			if (
				$this->containsGenericType($type)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @require-extends cannot contain generic type.')->build();
				continue;
			}

			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @require-extends contains unknown class %s.', $class))->discoveringSymbolsTip()->build();
					continue;
				}

				$referencedClassReflection = $this->reflectionProvider->getClass($class);
				if (!$referencedClassReflection->isClass()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @require-extends cannot contain non-class type %s.', $class))->build();
				} elseif ($referencedClassReflection->isFinal()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @require-extends cannot contain final class %s.', $class))->build();
				} elseif ($this->checkClassCaseSensitivity) {
					$errors = array_merge(
						$errors,
						$this->classCaseSensitivityCheck->checkClassNames([
							new ClassNameNodePair($class, $node),
						]),
					);
				}
			}
		}

		return $errors;
	}

	private function containsGenericType(Type $phpDocType): bool
	{
		$containsGeneric = false;
		TypeTraverser::map($phpDocType, static function (Type $type, callable $traverse) use (&$containsGeneric): Type {
			if ($type instanceof GenericObjectType) {
				$containsGeneric = true;
				return $type;
			}
			$traverse($type);
			return $type;
		});

		return $containsGeneric;
	}

}
