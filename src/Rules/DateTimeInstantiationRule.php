<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use DateTime;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\New_>
 */
class DateTimeInstantiationRule implements \PHPStan\Rules\Rule
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
			!($node->class instanceof \PhpParser\Node\Name)
			|| \count($node->args) === 0
			|| !\in_array(strtolower((string) $node->class), ['datetime', 'datetimeimmutable'], true)
		) {
			return [];
		}

		$arg = $scope->getType($node->args[0]->value);
		if (!($arg instanceof ConstantStringType)) {
			return [];
		}

		$errors = [];
		$dateString = $arg->getValue();
		try {
			new DateTime($dateString);
		} catch (\Throwable $e) {
			// an exception is thrown for errors only but we want to catch warnings too
		}
		$lastErrors = DateTime::getLastErrors();
		if ($lastErrors !== false) {
			foreach ($lastErrors['warnings'] as $warning) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating %s with %s produces a warning: %s',
					(string) $node->class,
					$dateString,
					$warning
				))->build();
			}
			foreach ($lastErrors['errors'] as $error) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating %s with %s produces an error: %s',
					(string) $node->class,
					$dateString,
					$error
				))->build();
			}
		}

		return $errors;
	}

}
