<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeVisitorAbstract;
use function array_merge;

final class PipeTransformerVisitor extends NodeVisitorAbstract
{

	public const ORIGINAL_PIPE_ATTRIBUTE_NAME = 'originalPipeAttrs';

	public const ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME = 'originalVariadicPlaceholderAttrs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Expr\BinaryOp\Pipe) {
			return null;
		}

		if ($node->right instanceof Node\Expr\FuncCall && $node->right->isFirstClassCallable()) {
			return new FuncCall($node->right->name, [
				new Arg($node->left),
			], attributes: array_merge($node->right->getAttributes(), [
				self::ORIGINAL_PIPE_ATTRIBUTE_NAME => $node->getAttributes(),
				self::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME => $node->right->getRawArgs()[0]->getAttributes(),
			]));
		}

		if ($node->right instanceof MethodCall && $node->right->isFirstClassCallable()) {
			return new MethodCall($node->right->var, $node->right->name, [
				new Arg($node->left),
			], attributes: array_merge($node->right->getAttributes(), [
				self::ORIGINAL_PIPE_ATTRIBUTE_NAME => $node->getAttributes(),
				self::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME => $node->right->getRawArgs()[0]->getAttributes(),
			]));
		}

		if ($node->right instanceof Node\Expr\StaticCall && $node->right->isFirstClassCallable()) {
			return new Node\Expr\StaticCall($node->right->class, $node->right->name, [
				new Arg($node->left),
			], attributes: array_merge($node->right->getAttributes(), [
				self::ORIGINAL_PIPE_ATTRIBUTE_NAME => $node->getAttributes(),
				self::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME => $node->right->getRawArgs()[0]->getAttributes(),
			]));
		}

		return new FuncCall($node->right, [
			new Arg($node->left),
		], attributes: [
			self::ORIGINAL_PIPE_ATTRIBUTE_NAME => $node->getAttributes(),
		]);
	}

}
