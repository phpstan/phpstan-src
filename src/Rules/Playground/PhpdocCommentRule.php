<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Comment;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
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

		$comments = $node->getAttribute('comments', []);

		$errors = [];
		foreach ($comments as $comment) {
			if (!$comment instanceof Comment) {
				throw new ShouldNotHappenException();
			}
			if (!str_contains($comment->getText(), '@')) {
				continue;
			}

			if (str_starts_with($comment->getText(), '/**')) {
				continue;
			}

			$errors[] =	RuleErrorBuilder::message('Comment contains phpdoc-tag but does not start with /** tag.')
				->identifier('phpstanPlayground.noPhpdoc')
				->build();
		}

		return $errors;
	}

}
