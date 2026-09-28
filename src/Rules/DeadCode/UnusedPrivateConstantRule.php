<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @implements Rule<ClassConstantsNode>
 */
#[RegisteredRule(level: 4)]
final class UnusedPrivateConstantRule implements Rule
{

	/**
	 * @param ExtensionsCollection<AlwaysUsedClassConstantsExtension> $extensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: AlwaysUsedClassConstantsExtension::class)]
		private ExtensionsCollection $extensions,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassConstantsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_ && !$node->getClass() instanceof Node\Stmt\Enum_) {
			return [];
		}

		$classReflection = $node->getClassReflection();
		$classType = new ObjectType($classReflection->getName(), classReflection: $classReflection);

		// A gathered ClassConst that is not one of the class' own statements comes from an
		// inlined trait body, which NodeScopeResolver only traverses for analysed files.
		// Its presence therefore proves the trait's own fetches are visible.
		$constantNamesDeclaredInTraitBody = [];
		foreach ($node->getConstants() as $constant) {
			if (in_array($constant, $node->getClass()->stmts, true)) {
				continue;
			}

			foreach ($constant->consts as $const) {
				$constantNamesDeclaredInTraitBody[$const->name->toString()] = true;
			}
		}

		$constants = [];
		foreach ($node->getConstants() as $constant) {
			if (!$constant->isPrivate()) {
				continue;
			}

			foreach ($constant->consts as $const) {
				$constantName = $const->name->toString();

				if (
					!array_key_exists($constantName, $constantNamesDeclaredInTraitBody)
					&& $this->isRedeclaringPrivateTraitConstant($classReflection, $constantName)
				) {
					continue;
				}

				$constantReflection = $classReflection->getConstant($constantName);
				foreach ($this->extensions->getAll() as $extension) {
					if ($extension->isAlwaysUsed($constantReflection)) {
						continue 2;
					}
				}

				$constants[$constantName] = $const;
			}
		}

		foreach ($node->getFetches() as $fetch) {
			$fetchNode = $fetch->getNode();

			$fetchScope = $fetch->getScope();
			if ($fetchNode->class instanceof Node\Name) {
				$fetchedOnClass = $fetchScope->resolveTypeByName($fetchNode->class);
			} else {
				$fetchedOnClass = $fetchScope->getType($fetchNode->class)->getObjectTypeOrClassStringObjectType();
			}

			if (!$fetchNode->name instanceof Node\Identifier) {
				if (!$classType->isSuperTypeOf($fetchedOnClass)->no()) {
					$constants = [];
					break;
				}
				continue;
			}

			$constantReflection = $fetchScope->getConstantReflection($fetchedOnClass, $fetchNode->name->toString());
			if ($constantReflection === null) {
				if (!$classType->isSuperTypeOf($fetchedOnClass)->no()) {
					unset($constants[$fetchNode->name->toString()]);
				}
				continue;
			}

			if ($constantReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
				if (!$classType->isSuperTypeOf($fetchedOnClass)->no()) {
					unset($constants[$fetchNode->name->toString()]);
				}
				continue;
			}

			unset($constants[$fetchNode->name->toString()]);
		}

		$errors = [];
		foreach ($constants as $constantName => $constantNode) {
			$errors[] = RuleErrorBuilder::message(sprintf('Constant %s::%s is unused.', $classReflection->getDisplayName(), $constantName))
				->line($constantNode->getStartLine())
				->identifier('classConstant.unused')
				->tip(sprintf('See: %s', 'https://phpstan.org/developing-extensions/always-used-class-constants'))
				->build();
		}

		return $errors;
	}

	/**
	 * A private constant redeclared from a used trait is the very constant the trait's
	 * own methods fetch. Callers must only rely on this when the trait's body was not
	 * traversed, otherwise those fetches are visible and no guessing is needed.
	 */
	private function isRedeclaringPrivateTraitConstant(ClassReflection $classReflection, string $constantName): bool
	{
		foreach ($classReflection->getTraits() as $trait) {
			if (!$trait->hasConstant($constantName)) {
				continue;
			}

			if (!$trait->getConstant($constantName)->isPrivate()) {
				continue;
			}

			return true;
		}

		return false;
	}

}
