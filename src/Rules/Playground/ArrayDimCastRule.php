<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<ArrayDimFetch>
 */
final class ArrayDimCastRule implements Rule
{

	public function getNodeType(): string
	{
		return ArrayDimFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->dim === null) {
			return [];
		}

		$varType = $scope->getType($node->var);
		if ($varType->isArray()->no()) {
			return [];
		}

		$dimType = $scope->getType($node->dim);
		if (!$dimType->isConstantScalarValue()->yes()) {
			return [];
		}

		$constantScalars = $dimType->getConstantScalarTypes();
		$errors = [];
		foreach ($constantScalars as $constantScalar) {
			$arrayKeyType = $constantScalar->toArrayKey();
			if ($arrayKeyType->equals($constantScalar)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Key %s (%s) will be cast to %s (%s) in the array access.',
				$constantScalar->describe(VerbosityLevel::value()),
				$constantScalar->generalize(GeneralizePrecision::lessSpecific())->describe(VerbosityLevel::typeOnly()),
				$arrayKeyType->describe(VerbosityLevel::value()),
				$arrayKeyType->describe(VerbosityLevel::typeOnly()),
			))->identifier('phpstanPlayground.arrayDimFetchCast')
				->tip('Learn more: <fg=cyan>https://phpstan.org/blog/why-array-string-keys-are-not-type-safe</>')
				->build();
		}

		return $errors;
	}

}
