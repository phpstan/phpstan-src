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

		$patternDelimiter = $this->getDelimiterFromConcat($normalizedArgs[0]->value, $scope);
		return $this->validateQuoteDelimiters($normalizedArgs[0]->value, $scope, $patternDelimiter);
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function validateQuoteDelimiters(Concat $concat, Scope $scope, ?string $patternDelimiter): array
	{
		if ($patternDelimiter === null) {
			return [];
		}

		$errors = [];
		if (
			$concat->left instanceof FuncCall
			&& $concat->left->name instanceof Name
			&& $concat->left->name->toLowerString() === 'preg_quote'
		) {
			$pregError = $this->validatePregQuote($concat->left, $scope, $patternDelimiter);
			if ($pregError !== null) {
				$errors[] = $pregError;
			}
		} elseif ($concat->left instanceof Concat) {
			$errors = array_merge($errors, $this->validateQuoteDelimiters($concat->left, $scope, $patternDelimiter));
		}

		if (
			$concat->right instanceof FuncCall
			&& $concat->right->name instanceof Name
			&& $concat->right->name->toLowerString() === 'preg_quote'
		) {
			$pregError = $this->validatePregQuote($concat->right, $scope, $patternDelimiter);
			if ($pregError !== null) {
				$errors[] = $pregError;
			}
		} elseif ($concat->right instanceof Concat) {
			$errors = array_merge($errors, $this->validateQuoteDelimiters($concat->right, $scope, $patternDelimiter));
		}

		return $errors;
	}

	private function validatePregQuote(FuncCall $pregQuote, Scope $scope, string $patternDelimiter): ?IdentifierRuleError
	{
		if (!$pregQuote->name instanceof Node\Name) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($pregQuote->name, $scope)) {
			return null;
		}
		$functionReflection = $this->reflectionProvider->getFunction($pregQuote->name, $scope);

		$normalizedArgs = $this->getNormalizedArgs($pregQuote, $scope, $functionReflection);
		if ($normalizedArgs === null) {
			return null;
		}

		if (!isset($normalizedArgs[1])) {
			return RuleErrorBuilder::message(sprintf('Call to preg_quote() is missing delimiter %s to be effective.', $patternDelimiter))
				->line($pregQuote->getStartLine())
				->identifier('argument.invalidPregQuote')
				->build();
		}

		if (count($normalizedArgs) >= 2) {
			foreach ($scope->getType($normalizedArgs[1]->value)->getConstantStrings() as $quoteDelimiterType) {
				if ($quoteDelimiterType->getValue() !== $patternDelimiter) {
					return RuleErrorBuilder::message(sprintf('Call to preg_quote() uses invalid delimiter %s while pattern uses %s.', $quoteDelimiterType->getValue(), $patternDelimiter))
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
	 */
	private function getDelimiterFromConcat(Concat $concat, Scope $scope): ?string
	{
		if ($concat->left instanceof Concat) {
			return $this->getDelimiterFromConcat($concat->left, $scope);
		}

		$left = $scope->getType($concat->left);

		$constantStrings = $left->getConstantStrings();
		// since we analyze a Expr directly given to a call, it can only ever resolve to a single constant string
		if (count($constantStrings) === 1) {
			return $this->getDelimiterFromString($constantStrings[0]);
		}

		return null;
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
