<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;
use function substr;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class RegularExpressionQuotingRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$functionName = strtolower((string) $node->name);
		if (
			!in_array($functionName, [
				'preg_match',
				'preg_match_all',
				'preg_filter',
				'preg_grep',
				'preg_replace',
				'preg_replace_callback',
				'preg_split',
			], true)
		) {
			return [];
		}

		$args = $node->getArgs();
		if (!isset($args[0])) {
			return [];
		}

		if (!$args[0]->value instanceof Concat) {
			return [];
		}

		$patternDelimiters = $this->getDelimitersFromConcat($args[0]->value, $scope);
		return $this->validateQuoteDelimiters($args[0]->value, $scope, $patternDelimiters);
	}

	/**
	 * @param string[] $patternDelimiters
	 *
	 * @return list<IdentifierRuleError>
	 */
	private function validateQuoteDelimiters(Concat $concat, Scope $scope, array $patternDelimiters): array
	{
		if ($patternDelimiters === []) {
			return [];
		}

		$errors = [];
		if (
			$concat->left instanceof FuncCall
			&& $concat->left->name instanceof Name
			&& $concat->left->name->toLowerString() === 'preg_quote'
		) {
			$pregError = $this->validatePregQuote($concat->left, $scope, $patternDelimiters);
			if ($pregError !== null) {
				$errors[] = $pregError;
			}
		} elseif ($concat->left instanceof Concat) {
			$errors = array_merge($errors, $this->validateQuoteDelimiters($concat->left, $scope, $patternDelimiters));
		}

		if (
			$concat->right instanceof FuncCall
			&& $concat->right->name instanceof Name
			&& $concat->right->name->toLowerString() === 'preg_quote'
		) {
			$pregError = $this->validatePregQuote($concat->right, $scope, $patternDelimiters);
			if ($pregError !== null) {
				$errors[] = $pregError;
			}
		} elseif ($concat->right instanceof Concat) {
			$errors = array_merge($errors, $this->validateQuoteDelimiters($concat->right, $scope, $patternDelimiters));
		}

		return $errors;
	}

	/**
	 * @param string[] $patternDelimiters
	 */
	private function validatePregQuote(FuncCall $pregQuote, Scope $scope, array $patternDelimiters): ?IdentifierRuleError
	{
		$args = $pregQuote->getArgs();

		if (
			count($args) === 1
		) {
			return RuleErrorBuilder::message('Call to preg_quote() is missing delimiter parameter to be effective.')
				->line($pregQuote->getStartLine())
				->identifier('argument.invalidPregQuote')
				->build();
		}

		if (count($args) >= 2) {
			foreach ($scope->getType($args[1]->value)->getConstantStrings() as $quoteDelimiterType) {
				if (!in_array($quoteDelimiterType->getValue(), $patternDelimiters, true)) {
					return RuleErrorBuilder::message(sprintf('Call to preg_quote() uses invalid delimiter %s.', $quoteDelimiterType->getValue()))
						->line($pregQuote->getStartLine())
						->identifier('argument.invalidPregQuote')
						->build();
				}
			}
		}

		return null;
	}

	/**
	 * Get delimiters from non-constant patterns, if possible.
	 *
	 * @return string[]
	 */
	private function getDelimitersFromConcat(Concat $concat, Scope $scope): array
	{
		if ($concat->left instanceof Concat) {
			return $this->getDelimitersFromConcat($concat->left, $scope);
		}

		$left = $scope->getType($concat->left);

		$delimiters = [];
		foreach ($left->getConstantStrings() as $leftString) {
			$delimiter = $this->getDelimiterFromString($leftString);
			if ($delimiter === null) {
				continue;
			}

			$delimiters[] = $delimiter;
		}
		return $delimiters;
	}

	private function getDelimiterFromString(ConstantStringType $string): ?string
	{
		if ($string->getValue() === '') {
			return null;
		}

		$firstChar = substr($string->getValue(), 0, 1);

		if (in_array(
			$firstChar,
			['.', '\\',  '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-', '#'],
			true,
		)
		) {
			return null;
		}

		return $firstChar;
	}

}
