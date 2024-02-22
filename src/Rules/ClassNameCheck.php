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
			$errors += $this->classCaseSensitivityCheck->checkClassNames($pairs);
		}
		$errors += $this->classForbiddenNameCheck->checkClassNames($pairs);

		return $errors;
	}

}
