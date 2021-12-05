<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\BuilderHelpers;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\Util\ConstantNodeChecker;

class CachingVisitor extends NodeVisitorAbstract
{

	private string $fileName;

	private string $contents;

	/** @var array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>> */
	private array $classNodes;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> */
	private array $functionNodes;

	/** @var array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>> */
	private array $constantNodes;

	private ?\PhpParser\Node\Stmt\Namespace_ $currentNamespaceNode = null;

	public function enterNode(\PhpParser\Node $node): ?int
	{
		if ($node instanceof Namespace_) {
			$this->currentNamespaceNode = $node;
		}

		if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
			if ($node->name !== null) {
				$fullClassName = $node->name->toString();
				if ($this->currentNamespaceNode !== null && $this->currentNamespaceNode->name !== null) {
					$fullClassName = $this->currentNamespaceNode->name . '\\' . $fullClassName;
				}
				$this->classNodes[strtolower($fullClassName)][] = new FetchedNode(
					$node,
					$this->currentNamespaceNode,
					$this->fileName,
					new LocatedSource($this->contents, $fullClassName, $this->fileName)
				);
			}

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Stmt\Function_) {
			$functionName = $node->namespacedName->toString();
			$this->functionNodes[strtolower($functionName)] = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				$this->fileName,
				new LocatedSource($this->contents, $functionName, $this->fileName)
			);

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Stmt\Const_) {
			$this->constantNodes[] = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				$this->fileName,
				new LocatedSource($this->contents, null, $this->fileName)
			);

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Expr\FuncCall) {
			try {
				ConstantNodeChecker::assertValidDefineFunctionCall($node);
			} catch (InvalidConstantNode $e) {
				return null;
			}

			/** @var \PhpParser\Node\Scalar\String_ $nameNode */
			$nameNode = $node->getArgs()[0]->value;
			$constantName = $nameNode->value;

			if (defined($constantName)) {
				$constantValue = @constant($constantName);
				$node->getArgs()[1]->value = BuilderHelpers::normalizeValue($constantValue);
			}

			$constantNode = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				$this->fileName,
				new LocatedSource($this->contents, $constantName, $this->fileName)
			);
			$this->constantNodes[] = $constantNode;

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

	/**
	 * @return null
	 */
	public function leaveNode(\PhpParser\Node $node)
	{
		if (!$node instanceof Namespace_) {
			return null;
		}

		$this->currentNamespaceNode = null;
		return null;
	}

	/**
	 * @return array<string, array<FetchedNode<\PhpParser\Node\Stmt\ClassLike>>>
	 */
	public function getClassNodes(): array
	{
		return $this->classNodes;
	}

	/**
	 * @return array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>>
	 */
	public function getFunctionNodes(): array
	{
		return $this->functionNodes;
	}

	/**
	 * @return array<int, FetchedNode<\PhpParser\Node\Stmt\Const_|\PhpParser\Node\Expr\FuncCall>>
	 */
	public function getConstantNodes(): array
	{
		return $this->constantNodes;
	}

	public function reset(string $fileName, string $contents): void
	{
		$this->classNodes = [];
		$this->functionNodes = [];
		$this->constantNodes = [];
		$this->fileName = $fileName;
		$this->contents = $contents;
	}

}
