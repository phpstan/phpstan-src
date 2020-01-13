<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeUtils;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
class PrintfParametersRule implements \PHPStan\Rules\Rule
{

	/** @var bool */
	private $reportInvalidPlaceholders;

	public function __construct(bool $reportInvalidPlaceholders = false)
	{
		$this->reportInvalidPlaceholders = $reportInvalidPlaceholders;
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof \PhpParser\Node\Name)) {
			return [];
		}

		$name = strtolower((string) $node->name);
		if (!in_array($name, ['printf', 'sprintf'], true)) {
			return [];
		}

		$formatArgumentPosition = 0;

		$args = $node->args;
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return [];
			}
		}
		$argsCount = count($args);
		if ($argsCount < 1) {
			return []; // caught by CallToFunctionParametersRule
		}

		$errors = [];
		$formatArgType = $scope->getType($args[$formatArgumentPosition]->value);
		$placeholdersCount = null;
		foreach (TypeUtils::getConstantStrings($formatArgType) as $formatString) {
			$formatStringObj = new PrintfFormatStringHelper($formatString->getValue());
			$placeholdersCount = max($placeholdersCount ?? 0, $formatStringObj->getNumberOfRequiredArguments());

			if (!$this->reportInvalidPlaceholders) {
				continue;
			}

			foreach ($formatStringObj->getPlaceholders() as $placeholder) {
				if ($placeholder[0] !== '_') {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Call to %s with invalid format string, `%s`, contains invalid placeholder, `%s`.',
					$name,
					$formatString->getValue(),
					$placeholder[2]
				))->build();
			}
		}

		if ($placeholdersCount === null) {
			return $errors;
		}

		$argsCount -= $formatArgumentPosition;

		if ($argsCount !== $placeholdersCount + 1) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				sprintf(
					'%s, %s.',
					$placeholdersCount === 1 ? 'Call to %s contains %d placeholder' : 'Call to %s contains %d placeholders',
					$argsCount - 1 === 1 ? '%d value given' : '%d values given'
				),
				$name,
				$placeholdersCount,
				$argsCount - 1
			))->build();
		}

		return $errors;
	}

}
