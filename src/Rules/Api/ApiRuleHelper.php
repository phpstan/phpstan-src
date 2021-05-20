<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

class ApiRuleHelper
{

	public function isInPhpStanNamespace(?string $namespace): bool
	{
		if ($namespace === null) {
			return false;
		}

		if (strtolower($namespace) === 'phpstan') {
			return true;
		}

		if (strpos($namespace, 'PHPStan\\PhpDocParser\\') === 0) {
			return false;
		}

		return stripos($namespace, 'PHPStan\\') === 0;
	}

}
