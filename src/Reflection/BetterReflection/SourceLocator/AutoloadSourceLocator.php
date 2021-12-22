<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\NodeToReflection;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use ReflectionFunction;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function constant;
use function count;
use function defined;
use function function_exists;
use function interface_exists;
use function is_file;
use function is_string;
use function strtolower;
use function trait_exists;
use const PHP_VERSION_ID;

/**
 * Use PHP's built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *
 * Modified code from Roave/BetterReflection, Copyright (c) 2017 Roave, LLC.
 */
class AutoloadSourceLocator implements SourceLocator
{

	private FileNodesFetcher $fileNodesFetcher;

	/** @var array<string, array<FetchedNode<Node\Stmt\ClassLike>>> */
	private array $classNodes = [];

	/** @var array<string, Reflection|null> */
	private array $classReflections = [];

	/** @var array<string, FetchedNode<Node\Stmt\Function_>> */
	private array $functionNodes = [];

	/** @var array<int, FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>> */
	private array $constantNodes = [];

	/** @var array<string, true> */
	private array $fetchedNodesByFile = [];

	public function __construct(FileNodesFetcher $fileNodesFetcher)
	{
		$this->fileNodesFetcher = $fileNodesFetcher;
	}

	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		if ($identifier->isFunction()) {
			$functionName = $identifier->getName();
			$loweredFunctionName = strtolower($functionName);
			if (array_key_exists($loweredFunctionName, $this->functionNodes)) {
				$nodeToReflection = new NodeToReflection();
				return $nodeToReflection->__invoke(
					$reflector,
					$this->functionNodes[$loweredFunctionName]->getNode(),
					$this->functionNodes[$loweredFunctionName]->getLocatedSource(),
					$this->functionNodes[$loweredFunctionName]->getNamespace(),
				);
			}
			if (!function_exists($functionName)) {
				return null;
			}

			$reflection = new ReflectionFunction($functionName);
			$reflectionFileName = $reflection->getFileName();

			if (!is_string($reflectionFileName)) {
				return null;
			}
			if (!is_file($reflectionFileName)) {
				return null;
			}

			return $this->findReflection($reflector, $reflectionFileName, $identifier, null);
		}

		if ($identifier->isConstant()) {
			$constantName = $identifier->getName();
			$nodeToReflection = new NodeToReflection();
			foreach ($this->constantNodes as $stmtConst) {
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

			if (!defined($constantName)) {
				return null;
			}

			$reflection = ReflectionConstant::createFromNode(
				$reflector,
				new FuncCall(new Name('define'), [
					new Arg(new String_($constantName)),
					new Arg(new String_('')), // not actually used
				]),
				new LocatedSource('', $constantName, null),
				null,
				null,
			);
			$reflection->populateValue(@constant($constantName));

			return $reflection;
		}

		if (!$identifier->isClass()) {
			return null;
		}

		$loweredClassName = strtolower($identifier->getName());
		if (array_key_exists($loweredClassName, $this->classReflections)) {
			return $this->classReflections[$loweredClassName];
		}

		$locateResult = $this->locateClassByName($identifier->getName());
		if ($locateResult === null) {
			if (array_key_exists($loweredClassName, $this->classNodes)) {
				foreach ($this->classNodes[$loweredClassName] as $classNode) {
					$nodeToReflection = new NodeToReflection();
					return $this->classReflections[$loweredClassName] = $nodeToReflection->__invoke(
						$reflector,
						$classNode->getNode(),
						$classNode->getLocatedSource(),
						$classNode->getNamespace(),
					);
				}
			}

			return null;
		}

		[$potentiallyLocatedFile, $className, $startLine] = $locateResult;

		return $this->findReflection($reflector, $potentiallyLocatedFile, new Identifier($className, $identifier->getType()), $startLine);
	}

	private function findReflection(Reflector $reflector, string $file, Identifier $identifier, ?int $startLine): ?Reflection
	{
		if (!array_key_exists($file, $this->fetchedNodesByFile)) {
			$result = $this->fileNodesFetcher->fetchNodes($file);
			foreach ($result->getClassNodes() as $className => $fetchedClassNodes) {
				foreach ($fetchedClassNodes as $fetchedClassNode) {
					$this->classNodes[$className][] = $fetchedClassNode;
				}
			}
			foreach ($result->getFunctionNodes() as $functionName => $fetchedFunctionNode) {
				$this->functionNodes[$functionName] = $fetchedFunctionNode;
			}
			foreach ($result->getConstantNodes() as $fetchedConstantNode) {
				$this->constantNodes[] = $fetchedConstantNode;
			}

			$this->fetchedNodesByFile[$file] = true;
		}

		$nodeToReflection = new NodeToReflection();
		if ($identifier->isClass()) {
			$identifierName = strtolower($identifier->getName());
			if (array_key_exists($identifierName, $this->classReflections)) {
				return $this->classReflections[$identifierName];
			}
			if (!array_key_exists($identifierName, $this->classNodes)) {
				return null;
			}

			$classNodesCount = count($this->classNodes[$identifierName]);
			foreach ($this->classNodes[$identifierName] as $classNode) {
				if ($classNodesCount > 1 && $startLine !== null) {
					if (count($classNode->getNode()->attrGroups) > 0 && PHP_VERSION_ID < 80000) {
						$startLine--;
					}
					if ($startLine !== $classNode->getNode()->getStartLine()) {
						continue;
					}
				}

				return $this->classReflections[$identifierName] = $nodeToReflection->__invoke(
					$reflector,
					$classNode->getNode(),
					$classNode->getLocatedSource(),
					$classNode->getNamespace(),
				);
			}

			return null;
		}
		if ($identifier->isFunction()) {
			$identifierName = strtolower($identifier->getName());
			if (!array_key_exists($identifierName, $this->functionNodes)) {
				return null;
			}

			return $nodeToReflection->__invoke(
				$reflector,
				$this->functionNodes[$identifierName]->getNode(),
				$this->functionNodes[$identifierName]->getLocatedSource(),
				$this->functionNodes[$identifierName]->getNamespace(),
			);
		}

		return null;
	}

	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return [];
	}

	/**
	 * @return array{string, string, int|null}|null
	 */
	private function locateClassByName(string $className): ?array
	{
		if (class_exists($className) || interface_exists($className) || trait_exists($className)) {
			$reflection = new ReflectionClass($className);
			$filename = $reflection->getFileName();

			if (!is_string($filename)) {
				return null;
			}

			if (!is_file($filename)) {
				return null;
			}

			return [$filename, $reflection->getName(), $reflection->getStartLine() !== false ? $reflection->getStartLine() : null];
		}

		return null;
	}

}
