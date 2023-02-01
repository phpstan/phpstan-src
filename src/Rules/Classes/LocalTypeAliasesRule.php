<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CircularTypeAliasErrorType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class LocalTypeAliasesRule implements Rule
{

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		private array $globalTypeAliases,
		private ReflectionProvider $reflectionProvider,
		private TypeNodeResolver $typeNodeResolver,
	)
	{
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
			$importedFromClassName = $typeAliasImportTag->getImportedFrom();

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

			$resolvedName = $resolveName($aliasName);
			if ($this->reflectionProvider->hasClass($resolveName($aliasName))) {
				$classReflection = $this->reflectionProvider->getClass($resolvedName);
				$classLikeDescription = 'a class';
				if ($classReflection->isInterface()) {
					$classLikeDescription = 'an interface';
				} elseif ($classReflection->isTrait()) {
					$classLikeDescription = 'a trait';
				} elseif ($classReflection->isEnum()) {
					$classLikeDescription = 'an enum';
				}
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as %s in scope of %s.', $aliasName, $classLikeDescription, $className))->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->build();
				continue;
			}

			$importedAs = $typeAliasImportTag->getImportedAs();
			if ($importedAs !== null && !$this->isAliasNameValid($importedAs, $nameScope)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Imported type alias %s has an invalid name: %s.', $importedAlias, $importedAs))->build();
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

			$resolvedName = $resolveName($aliasName);
			if ($this->reflectionProvider->hasClass($resolvedName)) {
				$classReflection = $this->reflectionProvider->getClass($resolvedName);
				$classLikeDescription = 'a class';
				if ($classReflection->isInterface()) {
					$classLikeDescription = 'an interface';
				} elseif ($classReflection->isTrait()) {
					$classLikeDescription = 'a trait';
				} elseif ($classReflection->isEnum()) {
					$classLikeDescription = 'an enum';
				}
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as %s in scope of %s.', $aliasName, $classLikeDescription, $className))->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->build();
				continue;
			}

			if (!$this->isAliasNameValid($aliasName, $nameScope)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias has an invalid name: %s.', $aliasName))->build();
				continue;
			}

			$resolvedType = $typeAliasTag->getTypeAlias()->resolve($this->typeNodeResolver);
			$foundError = false;
			TypeTraverser::map($resolvedType, static function (Type $type, callable $traverse) use (&$errors, &$foundError, $aliasName): Type {
				if ($foundError) {
					return $type;
				}

				if ($type instanceof CircularTypeAliasErrorType) {
					$errors[] = RuleErrorBuilder::message(sprintf('Circular definition detected in type alias %s.', $aliasName))->build();
					$foundError = true;
					return $type;
				}

				if ($type instanceof ErrorType) {
					$errors[] = RuleErrorBuilder::message(sprintf('Invalid type definition detected in type alias %s.', $aliasName))->build();
					$foundError = true;
					return $type;
				}

				return $traverse($type);
			});
		}

		return $errors;
	}

	private function isAliasNameValid(string $aliasName, ?NameScope $nameScope): bool
	{
		if ($nameScope === null) {
			return true;
		}

		$aliasNameResolvedType = $this->typeNodeResolver->resolve(new IdentifierTypeNode($aliasName), $nameScope->bypassTypeAliases());
		return ($aliasNameResolvedType->isObject()->yes() && !in_array($aliasName, ['self', 'parent'], true))
			|| $aliasNameResolvedType instanceof TemplateType; // aliases take precedence over type parameters, this is reported by other rules using TemplateTypeCheck
	}

}
