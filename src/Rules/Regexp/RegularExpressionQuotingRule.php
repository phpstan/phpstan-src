<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Php\RegexExpressionHelper;
use function array_filter;
use function array_merge;
use function array_values;
use function count;
use function in_array;
use function sprintf;
use function strlen;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class RegularExpressionQuotingRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RegexExpressionHelper $regexExpressionHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (
			!in_array($functionReflection->getName(), [
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

		$normalizedArgs = $this->getNormalizedArgs($node, $scope, $functionReflection);
		if ($normalizedArgs === null) {
			return [];
		}
		if (!isset($normalizedArgs[0])) {
			return [];
		}
		if (!$normalizedArgs[0]->value instanceof Concat) {
			return [];
		}

		$patternDelimiters = $this->regexExpressionHelper->getPatternDelimiters($normalizedArgs[0]->value, $scope);
		return $this->validateQuoteDelimiters($normalizedArgs[0]->value, $scope, $patternDelimiters);
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
		if (!$pregQuote->name instanceof Node\Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($pregQuote->name, $scope)) {
			return null;
		}
		$functionReflection = $this->reflectionProvider->getFunction($pregQuote->name, $scope);

		$args = $this->getNormalizedArgs($pregQuote, $scope, $functionReflection);
		if ($args === null) {
			return null;
		}

		$patternDelimiters = $this->removeDefaultEscapedDelimiters($patternDelimiters);
		if ($patternDelimiters === []) {
			return null;
		}

		if (count($args) === 1) {
			if (count($patternDelimiters) === 1) {
				return RuleErrorBuilder::message(sprintf('Call to preg_quote() is missing delimiter %s to be effective.', $patternDelimiters[0]))
					->line($pregQuote->getStartLine())
					->identifier('argument.invalidPregQuote')
					->build();
			}

			return RuleErrorBuilder::message('Call to preg_quote() is missing delimiter parameter to be effective.')
				->line($pregQuote->getStartLine())
				->identifier('argument.invalidPregQuote')
				->build();
		}

		if (count($args) >= 2) {

			foreach ($scope->getType($args[1]->value)->getConstantStrings() as $quoteDelimiterType) {
				$quoteDelimiter = $quoteDelimiterType->getValue();

				$quoteDelimiters = $this->removeDefaultEscapedDelimiters([$quoteDelimiter]);
				if ($quoteDelimiters === []) {
					continue;
				}

				if (count($quoteDelimiters) !== 1) {
					throw new ShouldNotHappenException();
				}
				$quoteDelimiter = $quoteDelimiters[0];

				if (!in_array($quoteDelimiter, $patternDelimiters, true)) {
					if (count($patternDelimiters) === 1) {
						return RuleErrorBuilder::message(sprintf('Call to preg_quote() uses invalid delimiter %s while pattern uses %s.', $quoteDelimiter, $patternDelimiters[0]))
							->line($pregQuote->getStartLine())
							->identifier('argument.invalidPregQuote')
							->build();
					}

					return RuleErrorBuilder::message(sprintf('Call to preg_quote() uses invalid delimiter %s.', $quoteDelimiter))
						->line($pregQuote->getStartLine())
						->identifier('argument.invalidPregQuote')
						->build();
				}
			}
		}

		return null;
	}

	/**
	 * @param string[] $delimiters
	 *
	 * @return list<string>
	 */
	private function removeDefaultEscapedDelimiters(array $delimiters): array
	{
		return array_values(array_filter($delimiters, fn (string $delimiter): bool => !$this->isDefaultEscaped($delimiter)));
	}

	private function isDefaultEscaped(string $delimiter): bool
	{
		if (strlen($delimiter) !== 1) {
			return false;
		}

		return in_array(
			$delimiter,
			// these delimiters are escaped, no matter what preg_quote() 2nd arg looks like
			['.', '\\',  '+', '*', '?', '[', '^', ']', '$', '(', ')', '{', '}', '=', '!', '<', '>', '|', ':', '-', '#'],
			true,
		);
	}

	/**
	 * @return Node\Arg[]|null
	 */
	private function getNormalizedArgs(FuncCall $functionCall, Scope $scope, FunctionReflection $functionReflection): ?array
	{
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $functionCall);
		if ($normalizedFuncCall === null) {
			return null;
		}

		return $normalizedFuncCall->getArgs();
	}

}
