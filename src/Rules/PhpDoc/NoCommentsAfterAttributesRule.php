<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use LogicException;
use Override;
use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function assert;
use function max;
use function min;
use function property_exists;

/** @implements Rule<Node> */
#[RegisteredRule(level: 0)]
final class NoCommentsAfterAttributesRule implements Rule
{

	#[Override]
	public function getNodeType(): string
	{
		return Node::class;
	}

	#[Override]
	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof VirtualNode) {
			return [];
		}

		if ($node->getDocComment() !== null) {
			return [];
		}

		if (! property_exists($node, 'attrGroups')) {
			return [];
		}

		$attrGroups = $node->attrGroups;

		if ($attrGroups === []) {
			return [];
		}

		$attrGroupEndLine = max(array_map(static fn (AttributeGroup $g) => $g->getEndLine(), $attrGroups));

		if (property_exists($node, 'name')) {
			$name = $node->name;
			assert($name instanceof Node);
			$startLine = $name->getStartLine();
		} elseif ($node instanceof ClassConst) {
			$startLine = min(array_map(static fn ($c) => $c->getStartLine(), $node->consts));
		} elseif ($node instanceof Property) {
			$startLine = min(array_map(static fn ($c) => $c->getStartLine(), $node->props));
		} elseif ($node instanceof Param) {
			$startLine = $node->var->getStartLine();
		} else {
			throw new LogicException('Unexpected node type: ' . get_class($node));
		}

		if ($startLine - $attrGroupEndLine <= 1) {
			return [];
		}

		return [
			RuleErrorBuilder::message('No comments after attributes.')
				->identifier('node.noCommentsAfterAttributes')
				->line($attrGroupEndLine + 1)
				->nonIgnorable()
				->build(),
		];
	}

}
