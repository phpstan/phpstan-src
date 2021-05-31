<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

class ApiRuleHelper
{

	public function isCalledFromPhpStan(?string $namespace): bool
	{
		return false;
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
