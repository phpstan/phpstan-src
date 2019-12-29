<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\BuilderHelpers;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\Util\ConstantNodeChecker;

class CachingVisitor extends NodeVisitorAbstract
{

	/** @var string */
	private $fileName;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\ClassLike>> */
	private $classNodes;

	/** @var array<string, FetchedNode<\PhpParser\Node\Stmt\Function_>> */
	private $functionNodes;

	/** @var array<string, FetchedNode<\PhpParser\Node\Const_|\PhpParser\Node\Expr\FuncCall>> */
	private $constantNodes;

	/** @var \PhpParser\Node\Stmt\Namespace_|null */
	private $currentNamespaceNode;

	public function enterNode(\PhpParser\Node $node): ?int
	{
		if ($node instanceof Namespace_) {
			$this->currentNamespaceNode = $node;
		}

		if ($node instanceof \PhpParser\Node\Stmt\ClassLike) {
			if ($node->name !== null) {
				$this->classNodes[$node->namespacedName->toString()] = new FetchedNode(
					$node,
					$this->currentNamespaceNode,
					$this->fileName
				);
			}

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Stmt\Function_) {
			$this->functionNodes[$node->namespacedName->toString()] = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				$this->fileName
			);

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Stmt\Const_) {
			foreach ($node->consts as $constNode) {
				$this->constantNodes[$constNode->namespacedName->toString()] = new FetchedNode(
					$constNode,
					$this->currentNamespaceNode,
					$this->fileName
				);
			}

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof \PhpParser\Node\Expr\FuncCall) {
			try {
				ConstantNodeChecker::assertValidDefineFunctionCall($node);
			} catch (InvalidConstantNode $e) {
				return null;
			}

			/** @var \PhpParser\Node\Scalar\String_ $nameNode */
			$nameNode = $node->args[0]->value;
			$constantName = $nameNode->value;

			if (defined($constantName)) {
				$constantValue = constant($constantName);
				$node->args[1]->value = BuilderHelpers::normalizeValue($constantValue);
			}

			$constantNode = new FetchedNode(
				$node,
				$this->currentNamespaceNode,
				$this->fileName
			);
			$this->constantNodes[$constantName] = $constantNode;

			if (count($node->args) === 3
				&& $node->args[2]->value instanceof \PhpParser\Node\Expr\ConstFetch
				&& $node->args[2]->value->name->toLowerString() === 'true'
			) {
				$this->constantNodes[strtolower($constantName)] = $constantNode;
			}

			return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		return null;
	}

	/**
	 * @param \PhpParser\Node $node
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
	 * @return array<string, FetchedNode<\PhpParser\Node\Stmt\ClassLike>>
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
	 * @return array<string, FetchedNode<\PhpParser\Node\Const_|\PhpParser\Node\Expr\FuncCall>>
	 */
	public function getConstantNodes(): array
	{
		return $this->constantNodes;
	}

	public function reset(string $fileName): void
	{
		$this->classNodes = [];
		$this->functionNodes = [];
		$this->constantNodes = [];
		$this->fileName = $fileName;
	}

}
