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

		return stripos($namespace, 'PHPStan\\') === 0;
	}

}
