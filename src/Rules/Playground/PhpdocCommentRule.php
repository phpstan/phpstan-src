<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_contains;
use function str_starts_with;

/**
 * @implements Rule<Node>
 */
final class PhpdocCommentRule implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof VirtualNode) {
			return [];
		}

		$comments = $node->getComments();

		$errors = [];
		foreach ($comments as $comment) {
			if (!str_contains($comment->getText(), '@')) {
				continue;
			}

			foreach (['/**', '//', '#'] as $startTag) {
				if (str_starts_with($comment->getText(), $startTag)) {
					continue 2;
				}
			}

			$errors[] =	RuleErrorBuilder::message('Comment contains PHPDoc tag but does not start with /** prefix.')
				->identifier('phpstanPlayground.phpDoc')
				->build();
		}

		return $errors;
	}

}
