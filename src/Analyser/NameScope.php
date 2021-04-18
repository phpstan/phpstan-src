<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Type;

class NameScope
{

	private ?string $namespace;

	/** @var array<string, string> alias(string) => fullName(string) */
	private array $uses;

	private ?string $className;

	private ?string $functionName;

	private TemplateTypeMap $templateTypeMap;

	/** @var array<string, true> */
	private array $typeAliasesMap;

	/**
	 * @param string|null $namespace
	 * @param array<string, string> $uses alias(string) => fullName(string)
	 * @param string|null $className
	 * @param array<string, true> $typeAliasesMap
	 */
	public function __construct(?string $namespace, array $uses, ?string $className = null, ?string $functionName = null, ?TemplateTypeMap $templateTypeMap = null, array $typeAliasesMap = [])
	{
		$this->namespace = $namespace;
		$this->uses = $uses;
		$this->className = $className;
		$this->functionName = $functionName;
		$this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
		$this->typeAliasesMap = $typeAliasesMap;
	}

	public function getNamespace(): ?string
	{
		return $this->namespace;
	}

	/**
	 * @return array<string, string>
	 */
	public function getUses(): array
	{
		return $this->uses;
	}

	public function hasUseAlias(string $name): bool
	{
		return isset($this->uses[strtolower($name)]);
	}

	public function getClassName(): ?string
	{
		return $this->className;
	}

	public function resolveStringName(string $name): string
	{
		if (strpos($name, '\\') === 0) {
			return ltrim($name, '\\');
		}

		$nameParts = explode('\\', $name);
		$firstNamePart = strtolower($nameParts[0]);
		if (isset($this->uses[$firstNamePart])) {
			if (count($nameParts) === 1) {
				return $this->uses[$firstNamePart];
			}
			array_shift($nameParts);
			return sprintf('%s\\%s', $this->uses[$firstNamePart], implode('\\', $nameParts));
		}

		if ($this->namespace !== null) {
			return sprintf('%s\\%s', $this->namespace, $name);
		}

		return $name;
	}

	public function getTemplateTypeScope(): ?TemplateTypeScope
	{
		if ($this->className !== null) {
			if ($this->functionName !== null) {
				return TemplateTypeScope::createWithMethod($this->className, $this->functionName);
			}

			return TemplateTypeScope::createWithClass($this->className);
		}

		if ($this->functionName !== null) {
			return TemplateTypeScope::createWithFunction($this->functionName);
		}

		return null;
	}

	public function getTemplateTypeMap(): TemplateTypeMap
	{
		return $this->templateTypeMap;
	}

	public function resolveTemplateTypeName(string $name): ?Type
	{
		return $this->templateTypeMap->getType($name);
	}

	public function withTemplateTypeMap(TemplateTypeMap $map): self
	{
		if ($map->isEmpty()) {
			return $this;
		}

		return new self(
			$this->namespace,
			$this->uses,
			$this->className,
			$this->functionName,
			new TemplateTypeMap(array_merge(
				$this->templateTypeMap->getTypes(),
				$map->getTypes()
			)),
			$this->typeAliasesMap
		);
	}

	public function unsetTemplateType(string $name): self
	{
		$map = $this->templateTypeMap;
		if (!$map->hasType($name)) {
			return $this;
		}

		return new self(
			$this->namespace,
			$this->uses,
			$this->className,
			$this->functionName,
			$this->templateTypeMap->unsetType($name),
			$this->typeAliasesMap
		);
	}

	public function hasTypeAlias(string $alias): bool
	{
		return array_key_exists($alias, $this->typeAliasesMap);
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['namespace'],
			$properties['uses'],
			$properties['className'],
			$properties['functionName'],
			$properties['templateTypeMap'],
			$properties['typeAliasesMap']
		);
	}

}
