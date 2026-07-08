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

#[AutowiredService]
final class ClosureBindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'closureBindArg';

	public const SCOPE_ATTRIBUTE_NAME = 'closureBindScope';

	/** @var list<?Expr> */
	private array $scopeStack = [];

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
			}

			// null means default scope "static"
			array_unshift($this->scopeStack, $args[2]->value ?? null);
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
		if (
			$node instanceof Node\Expr\StaticCall
			&& $node->class instanceof Node\Name
			&& $node->class->toLowerString() === 'closure'
			&& $node->name instanceof Identifier
			&& $node->name->toLowerString() === 'bind'
			&& !$node->isFirstClassCallable()
		) {
			array_shift($this->scopeStack);
		}

		return null;
	}

}
