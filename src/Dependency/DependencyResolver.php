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
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_merge;
use function count;

class DependencyResolver
{

	public function __construct(
		private FileHelper $fileHelper,
		private ReflectionProvider $reflectionProvider,
		private ExportedNodeResolver $exportedNodeResolver,
	)
	{
	}

	public function resolveDependencies(Node $node, Scope $scope): NodeDependencies
	{
		$dependenciesReflections = [];

		if ($node instanceof Node\Stmt\Class_) {
			if ($node->extends !== null) {
				$this->addClassToDependencies($node->extends->toString(), $dependenciesReflections);
			}
			foreach ($node->implements as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Stmt\Interface_) {
			foreach ($node->extends as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Stmt\Enum_) {
			foreach ($node->implements as $className) {
				$this->addClassToDependencies($className->toString(), $dependenciesReflections);
			}
		} elseif ($node instanceof InClassMethodNode) {
			$nativeMethod = $scope->getFunction();
			if ($nativeMethod !== null) {
				$parametersAcceptor = ParametersAcceptorSelector::selectSingle($nativeMethod->getVariants());
				$this->extractThrowType($nativeMethod->getThrowType(), $dependenciesReflections);
				if ($parametersAcceptor instanceof ParametersAcceptorWithPhpDocs) {
					$this->extractFromParametersAcceptor($parametersAcceptor, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof InFunctionNode) {
			$functionReflection = $scope->getFunction();
			if ($functionReflection !== null) {
				$this->extractThrowType($functionReflection->getThrowType(), $dependenciesReflections);
				$parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

				if ($parametersAcceptor instanceof ParametersAcceptorWithPhpDocs) {
					$this->extractFromParametersAcceptor($parametersAcceptor, $dependenciesReflections);
				}
			}
		} elseif ($node instanceof Closure) {
			/** @var ClosureType $closureType */
			$closureType = $scope->getType($node);
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
		} elseif ($node instanceof Node\Expr\FuncCall) {
			$functionName = $node->name;
			if ($functionName instanceof Node\Name) {
				try {
					$dependenciesReflections[] = $this->getFunctionReflection($functionName, $scope);
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
					}
				}
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		} elseif ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\PropertyFetch) {
			$classNames = $scope->getType($node->var)->getReferencedClasses();
			foreach ($classNames as $className) {
				$this->addClassToDependencies($className, $dependenciesReflections);
			}

			$returnType = $scope->getType($node);
			foreach ($returnType->getReferencedClasses() as $referencedClass) {
				$this->addClassToDependencies($referencedClass, $dependenciesReflections);
			}
		} elseif (
			$node instanceof Node\Expr\StaticCall
			|| $node instanceof Node\Expr\ClassConstFetch
			|| $node instanceof Node\Expr\StaticPropertyFetch
		) {
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
		} elseif (
			$node instanceof Node\Expr\New_
			&& $node->class instanceof Node\Name
		) {
			$this->addClassToDependencies($scope->resolveName($node->class), $dependenciesReflections);
		} elseif ($node instanceof Node\Stmt\TraitUse) {
			foreach ($node->traits as $traitName) {
				$this->addClassToDependencies($traitName->toString(), $dependenciesReflections);
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
				foreach ($exprType->getIterableKeyType()->getReferencedClasses() as $referencedClass) {
					$this->addClassToDependencies($referencedClass, $dependenciesReflections);
				}
			}

			foreach ($exprType->getIterableValueType()->getReferencedClasses() as $referencedClass) {
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

		if ($items[0] === null) {
			return false;
		}

		$itemType = $scope->getType($items[0]->value);
		if (!$itemType instanceof ConstantStringType) {
			return false;
		}

		return $itemType->isClassString();
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
