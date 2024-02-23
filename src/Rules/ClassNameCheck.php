<?php declare(strict_types = 1);

namespace PHPStan\Rules;

class ClassNameCheck
{

	public function __construct(
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private ClassForbiddenNameCheck $classForbiddenNameCheck,
	)
	{
	}

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return RuleError[]
	 */
	public function checkClassNames(array $pairs, bool $checkClassCaseSensitivity = true): array
	{
		$errors = [];

		if ($checkClassCaseSensitivity) {
			foreach ($this->classCaseSensitivityCheck->checkClassNames($pairs) as $error) {
				$errors[] = $error;
			}
		}
		foreach ($this->classForbiddenNameCheck->checkClassNames($pairs) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

}
