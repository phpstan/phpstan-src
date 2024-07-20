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
use PHPStan\Type\Constant\ConstantStringType;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function substr;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class RegularExpressionQuotingRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
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

		$patternDelimiters = $this->getDelimitersFromConcat($normalizedArgs[0]->value, $scope);
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
		if (!$this->reflectionProvider->hasFunction($pregQuote->name, $scope)) {
			return null;
		}
		$functionReflection = $this->reflectionProvider->getFunction($pregQuote->name, $scope);

		$args = $this->getNormalizedArgs($pregQuote, $scope, $functionReflection);
		if ($args === null) {
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
				if (!in_array($quoteDelimiterType->getValue(), $patternDelimiters, true)) {
					if (count($patternDelimiters) === 1) {
						return RuleErrorBuilder::message(sprintf('Call to preg_quote() uses invalid delimiter %s while pattern uses %s.', $quoteDelimiterType->getValue(), $patternDelimiters[0]))
							->line($pregQuote->getStartLine())
							->identifier('argument.invalidPregQuote')
							->build();
					}

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

		// check for delimiters which get properly escaped by default
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
