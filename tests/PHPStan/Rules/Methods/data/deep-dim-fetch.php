<?php declare(strict_types = 1);

namespace DeepDimFetch;

use function is_callable;
use function PHPStan\debugScope;

final class NameScope {}

final class FileTypeMapper
{

	/** @var (true|callable(): NameScope|NameScope)[][] */
	private array $inProcess = [];

	public function getNameScope(
		string $fileName,
		?string $className,
		?string $traitName,
		?string $functionName,
	): NameScope
	{
		$nameScopeKey = $this->getNameScopeKey($fileName, $className, $traitName, $functionName);
		if (!isset($this->inProcess[$fileName][$nameScopeKey])) { // wrong $fileName due to traits
			throw new \RuntimeException();
		}

		if ($this->inProcess[$fileName][$nameScopeKey] === true) { // PHPDoc has cyclic dependency
			throw new \RuntimeException();
		}

		if (is_callable($this->inProcess[$fileName][$nameScopeKey])) {
			$resolveCallback = $this->inProcess[$fileName][$nameScopeKey];
			$this->inProcess[$fileName][$nameScopeKey] = true;
			$this->inProcess[$fileName][$nameScopeKey] = $resolveCallback();
		}

		return $this->inProcess[$fileName][$nameScopeKey];
	}

	private function getNameScopeKey(
		?string $file,
		?string $class,
		?string $trait,
		?string $function,
	): string
	{
		return '';
	}

}
