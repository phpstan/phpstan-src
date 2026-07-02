<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use function serialize;

final class IntermediaryNameScope
{

	/**
	 * @api
	 * @param non-empty-string|null $namespace
	 * @param array<string, string> $uses alias(string) => fullName(string)
	 * @param array<string, array{string, TemplateTagValueNode}> $templatePhpDocNodes
	 * @param array<string, string> $constUses alias(string) => fullName(string)
	 * @param array<string, true> $typeAliasesMap
	 * @param array{string, string, string, string|null, string|null}|null $traitData
	 */
	public function __construct(
		private ?string $namespace,
		private array $uses,
		private ?string $className = null,
		private ?string $functionName = null,
		private array $templatePhpDocNodes = [],
		private ?self $parent = null,
		private array $typeAliasesMap = [],
		private bool $bypassTypeAliases = false,
		private array $constUses = [],
		private ?string $typeAliasClassName = null,
		private ?array $traitData = null,
	)
	{
	}

	/**
	 * @return non-empty-string|null
	 */
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

	/**
	 * @return array<string, string>
	 */
	public function getConstUses(): array
	{
		return $this->constUses;
	}

	public function getClassName(): ?string
	{
		return $this->className;
	}

	public function getFunctionName(): ?string
	{
		return $this->functionName;
	}

	/**
	 * @return array<string, array{string, TemplateTagValueNode}>
	 */
	public function getTemplatePhpDocNodes(): array
	{
		return $this->templatePhpDocNodes;
	}

	public function withTraitData(string $fileName, string $className, string $traitName, ?string $lookForTraitName, ?string $docComment): self
	{
		return new self(
			$this->namespace,
			$this->uses,
			$this->className,
			$this->functionName,
			$this->templatePhpDocNodes,
			$this->parent,
			$this->typeAliasesMap,
			$this->bypassTypeAliases,
			$this->constUses,
			$this->typeAliasClassName,
			[$fileName, $className, $traitName, $lookForTraitName, $docComment],
		);
	}

	/**
	 * @param string[] $namesToUnset
	 */
	public function unsetTemplatePhpDocNodes(array $namesToUnset): self
	{
		$templatePhpDocNodes = $this->templatePhpDocNodes;
		foreach ($namesToUnset as $name) {
			unset($templatePhpDocNodes[$name]);
		}
		return new self(
			$this->namespace,
			$this->uses,
			$this->className,
			$this->functionName,
			$templatePhpDocNodes,
			$this->parent,
			$this->typeAliasesMap,
			$this->bypassTypeAliases,
			$this->constUses,
			$this->typeAliasClassName,
			$this->traitData,
		);
	}

	/**
	 * Restores sharing of identical property values and parent scopes after hydration from the file cache.
	 *
	 * var_export() used by the cache cannot represent shared references, so every hydrated
	 * scope carries its own copy of the same uses maps and of the whole parent chain.
	 * Without interning, the name scope map of a file with many members takes up
	 * many times more memory when loaded from the cache than when freshly created.
	 *
	 * @param array<string, self|array<mixed>> $pool
	 */
	public function intern(array &$pool): self
	{
		$key = serialize($this);
		if (isset($pool[$key])) {
			/** @var self */
			return $pool[$key];
		}

		$this->uses = self::internArray($pool, $this->uses);
		$this->templatePhpDocNodes = self::internArray($pool, $this->templatePhpDocNodes);
		$this->typeAliasesMap = self::internArray($pool, $this->typeAliasesMap);
		$this->constUses = self::internArray($pool, $this->constUses);
		if ($this->parent !== null) {
			$this->parent = $this->parent->intern($pool);
		}

		return $pool[$key] = $this;
	}

	/**
	 * @template T of array<mixed>
	 * @param array<string, self|array<mixed>> $pool
	 * @param T $value
	 * @return T
	 */
	private static function internArray(array &$pool, array $value): array
	{
		$key = 'a:' . serialize($value);
		if (isset($pool[$key])) {
			/** @var T */
			return $pool[$key];
		}

		$pool[$key] = $value;

		return $value;
	}

	/**
	 * @return array{string, string, string, string|null, string|null}|null
	 */
	public function getTraitData(): ?array
	{
		return $this->traitData;
	}

	public function getParent(): ?self
	{
		return $this->parent;
	}

	/**
	 * @return array<string, true>
	 */
	public function getTypeAliasesMap(): array
	{
		return $this->typeAliasesMap;
	}

	public function shouldBypassTypeAliases(): bool
	{
		return $this->bypassTypeAliases;
	}

	public function getClassNameForTypeAlias(): ?string
	{
		return $this->typeAliasClassName;
	}

	/**
	 * @param array<string, mixed> $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['namespace'],
			$properties['uses'],
			$properties['className'],
			$properties['functionName'],
			$properties['templatePhpDocNodes'],
			$properties['parent'],
			$properties['typeAliasesMap'],
			$properties['bypassTypeAliases'],
			$properties['constUses'],
			$properties['typeAliasClassName'],
			$properties['traitData'],
		);
	}

}
