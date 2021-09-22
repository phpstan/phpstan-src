<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
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
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionDefinitionCheck
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private PhpVersion $phpVersion;

	private bool $checkClassCaseSensitivity;

	private bool $checkThisOnly;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		PhpVersion $phpVersion,
		bool $checkClassCaseSensitivity,
		bool $checkThisOnly
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->phpVersion = $phpVersion;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
		$this->checkThisOnly = $checkThisOnly;
	}

	/**
	 * @param \PhpParser\Node\Stmt\Function_ $function
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @param string $unionTypesMessage
	 * @param string $templateTypeMissingInParameterMessage
	 * @return RuleError[]
	 */
	public function checkFunction(
		Function_ $function,
		FunctionReflection $functionReflection,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage
	): array
	{
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$function,
			$parameterMessage,
			$returnMessage,
			$unionTypesMessage,
			$templateTypeMissingInParameterMessage
		);
	}

	/**
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Param[] $parameters
	 * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null $returnTypeNode
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @param string $unionTypesMessage
	 * @return \PHPStan\Rules\RuleError[]
	 */
	public function checkAnonymousFunction(
		Scope $scope,
		array $parameters,
		$returnTypeNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage
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
				throw new \PHPStan\ShouldNotHappenException();
			}
			$type = $scope->getFunctionType($param->type, false, false);
			if ($type instanceof VoidType) {
				$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, 'void'))->line($param->type->getLine())->nonIgnorable()->build();
			}
			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class) || $this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $param->var->name, $class))->line($param->type->getLine())->build();
				} elseif ($this->checkClassCaseSensitivity) {
					$errors = array_merge(
						$errors,
						$this->classCaseSensitivityCheck->checkClassNames([
							new ClassNameNodePair($class, $param->type),
						])
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
		foreach ($returnType->getReferencedClasses() as $returnTypeClass) {
			if (!$this->reflectionProvider->hasClass($returnTypeClass) || $this->reflectionProvider->getClass($returnTypeClass)->isTrait()) {
				$errors[] = RuleErrorBuilder::message(sprintf($returnMessage, $returnTypeClass))->line($returnTypeNode->getLine())->build();
			} elseif ($this->checkClassCaseSensitivity) {
				$errors = array_merge(
					$errors,
					$this->classCaseSensitivityCheck->checkClassNames([
						new ClassNameNodePair($returnTypeClass, $returnTypeNode),
					])
				);
			}
		}

		return $errors;
	}

	/**
	 * @param PhpMethodFromParserNodeReflection $methodReflection
	 * @param ClassMethod $methodNode
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @param string $unionTypesMessage
	 * @param string $templateTypeMissingInParameterMessage
	 * @return RuleError[]
	 */
	public function checkClassMethod(
		PhpMethodFromParserNodeReflection $methodReflection,
		ClassMethod $methodNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage
	): array
	{
		/** @var \PHPStan\Reflection\ParametersAcceptorWithPhpDocs $parametersAcceptor */
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$methodNode,
			$parameterMessage,
			$returnMessage,
			$unionTypesMessage,
			$templateTypeMissingInParameterMessage
		);
	}

	/**
	 * @param ParametersAcceptor $parametersAcceptor
	 * @param FunctionLike $functionNode
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @param string $unionTypesMessage
	 * @param string $templateTypeMissingInParameterMessage
	 * @return RuleError[]
	 */
	private function checkParametersAcceptor(
		ParametersAcceptor $parametersAcceptor,
		FunctionLike $functionNode,
		string $parameterMessage,
		string $returnMessage,
		string $unionTypesMessage,
		string $templateTypeMissingInParameterMessage
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
			if (
				$parameter instanceof ParameterReflectionWithPhpDocs
				&& $parameter->getNativeType() instanceof VoidType
			) {
				$parameterVar = $parameterNodeCallback()->var;
				if (!$parameterVar instanceof Variable || !is_string($parameterVar->name)) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $parameterVar->name, 'void'))->line($parameterNodeCallback()->getLine())->nonIgnorable()->build();
			}
			foreach ($referencedClasses as $class) {
				if ($this->reflectionProvider->hasClass($class) && !$this->reflectionProvider->getClass($class)->isTrait()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					$parameterMessage,
					$parameter->getName(),
					$class
				))->line($parameterNodeCallback()->getLine())->build();
			}

			if ($this->checkClassCaseSensitivity) {
				$errors = array_merge(
					$errors,
					$this->classCaseSensitivityCheck->checkClassNames(array_map(static function (string $class) use ($parameterNodeCallback): ClassNameNodePair {
						return new ClassNameNodePair($class, $parameterNodeCallback());
					}, $referencedClasses))
				);
			}
			if (!($parameter->getType() instanceof NonexistentParentClassType)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf($parameterMessage, $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly())))->line($parameterNodeCallback()->getLine())->build();
		}

		$returnTypeReferencedClasses = $this->getReturnTypeReferencedClasses($parametersAcceptor);

		foreach ($returnTypeReferencedClasses as $class) {
			if ($this->reflectionProvider->hasClass($class) && !$this->reflectionProvider->getClass($class)->isTrait()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf($returnMessage, $class))->line($returnTypeNode->getLine())->build();
		}

		if ($this->checkClassCaseSensitivity) {
			$errors = array_merge(
				$errors,
				$this->classCaseSensitivityCheck->checkClassNames(array_map(static function (string $class) use ($returnTypeNode): ClassNameNodePair {
					return new ClassNameNodePair($class, $returnTypeNode);
				}, $returnTypeReferencedClasses))
			);
		}
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
		foreach ($parameterNodes as $parameterNode) {
			if (!$parameterNode->var instanceof Variable) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			if (!is_string($parameterNode->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$parameterName = $parameterNode->var->name;
			if ($optionalParameter !== null && $parameterNode->default === null && !$parameterNode->variadic) {
				$errors[] = RuleErrorBuilder::message(sprintf('Deprecated in PHP 8.0: Required parameter $%s follows optional parameter $%s.', $parameterName, $optionalParameter))->line($parameterNode->getStartLine())->nonIgnorable()->build();
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
				continue;
			}

			$optionalParameter = $parameterName;
		}

		return $errors;
	}

	/**
	 * @param string $parameterName
	 * @param Param[] $parameterNodes
	 * @return Param
	 */
	private function getParameterNode(
		string $parameterName,
		array $parameterNodes
	): Param
	{
		foreach ($parameterNodes as $param) {
			if ($param->var instanceof \PhpParser\Node\Expr\Error) {
				continue;
			}

			if (!is_string($param->var->name)) {
				continue;
			}

			if ($param->var->name === $parameterName) {
				return $param;
			}
		}

		throw new \PHPStan\ShouldNotHappenException(sprintf('Parameter %s not found.', $parameterName));
	}

	/**
	 * @param \PHPStan\Reflection\ParameterReflection $parameter
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
			$parameter->getPhpDocType()->getReferencedClasses()
		);
	}

	/**
	 * @param \PHPStan\Reflection\ParametersAcceptor $parametersAcceptor
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
			$parametersAcceptor->getPhpDocReturnType()->getReferencedClasses()
		);
	}

}
