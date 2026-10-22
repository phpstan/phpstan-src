<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_starts_with;

/**
 * @implements Rule<Node\Stmt>
 */
final class PhpdocCommentRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof VirtualNode) {
			return [];
		}

		$comments = $node->getComments();

		$errors = [];
		foreach ($comments as $comment) {
			foreach (['/**', '//', '#'] as $startTag) {
				if (str_starts_with($comment->getText(), $startTag)) {
					continue 2;
				}
			}

			if (Strings::match($comment->getText(), '{(\s|^)@\w+(\s|$)}') === null) {
				continue;
			}

			$errors[] =	RuleErrorBuilder::message('Comment contains PHPDoc tag but does not start with /** prefix.')
				->identifier('phpstanPlayground.phpDoc')
				->build();
		}

		return $errors;
	}

}
