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
		$visitor = new class () extends NodeVisitorAbstract {

			private ?Node\Stmt\ClassMethod $constructorNode = null;

			/** @var list<Node\Stmt\Property> */
			private array $nonPrivateReadonlyPropertyNodes = [];

			/** @var list<Node\Expr\StaticCall> */
			private array $constructorCalls = [];

			private bool $enteredClass = false;

			public function enterNode(Node $node): ?int
			{
				if ($node instanceof Node\Stmt\Class_) {
					if ($this->enteredClass) {
						return NodeTraverser::DONT_TRAVERSE_CHILDREN;
					}

					$this->enteredClass = true;
					return null;
				}
				if ($node instanceof Node\Stmt\ClassMethod && $node->name->toLowerString() === '__construct') {
					$this->constructorNode = $node;
				} elseif ($node instanceof Node\Stmt\Property && !$node->isPrivate() && $node->isReadonly()) {
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

				$redeclaredProperties[$propertyName] = $property;
			}
		}

		foreach ($visitor->getConstructorNode()->params ?? [] as $param) {
			if (
				($param->flags & Node\Stmt\Class_::MODIFIER_READONLY) !== Node\Stmt\Class_::MODIFIER_READONLY
				|| ($param->flags & Node\Stmt\Class_::MODIFIER_PRIVATE) === Node\Stmt\Class_::MODIFIER_PRIVATE
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

			$redeclaredProperties[$propertyName] = $param;
		}

		if ($redeclaredProperties === []) {
			return [];
		}

		$parentAncestorMap = [];
		foreach ($parentClass->getAncestors() as $ancestor) {
			$parentAncestorMap[$ancestor->getName()] = $ancestor;
		}

		$callsParentConstructor = false;
		foreach ($visitor->getConstructorCalls() as $constructorCall) {
			if ($constructorCall->class instanceof Node\Expr) {
				continue;
			}

			// TODO: what if we call constructor from deeper ancestor which doesn't have the property yet?
			$name = $scope->resolveName($constructorCall->class);
			if (!array_key_exists($name, $parentAncestorMap)) {
				continue;
			}

			$callsParentConstructor = true;
			break;
		}

		if (!$callsParentConstructor) {
			return [];
		}

		$errors = [];

		foreach ($redeclaredProperties as $propertyName => $propertyNode) {
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
