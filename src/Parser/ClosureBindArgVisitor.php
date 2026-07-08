<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_key_exists;
use function array_shift;
use function array_unshift;
use function count;
use function spl_object_id;

#[AutowiredService]
final class ClosureBindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindArg';

	public const SCOPE_ATTRIBUTE_NAME = 'closureBindScope';

	/** @var list<?Expr> */
	private array $scopeStack = [];

	/**
	 * Maps the object id of an inline closure/arrow function passed as the 1st argument of
	 * `Closure::bind()` to that call's scope argument (the 3rd argument, or null for the
	 * default "static" scope). Only the closure body is rescoped, not the other arguments.
	 *
	 * @var array<int, ?Expr>
	 */
	private array $boundClosures = [];

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		// This visitor is a shared service, so its per-file state must be reset for each
		// traversal. Otherwise object ids reused across files (spl_object_id() is only unique
		// among live objects) could match a stale entry and rescope unrelated nodes.
		$this->scopeStack = [];
		$this->boundClosures = [];

		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'closure'
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bind'
			&& !$node->isFirstClassCallable()
		) {
			$args = $node->getArgs();
			if (count($args) > 1) {
				$args[0]->setAttribute(self::ATTRIBUTE_NAME, true);

				$closure = $args[0]->value;
				if ($closure instanceof Expr\Closure || $closure instanceof Expr\ArrowFunction) {
					// null means default scope "static"
					$this->boundClosures[spl_object_id($closure)] = $args[2]->value ?? null;
				}
			}
		}

		if (array_key_exists(spl_object_id($node), $this->boundClosures)) {
			array_unshift($this->scopeStack, $this->boundClosures[spl_object_id($node)]);
		}

		if ($node instanceof Name
			&& array_key_exists(0, $this->scopeStack)
			&& $node->isSpecialClassName()
		) {
			$node->setAttribute(self::SCOPE_ATTRIBUTE_NAME, $this->scopeStack[0]);
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (array_key_exists(spl_object_id($node), $this->boundClosures)) {
			array_shift($this->scopeStack);
		}

		return null;
	}

}
