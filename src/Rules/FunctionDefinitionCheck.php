<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\NonexistentParentClassType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionDefinitionCheck
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private bool $checkClassCaseSensitivity;

	private bool $checkThisOnly;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		bool $checkClassCaseSensitivity,
		bool $checkThisOnly
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
		$this->checkThisOnly = $checkThisOnly;
	}

	/**
	 * @param \PhpParser\Node\Stmt\Function_ $function
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @return RuleError[]
	 */
	public function checkFunction(
		Function_ $function,
		FunctionReflection $functionReflection,
		string $parameterMessage,
		string $returnMessage
	): array
	{
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$function,
			$parameterMessage,
			$returnMessage
		);
	}

	/**
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Param[] $parameters
	 * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType|null $returnTypeNode
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @return \PHPStan\Rules\RuleError[]
	 */
	public function checkAnonymousFunction(
		Scope $scope,
		array $parameters,
		$returnTypeNode,
		string $parameterMessage,
		string $returnMessage
	): array
	{
		$errors = [];
		foreach ($parameters as $param) {
			if ($param->type === null) {
				continue;
			}
			if (!$param->var instanceof Variable || !is_string($param->var->name)) {
				throw new \PHPStan\ShouldNotHappenException();
			}
			$type = $scope->getFunctionType($param->type, false, $param->variadic);
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

		if ($returnTypeNode === null) {
			return $errors;
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
	 * @return RuleError[]
	 */
	public function checkClassMethod(
		PhpMethodFromParserNodeReflection $methodReflection,
		ClassMethod $methodNode,
		string $parameterMessage,
		string $returnMessage
	): array
	{
		/** @var \PHPStan\Reflection\ParametersAcceptorWithPhpDocs $parametersAcceptor */
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

		return $this->checkParametersAcceptor(
			$parametersAcceptor,
			$methodNode,
			$parameterMessage,
			$returnMessage
		);
	}

	/**
	 * @param ParametersAcceptor $parametersAcceptor
	 * @param FunctionLike $functionNode
	 * @param string $parameterMessage
	 * @param string $returnMessage
	 * @return RuleError[]
	 */
	private function checkParametersAcceptor(
		ParametersAcceptor $parametersAcceptor,
		FunctionLike $functionNode,
		string $parameterMessage,
		string $returnMessage
	): array
	{
		$errors = [];
		$parameterNodes = $functionNode->getParams();
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
