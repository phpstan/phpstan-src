<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\TypeTraverser;

/**
 * @implements Rule<InClassNode>
 */
class LocalTypeAliasesRule implements Rule
{

	/** @var array<string, string> */
	private array $globalTypeAliases;

	private ReflectionProvider $reflectionProvider;

	private TypeNodeResolver $typeNodeResolver;

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		array $globalTypeAliases,
		ReflectionProvider $reflectionProvider,
		TypeNodeResolver $typeNodeResolver
	)
	{
		$this->globalTypeAliases = $globalTypeAliases;
		$this->reflectionProvider = $reflectionProvider;
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$reflection = $node->getClassReflection();
		$phpDoc = $reflection->getResolvedPhpDoc();
		if ($phpDoc === null) {
			return [];
		}

		$nameScope = $phpDoc->getNullableNameScope();
		$resolveName = static function (string $name) use ($nameScope): string {
			if ($nameScope === null) {
				return $name;
			}

			return $nameScope->resolveStringName($name);
		};

		$errors = [];
		$className = $reflection->getName();

		$importedAliases = [];

		foreach ($phpDoc->getTypeAliasImportTags() as $typeAliasImportTag) {
			$aliasName = $typeAliasImportTag->getImportedAs() ?? $typeAliasImportTag->getImportedAlias();
			$importedAlias = $typeAliasImportTag->getImportedAlias();
			$importedFromClassName = $resolveName($typeAliasImportTag->getImportedFrom());

			if (!$this->reflectionProvider->hasClass($importedFromClassName)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot import type alias %s: class %s does not exist.', $importedAlias, $importedFromClassName))->build();
				continue;
			}

			$importedFromReflection = $this->reflectionProvider->getClass($importedFromClassName);
			$typeAliases = $importedFromReflection->getTypeAliases();

			if (!array_key_exists($importedAlias, $typeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot import type alias %s: type alias does not exist in %s.', $importedAlias, $importedFromClassName))->build();
				continue;
			}

			if ($this->reflectionProvider->hasClass($resolveName($aliasName))) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a class in scope of %s.', $aliasName, $className))->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->build();
				continue;
			}

			$importedAliases[] = $aliasName;
		}

		foreach ($phpDoc->getTypeAliasTags() as $typeAliasTag) {
			$aliasName = $typeAliasTag->getAliasName();

			if (in_array($aliasName, $importedAliases, true)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s overwrites an imported type alias of the same name.', $aliasName))->build();
				continue;
			}

			if ($this->reflectionProvider->hasClass($resolveName($aliasName))) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a class in scope of %s.', $aliasName, $className))->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->build();
				continue;
			}

			$resolvedType = $typeAliasTag->getTypeAlias()->resolve($this->typeNodeResolver);
			$foundError = false;
			TypeTraverser::map($resolvedType, static function (\PHPStan\Type\Type $type, callable $traverse) use (&$errors, &$foundError, $aliasName): \PHPStan\Type\Type {
				if ($foundError) {
					return $type;
				}

				if ($type instanceof ErrorType) {
					$errors[] = RuleErrorBuilder::message(sprintf('Circular definition detected in type alias %s.', $aliasName))->build();
					$foundError = true;
					return $type;
				}

				return $traverse($type);
			});
		}

		return $errors;
	}

}
