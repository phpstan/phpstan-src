<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Stmt\Switch_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<Switch_>
 */
final class DuplicateCasesInSwitchRule implements Rule
{

	public function __construct(private ExprPrinter $exprPrinter)
	{
	}

	public function getNodeType(): string
	{
		return Switch_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		$seenCases = [];

		foreach ($node->cases as $case) {
			if ($case->cond === null) {
				continue;
			}

			$condType = $scope->getType($case->cond);
			$scalarValues = $condType->getConstantScalarValues();

			if (count($scalarValues) === 1) {
				$key = ['scalar', $scalarValues[0]];
			} else {
				$enumCases = $condType->getEnumCases();

				if (count($enumCases) !== 1) {
					continue;
				}

				$key = ['enum', $enumCases[0]->getClassName(), $enumCases[0]->getEnumCaseName()];
			}

			$firstSeen = null;

			foreach ($seenCases as $seenCase) {
				if ($seenCase['key'] === $key) {
					$firstSeen = $seenCase;
					break;
				}
			}

			if ($firstSeen === null) {
				$seenCases[] = [
					'key' => $key,
					'printed' => $this->exprPrinter->printExpr($case->cond),
					'line' => $case->cond->getStartLine(),
				];
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Case %s in switch is a duplicate of case %s on line %d.',
				$this->exprPrinter->printExpr($case->cond),
				$firstSeen['printed'],
				$firstSeen['line'],
			))->identifier('switch.duplicateCase')->line($case->getStartLine())->build();
		}

		return $errors;
	}

}
