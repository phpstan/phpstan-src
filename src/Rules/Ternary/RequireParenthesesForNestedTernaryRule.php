<?php declare(strict_types = 1);

namespace PHPStan\Rules\Ternary;

use PhpParser\Node;
use PhpParser\Node\Expr\Ternary;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Ternary>
 */
class RequireParenthesesForNestedTernaryRule implements Rule
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function getNodeType(): string
	{
		return Ternary::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->requiresParenthesesForNestedTernaries()) {
			return [];
		}

		$parent = $node->getAttribute('parent');
		if (!$parent instanceof Ternary) {
			return [];
		}

		if ($node->if === null && $parent->if === null) {
			return [];
		}

		if ($node->getAttribute('expressionOrder') === 1) {
			return [];
		}

		$beforeTokens = $node->getAttribute('tokensBefore', []);
		$afterTokens = $node->getAttribute('tokensAfter', []);
		if (
			$this->hasParentheses(array_reverse($beforeTokens))
			&& $this->hasParentheses($afterTokens)
		) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Nested ternary operator needs to have parentheses around it.')
				->tip('See: https://wiki.php.net/rfc/ternary_associativity')
				->nonIgnorable()
				->build(),
		];
	}

	/**
	 * @param mixed[] $tokens
	 * @return bool
	 */
	private function hasParentheses(array $tokens): bool
	{
		foreach ($tokens as $token) {
			if (is_array($token)) {
				$content = $token[1];
			} else {
				$content = $token;
			}

			$trimmed = trim($content);
			if ($trimmed === '') {
				continue;
			}

			if (in_array($trimmed, ['(', ')'], true)) {
				return true;
			}

			return false;
		}

		return false;
	}

}
