<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\SprintfHelper;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CircularTypeAliasErrorType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_merge;
use function in_array;
use function sprintf;

#[AutowiredService]
final class LocalTypeAliasesCheck
{

	/**
	 * @param array<string, string> $globalTypeAliases
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%typeAliases%')]
		private array $globalTypeAliases,
		private ReflectionProvider $reflectionProvider,
		private TypeNodeResolver $typeNodeResolver,
		private MissingTypehintCheck $missingTypehintCheck,
		private ClassNameCheck $classCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		#[AutowiredParameter]
		private bool $checkMissingTypehints,
		#[AutowiredParameter]
		private bool $checkClassCaseSensitivity,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(Scope $scope, ClassReflection $reflection, ClassLike $node): array
	{
		$errors = [];
		foreach ($this->checkInTraitDefinitionContext($reflection) as $error) {
			$errors[] = $error;
		}
		foreach ($this->checkInTraitUseContext($scope, $reflection, $reflection, $node) as $error) {
			$errors[] = $error;
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitDefinitionContext(ClassReflection $reflection): array
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
		$className = $reflection->getDisplayName();

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
			if ($this->hasErrorType($resolvedType, $aliasName, $errors)) {
				continue;
			}

			if (!$this->checkMissingTypehints) {
				continue;
			}

			foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($resolvedType) as $iterableType) {
				$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s %s has type alias %s with no value type specified in iterable type %s.',
					$reflection->getClassTypeDescription(),
					$reflection->getDisplayName(),
					$aliasName,
					$iterableTypeDescription,
				))
					->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
					->identifier('missingType.iterableValue')
					->build();
			}

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($resolvedType) as [$name, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s %s has type alias %s with generic %s but does not specify its types: %s',
					$reflection->getClassTypeDescription(),
					$reflection->getDisplayName(),
					$aliasName,
					$name,
					$genericTypeNames,
				))
					->identifier('missingType.generics')
					->build();
			}

			foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($resolvedType) as $callableType) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s %s has type alias %s with no signature specified for %s.',
					$reflection->getClassTypeDescription(),
					$reflection->getDisplayName(),
					$aliasName,
					$callableType->describe(VerbosityLevel::typeOnly()),
				))->identifier('missingType.callable')->build();
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkInTraitUseContext(
		Scope $scope,
		ClassReflection $reflection,
		ClassReflection $implementingClassReflection,
		ClassLike $node,
	): array
	{
		if ($reflection->getNativeReflection()->getName() === $implementingClassReflection->getName()) {
			$phpDoc = $reflection->getResolvedPhpDoc();
		} else {
			$phpDoc = $reflection->getTraitContextResolvedPhpDoc($implementingClassReflection);
		}
		if ($phpDoc === null) {
			return [];
		}

		$errors = [];

		foreach ($phpDoc->getTypeAliasTags() as $typeAliasTag) {
			$aliasName = $typeAliasTag->getAliasName();
			$resolvedType = $typeAliasTag->getTypeAlias()->resolve($this->typeNodeResolver);
			$throwawayErrors = [];
			if ($this->hasErrorType($resolvedType, $aliasName, $throwawayErrors)) {
				continue;
			}
			foreach ($resolvedType->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errorBuilder = RuleErrorBuilder::message(sprintf('Type alias %s contains unknown class %s.', $aliasName, $class))
						->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					$errors[] = $errorBuilder->build();
				} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s contains invalid type %s.', $aliasName, $class))
						->identifier('typeAlias.trait')
						->build();
				} else {
					$errors = array_merge(
						$errors,
						$this->classCheck->checkClassNames($scope, [
							new ClassNameNodePair($class, $node),
						], ClassNameUsageLocation::from(ClassNameUsageLocation::TYPE_ALIAS, [
							'typeAliasName' => $aliasName,
						]), $this->checkClassCaseSensitivity),
					);
				}
			}

			if ($this->unresolvableTypeHelper->containsUnresolvableType($resolvedType)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Type alias %s contains unresolvable type.', $aliasName))
					->identifier('typeAlias.unresolvableType')
					->build();
			}

			$escapedTypeAlias = SprintfHelper::escapeFormatString($aliasName);
			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$resolvedType,
				sprintf(
					'Type alias %s contains generic type %%s but %%s %%s is not generic.',
					$escapedTypeAlias,
				),
				sprintf(
					'Generic type %%s in type alias %s does not specify all template types of %%s %%s: %%s',
					$escapedTypeAlias,
				),
				sprintf(
					'Generic type %%s in type alias %s specifies %%d template types, but %%s %%s supports only %%d: %%s',
					$escapedTypeAlias,
				),
				sprintf(
					'Type %%s in generic type %%s in type alias %s is not subtype of template type %%s of %%s %%s.',
					$escapedTypeAlias,
				),
				sprintf(
					'Call-site variance of %%s in generic type %%s in type alias %s is in conflict with %%s template type %%s of %%s %%s.',
					$escapedTypeAlias,
				),
				sprintf(
					'Call-site variance of %%s in generic type %%s in type alias %s is redundant, template type %%s of %%s %%s has the same variance.',
					$escapedTypeAlias,
				),
			));
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

	/**
	 * @param list<IdentifierRuleError> $errors
	 * @param-out list<IdentifierRuleError> $errors
	 */
	private function hasErrorType(Type $type, string $aliasName, array &$errors): bool
	{
		$foundError = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$errors, &$foundError, $aliasName): Type {
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

		return $foundError;
	}

}
