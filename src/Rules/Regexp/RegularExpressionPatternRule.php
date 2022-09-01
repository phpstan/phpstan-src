<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\TypeUtils;
use function in_array;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class RegularExpressionPatternRule implements Rule
{

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$patterns = $this->extractPatterns($node, $scope);

		$errors = [];
		foreach ($patterns as $pattern) {
			$errorMessage = $this->validatePattern($pattern);
			if ($errorMessage === null) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Regex pattern is invalid: %s', $errorMessage))->build();
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

		foreach (TypeUtils::getConstantStrings($patternType) as $constantStringType) {
			if (
				!in_array($functionName, [
					'preg_match',
					'preg_match_all',
					'preg_split',
					'preg_grep',
					'preg_replace',
					'preg_replace_callback',
					'preg_filter',
				], true)
			) {
				continue;
			}

			$patternStrings[] = $constantStringType->getValue();
		}

		foreach ($patternType->getConstantArrays() as $constantArrayType) {
			if (
				in_array($functionName, [
					'preg_replace',
					'preg_replace_callback',
					'preg_filter',
				], true)
			) {
				foreach ($constantArrayType->getValueTypes() as $arrayKeyType) {
					if (!$arrayKeyType instanceof ConstantStringType) {
						continue;
					}

					$patternStrings[] = $arrayKeyType->getValue();
				}
			}

			if ($functionName !== 'preg_replace_callback_array') {
				continue;
			}

			foreach ($constantArrayType->getKeyTypes() as $arrayKeyType) {
				if (!$arrayKeyType instanceof ConstantStringType) {
					continue;
				}

				$patternStrings[] = $arrayKeyType->getValue();
			}
		}

		return $patternStrings;
	}

	private function validatePattern(string $pattern): ?string
	{
		try {
			Strings::match('', $pattern);
		} catch (RegexpException $e) {
			return $e->getMessage();
		}

		return null;
	}

}
