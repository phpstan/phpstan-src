<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use function sprintf;
use function str_starts_with;
use function strpos;
use function substr;

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

			$error = RuleErrorBuilder::message(sprintf(
				'Referencing prefixed %s class: %s.',
				$projectName,
				$className,
			))->line($pair->getNode()->getLine())->nonIgnorable();

			if (strpos($className, '\\') !== false) {
				$error->tip(sprintf(
					'This is most likely unintentional. Did you mean to type %s?',
					substr($className, strpos($className, '\\')),
				));
			}

			$errors[] = $error->build();
		}

		return $errors;
	}

}
