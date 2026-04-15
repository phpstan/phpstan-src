<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_merge;
use function sprintf;
use function substr;

#[AutowiredService]
final class AssertRuleHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private ClassNameCheck $classCheck,
		private MissingTypehintCheck $missingTypehintCheck,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		#[AutowiredParameter]
		private bool $checkClassCaseSensitivity,
		#[AutowiredParameter]
		private bool $checkMissingTypehints,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		Function_|ClassMethod $node,
		ExtendedMethodReflection|FunctionReflection $reflection,
		ParametersAcceptor $acceptor,
	): array
	{
		$parametersByName = [];
		foreach ($acceptor->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter->getType();
		}

		if ($reflection instanceof ExtendedMethodReflection && !$reflection->isStatic()) {
			$class = $reflection->getDeclaringClass();
			$parametersByName['this'] = new ObjectType($class->getName(), classReflection: $class);
		}

		$errors = [];
		foreach ($reflection->getAsserts()->getAll() as $assert) {
			$parameterName = substr($assert->getParameter()->getParameterName(), 1);
			if (!array_key_exists($parameterName, $parametersByName)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Assert references unknown parameter $%s.', $parameterName))
					->identifier('parameter.notFound')
					->build();
				continue;
			}

			if (!$assert->isExplicit()) {
				continue;
			}

			$assertedExpr = $assert->getParameter()->getExpr(new TypeExpr($parametersByName[$parameterName]));
			$assertedExprType = $scope->getType($assertedExpr);
			$assertedExprString = $assert->getParameter()->describe();
			if ($assertedExprType instanceof ErrorType) {
				$errors[] = RuleErrorBuilder::message(sprintf('Assert references unknown %s.', $assertedExprString))
					->identifier('assert.unknownExpr')
					->build();
				continue;
			}

			$assertedType = $assert->getType();

			$tagName = [
				AssertTag::NULL => '@phpstan-assert',
				AssertTag::IF_TRUE => '@phpstan-assert-if-true',
				AssertTag::IF_FALSE => '@phpstan-assert-if-false',
			][$assert->getIf()];

			if ($this->unresolvableTypeHelper->containsUnresolvableType($assertedType)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s for %s contains unresolvable type.',
					$tagName,
					$assertedExprString,
				))->identifier('assert.unresolvableType')->build();
				continue;
			}

			$isSuperType = $assertedType->isSuperTypeOf($assertedExprType);
			if (!$isSuperType->maybe()) {
				if ($assert->isNegated() ? $isSuperType->yes() : $isSuperType->no()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Asserted %stype %s for %s with type %s can never happen.',
						$assert->isNegated() ? 'negated ' : '',
						$assertedType->describe(VerbosityLevel::precise()),
						$assertedExprString,
						$assertedExprType->describe(VerbosityLevel::precise()),
					))->identifier('assert.impossibleType')->build();
				} elseif ($assert->isNegated() ? $isSuperType->no() : $isSuperType->yes()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Asserted %stype %s for %s with type %s does not narrow down the type.',
						$assert->isNegated() ? 'negated ' : '',
						$assertedType->describe(VerbosityLevel::precise()),
						$assertedExprString,
						$assertedExprType->describe(VerbosityLevel::precise()),
					))->identifier('assert.alreadyNarrowedType')->build();
				}
			}

			foreach ($assertedType->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s for %s contains unknown class %s.',
						$tagName,
						$assertedExprString,
						$class,
					))->identifier('class.notFound')->build();
					continue;
				}

				$classReflection = $this->reflectionProvider->getClass($class);
				if ($classReflection->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'PHPDoc tag %s for %s contains invalid type %s.',
						$tagName,
						$assertedExprString,
						$class,
					))->identifier('assert.trait')->build();
					continue;
				}

				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames($scope, [
						new ClassNameNodePair($class, $node),
					], ClassNameUsageLocation::from(ClassNameUsageLocation::PHPDOC_TAG_ASSERT, [
						'phpDocTagName' => $tagName,
						'assertedExprString' => $assertedExprString,
					]), $this->checkClassCaseSensitivity),
				);
			}

			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$assertedType,
				sprintf('PHPDoc tag %s for %s contains generic type %%s but %%s %%s is not generic.', $tagName, $assertedExprString),
				sprintf('Generic type %%s in PHPDoc tag %s for %s does not specify all template types of %%s %%s: %%s', $tagName, $assertedExprString),
				sprintf('Generic type %%s in PHPDoc tag %s for %s specifies %%d template types, but %%s %%s supports only %%d: %%s', $tagName, $assertedExprString),
				sprintf('Type %%s in generic type %%s in PHPDoc tag %s for %s is not subtype of template type %%s of %%s %%s.', $tagName, $assertedExprString),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag %s for %s is in conflict with %%s template type %%s of %%s %%s.', $tagName, $assertedExprString),
				sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag %s for %s is redundant, template type %%s of %%s %%s has the same variance.', $tagName, $assertedExprString),
			));

			if (!$this->checkMissingTypehints) {
				continue;
			}

			foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($assertedType) as $iterableType) {
				$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s for %s has no value type specified in iterable type %s.',
					$tagName,
					$assertedExprString,
					$iterableTypeDescription,
				))
					->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
					->identifier('missingType.iterableValue')
					->build();
			}

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($assertedType) as [$innerName, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s for %s contains generic %s but does not specify its types: %s',
					$tagName,
					$assertedExprString,
					$innerName,
					$genericTypeNames,
				))
					->identifier('missingType.generics')
					->build();
			}

			foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($assertedType) as $callableType) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag %s for %s has no signature specified for %s.',
					$tagName,
					$assertedExprString,
					$callableType->describe(VerbosityLevel::typeOnly()),
				))->identifier('missingType.callable')->build();
			}
		}
		return $errors;
	}

}
