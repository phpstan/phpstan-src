<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Echo_>
 */
#[RegisteredRule(level: 2)]
final class EchoRule implements Rule
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];

		foreach ($node->exprs as $key => $expr) {
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$expr,
				'',
				static fn (Type $type): bool => !$type->toString()->isError()->yes(),
			);

			if ($typeResult->getType()->isError()->yes()
				|| !$typeResult->getType()->toString()->isError()->yes()
			) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Parameter #%d (%s) of echo cannot be converted to string.',
				$key + 1,
				$typeResult->getType()->describe(VerbosityLevel::value()),
			))->identifier('echo.nonString')->line($expr->getStartLine())->build();
		}
		return $messages;
	}

}
