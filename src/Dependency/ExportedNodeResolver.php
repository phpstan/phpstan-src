<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Dependency\ExportedNode\ExportedClassConstantNode;
use PHPStan\Dependency\ExportedNode\ExportedClassNode;
use PHPStan\Dependency\ExportedNode\ExportedFunctionNode;
use PHPStan\Dependency\ExportedNode\ExportedInterfaceNode;
use PHPStan\Dependency\ExportedNode\ExportedMethodNode;
use PHPStan\Dependency\ExportedNode\ExportedParameterNode;
use PHPStan\Dependency\ExportedNode\ExportedPhpDocNode;
use PHPStan\Dependency\ExportedNode\ExportedPropertyNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitNode;
use PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation;
use PHPStan\Type\FileTypeMapper;

class ExportedNodeResolver
{

	private FileTypeMapper $fileTypeMapper;

	private Standard $printer;

	public function __construct(FileTypeMapper $fileTypeMapper, Standard $printer)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->printer = $printer;
	}

	public function resolve(string $fileName, \PhpParser\Node $node): ?ExportedNode
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
					$docComment !== null ? $docComment->getText() : null
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
							$adaptation->newName !== null ? $adaptation->newName->toString() : null
						);
					}

					if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
						return ExportedTraitUseAdaptation::createPrecedence(
							$adaptation->trait !== null ? $adaptation->trait->toString() : null,
							$adaptation->method->toString(),
							array_map(static function (Name $name): string {
								return $name->toString();
							}, $adaptation->insteadof)
						);
					}

					throw new \PHPStan\ShouldNotHappenException();
				}, $adaptations)
			);
		}

		if ($node instanceof \PhpParser\Node\Stmt\Interface_ && isset($node->namespacedName)) {
			$extendsNames = array_map(static function (Name $name): string {
				return (string) $name;
			}, $node->extends);
			$docComment = $node->getDocComment();

			$interfaceName = $node->namespacedName->toString();

			return new ExportedInterfaceNode(
				$interfaceName,
				$this->exportPhpDocNode(
					$fileName,
					$interfaceName,
					null,
					$docComment !== null ? $docComment->getText() : null
				),
				$extendsNames
			);
		}

		if ($node instanceof Node\Stmt\Trait_ && isset($node->namespacedName)) {
			return new ExportedTraitNode($node->namespacedName->toString());
		}

		if ($node instanceof ClassMethod) {
			if ($node->isAbstract() || $node->isFinal() || !$node->isPrivate()) {
				$methodName = $node->name->toString();
				$docComment = $node->getDocComment();
				$parentNode = $node->getAttribute('parent');
				$continue = ($parentNode instanceof Class_ || $parentNode instanceof Node\Stmt\Interface_) && isset($parentNode->namespacedName);
				if (!$continue) {
					return null;
				}

				return new ExportedMethodNode(
					$methodName,
					$this->exportPhpDocNode(
						$fileName,
						$parentNode->namespacedName->toString(),
						$methodName,
						$docComment !== null ? $docComment->getText() : null
					),
					$node->byRef,
					$node->isPublic(),
					$node->isPrivate(),
					$node->isAbstract(),
					$node->isFinal(),
					$node->isStatic(),
					$this->printType($node->returnType),
					$this->exportParameterNodes($node->params)
				);
			}
		}

		if ($node instanceof Node\Stmt\PropertyProperty) {
			$parentNode = $node->getAttribute('parent');
			if (!$parentNode instanceof Property) {
				throw new \PHPStan\ShouldNotHappenException(sprintf('Expected node type %s, %s occurred.', Property::class, is_object($parentNode) ? get_class($parentNode) : gettype($parentNode)));
			}
			if ($parentNode->isPrivate()) {
				return null;
			}

			$classNode = $parentNode->getAttribute('parent');
			if (!$classNode instanceof Class_ || !isset($classNode->namespacedName)) {
				return null;
			}

			$docComment = $parentNode->getDocComment();

			return new ExportedPropertyNode(
				$node->name->toString(),
				$this->exportPhpDocNode(
					$fileName,
					$classNode->namespacedName->toString(),
					null,
					$docComment !== null ? $docComment->getText() : null
				),
				$this->printType($parentNode->type),
				$parentNode->isPublic(),
				$parentNode->isPrivate(),
				$parentNode->isStatic()
			);
		}

		if ($node instanceof Node\Const_) {
			$parentNode = $node->getAttribute('parent');
			if (!$parentNode instanceof Node\Stmt\ClassConst) {
				return null;
			}

			if ($parentNode->isPrivate()) {
				return null;
			}

			$classNode = $parentNode->getAttribute('parent');
			if (!$classNode instanceof Class_ || !isset($classNode->namespacedName)) {
				return null;
			}

			$docComment = $parentNode->getDocComment();

			return new ExportedClassConstantNode(
				$node->name->toString(),
				$this->printer->prettyPrintExpr($node->value),
				$parentNode->isPublic(),
				$parentNode->isPrivate(),
				$this->exportPhpDocNode(
					$fileName,
					$classNode->namespacedName->toString(),
					null,
					$docComment !== null ? $docComment->getText() : null
				),
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
				$this->exportPhpDocNode(
					$fileName,
					null,
					$functionName,
					$docComment !== null ? $docComment->getText() : null
				),
				$node->byRef,
				$this->printType($node->returnType),
				$this->exportParameterNodes($node->params)
			);
		}

		return null;
	}

	/**
	 * @param Node\Identifier|Node\Name|Node\NullableType|Node\UnionType|null $type
	 * @return string|null
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
					throw new \PHPStan\ShouldNotHappenException();
				}

				return $printedType;
			}, $type->types));
		}

		return $type->toString();
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
				throw new \PHPStan\ShouldNotHappenException();
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
				} elseif (!$type instanceof Node\NullableType) {
					$type = new Node\NullableType($type);
				}
			}
			$nodes[] = new ExportedParameterNode(
				$param->var->name,
				$this->printType($type),
				$param->byRef,
				$param->variadic,
				$param->default !== null
			);
		}

		return $nodes;
	}

	private function exportPhpDocNode(
		string $file,
		?string $className,
		?string $functionName,
		?string $text
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
			$text
		);

		$nameScope = $resolvedPhpDocBlock->getNullableNameScope();
		if ($nameScope === null) {
			return null;
		}

		return new ExportedPhpDocNode($text, $nameScope->getNamespace(), $nameScope->getUses());
	}

}
