<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node\Const_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_keys;
use function strtolower;

class OptimizedSingleFileSourceLocator implements SourceLocator
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
				if (!array_key_exists($identifier->getName(), $this->presentSymbols['constants'])) {
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
			foreach ($fetchedNodesResult->getConstantNodes() as $stmtConst) {
				if ($stmtConst->getNode() instanceof FuncCall) {
					/** @var String_ $nameNode */
					$nameNode = $stmtConst->getNode()->getArgs()[0]->value;
					$presentSymbols['constants'][$nameNode->value] = true;
					continue;
				}

				/** @var Const_ $const */
				foreach ($stmtConst->getNode()->consts as $const) {
					$constName = $const->namespacedName;
					if ($constName === null) {
						continue;
					}
					$presentSymbols['constants'][$constName->toString()] = true;
				}
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
			foreach ($constantNodes as $stmtConst) {
				if ($stmtConst->getNode() instanceof FuncCall) {
					$constantReflection = $nodeToReflection->__invoke(
						$reflector,
						$stmtConst->getNode(),
						$stmtConst->getLocatedSource(),
						$stmtConst->getNamespace(),
					);
					if (!$constantReflection instanceof ReflectionConstant) {
						throw new ShouldNotHappenException();
					}
					if ($constantReflection->getName() !== $identifier->getName()) {
						continue;
					}

					return $constantReflection;
				}

				foreach (array_keys($stmtConst->getNode()->consts) as $i) {
					$constantReflection = $nodeToReflection->__invoke(
						$reflector,
						$stmtConst->getNode(),
						$stmtConst->getLocatedSource(),
						$stmtConst->getNamespace(),
						$i,
					);
					if (!$constantReflection instanceof ReflectionConstant) {
						throw new ShouldNotHappenException();
					}
					if ($constantReflection->getName() !== $identifier->getName()) {
						continue;
					}

					return $constantReflection;
				}
			}

			return null;
		}

		throw new ShouldNotHappenException();
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

}
