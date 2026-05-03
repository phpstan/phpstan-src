<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use DateTimeZone;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use Throwable;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
#[RegisteredRule(level: 5)]
final class DateTimeZoneInstantiationRule implements Rule
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
			|| strtolower((string) $node->class) !== 'datetimezone'
		) {
			return [];
		}

		$arg = $scope->getType($node->getArgs()[0]->value);
		$errors = [];

		foreach ($arg->getConstantStrings() as $constantString) {
			$timezoneString = $constantString->getValue();
			try {
				new DateTimeZone($timezoneString);
			} catch (Throwable $e) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Instantiating DateTimeZone with %s produces an error: %s',
					$timezoneString,
					$e->getMessage(),
				))->identifier('new.dateTimeZone')->build();
			}
		}

		return $errors;
	}

}
