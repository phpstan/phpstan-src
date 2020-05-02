<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
class MixinRule implements Rule
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var \PHPStan\Rules\ClassCaseSensitivityCheck */
	private $classCaseSensitivityCheck;

	/** @var \PHPStan\Rules\Generics\GenericObjectTypeCheck */
	private $genericObjectTypeCheck;

	/** @var MissingTypehintCheck */
	private $missingTypehintCheck;

	/** @var bool */
	private $checkClassCaseSensitivity;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		GenericObjectTypeCheck $genericObjectTypeCheck,
		MissingTypehintCheck $missingTypehintCheck,
		bool $checkClassCaseSensitivity
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->genericObjectTypeCheck = $genericObjectTypeCheck;
		$this->missingTypehintCheck = $missingTypehintCheck;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!isset($node->namespacedName)) {
			// anonymous class
			return [];
		}

		$className = (string) $node->namespacedName;
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}
		$classReflection = $this->reflectionProvider->getClass($className);
		$mixinTags = $classReflection->getMixinTags();
		$errors = [];
		foreach ($mixinTags as $mixinTag) {
			$type = $mixinTag->getType();
			if (!$type->canCallMethods()->yes() || !$type->canAccessProperties()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))->build();
				continue;
			}

			if (
				$type instanceof ErrorType
				|| ($type instanceof NeverType && !$type->isExplicit())
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @mixin contains unresolvable type.')->build();
				continue;
			}

			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$type,
				'PHPDoc tag @mixin contains generic type %s but class %s is not generic.',
				'Generic type %s in PHPDoc tag @mixin does not specify all template types of class %s: %s',
				'Generic type %s in PHPDoc tag @mixin specifies %d template types, but class %s supports only %d: %s',
				'Type %s in generic type %s in PHPDoc tag @mixin is not subtype of template type %s of class %s.'
			));

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($type) as [$innerName, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @mixin contains generic %s but does not specify its types: %s',
					$innerName,
					implode(', ', $genericTypeNames)
				))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
			}

			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains unknown class %s.', $class))->build();
				} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains invalid type %s.', $class))->build();
				} elseif ($this->checkClassCaseSensitivity) {
					$errors = array_merge(
						$errors,
						$this->classCaseSensitivityCheck->checkClassNames([
							new ClassNameNodePair($class, $node),
						])
					);
				}
			}
		}

		return $errors;
	}

}
