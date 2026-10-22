<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Cast>
 */
#[RegisteredRule(level: 0)]
final class DeprecatedCastRule implements Rule
{

	public function getNodeType(): string
	{
		return Cast::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->getPhpVersion()->deprecatesNonStandardCasts()->yes()) {
			return [];
		}

		if ($node instanceof Cast\Int_) {
			$kind = $node->getAttribute('kind', Cast\Int_::KIND_INT);
			if ($kind === Cast\Int_::KIND_INTEGER) {
				return [
					RuleErrorBuilder::message('Non-standard (integer) cast is deprecated in PHP 8.5. Use (int) instead.')
						->identifier('cast.deprecated')
						->build(),
				];
			}

			return [];
		}

		if ($node instanceof Cast\Bool_) {
			$kind = $node->getAttribute('kind', Cast\Bool_::KIND_BOOL);
			if ($kind === Cast\Bool_::KIND_BOOLEAN) {
				return [
					RuleErrorBuilder::message('Non-standard (boolean) cast is deprecated in PHP 8.5. Use (bool) instead.')
						->identifier('cast.deprecated')
						->build(),
				];
			}

			return [];
		}

		if ($node instanceof Cast\Double) {
			$kind = $node->getAttribute('kind', Cast\Double::KIND_FLOAT);
			if ($kind === Cast\Double::KIND_DOUBLE) {
				return [
					RuleErrorBuilder::message('Non-standard (double) cast is deprecated in PHP 8.5. Use (float) instead.')
						->identifier('cast.deprecated')
						->build(),
				];
			}

			return [];
		}

		if ($node instanceof Cast\String_) {
			$kind = $node->getAttribute('kind', Cast\String_::KIND_STRING);
			if ($kind === Cast\String_::KIND_BINARY) {
				return [
					RuleErrorBuilder::message('Non-standard (binary) cast is deprecated in PHP 8.5. Use (string) instead.')
						->identifier('cast.deprecated')
						->build(),
				];
			}

			return [];
		}

		return [];
	}

}
