<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node\Expr\FuncCall;
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

	private FileNodesFetcher $fileNodesFetcher;

	private string $fileName;

	private ?FetchedNodesResult $fetchedNodesResult = null;

	public function __construct(
		FileNodesFetcher $fileNodesFetcher,
		string $fileName,
	)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
		$this->fileName = $fileName;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($this->fetchedNodesResult === null) {
			$this->fetchedNodesResult = $this->fileNodesFetcher->fetchNodes($this->fileName);
		}
		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			$classNodes = $this->fetchedNodesResult->getClassNodes();
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
			$functionNodes = $this->fetchedNodesResult->getFunctionNodes();
			$functionName = strtolower($identifier->getName());
			if (!array_key_exists($functionName, $functionNodes)) {
				return null;
			}

			$functionReflection = $nodeToReflection->__invoke(
				$reflector,
				$functionNodes[$functionName]->getNode(),
				$functionNodes[$functionName]->getLocatedSource(),
				$functionNodes[$functionName]->getNamespace(),
			);
			if (!$functionReflection instanceof ReflectionFunction) {
				throw new ShouldNotHappenException();
			}

			return $functionReflection;
		}

		if ($identifier->isConstant()) {
			$constantNodes = $this->fetchedNodesResult->getConstantNodes();
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
