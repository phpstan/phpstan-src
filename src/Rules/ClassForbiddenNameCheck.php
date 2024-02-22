<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use function sprintf;
use function str_starts_with;

class ClassForbiddenNameCheck
{

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return RuleError[]
	 */
	public function checkClassNames(array $pairs): array
	{
		$errors = [];
		foreach ($pairs as $pair) {
			$className = $pair->getClassName();

			if (!str_starts_with($className, '_PHPStan_')) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Internal PHPStan Class cannot be referenced: %s.',
				$className,
			))->line($pair->getNode()->getLine())->build();
		}

		return $errors;
	}

}
