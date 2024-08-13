<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function is_file;
use function is_string;
use function sprintf;
use function str_replace;

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
		$filePath = $this->resolveFilePath($node, $scope);
		if (is_string($filePath) && !is_file($filePath)) {
			return [
				$this->getErrorMessage($node, $filePath),
			];
		}

		return [];
	}

	private function getErrorMessage(Include_ $node, string $filePath): IdentifierRuleError
	{
		$message = 'Path in %s() "%s" is not a file or it does not exist.';

		switch ($node->type) {
			case Include_::TYPE_REQUIRE:
				$type = 'require';
				break;
			case Include_::TYPE_REQUIRE_ONCE:
				$type = 'require_once';
				break;
			case Include_::TYPE_INCLUDE:
				$type = 'include';
				break;
			case Include_::TYPE_INCLUDE_ONCE:
				$type = 'include_once';
				break;
			default:
				throw new ShouldNotHappenException('Rule should have already validated the node type.');
		}

		$identifier = sprintf('%s.fileNotFound', str_replace('_once', 'Once', $type));

		return RuleErrorBuilder::message(
			sprintf(
				$message,
				$type,
				$filePath,
			),
		)->nonIgnorable()->identifier($identifier)->build();
	}

	private function resolveFilePath(Include_ $node, Scope $scope): ?string
	{
		$type = $scope->getType($node->expr);
		$paths = $type->getConstantStrings();

		return isset($paths[0]) ? $paths[0]->getValue() : null;
	}

}
