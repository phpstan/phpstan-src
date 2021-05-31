<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

class ApiRuleHelper
{

	public function isCalledFromPhpStan(?string $namespace): bool
	{
		if ($namespace === null) {
			return false;
		}

		return $this->isPhpStanCode($namespace);
	}

	public function isPhpStanCode(string $namespace): bool
	{
		if (strtolower($namespace) === 'phpstan') {
			return true;
		}

		if (strpos($namespace, 'PHPStan\\PhpDocParser\\') === 0) {
			return false;
		}

		return stripos($namespace, 'PHPStan\\') === 0;
	}

}
