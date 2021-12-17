<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use DateTime;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use Throwable;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
class DateTimeInstantiationRule implements Rule
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
		if (
			!($node->class instanceof Node\Name)
			|| count($node->getArgs()) === 0
			|| !in_array(strtolower((string) $node->class), ['datetime', 'datetimeimmutable'], true)
		) {
			return [];
		}

		$arg = $scope->getType($node->getArgs()[0]->value);
		if (!($arg instanceof ConstantStringType)) {
			return [];
		}

		$errors = [];
		$dateString = $arg->getValue();
		try {
			new DateTime($dateString);
		} catch (Throwable $e) {
			// an exception is thrown for errors only but we want to catch warnings too
		}
		$lastErrors = DateTime::getLastErrors();
		if ($lastErrors !== false) {
			foreach ($lastErrors['errors'] as $error) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating %s with %s produces an error: %s',
					(string) $node->class,
					$dateString,
					$error,
				))->build();
			}
		}

		return $errors;
	}

}
