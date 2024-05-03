<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function array_keys;
use function is_string;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class RedeclareReadOnlyProperty implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->supportsPromotedProperties() || !$this->phpVersion->supportsReadOnlyProperties()) {
			/** Error is already reported by {@see InvalidPromotedPropertiesRule} and {@see \PHPStan\Rules\Properties\ReadOnlyPropertyRule} */
			return [];
		}

		$reflection = $node->getClassReflection();
		$parentClass = $reflection->getParentClass();

		if ($parentClass === null) {
			return [];
		}

		$nodeTraverser = new NodeTraverser();
		$visitor = new class ($reflection->isReadOnly()) extends NodeVisitorAbstract {

			private ?Node\Stmt\ClassMethod $constructorNode = null;

			/** @var list<Node\Stmt\Property> */
			private array $nonPrivateReadonlyPropertyNodes = [];

			/** @var list<Node\Expr\StaticCall> */
			private array $constructorCalls = [];

			private bool $enteredClass = false;

			public function __construct(private bool $isReadonlyClass)
			{
			}

			public function enterNode(Node $node): ?int
			{
				// Don't enter anonymous classes
				if ($node instanceof Node\Stmt\Class_) {
					if ($this->enteredClass) {
						return NodeTraverser::DONT_TRAVERSE_CHILDREN;
					}

					$this->enteredClass = true;
					return null;
				}
				if ($node instanceof Node\Stmt\ClassMethod && $node->name->toLowerString() === '__construct') {
					$this->constructorNode = $node;
				} elseif ($node instanceof Node\Stmt\Property && !$node->isPrivate() && ($node->isReadonly() || $this->isReadonlyClass)) {
					$this->nonPrivateReadonlyPropertyNodes[] = $node;
				} elseif ($node instanceof Node\Expr\StaticCall && $node->name instanceof Node\Identifier && $node->name->toLowerString() === '__construct') {
					$this->constructorCalls[] = $node;
				}
				return null;
			}

			public function getConstructorNode(): ?Node\Stmt\ClassMethod
			{
				return $this->constructorNode;
			}

			/** @return list<Node\Stmt\Property> */
			public function getNonPrivateReadonlyPropertyNodes(): array
			{
				return $this->nonPrivateReadonlyPropertyNodes;
			}

			/** @return list<Node\Expr\StaticCall> */
			public function getConstructorCalls(): array
			{
				return $this->constructorCalls;
			}

		};
		$nodeTraverser->addVisitor($visitor);
		$nodeTraverser->traverse([$node->getOriginalNode()]);
		if ($visitor->getConstructorCalls() === []) {
			return [];
		}

		$redeclaredProperties = [];

		foreach ($visitor->getNonPrivateReadonlyPropertyNodes() as $propertyNode) {
			foreach ($propertyNode->props as $property) {
				$propertyName = $property->name->name;
				$parentProperty = $this->findPrototype($parentClass, $propertyName);
				if ($parentProperty === null) {
					continue;
				}

				$declaringClass = $parentProperty->getDeclaringClass();
				$redeclaredProperties[$propertyName] = [$property, $declaringClass->getName()];
			}
		}

		foreach ($visitor->getConstructorNode()->params ?? [] as $param) {
			if (
				(!$reflection->isReadOnly() && ($param->flags & Node\Stmt\Class_::MODIFIER_READONLY) !== Node\Stmt\Class_::MODIFIER_READONLY)
				|| (
					($param->flags & Node\Stmt\Class_::MODIFIER_PUBLIC) !== Node\Stmt\Class_::MODIFIER_PUBLIC
					&& ($param->flags & Node\Stmt\Class_::MODIFIER_PROTECTED) !== Node\Stmt\Class_::MODIFIER_PROTECTED
				)
				|| $param->var instanceof Node\Expr\Error
				|| !is_string($param->var->name)
			) {
				continue;
			}

			$propertyName = $param->var->name;
			$parentProperty = $this->findPrototype($parentClass, $propertyName);
			if ($parentProperty === null) {
				continue;
			}

			$declaringClass = $parentProperty->getDeclaringClass();
			$redeclaredProperties[$propertyName] = [$param, $declaringClass->getName()];
		}

		if ($redeclaredProperties === []) {
			return [];
		}

		$parentAncestorMap = [];
		foreach ($parentClass->getAncestors() as $ancestor) {
			$parentAncestorMap[$ancestor->getName()] = $ancestor;
		}

		$calledAncestorConstructorMap = [];
		foreach ($visitor->getConstructorCalls() as $constructorCall) {
			if ($constructorCall->class instanceof Node\Expr) {
				continue;
			}

			$name = $scope->resolveName($constructorCall->class);
			if (!array_key_exists($name, $parentAncestorMap)) {
				continue;
			}

			$calledAncestorConstructorMap[$name] = true;
		}

		if ($calledAncestorConstructorMap === []) {
			return [];
		}

		$errors = [];

		foreach ($redeclaredProperties as $propertyName => [$propertyNode, $declaringClassName]) {
			foreach (array_keys($parentAncestorMap) as $ancestorName) {
				if (array_key_exists($ancestorName, $calledAncestorConstructorMap)) {
					break;
				}

				if ($ancestorName === $declaringClassName) {
					continue 2;
				}
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Readonly property %s::$%s cannot be redeclared, because you call the parent constructor.',
				$reflection->getName(),
				$propertyName,
			))->line($propertyNode->getLine())->build();
		}

		return $errors;
	}

	private function findPrototype(ClassReflection $parentClass, string $propertyName): ?PhpPropertyReflection
	{
		if (!$parentClass->hasNativeProperty($propertyName)) {
			return null;
		}

		$property = $parentClass->getNativeProperty($propertyName);
		if ($property->isPrivate()) {
			return null;
		}

		return $property;
	}

}
