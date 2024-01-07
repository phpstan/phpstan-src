<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
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
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
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
				} elseif (!$this->reflectionProvider->getClass($class)->isClass()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @require-extends contains invalid type %s.', $class))->build();
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
