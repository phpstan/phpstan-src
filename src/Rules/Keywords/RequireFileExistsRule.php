<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Expr\Include_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
use function str_replace;
use function stream_resolve_include_path;

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
		$errors = [];
		$paths = $this->resolveFilePaths($node, $scope);

		foreach ($paths as $path) {
			if (stream_resolve_include_path($path) !== false) {
				continue;
			}

			$errors[] = $this->getErrorMessage($node, $path);
		}

		return $errors;
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

	/**
	 * @return array<string>
	 */
	private function resolveFilePaths(Include_ $node, Scope $scope): array
	{
		$paths = [];
		$type = $scope->getType($node->expr);
		$constantStrings = $type->getConstantStrings();

		foreach ($constantStrings as $constantString) {
			$paths[] = $constantString->getValue();
		}

		return $paths;
	}

}
