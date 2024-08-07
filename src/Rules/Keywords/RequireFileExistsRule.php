<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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
					$this->getErrorMessage($node, $filePath)->build()
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

	private function getErrorMessage(Include_ $node, string $filePath): RuleErrorBuilder
	{
		$message = match ($node->type) {
			Include_::TYPE_REQUIRE => 'Path in require() "%s" is not a file or it does not exist.',
			Include_::TYPE_REQUIRE_ONCE => 'Path in require_once() "%s" is not a file or it does not exist.',
		};

		return RuleErrorBuilder::message(
			sprintf(
				$message,
				$filePath,
			),
		);
	}

	private function resolveFilePath(Include_ $node, Scope $scope): ?string
	{
		$type = $scope->getType($node->expr);
		$paths = $type->getConstantStrings();

		return $paths ? $paths[0]->getValue() : null;
	}
}
