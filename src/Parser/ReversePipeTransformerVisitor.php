<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Turbo\ImplementedInTurboExtension;
use function count;

#[ImplementedInTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ReversePipeTransformerVisitor.cpp')]
final class ReversePipeTransformerVisitor extends NodeVisitorAbstract
{

	public const ARG_ATTRIBUTES_NAME = 'argAttributes';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && !$node->isFirstClassCallable()) {
			$attributes = $node->getAttributes();
			$origPipeAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME] ?? [];
			$origVariadicPlaceholderAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME] ?? [];
			if ($origPipeAttributes !== [] && count($node->getArgs()) === 1) {
				$origPipeAttributes[self::ARG_ATTRIBUTES_NAME] = $node->getArgs()[0]->getAttributes();
				if ($node->name instanceof Node\Name) {
					unset($attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME]);
					unset($attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME]);
					return new Node\Expr\BinaryOp\Pipe(
						$node->getArgs()[0]->value,
						new Node\Expr\FuncCall($node->name, [new Node\VariadicPlaceholder($origVariadicPlaceholderAttributes)], attributes: $attributes),
						attributes: $origPipeAttributes,
					);
				}

				return new Node\Expr\BinaryOp\Pipe(
					$node->getArgs()[0]->value,
					$node->name,
					attributes: $origPipeAttributes,
				);
			}
		}

		if ($node instanceof Node\Expr\MethodCall && !$node->isFirstClassCallable()) {
			$attributes = $node->getAttributes();
			$origPipeAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME] ?? [];
			$origVariadicPlaceholderAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME] ?? [];
			if ($origPipeAttributes !== [] && count($node->getArgs()) === 1) {
				$origPipeAttributes[self::ARG_ATTRIBUTES_NAME] = $node->getArgs()[0]->getAttributes();
				unset($attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME]);
				unset($attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME]);
				return new Node\Expr\BinaryOp\Pipe(
					$node->getArgs()[0]->value,
					new Node\Expr\MethodCall($node->var, $node->name, [new Node\VariadicPlaceholder($origVariadicPlaceholderAttributes)], attributes: $attributes),
					attributes: $origPipeAttributes,
				);
			}
		}

		if ($node instanceof Node\Expr\StaticCall && !$node->isFirstClassCallable()) {
			$attributes = $node->getAttributes();
			$origPipeAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME] ?? [];
			$origVariadicPlaceholderAttributes = $attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME] ?? [];
			if ($origPipeAttributes !== [] && count($node->getArgs()) === 1) {
				$origPipeAttributes[self::ARG_ATTRIBUTES_NAME] = $node->getArgs()[0]->getAttributes();
				unset($attributes[PipeTransformerVisitor::ORIGINAL_PIPE_ATTRIBUTE_NAME]);
				unset($attributes[PipeTransformerVisitor::ORIGINAL_VARIADIC_PLACEHOLDER_ATTRIBUTE_NAME]);
				return new Node\Expr\BinaryOp\Pipe(
					$node->getArgs()[0]->value,
					new Node\Expr\StaticCall($node->class, $node->name, [new Node\VariadicPlaceholder($origVariadicPlaceholderAttributes)], attributes: $attributes),
					attributes: $origPipeAttributes,
				);
			}
		}

		return null;
	}

}
