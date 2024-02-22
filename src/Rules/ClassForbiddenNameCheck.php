<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use function sprintf;
use function str_starts_with;

class ClassForbiddenNameCheck
{

	private const INTERNAL_CLASS_PREFIXES = [
		'PHPStan' => '_PHPStan_',
		'Rector' => 'RectorPrefix',
		'PHP-Scoper' => '_PhpScoper',
	];

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return RuleError[]
	 */
	public function checkClassNames(array $pairs): array
	{
		$errors = [];
		foreach ($pairs as $pair) {
			$className = $pair->getClassName();

			$projectName = null;
			foreach (self::INTERNAL_CLASS_PREFIXES as $project => $prefix) {
				if (str_starts_with($className, $prefix)) {
					$projectName = $project;
					break;
				}
			}

			if ($projectName === null) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Internal %s Class cannot be referenced: %s.',
				$projectName,
				$className,
			))->line($pair->getNode()->getLine())->build();
		}

		return $errors;
	}

}
