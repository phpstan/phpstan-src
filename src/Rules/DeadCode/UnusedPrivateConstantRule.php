<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\ClassConstantsNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Node as PhpDocNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function array_keys;
use function get_object_vars;
use function is_array;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * @implements Rule<ClassConstantsNode>
 */
#[RegisteredRule(level: 4)]
final class UnusedPrivateConstantRule implements Rule
{

	public function __construct(private AlwaysUsedClassConstantsExtensionProvider $extensionProvider)
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

		$constants = [];
		foreach ($node->getConstants() as $constant) {
			if (!$constant->isPrivate()) {
				continue;
			}

			foreach ($constant->consts as $const) {
				$constantName = $const->name->toString();

				$constantReflection = $classReflection->getConstant($constantName);
				foreach ($this->extensionProvider->getExtensions() as $extension) {
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
				$fetchedOnClass = $fetchScope->getType($fetchNode->class);
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

		if ($constants !== []) {
			$this->removePhpDocReferencedConstants($classReflection, $constants);
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
	 * @param array<string, Node\Const_> $constants
	 */
	private function removePhpDocReferencedConstants(ClassReflection $classReflection, array &$constants): void
	{
		$className = $classReflection->getName();

		$phpDocBlocks = [];

		$resolvedPhpDoc = $classReflection->getResolvedPhpDoc();
		if ($resolvedPhpDoc !== null) {
			$phpDocBlocks[] = $resolvedPhpDoc;
		}

		foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
			if ($method->getDeclaringClass()->getName() !== $className) {
				continue;
			}
			if (!$classReflection->hasNativeMethod($method->getName())) {
				continue;
			}
			$methodReflection = $classReflection->getNativeMethod($method->getName());
			$methodPhpDoc = $methodReflection->getResolvedPhpDoc();
			if ($methodPhpDoc === null) {
				continue;
			}

			$phpDocBlocks[] = $methodPhpDoc;
		}

		foreach ($classReflection->getNativeReflection()->getProperties() as $property) {
			if ($property->getDeclaringClass()->getName() !== $className) {
				continue;
			}
			if (!$classReflection->hasNativeProperty($property->getName())) {
				continue;
			}
			$propertyReflection = $classReflection->getNativeProperty($property->getName());
			$propertyPhpDoc = $propertyReflection->getResolvedPhpDoc();
			if ($propertyPhpDoc === null) {
				continue;
			}

			$phpDocBlocks[] = $propertyPhpDoc;
		}

		foreach ($phpDocBlocks as $phpDocBlock) {
			foreach ($phpDocBlock->getPhpDocNodes() as $phpDocNode) {
				$this->findConstFetchReferences($phpDocNode, $className, $constants);
			}
			if ($constants === []) {
				return;
			}
		}
	}

	/**
	 * @param array<string, Node\Const_> $constants
	 */
	private function findConstFetchReferences(PhpDocNode $phpDocNode, string $className, array &$constants): void
	{
		if ($phpDocNode instanceof ConstFetchNode) {
			if ($phpDocNode->className !== '' && $this->isSameClass($phpDocNode->className, $className)) {
				$name = $phpDocNode->name;
				if (str_contains($name, '*')) {
					$pattern = '{^' . str_replace('\\*', '.*', preg_quote($name, '{')) . '$}D';
					foreach (array_keys($constants) as $constantName) {
						if (preg_match($pattern, $constantName) !== 1) {
							continue;
						}

						unset($constants[$constantName]);
					}
				} else {
					unset($constants[$name]);
				}
			}
			return;
		}

		foreach (get_object_vars($phpDocNode) as $prop) {
			if ($prop instanceof PhpDocNode) {
				$this->findConstFetchReferences($prop, $className, $constants);
			} elseif (is_array($prop)) {
				foreach ($prop as $item) {
					if (!($item instanceof PhpDocNode)) {
						continue;
					}

					$this->findConstFetchReferences($item, $className, $constants);
				}
			}
			if ($constants === []) {
				return;
			}
		}
	}

	private function isSameClass(string $fetchedClassName, string $className): bool
	{
		$lower = strtolower($fetchedClassName);
		return $lower === 'self' || $lower === 'static' || strtolower($className) === strtolower($fetchedClassName);
	}

}
