<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrors\TransformedRuleError;
use function array_values;
use function count;
use function is_array;
use function ksort;
use function var_export;

/**
 * @implements Rule<CollectedDataNode>
 */
#[RegisteredRule(level: 0)]
final class ConstantConditionInTraitRule implements Rule
{

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errorsByRuleTraitExprValue = [];
		foreach ($node->get(ConstantConditionInTraitCollector::class) as $fileData) {
			foreach ($fileData as $data) {
				$ruleName = $data[0];
				$traitName = $data[1];
				$exprString = $data[2];
				$value = $data[3];
				$valueKey = var_export($value, true);
				if ($data[3] === null) {
					$errorsByRuleTraitExprValue[$ruleName][$traitName][$exprString][$valueKey][] = null;
					// no error reported
					continue;
				}

				$error = $data[4];
				$errorsByRuleTraitExprValue[$ruleName][$traitName][$exprString][$valueKey][] = $error;
			}
		}

		$transformedErrors = [];
		foreach ($errorsByRuleTraitExprValue as $ruleData) {
			foreach ($ruleData as $traitData) {
				foreach ($traitData as $valueData) {
					if (count($valueData) > 1) {
						continue;
					}

					$uniquedErrors = [];
					$traitContexts = [];
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
							$traitContexts[$errorObject->getFilePath()] = $errorObject->getFile();
						}
					}

					$uniquedErrors = array_values($uniquedErrors);
					if (count($uniquedErrors) === 0) {
						continue;
					}

					if (count($uniquedErrors) === 1) {
						// report directly in trait, no "in context of"
						ksort($traitContexts);
						$transformedErrors[] = new TransformedRuleError($uniquedErrors[0]->removeTraitContext()->withTraitContexts($traitContexts));
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
