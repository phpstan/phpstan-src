<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function array_pop;
use function array_reverse;
use function count;

#[AutowiredService]
final class TryCatchTypeVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'tryCatchTypes';

	/** @var array<int, array<int, string>|null> */
	private array $typeStack = [];

	#[Override]
	public function beforeTraverse(array $nodes): ?array
	{
		$this->typeStack = [];
		return null;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt || $node instanceof Node\Expr\Match_) {
			if (count($this->typeStack) > 0) {
				$node->setAttribute(self::ATTRIBUTE_NAME, array_last($this->typeStack));
			}
		}

		if ($node instanceof Node\FunctionLike) {
			$this->typeStack[] = null;
		}

		if ($node instanceof Node\Stmt\TryCatch) {
			$types = [];
			foreach (array_reverse($this->typeStack) as $stackTypes) {
				if ($stackTypes === null) {
					break;
				}

				foreach ($stackTypes as $type) {
					$types[] = $type;
				}
			}
			foreach ($node->catches as $catch) {
				foreach ($catch->types as $type) {
					$types[] = $type->toString();
				}
			}

			$this->typeStack[] = $types;
		}

		return null;
	}

	#[Override]
	public function leaveNode(Node $node): ?Node
	{
		if (
			!$node instanceof Node\Stmt\TryCatch
			&& !$node instanceof Node\FunctionLike
		) {
			return null;
		}

		array_pop($this->typeStack);

		return null;
	}

}
