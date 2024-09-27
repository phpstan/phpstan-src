<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\Exception\Exception as HoaException;
use Hoa\File\Read;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Regex\RegexExpressionHelper;
use function in_array;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class RegularExpressionPatternRule implements Rule
{

	private static ?Parser $parser = null;

	public function __construct(
		private RegexExpressionHelper $regexExpressionHelper,
		private bool $reportUnparsedRegexpWhenCompilable,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$patterns = $this->extractPatterns($node, $scope);

		$errors = [];
		foreach ($patterns as $pattern) {
			$errorMessage = $this->validatePatternByPcre($pattern);

			if ($errorMessage !== null) {
				$errors[] = RuleErrorBuilder::message(sprintf('Regex pattern is invalid: %s', $errorMessage))->identifier('regexp.pattern')->build();

				continue;
			}

			$errorMessage = $this->validatePatternByParserGrammar($pattern);

			if ($errorMessage === null || !$this->reportUnparsedRegexpWhenCompilable) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Regex pattern cannot be parsed: %s', $errorMessage))
				->identifier('regexp.pattern')
				->tip(sprintf('Please open an issue for your regex pattern at %s', 'https://github.com/phpstan/phpstan/issues'))
				->build();
		}

		return $errors;
	}

	/**
	 * @return string[]
	 */
	private function extractPatterns(FuncCall $functionCall, Scope $scope): array
	{
		if (!$functionCall->name instanceof Node\Name) {
			return [];
		}
		$functionName = strtolower((string) $functionCall->name);
		if (!str_starts_with($functionName, 'preg_')) {
			return [];
		}

		if (!isset($functionCall->getArgs()[0])) {
			return [];
		}
		$patternNode = $functionCall->getArgs()[0]->value;
		$patternType = $scope->getType($patternNode);

		$patternStrings = [];

		if (
			in_array($functionName, [
				'preg_match',
				'preg_match_all',
				'preg_split',
				'preg_grep',
				'preg_replace',
				'preg_replace_callback',
				'preg_filter',
			], true)
		) {
			if ($patternNode instanceof Node\Expr\BinaryOp\Concat) {
				$patternType = $this->regexExpressionHelper->resolvePatternConcat($patternNode, $scope);
			}
			foreach ($patternType->getConstantStrings() as $constantStringType) {
				$patternStrings[] = $constantStringType->getValue();
			}
		}

		if (
			in_array($functionName, [
				'preg_replace',
				'preg_replace_callback',
				'preg_filter',
			], true)
		) {
			foreach ($patternType->getConstantArrays() as $constantArrayType) {
				foreach ($constantArrayType->getValueTypes() as $arrayKeyType) {
					foreach ($arrayKeyType->getConstantStrings() as $constantString) {
						$patternStrings[] = $constantString->getValue();
					}
				}
			}
		}

		if ($functionName === 'preg_replace_callback_array') {
			foreach ($patternType->getConstantArrays() as $constantArrayType) {
				foreach ($constantArrayType->getKeyTypes() as $arrayKeyType) {
					foreach ($arrayKeyType->getConstantStrings() as $constantString) {
						$patternStrings[] = $constantString->getValue();
					}
				}
			}
		}

		return $patternStrings;
	}

	private function validatePatternByPcre(string $pattern): ?string
	{
		try {
			Strings::match('', $pattern);
		} catch (RegexpException $e) {
			return $e->getMessage();
		}

		return null;
	}

	private function validatePatternByParserGrammar(string $pattern): ?string
	{
		if (self::$parser === null) {
			/** @throws void */
			self::$parser = Llk::load(new Read(__DIR__ . '/../../../resources/RegexGrammar.pp'));
		}

		$rawRegex = $this->regexExpressionHelper->removeDelimitersAndModifiers($pattern);
		try {
			self::$parser->parse($rawRegex);
		} catch (HoaException $e) {
			return $e->getMessage();
		}

		return null;
	}

}
