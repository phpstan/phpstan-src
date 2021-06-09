<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;

class ApiRuleHelper
{

	public function isPhpStanCode(Scope $scope, string $namespace): bool
	{
		$scopeNamespace = $scope->getNamespace();
		if ($scopeNamespace === null) {
			return $this->isPhpStanName($namespace);
		}

		if ($this->isPhpStanName($scopeNamespace)) {
			return false;
		}

		return $this->isPhpStanName($namespace);
	}

	public function isPhpStanName(string $namespace): bool
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
