<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CircularTypeAliasErrorType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function in_array;
use function sprintf;

final class LocalTypeAliasesCheck
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

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(ClassReflection $reflection): array
	{
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
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot import type alias %s: class %s does not exist.', $importedAlias, $importedFromClassName))
					->identifier('class.notFound')
					->build();
				continue;
			}

			$importedFromReflection = $this->reflectionProvider->getClass($importedFromClassName);
			$typeAliases = $importedFromReflection->getTypeAliases();

			if (!array_key_exists($importedAlias, $typeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot import type alias %s: type alias does not exist in %s.', $importedAlias, $importedFromClassName))
					->identifier('typeAlias.notFound')
					->build();
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
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as %s in scope of %s.', $aliasName, $classLikeDescription, $className))
					->identifier('typeAlias.duplicate')
					->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->identifier('typeAlias.duplicate')->build();
				continue;
			}

			$importedAs = $typeAliasImportTag->getImportedAs();
			if ($importedAs !== null && !$this->isAliasNameValid($importedAs, $nameScope)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Imported type alias %s has an invalid name: %s.', $importedAlias, $importedAs))->identifier('typeAlias.invalidName')->build();
				continue;
			}

			$importedAliases[] = $aliasName;
		}

		foreach ($phpDoc->getTypeAliasTags() as $typeAliasTag) {
			$aliasName = $typeAliasTag->getAliasName();

			if (in_array($aliasName, $importedAliases, true)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s overwrites an imported type alias of the same name.', $aliasName))->identifier('typeAlias.duplicate')->build();
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
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as %s in scope of %s.', $aliasName, $classLikeDescription, $className))->identifier('typeAlias.duplicate')->build();
				continue;
			}

			if (array_key_exists($aliasName, $this->globalTypeAliases)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s already exists as a global type alias.', $aliasName))->identifier('typeAlias.duplicate')->build();
				continue;
			}

			if (!$this->isAliasNameValid($aliasName, $nameScope)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias has an invalid name: %s.', $aliasName))
					->identifier('typeAlias.invalidName')
					->build();
				continue;
			}

			$resolvedType = $typeAliasTag->getTypeAlias()->resolve($this->typeNodeResolver);
			$foundError = false;
			TypeTraverser::map($resolvedType, static function (Type $type, callable $traverse) use (&$errors, &$foundError, $aliasName): Type {
				if ($foundError) {
					return $type;
				}

				if ($type instanceof CircularTypeAliasErrorType) {
					$errors[] = RuleErrorBuilder::message(sprintf('Circular definition detected in type alias %s.', $aliasName))
						->identifier('typeAlias.circular')
						->build();
					$foundError = true;
					return $type;
				}

				if ($type instanceof ErrorType) {
					$errors[] = RuleErrorBuilder::message(sprintf('Invalid type definition detected in type alias %s.', $aliasName))
						->identifier('typeAlias.invalidType')
						->build();
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
