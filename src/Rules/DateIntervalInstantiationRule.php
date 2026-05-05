<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use DateInterval;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use Throwable;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class DateIntervalInstantiationRule implements Rule
{

	public function getNodeType(): string
	{
		return New_::class;
	}

	/**
	 * @param New_ $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		if (
			count($node->getArgs()) === 0
			|| strtolower((string) $node->class) !== 'dateinterval'
		) {
			return [];
		}

		$arg = $scope->getType($node->getArgs()[0]->value);
		$errors = [];

		foreach ($arg->getConstantStrings() as $constantString) {
			$dateIntervalString = $constantString->getValue();
			try {
				new DateInterval($dateIntervalString);
			} catch (Throwable $e) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating DateInterval with %s produces an error: %s',
					$dateIntervalString,
					$e->getMessage(),
				))->identifier('new.dateInterval')->build();
			}
		}

		return $errors;
	}

}
