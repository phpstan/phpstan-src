<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Dependency\ExportedNode\ExportedAttributeNode;
use PHPStan\Dependency\ExportedNode\ExportedClassConstantNode;
use PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode;
use PHPStan\Dependency\ExportedNode\ExportedClassNode;
use PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode;
use PHPStan\Dependency\ExportedNode\ExportedEnumNode;
use PHPStan\Dependency\ExportedNode\ExportedFunctionNode;
use PHPStan\Dependency\ExportedNode\ExportedInterfaceNode;
use PHPStan\Dependency\ExportedNode\ExportedMethodNode;
use PHPStan\Dependency\ExportedNode\ExportedParameterNode;
use PHPStan\Dependency\ExportedNode\ExportedPhpDocNode;
use PHPStan\Dependency\ExportedNode\ExportedPropertiesNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use function array_map;
use function implode;
use function is_string;

class ExportedNodeResolver
{

	public function __construct(private FileTypeMapper $fileTypeMapper, private ExprPrinter $exprPrinter)
	{
	}

	public function resolve(string $fileName, Node $node): ?RootExportedNode
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
				$this->exportPhpDocNode(
					$fileName,
					$className,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
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
				$this->exportClassStatements($node->stmts, $fileName, $className),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Node\Stmt\Interface_ && isset($node->namespacedName)) {
			$extendsNames = array_map(static fn (Name $name): string => (string) $name, $node->extends);
			$docComment = $node->getDocComment();

			$interfaceName = $node->namespacedName->toString();

			return new ExportedInterfaceNode(
				$interfaceName,
				$this->exportPhpDocNode(
					$fileName,
					$interfaceName,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
				$extendsNames,
				$this->exportClassStatements($node->stmts, $fileName, $interfaceName),
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
				$this->exportPhpDocNode(
					$fileName,
					$enumName,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
				$implementsNames,
				$this->exportClassStatements($node->stmts, $fileName, $enumName),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		if ($node instanceof Node\Stmt\Trait_ && isset($node->namespacedName)) {
			return new ExportedTraitNode($node->namespacedName->toString());
		}

		if ($node instanceof Function_) {
			$functionName = $node->name->name;
			if (isset($node->namespacedName)) {
				$functionName = (string) $node->namespacedName;
			}

			$docComment = $node->getDocComment();

			return new ExportedFunctionNode(
				$functionName,
				$this->exportPhpDocNode(
					$fileName,
					null,
					$functionName,
					$docComment !== null ? $docComment->getText() : null,
				),
				$node->byRef,
				$this->printType($node->returnType),
				$this->exportParameterNodes($node->params),
				$this->exportAttributeNodes($node->attrGroups),
			);
		}

		return null;
	}

	/**
	 * @param Node\Identifier|Node\Name|Node\ComplexType|null $type
	 */
	private function printType($type): ?string
	{
		if ($type === null) {
			return null;
		}

		if ($type instanceof Node\NullableType) {
			return '?' . $this->printType($type->type);
		}

		if ($type instanceof Node\UnionType) {
			return implode('|', array_map(function ($innerType): string {
				$printedType = $this->printType($innerType);
				if ($printedType === null) {
					throw new ShouldNotHappenException();
				}

				return $printedType;
			}, $type->types));
		}

		if ($type instanceof Node\IntersectionType) {
			return implode('&', array_map(function ($innerType): string {
				$printedType = $this->printType($innerType);
				if ($printedType === null) {
					throw new ShouldNotHappenException();
				}

				return $printedType;
			}, $type->types));
		}

		if ($type instanceof Node\Identifier || $type instanceof Name) {
			return $type->toString();
		}

		throw new ShouldNotHappenException();
	}

	/**
	 * @param Node\Param[] $params
	 * @return ExportedParameterNode[]
	 */
	private function exportParameterNodes(array $params): array
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
			$nodes[] = new ExportedParameterNode(
				$param->var->name,
				$this->printType($type),
				$param->byRef,
				$param->variadic,
				$param->default !== null,
				$this->exportAttributeNodes($param->attrGroups),
			);
		}

		return $nodes;
	}

	private function exportPhpDocNode(
		string $file,
		?string $className,
		?string $functionName,
		?string $text,
	): ?ExportedPhpDocNode
	{
		if ($text === null) {
			return null;
		}

		$resolvedPhpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc(
			$file,
			$className,
			null,
			$functionName,
			$text,
		);

		$nameScope = $resolvedPhpDocBlock->getNullableNameScope();
		if ($nameScope === null) {
			return null;
		}

		return new ExportedPhpDocNode($text, $nameScope->getNamespace(), $nameScope->getUses(), $nameScope->getConstUses());
	}

	/**
	 * @param Node\Stmt[] $statements
	 * @return ExportedNode[]
	 */
	private function exportClassStatements(array $statements, string $fileName, string $namespacedName): array
	{
		$exportedNodes = [];
		foreach ($statements as $statement) {
			$exportedNode = $this->exportClassStatement($statement, $fileName, $namespacedName);
			if ($exportedNode === null) {
				continue;
			}

			$exportedNodes[] = $exportedNode;
		}

		return $exportedNodes;
	}

	private function exportClassStatement(Node\Stmt $node, string $fileName, string $namespacedName): ?ExportedNode
	{
		if ($node instanceof ClassMethod) {
			if ($node->isAbstract() || $node->isFinal() || !$node->isPrivate()) {
				$methodName = $node->name->toString();
				$docComment = $node->getDocComment();

				return new ExportedMethodNode(
					$methodName,
					$this->exportPhpDocNode(
						$fileName,
						$namespacedName,
						$methodName,
						$docComment !== null ? $docComment->getText() : null,
					),
					$node->byRef,
					$node->isPublic(),
					$node->isPrivate(),
					$node->isAbstract(),
					$node->isFinal(),
					$node->isStatic(),
					$this->printType($node->returnType),
					$this->exportParameterNodes($node->params),
					$this->exportAttributeNodes($node->attrGroups),
				);
			}
		}

		if ($node instanceof Node\Stmt\Property) {
			if ($node->isPrivate()) {
				return null;
			}

			$docComment = $node->getDocComment();

			return new ExportedPropertiesNode(
				array_map(static fn (Node\PropertyItem $prop): string => $prop->name->toString(), $node->props),
				$this->exportPhpDocNode(
					$fileName,
					$namespacedName,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
				$this->printType($node->type),
				$node->isPublic(),
				$node->isPrivate(),
				$node->isStatic(),
				$node->isReadonly(),
				$this->exportAttributeNodes($node->attrGroups),
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
				$this->exportPhpDocNode(
					$fileName,
					$namespacedName,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
			);
		}

		if ($node instanceof Node\Stmt\EnumCase) {
			$docComment = $node->getDocComment();

			return new ExportedEnumCaseNode(
				$node->name->toString(),
				$node->expr !== null ? $this->exprPrinter->printExpr($node->expr) : null,
				$this->exportPhpDocNode(
					$fileName,
					$namespacedName,
					null,
					$docComment !== null ? $docComment->getText() : null,
				),
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

}
