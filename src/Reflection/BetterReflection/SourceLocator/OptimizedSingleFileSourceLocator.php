<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node\Stmt\Const_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\ConstantNameHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_keys;
use function strtolower;

final class OptimizedSingleFileSourceLocator implements SourceLocator
{

	/** @var array{classes: array<string, true>, functions: array<string, true>, constants: array<string, true>}|null */
	private ?array $presentSymbols = null;

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		private string $fileName,
	)
	{
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($this->presentSymbols !== null) {
			if ($identifier->isClass()) {
				$className = strtolower($identifier->getName());
				if (!array_key_exists($className, $this->presentSymbols['classes'])) {
					return null;
				}
			}
			if ($identifier->isFunction()) {
				$className = strtolower($identifier->getName());
				if (!array_key_exists($className, $this->presentSymbols['functions'])) {
					return null;
				}
			}
			if ($identifier->isConstant()) {
				$constantName = ConstantNameHelper::normalize($identifier->getName());
				if (!array_key_exists($constantName, $this->presentSymbols['constants'])) {
					return null;
				}
			}
		}
		$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
		if ($this->presentSymbols === null) {
			$presentSymbols = [
				'classes' => [],
				'functions' => [],
				'constants' => [],
			];
			foreach (array_keys($fetchedNodesResult->getClassNodes()) as $className) {
				$presentSymbols['classes'][$className] = true;
			}
			foreach (array_keys($fetchedNodesResult->getFunctionNodes()) as $functionName) {
				$presentSymbols['functions'][$functionName] = true;
			}
			foreach (array_keys($fetchedNodesResult->getConstantNodes()) as $constantName) {
				$presentSymbols['constants'][$constantName] = true;
			}

			$this->presentSymbols = $presentSymbols;
		}
		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			$classNodes = $fetchedNodesResult->getClassNodes();
			$className = strtolower($identifier->getName());
			if (!array_key_exists($className, $classNodes)) {
				return null;
			}

			foreach ($classNodes[$className] as $classNode) {
				$classReflection = $nodeToReflection->__invoke(
					$reflector,
					$classNode->getNode(),
					$classNode->getLocatedSource(),
					$classNode->getNamespace(),
				);
				if (!$classReflection instanceof ReflectionClass) {
					throw new ShouldNotHappenException();
				}

				return $classReflection;
			}
		}

		if ($identifier->isFunction()) {
			$functionNodes = $fetchedNodesResult->getFunctionNodes();
			$functionName = strtolower($identifier->getName());
			if (!array_key_exists($functionName, $functionNodes)) {
				return null;
			}

			foreach ($functionNodes[$functionName] as $functionNode) {
				$functionReflection = $nodeToReflection->__invoke(
					$reflector,
					$functionNode->getNode(),
					$functionNode->getLocatedSource(),
					$functionNode->getNamespace(),
				);
				if (!$functionReflection instanceof ReflectionFunction) {
					throw new ShouldNotHappenException();
				}

				return $functionReflection;
			}
		}

		if ($identifier->isConstant()) {
			$constantNodes = $fetchedNodesResult->getConstantNodes();
			$constantName = ConstantNameHelper::normalize($identifier->getName());

			if (!array_key_exists($constantName, $constantNodes)) {
				return null;
			}

			foreach ($constantNodes[$constantName] as $fetchedConstantNode) {
				$constantNode = $fetchedConstantNode->getNode();

				$positionInNode = null;
				if ($constantNode instanceof Const_) {
					foreach ($constantNode->consts as $constPosition => $const) {
						if ($const->namespacedName === null) {
							throw new ShouldNotHappenException();
						}

						if (ConstantNameHelper::normalize($const->namespacedName->toString()) === $constantName) {
							/** @var int $positionInNode */
							$positionInNode = $constPosition;
							break;
						}
					}

					if ($positionInNode === null) {
						throw new ShouldNotHappenException();
					}
				}

				$constantReflection = $nodeToReflection->__invoke(
					$reflector,
					$fetchedConstantNode->getNode(),
					$fetchedConstantNode->getLocatedSource(),
					$fetchedConstantNode->getNamespace(),
					$positionInNode,
				);
				if (!$constantReflection instanceof ReflectionConstant) {
					throw new ShouldNotHappenException();
				}

				return $constantReflection;
			}

			return null;
		}

		throw new ShouldNotHappenException();
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		$fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
		$nodeToReflection = new NodeToReflection();
		$reflections = [];
		if ($identifierType->isClass()) {
			$classNodes = $fetchedNodesResult->getClassNodes();

			foreach ($classNodes as $classNodesArray) {
				foreach ($classNodesArray as $classNode) {
					$classReflection = $nodeToReflection->__invoke(
						$reflector,
						$classNode->getNode(),
						$classNode->getLocatedSource(),
						$classNode->getNamespace(),
					);

					if (!$classReflection instanceof ReflectionClass) {
						throw new ShouldNotHappenException();
					}

					$reflections[] = $classReflection;
				}
			}
		}

		if ($identifierType->isFunction()) {
			$functionNodes = $fetchedNodesResult->getFunctionNodes();

			foreach ($functionNodes as $functionNodesArray) {
				foreach ($functionNodesArray as $functionNode) {
					$functionReflection = $nodeToReflection->__invoke(
						$reflector,
						$functionNode->getNode(),
						$functionNode->getLocatedSource(),
						$functionNode->getNamespace(),
					);

					$reflections[] = $functionReflection;
				}
			}
		}

		if ($identifierType->isConstant()) {
			$constantNodes = $fetchedNodesResult->getConstantNodes();
			foreach ($constantNodes as $constantNodesArray) {
				foreach ($constantNodesArray as $fetchedConstantNode) {
					$constantNode = $fetchedConstantNode->getNode();

					if ($constantNode instanceof Const_) {
						foreach ($constantNode->consts as $constPosition => $const) {
							if ($const->namespacedName === null) {
								throw new ShouldNotHappenException();
							}

							$constantReflection = $nodeToReflection->__invoke(
								$reflector,
								$constantNode,
								$fetchedConstantNode->getLocatedSource(),
								$fetchedConstantNode->getNamespace(),
								$constPosition,
							);
							if (!$constantReflection instanceof ReflectionConstant) {
								throw new ShouldNotHappenException();
							}

							$reflections[] = $constantReflection;
						}

						continue;
					}

					$constantReflection = $nodeToReflection->__invoke(
						$reflector,
						$constantNode,
						$fetchedConstantNode->getLocatedSource(),
						$fetchedConstantNode->getNamespace(),
					);
					if (!$constantReflection instanceof ReflectionConstant) {
						throw new ShouldNotHappenException();
					}

					$reflections[] = $constantReflection;
				}
			}
		}

		return $reflections;
	}

}
