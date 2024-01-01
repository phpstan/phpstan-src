<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Broker\FunctionNotFoundException;
use PHPStan\File\FileHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use function array_merge;
use function count;

class DependencyResolver
{

	public function __construct(
		private FileHelper $fileHelper,
		private ReflectionProvider $reflectionProvider,
		private ExportedNodeResolver $exportedNodeResolver,
		private FileTypeMapper $fileTypeMapper,
	)
	{
	}

	public function resolveDependencies(Node $node, Scope $scope): NodeDependencies
	{
		$dependenciesReflections = [];

		if ($node instanceof InClassNode || $node instanceof InTraitNode) {
			$docComment = $node->getDocComment();
			if ($docComment !== null) {
				$phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$scope->getFile(),
					$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
					$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
					null,
					$docComment->getText(),
				);
				foreach ($phpDoc->getTypeAliasImportTags() as $importTag) {
					$this->addClassToDependencies($importTag->getImportedFrom(), $dependenciesReflections);
				}
			}
		}

		if ($node instanceof Node\Stmt\Class_) {
			if ($node->namespacedName !== null) {
				$this->addClassToDependencies($node->namespacedName->toString(), $dependenciesReflections);
			}
			if ($node->extends !== null) {
				$this->addClassToDependencies($node->extends->toString(), $dependenciesReflections);
			}
			foreach ($node->implements as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Stmt\Interface_) {
			if ($node->namespacedName !== null) {
				$this->addClassToDependencies($node->namespacedName->toString(), $dependenciesReflections);
			}
			foreach ($node->extends as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Stmt\Enum_) {
			if ($node->namespacedName !== null) {
				$this->addClassToDependencies($node->namespacedName->toString(), $dependenciesReflections);
			}
			foreach ($node->implements as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof InClassMethodNode) {
			$nativeMethod = $node->getMethodReflection();
			$parametersAcceptor = ParametersAcceptorSelector::selectSingle($nativeMethod->getVariants());
			$this->extractThrowType($nativeMethod->getThrowType(), $dependenciesReflections);
			$this->extractFromParametersAcceptor($parametersAcceptor, $dependenciesReflections);
			foreach ($nativeMethod->getAsserts()->getAll() as $assertTag) {
				foreach ($assertTag->getType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
				foreach ($assertTag->getOriginalType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
			if ($nativeMethod->getSelfOutType() !== null) {
				foreach ($nativeMethod->getSelfOutType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof ClassPropertyNode) {
			$nativeTypeNode = $node->getNativeType();
			if ($nativeTypeNode !== null) {
				$nativeType = ParserNodeTypeToPHPStanType::resolve($nativeTypeNode, $node->getClassReflection());
				foreach ($nativeType->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
			$phpDocType = $node->getPhpDocType();
			if ($phpDocType !== null) {
				foreach ($phpDocType->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof InFunctionNode) {
			$functionReflection = $node->getFunctionReflection();
			$this->extractThrowType($functionReflection->getThrowType(), $dependenciesReflections);
			$parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

			$this->extractFromParametersAcceptor($parametersAcceptor, $dependenciesReflections);
			foreach ($functionReflection->getAsserts()->getAll() as $assertTag) {
				foreach ($assertTag->getType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
				foreach ($assertTag->getOriginalType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof Closure || $node instanceof Node\Expr\ArrowFunction) {
			$closureType = $scope->getType($node);
			if ($closureType instanceof ClosureType) {
				foreach ($closureType->getParameters() as $parameter) {
					$referencedClasses = $parameter->getType()->getReferencedClasses();
					foreach ($referencedClasses as $referencedClass) {
						$this->addClassToDependencies($referencedClass, $dependenciesReflections);
					}
				}

				$returnTypeReferencedClasses = $closureType->getReturnType()->getReferencedClasses();
				foreach ($returnTypeReferencedClasses as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof Node\Expr\FuncCall) {
			$functionName = $node->name;
			if ($functionName instanceof Node\Name) {
				try {
					$functionReflection = $this->getFunctionReflection($functionName, $scope);
					$dependenciesReflections[] = $functionReflection;

					foreach ($functionReflection->getVariants() as $functionVariant) {
						foreach ($functionVariant->getParameters() as $parameter) {
							if ($parameter->getOutType() === null) {
								continue;
							}
							foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
								$this->addClassToDependencies($referencedClass, $dependenciesReflections);
							}
						}
					}

					foreach ($functionReflection->getAsserts()->getAll() as $assertTag) {
						foreach ($assertTag->getType()->getReferencedClasses() as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}
						foreach ($assertTag->getOriginalType()->getReferencedClasses() as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}
					}
				} catch (FunctionNotFoundException) {
					// pass
				}
			} else {
				$calledType = $scope->getType($functionName);
				if ($calledType->isCallable()->yes()) {
					$variants = $calledType->getCallableParametersAcceptors($scope);
					foreach ($variants as $variant) {
						$referencedClasses = $variant->getReturnType()->getReferencedClasses();
						foreach ($referencedClasses as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}

						foreach ($variant->getParameters() as $parameter) {
							if (!$parameter instanceof ParameterReflectionWithPhpDocs) {
								continue;
							}
							if ($parameter->getOutType() === null) {
								continue;
							}
							foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
								$this->addClassToDependencies($referencedClass, $dependenciesReflections);
							}
						}
					}
				}
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Expr\MethodCall) {
			$calledOnType = $scope->getType($node->var);
			$classNames = $calledOnType->getReferencedClasses();
			foreach ($classNames as $className) {
				$this->addClassToDependencies($className, $dependenciesReflections);
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($node->name instanceof Node\Identifier) {
				$methodReflection = $scope->getMethodReflection($calledOnType, $node->name->toString());
				if ($methodReflection !== null) {
					$this->addClassToDependencies($methodReflection->getDeclaringClass()->getName(), $dependenciesReflections);
					foreach ($methodReflection->getVariants() as $methodVariant) {
						foreach ($methodVariant->getParameters() as $parameter) {
							if ($parameter->getOutType() === null) {
								continue;
							}
							foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
								$this->addClassToDependencies($referencedClass, $dependenciesReflections);
							}
						}
					}

					foreach ($methodReflection->getAsserts()->getAll() as $assertTag) {
						foreach ($assertTag->getType()->getReferencedClasses() as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}
						foreach ($assertTag->getOriginalType()->getReferencedClasses() as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}
					}

					if ($methodReflection->getSelfOutType() !== null) {
						foreach ($methodReflection->getSelfOutType()->getReferencedClasses() as $referencedClass) {
							$this->addClassToDependencies($referencedClass, $dependenciesReflections);
						}
					}
				}
			}
		} elseif ($node instanceof Node\Expr\PropertyFetch) {
			$fetchedOnType = $scope->getType($node->var);
			$classNames = $fetchedOnType->getReferencedClasses();
			foreach ($classNames as $className) {
				$this->addClassToDependencies($className, $dependenciesReflections);
			}

			$propertyType = $scope->getType($node);
			foreach ($propertyType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($node->name instanceof Node\Identifier) {
				$propertyReflection = $scope->getPropertyReflection($fetchedOnType, $node->name->toString());
				if ($propertyReflection !== null) {
					$this->addClassToDependencies($propertyReflection->getDeclaringClass()->getName(), $dependenciesReflections);
				}
			}
		} elseif ($node instanceof Node\Expr\StaticCall) {
			if ($node->class instanceof Node\Name) {
				$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
			} else {
				foreach ($scope->getType($node->class)->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($node->name instanceof Node\Identifier) {
				if ($node->class instanceof Node\Name) {
					$className = $scope->resolveName($node->class);
					if ($this->reflectionProvider->hasClass($className)) {
						$methodClassReflection = $this->reflectionProvider->getClass($className);
						if ($methodClassReflection->hasMethod($node->name->toString())) {
							$methodReflection = $methodClassReflection->getMethod($node->name->toString(), $scope);
							$this->addClassToDependencies($methodReflection->getDeclaringClass()->getName(), $dependenciesReflections);
							foreach ($methodReflection->getVariants() as $methodVariant) {
								foreach ($methodVariant->getParameters() as $parameter) {
									if ($parameter->getOutType() === null) {
										continue;
									}
									foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
										$this->addClassToDependencies($referencedClass, $dependenciesReflections);
									}
								}
							}
						}
					}
				} else {
					$methodReflection = $scope->getMethodReflection($scope->getType($node->class), $node->name->toString());
					if ($methodReflection !== null) {
						$this->addClassToDependencies($methodReflection->getDeclaringClass()->getName(), $dependenciesReflections);
						foreach ($methodReflection->getVariants() as $methodVariant) {
							foreach ($methodVariant->getParameters() as $parameter) {
								if ($parameter->getOutType() === null) {
									continue;
								}
								foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
									$this->addClassToDependencies($referencedClass, $dependenciesReflections);
								}
							}
						}
					}
				}
			}
		} elseif ($node instanceof Node\Expr\ClassConstFetch) {
			if ($node->class instanceof Node\Name) {
				$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
			} else {
				foreach ($scope->getType($node->class)->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($node->name instanceof Node\Identifier && $node->name->toLowerString() !== 'class') {
				if ($node->class instanceof Node\Name) {
					$className = $scope->resolveName($node->class);
					if ($this->reflectionProvider->hasClass($className)) {
						$constantClassReflection = $this->reflectionProvider->getClass($className);
						if ($constantClassReflection->hasConstant($node->name->toString())) {
							$constantReflection = $constantClassReflection->getConstant($node->name->toString());
							$this->addClassToDependencies($constantReflection->getDeclaringClass()->getName(), $dependenciesReflections);
						}
					}
				} else {
					$constantReflection = $scope->getConstantReflection($scope->getType($node->class), $node->name->toString());
					if ($constantReflection !== null) {
						$this->addClassToDependencies($constantReflection->getDeclaringClass()->getName(), $dependenciesReflections);
					}
				}
			}
		} elseif ($node instanceof Node\Expr\StaticPropertyFetch) {
			if ($node->class instanceof Node\Name) {
				$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
			} else {
				foreach ($scope->getType($node->class)->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($node->name instanceof Node\Identifier) {
				if ($node->class instanceof Node\Name) {
					$className = $scope->resolveName($node->class);
					if ($this->reflectionProvider->hasClass($className)) {
						$propertyClassReflection = $this->reflectionProvider->getClass($className);
						if ($propertyClassReflection->hasProperty($node->name->toString())) {
							$propertyReflection = $propertyClassReflection->getProperty($node->name->toString(), $scope);
							$this->addClassToDependencies($propertyReflection->getDeclaringClass()->getName(), $dependenciesReflections);
						}
					}
				} else {
					$propertyReflection = $scope->getPropertyReflection($scope->getType($node->class), $node->name->toString());
					if ($propertyReflection !== null) {
						$this->addClassToDependencies($propertyReflection->getDeclaringClass()->getName(), $dependenciesReflections);
					}
				}
			}
		} elseif (
			$node instanceof Node\Expr\New_
			&& $node->class instanceof Node\Name
		) {
			$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
		} elseif ($node instanceof Node\Stmt\TraitUse) {
			foreach ($node->traits as $traitName) {
				$this->addClassToDependencies($traitName->toString(), $dependenciesReflections);
			}

			$docComment = $node->getDocComment();
			if ($docComment !== null) {
				$usesTags = $this->fileTypeMapper->getResolvedPhpDoc(
					$scope->getFile(),
					$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
					$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
					null,
					$docComment->getText(),
				)->getUsesTags();
				foreach ($usesTags as $usesTag) {
					foreach ($usesTag->getType()->getReferencedClasses() as $referencedClass) {
						$this->addClassToDependencies($referencedClass, $dependenciesReflections);
					}
				}
			}
		} elseif ($node instanceof Node\Expr\Instanceof_) {
			if ($node->class instanceof Name) {
				$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Stmt\Catch_) {
			foreach ($node->types as $type) {
				$this->addClassToDependencies($scope->resolveName($type), $dependenciesReflections);
			}
		} elseif ($node instanceof ArrayDimFetch && $node->dim !== null) {
			$varType = $scope->getType($node->var);
			$dimType = $scope->getType($node->dim);

			foreach ($varType->getOffsetValueType($dimType)->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		} elseif ($node instanceof Foreach_) {
			$exprType = $scope->getType($node->expr);
			if ($node->keyVar !== null) {

				foreach ($scope->getIterableKeyType($exprType)->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}

			foreach ($scope->getIterableValueType($exprType)->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		} elseif (
			$node instanceof Array_
			&& $this->considerArrayForCallableTest($scope, $node)
		) {
			$arrayType = $scope->getType($node);
			if (!$arrayType->isCallable()->no()) {
				foreach ($arrayType->getCallableParametersAcceptors($scope) as $variant) {
					$referencedClasses = $variant->getReturnType()->getReferencedClasses();
					foreach ($referencedClasses as $referencedClass) {
						$this->addClassToDependencies($referencedClass, $dependenciesReflections);
					}
				}
			}
		}

		return new NodeDependencies($this->fileHelper, $dependenciesReflections, $this->exportedNodeResolver->resolve($scope->getFile(), $node));
	}

	private function considerArrayForCallableTest(Scope $scope, Array_ $arrayNode): bool
	{
		$items = $arrayNode->items;
		if (count($items) !== 2) {
			return false;
		}

		$itemType = $scope->getType($items[0]->value);
		return $itemType->isClassStringType()->yes();
	}

	/**
	 * @param array<int, ClassReflection|FunctionReflection> $dependenciesReflections
	 */
	private function addClassToDependencies(string $className, array &$dependenciesReflections): void
	{
		try {
			$classReflection = $this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException) {
			return;
		}

		do {
			$dependenciesReflections[] = $classReflection;

			foreach ($classReflection->getInterfaces() as $interface) {
				$dependenciesReflections[] = $interface;
			}

			foreach ($classReflection->getTraits() as $trait) {
				$dependenciesReflections[] = $trait;
			}

			foreach ($classReflection->getResolvedMixinTypes() as $mixinType) {
				foreach ($mixinType->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			foreach ($classReflection->getTemplateTags() as $templateTag) {
				foreach ($templateTag->getBound()->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			foreach ($classReflection->getPropertyTags() as $propertyTag) {
				if ($propertyTag->isReadable()) {
					foreach ($propertyTag->getReadableType()->getReferencedClasses() as $referencedClass) {
						if (!$this->reflectionProvider->hasClass($referencedClass)) {
							continue;
						}
						$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
					}
				}

				if (!$propertyTag->isWritable()) {
					continue;
				}

				foreach ($propertyTag->getWritableType()->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			foreach ($classReflection->getMethodTags() as $methodTag) {
				foreach ($methodTag->getReturnType()->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
				foreach ($methodTag->getParameters() as $parameter) {
					foreach ($parameter->getType()->getReferencedClasses() as $referencedClass) {
						if (!$this->reflectionProvider->hasClass($referencedClass)) {
							continue;
						}
						$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
					}
					if ($parameter->getDefaultValue() === null) {
						continue;
					}
					foreach ($parameter->getDefaultValue()->getReferencedClasses() as $referencedClass) {
						if (!$this->reflectionProvider->hasClass($referencedClass)) {
							continue;
						}
						$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
					}
				}
			}

			foreach ($classReflection->getExtendsTags() as $extendsTag) {
				foreach ($extendsTag->getType()->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			foreach ($classReflection->getImplementsTags() as $implementsTag) {
				foreach ($implementsTag->getType()->getReferencedClasses() as $referencedClass) {
					if (!$this->reflectionProvider->hasClass($referencedClass)) {
						continue;
					}
					$dependenciesReflections[] = $this->reflectionProvider->getClass($referencedClass);
				}
			}

			$classReflection = $classReflection->getParentClass();
		} while ($classReflection !== null);
	}

	private function getFunctionReflection(Node\Name $nameNode, ?Scope $scope): FunctionReflection
	{
		return $this->reflectionProvider->getFunction($nameNode, $scope);
	}

	/**
	 * @param array<ClassReflection|FunctionReflection> $dependenciesReflections
	 */
	private function extractFromParametersAcceptor(
		ParametersAcceptorWithPhpDocs $parametersAcceptor,
		array &$dependenciesReflections,
	): void
	{
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			$referencedClasses = array_merge(
				$parameter->getNativeType()->getReferencedClasses(),
				$parameter->getPhpDocType()->getReferencedClasses(),
			);

			foreach ($referencedClasses as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}

			if ($parameter->getOutType() === null) {
				continue;
			}

			foreach ($parameter->getOutType()->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		}

		$returnTypeReferencedClasses = array_merge(
			$parametersAcceptor->getNativeReturnType()->getReferencedClasses(),
			$parametersAcceptor->getPhpDocReturnType()->getReferencedClasses(),
		);
		foreach ($returnTypeReferencedClasses as $referencedClass) {
			$this->addClassToDependencies($referencedClass, $dependenciesReflections);
		}
	}

	/**
	 * @param array<ClassReflection|FunctionReflection> $dependenciesReflections
	 */
	private function extractThrowType(
		?Type $throwType,
		array &$dependenciesReflections,
	): void
	{
		if ($throwType === null) {
			return;
		}

		foreach ($throwType->getReferencedClasses() as $referencedClass) {
			$this->addClassToDependencies($referencedClass, $dependenciesReflections);
		}
	}

}
