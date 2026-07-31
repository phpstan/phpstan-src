<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrors\TransformedRuleError;
use function array_key_exists;
use function array_values;
use function count;
use function is_array;
use function var_export;

/**
 * Reports the constant-condition errors deferred by the consumer rules for
 * function/method/static call conditions, after deduplicating them against the
 * ImpossibleCheckType* rules (which own the same call sites) via the
 * ImpossibleCheckTypeReportedCollector markers.
 *
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 4)]
final class FunctionCallConstantConditionRule implements Rule
{

	private const NULL_TRAIT_KEY = "\0null-trait";

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$reportedMarkers = [];
		$reportedMarkersByFile = [];
		foreach ($node->get(ImpossibleCheckTypeReportedCollector::class) as $filePath => $fileData) {
			foreach ($fileData as $data) {
				$reportedMarkers[$data[0]] = true;
				$reportedMarkersByFile[$filePath . "\0" . $data[0]] = true;
			}
		}

		$errorsByRuleTraitExprValue = [];
		foreach ($node->get(FunctionCallConstantConditionCollector::class) as $filePath => $fileData) {
			foreach ($fileData as $data) {
				$ruleName = $data[0];
				$traitName = $data[1];
				$traitKey = $traitName ?? self::NULL_TRAIT_KEY;
				// A non-trait call site is per-file: the same printed condition at
				// the same line in two different files must neither merge its
				// deferred errors nor be suppressed by the other file's marker.
				// Trait call sites keep merging across the analysed contexts -
				// trait names are unique project-wide.
				$exprString = $traitName === null ? $filePath . "\0" . $data[2] : $data[2];
				$value = $data[3];
				$valueKey = var_export($value, true);
				if ($data[3] === null) {
					$errorsByRuleTraitExprValue[$ruleName][$traitKey][$exprString][$valueKey][] = null;
					// no error reported
					continue;
				}

				$error = $data[4];
				$errorsByRuleTraitExprValue[$ruleName][$traitKey][$exprString][$valueKey][] = $error;
			}
		}

		$transformedErrors = [];
		foreach ($errorsByRuleTraitExprValue as $ruleData) {
			foreach ($ruleData as $traitKey => $traitData) {
				$isTrait = $traitKey !== self::NULL_TRAIT_KEY;
				foreach ($traitData as $exprString => $valueData) {
					// non-trait keys carry their file, so only the same file's
					// marker suppresses; trait entries match markers from any
					// analysed context
					if (array_key_exists($exprString, $isTrait ? $reportedMarkers : $reportedMarkersByFile)) {
						// the ImpossibleCheckType* rule owns this call site
						continue;
					}

					if ($isTrait && count($valueData) > 1) {
						continue;
					}

					$uniquedErrors = [];
					foreach ($valueData as $errors) {
						foreach ($errors as $errorObject) {
							if ($errorObject === null) {
								continue;
							}
							if (is_array($errorObject)) {
								$errorObject = Error::decode($errorObject);
							}

							$message = $errorObject->getMessage();
							$uniquedErrors[$message] = $errorObject;
						}
					}

					$uniquedErrors = array_values($uniquedErrors);
					if (count($uniquedErrors) === 0) {
						continue;
					}

					if (!$isTrait) {
						foreach ($uniquedErrors as $uniquedError) {
							$transformedErrors[] = new TransformedRuleError($uniquedError);
						}
						continue;
					}

					if (count($uniquedErrors) === 1) {
						// report directly in trait, no "in context of"
						$transformedErrors[] = new TransformedRuleError($uniquedErrors[0]->removeTraitContext());
						continue;
					}

					// report each error in its context
					foreach ($uniquedErrors as $uniquedError) {
						$transformedErrors[] = new TransformedRuleError($uniquedError);
					}
				}
			}
		}

		return $transformedErrors;
	}

}
