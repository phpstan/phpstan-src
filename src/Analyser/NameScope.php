<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Type;
use function array_key_exists;
use function array_merge;
use function array_shift;
use function count;
use function explode;
use function implode;
use function ltrim;
use function sprintf;
use function strpos;
use function strtolower;

/** @api */
class NameScope
{

	private TemplateTypeMap $templateTypeMap;

	/**
	 * @api
	 * @param array<string, string> $uses alias(string) => fullName(string)
	 * @param array<string, true> $typeAliasesMap
	 */
	public function __construct(private ?string $namespace, private array $uses, private ?string $className = null, private ?string $functionName = null, ?TemplateTypeMap $templateTypeMap = null, private array $typeAliasesMap = [], private bool $bypassTypeAliases = false)
	{
		$this->templateTypeMap = $templateTypeMap ?? TemplateTypeMap::createEmpty();
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

	/**
	 * @return non-empty-list<string>
	 */
	public function resolveConstantNames(string $name): array
	{
		if (strpos($name, '\\') === 0) {
			return [ltrim($name, '\\')];
		}

		$nameParts = explode('\\', $name);
		if (count($nameParts) > 1) {
			$firstNamePart = strtolower($nameParts[0]);
			if (isset($this->uses[$firstNamePart])) {
				array_shift($nameParts);
				return [sprintf('%s\\%s', $this->uses[$firstNamePart], implode('\\', $nameParts))];
			}
		}

		if ($this->namespace !== null) {
			return [
				sprintf('%s\\%s', $this->namespace, $name),
				$name,
			];
		}

		return [$name];
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
		if ($map->isEmpty() && $this->templateTypeMap->isEmpty()) {
			return $this;
		}

		return new self(
			$this->namespace,
			$this->uses,
			$this->className,
			$this->functionName,
			new TemplateTypeMap(array_merge(
				$this->templateTypeMap->getTypes(),
				$map->getTypes(),
			)),
			$this->typeAliasesMap,
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
			$this->typeAliasesMap,
		);
	}

	public function bypassTypeAliases(): self
	{
		return new self($this->namespace, $this->uses, $this->className, $this->functionName, $this->templateTypeMap, $this->typeAliasesMap, true);
	}

	public function shouldBypassTypeAliases(): bool
	{
		return $this->bypassTypeAliases;
	}

	public function hasTypeAlias(string $alias): bool
	{
		return array_key_exists($alias, $this->typeAliasesMap);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['namespace'],
			$properties['uses'],
			$properties['className'],
			$properties['functionName'],
			$properties['templateTypeMap'],
			$properties['typeAliasesMap'],
		);
	}

}
