<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\Util\ConstantNodeChecker;
use PHPStan\Reflection\ConstantNameHelper;
use function strtolower;

class CachingVisitor extends NodeVisitorAbstract
{

	private string $fileName;

	private string $contents;

	/** @var array<string, array<FetchedNode<Node\Stmt\ClassLike>>> */
	private array $classNodes;

	/** @var array<string, array<FetchedNode<Node\Stmt\Function_>>> */
	private array $functionNodes;

	/** @var array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>> */
	private array $constantNodes;

	private ?Node\Stmt\Namespace_ $currentNamespaceNode = null;

	public function enterNode(Node $node): ?int
	{
		if ($node instanceof Namespace_) {
			$this->currentNamespaceNode = $node;

			return null;
		}

		if ($node instanceof Node\Stmt\ClassLike) {
			if ($node->name !== null) {
				$fullClassName = $node->name->toString();
				if ($this->currentNamespaceNode !== null && $this->currentNamespaceNode->name !== null) {
					$fullClassName = $this->currentNamespaceNode->name . '\\' . $fullClassName;
				}
				$this->classNodes[strtolower($fullClassName)][] = new FetchedNode(
					$node,
					$this->currentNamespaceNode,
					new LocatedSource($this->contents, $fullClassName, $this->fileName),
				);
			}

			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\Stmt\Function_) {
			if ($node->namespacedName !== null) {
				$functionName = $node->namespacedName->toString();
				$this->functionNodes[strtolower($functionName)][] = new FetchedNode(
					$node,
					$this->currentNamespaceNode,
					new LocatedSource($this->contents, $functionName, $this->fileName),
				);
			}

			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\Stmt\Const_) {
			foreach ($node->consts as $const) {
				if ($const->namespacedName === null) {
					continue;
				}

				$this->constantNodes[ConstantNameHelper::normalize($const->namespacedName->toString())][] = new FetchedNode(
					$node,
					$this->currentNamespaceNode,
					new LocatedSource($this->contents, null, $this->fileName),
				);
			}

			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\Expr\FuncCall) {
			try {
				ConstantNodeChecker::assertValidDefineFunctionCall($node);
			} catch (InvalidConstantNode) {
				return null;
			}

			/** @var Node\Scalar\String_ $nameNode */
			$nameNode = $node->getArgs()[0]->value;
			$constantName = $nameNode->value;

			$constantNode = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				new LocatedSource($this->contents, $constantName, $this->fileName),
			);
			$this->constantNodes[ConstantNameHelper::normalize($constantName)][] = $constantNode;

			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

	/**
	 * @return null
	 */
	public function leaveNode(Node $node)
	{
		if (!$node instanceof Namespace_) {
			return null;
		}

		$this->currentNamespaceNode = null;
		return null;
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\ClassLike>>>
	 */
	public function getClassNodes(): array
	{
		return $this->classNodes;
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\Function_>>>
	 */
	public function getFunctionNodes(): array
	{
		return $this->functionNodes;
	}

	/**
	 * @return array<string, array<FetchedNode<Node\Stmt\Const_|Node\Expr\FuncCall>>>
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
