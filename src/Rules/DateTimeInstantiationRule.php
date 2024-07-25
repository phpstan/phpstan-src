<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use DateTime;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use Throwable;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
final class DateTimeInstantiationRule implements Rule
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

		$lowerClassName = strtolower((string) $node->class);
		if (
			count($node->getArgs()) === 0
			|| !in_array($lowerClassName, ['datetime', 'datetimeimmutable'], true)
		) {
			return [];
		}

		$arg = $scope->getType($node->getArgs()[0]->value);
		$errors = [];

		foreach ($arg->getConstantStrings() as $constantString) {
			$dateString = $constantString->getValue();
			try {
				new DateTime($dateString);
			} catch (Throwable) {
				// an exception is thrown for errors only but we want to catch warnings too
			}
			$lastErrors = DateTime::getLastErrors();
			if ($lastErrors === false) {
				continue;
			}

			foreach ($lastErrors['errors'] as $error) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating %s with %s produces an error: %s',
					$lowerClassName === 'datetime' ? 'DateTime' : 'DateTimeImmutable',
					$dateString,
					$error,
				))->identifier(sprintf('new.%s', $lowerClassName === 'datetime' ? 'dateTime' : 'dateTimeImmutable'))->build();
			}
		}

		return $errors;
	}

}
