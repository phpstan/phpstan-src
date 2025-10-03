<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Rules\TypeCoercionRuleHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Scalar\InterpolatedString>
 */
#[RegisteredRule(level: 2)]
final class InvalidPartOfEncapsedStringRule implements Rule
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		private RuleLevelHelper $ruleLevelHelper,
		private TypeCoercionRuleHelper $typeCoercionRuleHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Scalar\InterpolatedString::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		foreach ($node->parts as $part) {
			if ($part instanceof Node\InterpolatedStringPart) {
				continue;
			}

			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$part,
				'',
				fn (Type $type): bool => !$this->typeCoercionRuleHelper->coerceToString($type) instanceof ErrorType,
			);
			$partType = $typeResult->getType();
			if ($partType instanceof ErrorType) {
				continue;
			}

			$stringPartType = $this->typeCoercionRuleHelper->coerceToString($partType);
			if (!$stringPartType instanceof ErrorType) {
				continue;
			}
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Part %s (%s) of encapsed string cannot be cast to string.',
				$this->exprPrinter->printExpr($part),
				$partType->describe(VerbosityLevel::value()),
			))->identifier('encapsedStringPart.nonString')->line($part->getStartLine())->build();
		}

		return $messages;
	}

}
