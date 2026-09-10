<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Dependency\ExportedNode\ExportedAttributeNode;
use PHPStan\Dependency\ExportedNode\ExportedClassConstantNode;
use PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode;
use PHPStan\Dependency\ExportedNode\ExportedClassNode;
use PHPStan\Dependency\ExportedNode\ExportedConstantNode;
use PHPStan\Dependency\ExportedNode\ExportedConstantsNode;
use PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode;
use PHPStan\Dependency\ExportedNode\ExportedEnumNode;
use PHPStan\Dependency\ExportedNode\ExportedFunctionNode;
use PHPStan\Dependency\ExportedNode\ExportedInterfaceNode;
use PHPStan\Dependency\ExportedNode\ExportedMethodNode;
use PHPStan\Dependency\ExportedNode\ExportedParameterNode;
use PHPStan\Dependency\ExportedNode\ExportedPhpDocNode;
use PHPStan\Dependency\ExportedNode\ExportedPropertiesNode;
use PHPStan\Dependency\ExportedNode\ExportedPropertyHookNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\NodeTypePrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function is_string;

#[AutowiredService]
final class ExportedNodeResolver
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function resolve(Node $node, ExportedNameScope $nameScope): ?RootExportedNode
	{
		if ($node instanceof Class_ && isset($node->namespacedName)) {
			$docComment = $node->getDocComment();
			$extendsName = null;
			if ($node->extends !== null) {
				$extendsName = $node->extends->toString();
			}

			$implementsNames = [];
			foreach ($node->implements as $className) {
				$implementsNames[] = $className->toString();
			}

			$usedTraits = [];
			$adaptations = [];
			foreach ($node->getTraitUses() as $traitUse) {
				foreach ($traitUse->traits as $usedTraitName) {
					$usedTraits[] = $usedTraitName->toString();
				}
				foreach ($traitUse->adaptations as $adaptation) {
					$adaptations[] = $adaptation;
				}
			}

			$className = $node->namespacedName->toString();

			return new ExportedClassNode(
				$className,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$node->isAbstract(),
				$node->isFinal(),
				$extendsName,
				$implementsNames,
				$usedTraits,
				array_map(static function (Node\Stmt\TraitUseAdaptation $adaptation): ExportedTraitUseAdaptation {
					if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
						return ExportedTraitUseAdaptation::createAlias(
							$adaptation->trait !== null ? $adaptation->trait->toString() : null,
							$adaptation->method->toString(),
							$adaptation->newModifier,
							$adaptation->newName !== null ? $adaptation->newName->toString() : null,
						);
					}

					if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
						return ExportedTraitUseAdaptation::createPrecedence(
							$adaptation->trait !== null ? $adaptation->trait->toString() : null,
							$adaptation->method->toString(),
							array_map(static fn (Name $name): string => $name->toString(), $adaptation->insteadof),
						);
					}

					throw new ShouldNotHappenException();
				}, $adaptations),
				$this->exportClassStatements($node->stmts, $className, $nameScope),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Node\Stmt\Interface_ && isset($node->namespacedName)) {
			$extendsNames = array_map(static fn (Name $name): string => (string) $name, $node->extends);
			$docComment = $node->getDocComment();

			$interfaceName = $node->namespacedName->toString();

			return new ExportedInterfaceNode(
				$interfaceName,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$extendsNames,
				$this->exportClassStatements($node->stmts, $interfaceName, $nameScope),
			);
		}

		if ($node instanceof Node\Stmt\Enum_ && $node->namespacedName !== null) {
			$implementsNames = array_map(static fn (Name $name): string => (string) $name, $node->implements);
			$docComment = $node->getDocComment();

			$enumName = $node->namespacedName->toString();
			$scalarType = null;
			if ($node->scalarType !== null) {
				$scalarType = $node->scalarType->toString();
			}

			return new ExportedEnumNode(
				$enumName,
				$scalarType,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$implementsNames,
				$this->exportClassStatements($node->stmts, $enumName, $nameScope),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Node\Stmt\Trait_ && isset($node->namespacedName)) {
			$docComment = $node->getDocComment();
			$usedTraits = [];
			$adaptations = [];
			foreach ($node->getTraitUses() as $traitUse) {
				foreach ($traitUse->traits as $usedTraitName) {
					$usedTraits[] = $usedTraitName->toString();
				}
				foreach ($traitUse->adaptations as $adaptation) {
					$adaptations[] = $adaptation;
				}
			}

			$className = $node->namespacedName->toString();

			return new ExportedTraitNode(
				$className,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$usedTraits,
				array_map(static function (Node\Stmt\TraitUseAdaptation $adaptation): ExportedTraitUseAdaptation {
					if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
						return ExportedTraitUseAdaptation::createAlias(
							$adaptation->trait !== null ? $adaptation->trait->toString() : null,
							$adaptation->method->toString(),
							$adaptation->newModifier,
							$adaptation->newName !== null ? $adaptation->newName->toString() : null,
						);
					}

					if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
						return ExportedTraitUseAdaptation::createPrecedence(
							$adaptation->trait !== null ? $adaptation->trait->toString() : null,
							$adaptation->method->toString(),
							array_map(static fn (Name $name): string => $name->toString(), $adaptation->insteadof),
						);
					}

					throw new ShouldNotHappenException();
				}, $adaptations),
				$this->exportClassStatements($node->stmts, $className, $nameScope),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Function_) {
			$functionName = $node->name->name;
			if (isset($node->namespacedName)) {
				$functionName = (string) $node->namespacedName;
			}

			$docComment = $node->getDocComment();

			return new ExportedFunctionNode(
				$functionName,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$node->byRef,
				NodeTypePrinter::printType($node->returnType),
				$this->exportParameterNodes($node->params, $nameScope),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Node\Stmt\Const_) {
			$constants = [];
			foreach ($node->consts as $const) {
				$constants[] = new ExportedConstantNode(
					$const->name->toString(),
					$this->exprPrinter->printExpr($const->value),
				);
			}

			return new ExportedConstantsNode($constants);
		}

		if (
			$node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Name
			&& $node->name->toLowerString() === 'define'
		) {
			$args = $node->getArgs();
			if (
				isset($args[0], $args[1])
				&& $args[0]->value instanceof Node\Scalar\String_
			) {
				return new ExportedConstantsNode([
					new ExportedConstantNode(
						$args[0]->value->value,
						$this->exprPrinter->printExpr($args[1]->value),
					),
				]);
			}
		}

		return null;
	}

	/**
	 * @param Node\Param[] $params
	 * @return ExportedParameterNode[]
	 */
	private function exportParameterNodes(array $params, ExportedNameScope $nameScope): array
	{
		$nodes = [];
		foreach ($params as $param) {
			if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}
			$type = $param->type;
			if (
				$type !== null
				&& $param->default instanceof Node\Expr\ConstFetch
				&& $param->default->name->toLowerString() === 'null'
			) {
				if ($type instanceof Node\UnionType) {
					$innerTypes = $type->types;
					$innerTypes[] = new Name('null');
					$type = new Node\UnionType($innerTypes);
				} elseif ($type instanceof Node\Identifier || $type instanceof Name) {
					$type = new Node\NullableType($type);
				}
			}
			$docComment = $param->getDocComment();
			$nodes[] = new ExportedParameterNode(
				$param->var->name,
				NodeTypePrinter::printType($type),
				$param->byRef,
				$param->variadic,
				$param->default !== null,
				$this->exportAttributeNodes($param->attrGroups),
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$param->flags,
			);
		}

		return $nodes;
	}

	private function exportPhpDocNode(?string $text, ExportedNameScope $nameScope): ?ExportedPhpDocNode
	{
		if ($text === null) {
			return null;
		}

		return new ExportedPhpDocNode($text, $nameScope->getNamespace(), $nameScope->getUses(), $nameScope->getConstUses());
	}

	/**
	 * @param Node\Stmt[] $statements
	 * @return ExportedNode[]
	 */
	private function exportClassStatements(array $statements, string $namespacedName, ExportedNameScope $nameScope): array
	{
		$exportedNodes = [];
		foreach ($statements as $statement) {
			$exportedNode = $this->exportClassStatement($statement, $namespacedName, $nameScope);
			if ($exportedNode === null) {
				continue;
			}

			$exportedNodes[] = $exportedNode;
		}

		return $exportedNodes;
	}

	private function exportClassStatement(Node\Stmt $node, string $namespacedName, ExportedNameScope $nameScope): ?ExportedNode
	{
		if ($node instanceof ClassMethod) {
			if ($node->isAbstract() || $node->isFinal() || !$node->isPrivate()) {
				$methodName = $node->name->toString();
				$docComment = $node->getDocComment();

				return new ExportedMethodNode(
					$methodName,
					$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
					$node->byRef,
					$node->isPublic(),
					$node->isPrivate(),
					$node->isAbstract(),
					$node->isFinal(),
					$node->isStatic(),
					NodeTypePrinter::printType($node->returnType),
					$this->exportParameterNodes($node->params, $nameScope),
					$this->exportAttributeNodes($node->attrGroups),
				);
			}
		}

		if ($node instanceof Node\Stmt\Property) {
			if ($node->isPrivate()) {
				return null;
			}

			$docComment = $node->getDocComment();

			$names = array_map(static fn (Node\PropertyItem $prop): string => $prop->name->toString(), $node->props);
			// Virtuality is decided by the native reflection, so it is read straight off it.
			// PhpPropertyReflection would give the same answer, but building it resolves the
			// class PHPDoc - see exportPhpDocNode() for why that is not allowed here.
			//
			// Only a hooked property can be virtual, so the hook-less ones are answered from the
			// node alone. Reaching the ReflectionProvider boots BetterReflection and the stub
			// files, and this runs in the main process during a result cache restore - where that
			// boot is pure latency in front of the analysis, paid as soon as a single file with a
			// non-private property changed.
			$virtual = false;
			if ($node->hooks !== [] && $this->reflectionProvider->hasClass($namespacedName)) {
				$nativeReflection = $this->reflectionProvider->getClass($namespacedName)->getNativeReflection();
				if ($nativeReflection->hasProperty($names[0])) {
					$virtual = $nativeReflection->getProperty($names[0])->isVirtual();
				}
			}

			return new ExportedPropertiesNode(
				$names,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				NodeTypePrinter::printType($node->type),
				$node->isPublic(),
				$node->isPrivate(),
				$node->isStatic(),
				$node->isReadonly(),
				$node->isAbstract(),
				$node->isFinal(),
				$node->isPublicSet(),
				$node->isProtectedSet(),
				$node->isPrivateSet(),
				$virtual,
				$this->exportAttributeNodes($node->attrGroups),
				$this->exportPropertyHooks($node->hooks, $nameScope),
			);
		}

		if ($node instanceof Node\Stmt\ClassConst) {
			if ($node->isPrivate()) {
				return null;
			}

			$docComment = $node->getDocComment();

			$constants = [];
			foreach ($node->consts as $const) {
				$constants[] = new ExportedClassConstantNode(
					$const->name->toString(),
					$this->exprPrinter->printExpr($const->value),
					$this->exportAttributeNodes($node->attrGroups),
				);
			}

			return new ExportedClassConstantsNode(
				$constants,
				$node->isPublic(),
				$node->isPrivate(),
				$node->isFinal(),
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
			);
		}

		if ($node instanceof Node\Stmt\EnumCase) {
			$docComment = $node->getDocComment();

			return new ExportedEnumCaseNode(
				$node->name->toString(),
				$node->expr !== null ? $this->exprPrinter->printExpr($node->expr) : null,
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
			);
		}

		return null;
	}

	/**
	 * @param Node\AttributeGroup[] $attributeGroups
	 * @return ExportedAttributeNode[]
	 */
	private function exportAttributeNodes(array $attributeGroups): array
	{
		$nodes = [];
		foreach ($attributeGroups as $attributeGroup) {
			foreach ($attributeGroup->attrs as $attribute) {
				$args = [];
				foreach ($attribute->args as $i => $arg) {
					$args[$arg->name->name ?? $i] = $this->exprPrinter->printExpr($arg->value);
				}

				$nodes[] = new ExportedAttributeNode(
					$attribute->name->toString(),
					$args,
				);
			}
		}

		return $nodes;
	}

	/**
	 * @param Node\PropertyHook[] $hooks
	 * @return ExportedPropertyHookNode[]
	 */
	private function exportPropertyHooks(
		array $hooks,
		ExportedNameScope $nameScope,
	): array
	{
		$nodes = [];
		foreach ($hooks as $hook) {
			$docComment = $hook->getDocComment();
			$propertyName = $hook->getAttribute('propertyName');
			if ($propertyName === null) {
				continue;
			}
			$nodes[] = new ExportedPropertyHookNode(
				$hook->name->toString(),
				$this->exportPhpDocNode($docComment !== null ? $docComment->getText() : null, $nameScope),
				$hook->byRef,
				$hook->body === null,
				$hook->isFinal(),
				$hook->body instanceof Expr,
				$this->exportParameterNodes($hook->params, $nameScope),
				$this->exportAttributeNodes($hook->attrGroups),
			);
		}

		return $nodes;
	}

}
