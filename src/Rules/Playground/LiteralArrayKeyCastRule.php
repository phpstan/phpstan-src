<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Array_>
 */
final class LiteralArrayKeyCastRule implements Rule
{

	public function getNodeType(): string
	{
		return Array_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->items as $item) {
			if ($item->key === null) {
				continue;
			}

			$keyType = $scope->getType($item->key);
			if (!$keyType->isConstantScalarValue()->yes()) {
				continue;
			}

			$constantScalars = $keyType->getConstantScalarTypes();
			foreach ($constantScalars as $constantScalar) {
				$arrayKeyType = $constantScalar->toArrayKey();
				if ($arrayKeyType->equals($constantScalar)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Key %s (%s) will be cast to %s (%s) in the array.',
					$constantScalar->describe(VerbosityLevel::value()),
					$constantScalar->generalize(GeneralizePrecision::lessSpecific())->describe(VerbosityLevel::typeOnly()),
					$arrayKeyType->describe(VerbosityLevel::value()),
					$arrayKeyType->describe(VerbosityLevel::typeOnly()),
				))->identifier('phpstanPlayground.arrayKeyCast')
					->tip('Learn more: <fg=cyan>https://phpstan.org/blog/why-array-string-keys-are-not-type-safe</>')
					->line($item->getStartLine())
					->build();
			}
		}

		return $errors;
	}

}
