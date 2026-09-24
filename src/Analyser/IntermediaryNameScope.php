<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use function count;
use function serialize;
use function spl_object_id;

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
	 * Converts a name scope map into a form for the file cache that stores every distinct
	 * array (uses, constUses, template tags, type aliases) and every scope only once.
	 *
	 * serialize() keeps objects shared but stores arrays by value, so serializing the scopes
	 * directly makes the cache entry grow with the number of imports times the number of scopes.
	 *
	 * @param array<string, self> $nameScopeMap
	 * @return array{list<array<mixed>>, list<array{string|null, int, string|null, string|null, int, int|null, int, bool, int, string|null, array{string, string, string, string|null, string|null}|null}>, array<string, int>}
	 */
	public static function dehydrateMap(array $nameScopeMap): array
	{
		$arrays = [];
		$arrayIndexes = [];
		$scopes = [];
		$scopeIndexes = [];
		$map = [];
		foreach ($nameScopeMap as $key => $nameScope) {
			$map[$key] = $nameScope->dehydrate($arrays, $arrayIndexes, $scopes, $scopeIndexes);
		}

		return [$arrays, $scopes, $map];
	}

	/**
	 * @param array{list<array<mixed>>, list<array{string|null, int, string|null, string|null, int, int|null, int, bool, int, string|null, array{string, string, string, string|null, string|null}|null}>, array<string, int>} $data
	 * @return array<string, self>
	 */
	public static function hydrateMap(array $data): array
	{
		[$arrays, $scopeTuples, $map] = $data;
		$scopes = [];
		foreach ($scopeTuples as $i => [$namespace, $uses, $className, $functionName, $templatePhpDocNodes, $parent, $typeAliasesMap, $bypassTypeAliases, $constUses, $typeAliasClassName, $traitData]) {
			/** @var non-empty-string|null $namespace */
			/** @var array<string, string> $usesArray */
			$usesArray = $arrays[$uses];
			/** @var array<string, array{string, TemplateTagValueNode}> $templatePhpDocNodesArray */
			$templatePhpDocNodesArray = $arrays[$templatePhpDocNodes];
			/** @var array<string, true> $typeAliasesMapArray */
			$typeAliasesMapArray = $arrays[$typeAliasesMap];
			/** @var array<string, string> $constUsesArray */
			$constUsesArray = $arrays[$constUses];
			$scopes[$i] = new self(
				$namespace,
				$usesArray,
				$className,
				$functionName,
				$templatePhpDocNodesArray,
				$parent !== null ? $scopes[$parent] : null,
				$typeAliasesMapArray,
				$bypassTypeAliases,
				$constUsesArray,
				$typeAliasClassName,
				$traitData,
			);
		}

		$nameScopeMap = [];
		foreach ($map as $key => $i) {
			$nameScopeMap[$key] = $scopes[$i];
		}

		return $nameScopeMap;
	}

	/**
	 * @param list<array<mixed>> $arrays
	 * @param array<string, int> $arrayIndexes
	 * @param list<array{string|null, int, string|null, string|null, int, int|null, int, bool, int, string|null, array{string, string, string, string|null, string|null}|null}> $scopes
	 * @param array<int, int> $scopeIndexes
	 */
	private function dehydrate(array &$arrays, array &$arrayIndexes, array &$scopes, array &$scopeIndexes): int
	{
		$objectId = spl_object_id($this);
		if (isset($scopeIndexes[$objectId])) {
			return $scopeIndexes[$objectId];
		}

		$parent = $this->parent?->dehydrate($arrays, $arrayIndexes, $scopes, $scopeIndexes);
		$scopes[] = [
			$this->namespace,
			self::dehydrateArray($arrays, $arrayIndexes, $this->uses),
			$this->className,
			$this->functionName,
			self::dehydrateArray($arrays, $arrayIndexes, $this->templatePhpDocNodes),
			$parent,
			self::dehydrateArray($arrays, $arrayIndexes, $this->typeAliasesMap),
			$this->bypassTypeAliases,
			self::dehydrateArray($arrays, $arrayIndexes, $this->constUses),
			$this->typeAliasClassName,
			$this->traitData,
		];

		return $scopeIndexes[$objectId] = count($scopes) - 1;
	}

	/**
	 * @param list<array<mixed>> $arrays
	 * @param array<string, int> $arrayIndexes
	 * @param array<mixed> $value
	 */
	private static function dehydrateArray(array &$arrays, array &$arrayIndexes, array $value): int
	{
		$key = serialize($value);
		if (isset($arrayIndexes[$key])) {
			return $arrayIndexes[$key];
		}

		$arrays[] = $value;

		return $arrayIndexes[$key] = count($arrays) - 1;
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
