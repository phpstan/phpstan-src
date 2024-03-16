<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function is_string;
use function sprintf;

class FunctionDefinitionCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PhpVersion $phpVersion,
		private bool $checkClassCaseSensitivity,
		private bool $checkThisOnly,
	)
	{
	}

	/**
	 * @return RuleError[]
	 */
	public function checkFunction(
		Function_ $function,
		FunctionReflection $functionReflection,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage,
		string $unresolvableParameterTypeMessage,
		string $unresolvableReturnTypeMessage,
	): array
	{
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$function,
			$parameterMessage,
			$returnMessage,
			$unionTypesMessage,
			$templateTypeMissingInParameterMessage,
			$unresolvableParameterTypeMessage,
			$unresolvableReturnTypeMessage,
		);
	}

	/**
	 * @param Node\Param[] $parameters
	 * @param Node\Identifier|Node\Name|Node\ComplexType|null $returnTypeNode
	 * @return RuleError[]
	 */
	public function checkAnonymousFunction(
		Scope $scope,
		array $parameters,
		$returnTypeNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $unresolvableParameterTypeMessage,
		string $unresolvableReturnTypeMessage,
	): array
	{
		$errors = [];
		$unionTypeReported = false;
		foreach ($parameters as $param) {
			if ($param->type === null) {
				continue;
			}
			if (
				!$unionTypeReported
				&& $param->type instanceof UnionType
				&& !$this->phpVersion->supportsNativeUnionTypes()
			) {
				$errors[] = RuleErrorBuilder::message($unionTypesMessage)->line($param->getLine())->nonIgnorable()->build();
				$unionTypeReported = true;
			}

			if (!$param->var instanceof Variable || !is_string($param->var->name)) {
				throw new ShouldNotHappenException();
			}
			$type = $scope->getFunctionType($param->type, false, false);
			if ($type->isVoid()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, 'void'))->line($param->type->getLine())->nonIgnorable()->build();
			}
			if (
				$this->phpVersion->supportsPureIntersectionTypes()
				&& $this->unresolvableTypeHelper->containsUnresolvableType($type)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf($unresolvableParameterTypeMessage, $param->var->name))->line($param->type->getLine())->nonIgnorable()->build();
			}

			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class) || $this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, $class))->line($param->type->getLine())->build();
				} else {
					$errors = array_merge(
						$errors,
						$this->classCheck->checkClassNames([
							new ClassNameNodePair($class, $param->type),
						], $this->checkClassCaseSensitivity),
					);
				}
			}
		}

		if ($this->phpVersion->deprecatesRequiredParameterAfterOptional()) {
			$errors = array_merge($errors, $this->checkRequiredParameterAfterOptional($parameters));
		}

		if ($returnTypeNode === null) {
			return $errors;
		}

		if (
			!$unionTypeReported
			&& $returnTypeNode instanceof UnionType
			&& !$this->phpVersion->supportsNativeUnionTypes()
		) {
			$errors[] = RuleErrorBuilder::message($unionTypesMessage)->line($returnTypeNode->getLine())->nonIgnorable()->build();
		}

		$returnType = $scope->getFunctionType($returnTypeNode, false, false);
		if (
			$this->phpVersion->supportsPureIntersectionTypes()
			&& $this->unresolvableTypeHelper->containsUnresolvableType($returnType)
		) {
			$errors[] = RuleErrorBuilder::message($unresolvableReturnTypeMessage)->line($returnTypeNode->getLine())->nonIgnorable()->build();
		}

		foreach ($returnType->getReferencedClasses() as $returnTypeClass) {
			if (!$this->reflectionProvider->hasClass($returnTypeClass) || $this->reflectionProvider->getClass($returnTypeClass)->isTrait()) {
				$errors[] = RuleErrorBuilder::message(sprintf($returnMessage, $returnTypeClass))->line($returnTypeNode->getLine())->build();
			} else {
				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames([
						new ClassNameNodePair($returnTypeClass, $returnTypeNode),
					], $this->checkClassCaseSensitivity),
				);
			}
		}

		return $errors;
	}

	/**
	 * @return RuleError[]
	 */
	public function checkClassMethod(
		PhpMethodFromParserNodeReflection $methodReflection,
		ClassMethod $methodNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage,
		string $unresolvableParameterTypeMessage,
		string $unresolvableReturnTypeMessage,
	): array
	{
		/** @var ParametersAcceptorWithPhpDocs $parametersAcceptor */
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$methodNode,
			$parameterMessage,
			$returnMessage,
			$unionTypesMessage,
			$templateTypeMissingInParameterMessage,
			$unresolvableParameterTypeMessage,
			$unresolvableReturnTypeMessage,
		);
	}

	/**
	 * @return RuleError[]
	 */
	private function checkParametersAcceptor(
		ParametersAcceptor $parametersAcceptor,
		FunctionLike $functionNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage,
		string $unresolvableParameterTypeMessage,
		string $unresolvableReturnTypeMessage,
	): array
	{
		$errors = [];
		$parameterNodes = $functionNode->getParams();
		if (!$this->phpVersion->supportsNativeUnionTypes()) {
			$unionTypeReported = false;
			foreach ($parameterNodes as $parameterNode) {
				if (!$parameterNode->type instanceof UnionType) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message($unionTypesMessage)->line($parameterNode->getLine())->nonIgnorable()->build();
				$unionTypeReported = true;
				break;
			}

			if (!$unionTypeReported && $functionNode->getReturnType() instanceof UnionType) {
				$errors[] = RuleErrorBuilder::message($unionTypesMessage)->line($functionNode->getReturnType()->getLine())->nonIgnorable()->build();
			}
		}

		if ($this->phpVersion->deprecatesRequiredParameterAfterOptional()) {
			$errors = array_merge($errors, $this->checkRequiredParameterAfterOptional($parameterNodes));
		}

		$returnTypeNode = $functionNode->getReturnType() ?? $functionNode;
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			$referencedClasses = $this->getParameterReferencedClasses($parameter);
			$parameterNode = null;
			$parameterNodeCallback = function () use ($parameter, $parameterNodes, &$parameterNode): Param {
				if ($parameterNode === null) {
					$parameterNode = $this->getParameterNode($parameter->getName(), $parameterNodes);
				}

				return $parameterNode;
			};
			if ($parameter instanceof ParameterReflectionWithPhpDocs) {
				$parameterVar = $parameterNodeCallback()->var;
				if (!$parameterVar instanceof Variable || !is_string($parameterVar->name)) {
					throw new ShouldNotHappenException();
				}
				if ($parameter->getNativeType()->isVoid()->yes()) {
					$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $parameterVar->name, 'void'))->line($parameterNodeCallback()->getLine())->nonIgnorable()->build();
				}
				if (
					$this->phpVersion->supportsPureIntersectionTypes()
					&& $this->unresolvableTypeHelper->containsUnresolvableType($parameter->getNativeType())
				) {
					$errors[] = RuleErrorBuilder::message(sprintf($unresolvableParameterTypeMessage, $parameterVar->name))->line($parameterNodeCallback()->getLine())->nonIgnorable()->build();
				}
			}
			foreach ($referencedClasses as $class) {
				if ($this->reflectionProvider->hasClass($class) && !$this->reflectionProvider->getClass($class)->isTrait()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					$parameterMessage,
					$parameter->getName(),
					$class,
				))->line($parameterNodeCallback()->getLine())->build();
			}

			$errors = array_merge(
				$errors,
				$this->classCheck->checkClassNames(
					array_map(static fn (string $class): ClassNameNodePair => new ClassNameNodePair($class, $parameterNodeCallback()), $referencedClasses),
					$this->checkClassCaseSensitivity,
				),
			);
			if (!($parameter->getType() instanceof NonexistentParentClassType)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly())))->line($parameterNodeCallback()->getLine())->build();
		}

		if ($this->phpVersion->supportsPureIntersectionTypes() && $functionNode->getReturnType() !== null) {
			$nativeReturnType = ParserNodeTypeToPHPStanType::resolve($functionNode->getReturnType(), null);
			if ($this->unresolvableTypeHelper->containsUnresolvableType($nativeReturnType)) {
				$errors[] = RuleErrorBuilder::message($unresolvableReturnTypeMessage)->nonIgnorable()->line($returnTypeNode->getLine())->build();
			}
		}

		$returnTypeReferencedClasses = $this->getReturnTypeReferencedClasses($parametersAcceptor);

		foreach ($returnTypeReferencedClasses as $class) {
			if ($this->reflectionProvider->hasClass($class) && !$this->reflectionProvider->getClass($class)->isTrait()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf($returnMessage, $class))->line($returnTypeNode->getLine())->build();
		}

		$errors = array_merge(
			$errors,
			$this->classCheck->checkClassNames(
				array_map(static fn (string $class): ClassNameNodePair => new ClassNameNodePair($class, $returnTypeNode), $returnTypeReferencedClasses),
				$this->checkClassCaseSensitivity,
			),
		);
		if ($parametersAcceptor->getReturnType() instanceof NonexistentParentClassType) {
			$errors[] = RuleErrorBuilder::message(sprintf($returnMessage, $parametersAcceptor->getReturnType()->describe(VerbosityLevel::typeOnly())))->line($returnTypeNode->getLine())->build();
		}

		$templateTypeMap = $parametersAcceptor->getTemplateTypeMap();
		$templateTypes = $templateTypeMap->getTypes();
		if (count($templateTypes) > 0) {
			foreach ($parametersAcceptor->getParameters() as $parameter) {
				TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$templateTypes): Type {
					if ($type instanceof TemplateType) {
						unset($templateTypes[$type->getName()]);
						return $traverse($type);
					}

					return $traverse($type);
				});
			}

			$returnType = $parametersAcceptor->getReturnType();
			if ($returnType instanceof ConditionalTypeForParameter && !$returnType->isNegated()) {
				TypeTraverser::map($returnType, static function (Type $type, callable $traverse) use (&$templateTypes): Type {
					if ($type instanceof TemplateType) {
						unset($templateTypes[$type->getName()]);
						return $traverse($type);
					}

					return $traverse($type);
				});
			}

			foreach (array_keys($templateTypes) as $templateTypeName) {
				$errors[] = RuleErrorBuilder::message(sprintf($templateTypeMissingInParameterMessage, $templateTypeName))->build();
			}
		}

		return $errors;
	}

	/**
	 * @param Param[] $parameterNodes
	 * @return RuleError[]
	 */
	private function checkRequiredParameterAfterOptional(array $parameterNodes): array
	{
		/** @var string|null $optionalParameter */
		$optionalParameter = null;
		$errors = [];
		$targetPhpVersion = null;
		foreach ($parameterNodes as $parameterNode) {
			if (!$parameterNode->var instanceof Variable) {
				throw new ShouldNotHappenException();
			}
			if (!is_string($parameterNode->var->name)) {
				throw new ShouldNotHappenException();
			}
			$parameterName = $parameterNode->var->name;
			if ($optionalParameter !== null && $parameterNode->default === null && !$parameterNode->variadic) {
				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Deprecated in PHP %s: Required parameter $%s follows optional parameter $%s.',
						$targetPhpVersion ?? '8.0',
						$parameterName,
						$optionalParameter,
					),
				)->line($parameterNode->getStartLine())->build();
				$targetPhpVersion = null;
				continue;
			}
			if ($parameterNode->default === null) {
				continue;
			}
			if ($parameterNode->type === null) {
				$optionalParameter = $parameterName;
				continue;
			}

			$defaultValue = $parameterNode->default;
			if (!$defaultValue instanceof ConstFetch) {
				$optionalParameter = $parameterName;
				continue;
			}

			$constantName = $defaultValue->name->toLowerString();
			if ($constantName === 'null') {
				if (!$this->phpVersion->deprecatesRequiredParameterAfterOptionalNullableAndDefaultNull()) {
					continue;
				}

				$parameterNodeType = $parameterNode->type;

				if ($parameterNodeType instanceof NullableType) {
					$targetPhpVersion = '8.1';
				}

				if ($this->phpVersion->deprecatesRequiredParameterAfterOptionalUnionOrMixed()) {
					$types = [];

					if ($parameterNodeType instanceof UnionType) {
						$types = $parameterNodeType->types;
					} elseif ($parameterNodeType instanceof Identifier) {
						$types = [$parameterNodeType];
					}

					$nullOrMixed = array_filter($types, static fn (Identifier|Name|IntersectionType $type): bool => $type instanceof Identifier && (in_array($type->name, ['null', 'mixed'], true)));

					if (0 < count($nullOrMixed)) {
						$targetPhpVersion = '8.3';
					}
				}

				if ($targetPhpVersion === null) {
					continue;
				}
			}

			$optionalParameter = $parameterName;
		}

		return $errors;
	}

	/**
	 * @param Param[] $parameterNodes
	 */
	private function getParameterNode(
		string $parameterName,
		array $parameterNodes,
	): Param
	{
		foreach ($parameterNodes as $param) {
			if ($param->var instanceof Node\Expr\Error) {
				continue;
			}

			if (!is_string($param->var->name)) {
				continue;
			}

			if ($param->var->name === $parameterName) {
				return $param;
			}
		}

		throw new ShouldNotHappenException(sprintf('Parameter %s not found.', $parameterName));
	}

	/**
	 * @return string[]
	 */
	private function getParameterReferencedClasses(ParameterReflection $parameter): array
	{
		if (!$parameter instanceof ParameterReflectionWithPhpDocs) {
			return $parameter->getType()->getReferencedClasses();
		}

		if ($this->checkThisOnly) {
			return $parameter->getNativeType()->getReferencedClasses();
		}

		return array_merge(
			$parameter->getNativeType()->getReferencedClasses(),
			$parameter->getPhpDocType()->getReferencedClasses(),
		);
	}

	/**
	 * @return string[]
	 */
	private function getReturnTypeReferencedClasses(ParametersAcceptor $parametersAcceptor): array
	{
		if (!$parametersAcceptor instanceof ParametersAcceptorWithPhpDocs) {
			return $parametersAcceptor->getReturnType()->getReferencedClasses();
		}

		if ($this->checkThisOnly) {
			return $parametersAcceptor->getNativeReturnType()->getReferencedClasses();
		}

		return array_merge(
			$parametersAcceptor->getNativeReturnType()->getReferencedClasses(),
			$parametersAcceptor->getPhpDocReturnType()->getReferencedClasses(),
		);
	}

}
