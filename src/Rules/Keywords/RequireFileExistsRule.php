<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function is_file;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Include_>
 */
final class RequireFileExistsRule implements Rule
{

	public function getNodeType(): string
	{
		return Include_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->shouldProcessNode($node)) {
			$filePath = $this->resolveFilePath($node, $scope);
			if (is_string($filePath) && !is_file($filePath)) {
				return [
					$this->getErrorMessage($node, $filePath),
				];
			}
		}

		return [];
	}

	private function shouldProcessNode(Node $node): bool
	{
		return $node instanceof Include_ && (
				$node->type === Include_::TYPE_REQUIRE
				|| $node->type === Include_::TYPE_REQUIRE_ONCE
			);
	}

	private function getErrorMessage(Include_ $node, string $filePath): RuleError
	{
		switch ($node->type) {
			case Include_::TYPE_REQUIRE:
				$message = 'Path in require() "%s" is not a file or it does not exist.';
				break;
			case Include_::TYPE_REQUIRE_ONCE:
				$message = 'Path in require_once() "%s" is not a file or it does not exist.';
				break;
			default:
				throw new ShouldNotHappenException('Rule should have already validated the node type.');
		}

		return RuleErrorBuilder::message(
			sprintf(
				$message,
				$filePath,
			),
		)->build();
	}

	private function resolveFilePath(Include_ $node, Scope $scope): ?string
	{
		$type = $scope->getType($node->expr);
		$paths = $type->getConstantStrings();

		return isset($paths[0]) ? $paths[0]->getValue() : null;
	}

}
