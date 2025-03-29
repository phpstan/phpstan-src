<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function array_map;
use function in_array;
use function is_array;

final class CleaningVisitor extends NodeVisitorAbstract
{

	private const CONTEXT_DEFAULT = 0;

	private const CONTEXT_FUNCTION_OR_METHOD = 1;

	private const CONTEXT_PROPERTY_HOOK = 2;

	/** @var self::CONTEXT_* */
	private int $context = self::CONTEXT_DEFAULT;

	private string|null $propertyName = null;

	/**
	 * @return int|Node[]|null
	 */
	public function enterNode(Node $node): int|array|null
	{
		switch ($this->context) {
			case self::CONTEXT_DEFAULT:
				return $this->clean($node);
			case self::CONTEXT_FUNCTION_OR_METHOD:
				return $this->cleanFunctionOrMethod($node);
			case self::CONTEXT_PROPERTY_HOOK:
				return $this->cleanPropertyHook($node);
		}
	}

	private function clean(Node $node): int|null
	{
		if (($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) && $node->stmts !== null) {
			$params = [];
			foreach ($this->traverse($node->params, self::CONTEXT_DEFAULT) as $param) {
				if (!($param instanceof Node\Param)) {
					continue;
				}

				$params[] = $param;
			}
			$node->params = $params;

			$stmts = [];
			foreach ($this->traverse($node->stmts, self::CONTEXT_FUNCTION_OR_METHOD) as $stmt) {
				if (!($stmt instanceof Node\Stmt)) {
					continue;
				}

				$stmts[] = $stmt;
			}
			$node->stmts = $stmts;

			return self::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\PropertyHook && is_array($node->body)) {
			$propertyName = $node->getAttribute('propertyName');
			if ($propertyName !== null) {
				$body = [];
				foreach ($this->traverse($node->body, self::CONTEXT_PROPERTY_HOOK, $propertyName) as $stmt) {
					if (!($stmt instanceof Node\Stmt)) {
						continue;
					}

					$body[] = $stmt;
				}
				$node->body = $body;

				return self::DONT_TRAVERSE_CHILDREN;
			}
		}

		return null;
	}

	/**
	 * @return int|Node[]
	 */
	private function cleanFunctionOrMethod(Node $node): int|array
	{
		if ($node instanceof Node\Expr\YieldFrom || $node instanceof Node\Expr\Yield_) {
			return self::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name
			&& in_array($node->name->toLowerString(), ParametersAcceptor::VARIADIC_FUNCTIONS, true)
		) {
			$node->name = new Node\Name\FullyQualified('func_get_args');
			return self::DONT_TRAVERSE_CHILDREN;
		}

		if ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction) {
			return self::REMOVE_NODE;
		}

		return $this->cleanSubnodes($node);
	}

	/**
	 * @param Node[] $nodes
	 * @param self::CONTEXT_* $context
	 * @return Node[]
	 */
	private function traverse(
		array $nodes,
		int $context = self::CONTEXT_DEFAULT,
		string|null $propertyName = null,
	): array
	{
		$visitor = new self();
		$visitor->context = $context;
		$visitor->propertyName = $propertyName;

		return (new NodeTraverser($visitor))->traverse($nodes);
	}

	/**
	 * @return int|Node[]
	 */
	private function cleanPropertyHook(Node $node): int|array
	{
		if (
			$node instanceof Node\Expr\PropertyFetch
			&& $node->var instanceof Node\Expr\Variable
			&& $node->var->name === 'this'
			&& $node->name instanceof Node\Identifier
			&& $node->name->toString() === $this->propertyName
		) {
			return self::DONT_TRAVERSE_CHILDREN;
		}

		return $this->cleanSubnodes($node);
	}

	/**
	 * @return Node[]
	 */
	private function cleanSubnodes(Node $node): array
	{
		$subnodes = [];
		foreach ($node->getSubNodeNames() as $subnodeName) {
			$subnodes = [...$subnodes, ...array_filter(
				is_array($node->$subnodeName) ? $node->$subnodeName : [$node->$subnodeName],
				static fn ($subnode) => $subnode instanceof Node,
			)];
		}

		return array_map(static function ($node) {
			switch (true) {
				case $node instanceof Node\Stmt:
					return $node;
				case $node instanceof Node\Expr:
					return new Node\Stmt\Expression($node);
				default:
					throw new ShouldNotHappenException();
			}
		}, $this->traverse($subnodes, $this->context, $this->propertyName));
	}

}
