<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

class TypeSpecifyingFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension, TypeSpecifierAwareExtension
{

	private bool $treatPhpDocTypesAsCertain;

	private ReflectionProvider $reflectionProvider;

	private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;

	private ?\PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $helper = null;

	/** @var string[] */
	private array $universalObjectCratesClasses;

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(ReflectionProvider $reflectionProvider, bool $treatPhpDocTypesAsCertain, array $universalObjectCratesClasses)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->universalObjectCratesClasses = $universalObjectCratesClasses;
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'array_key_exists',
			'in_array',
			'is_numeric',
			'is_int',
			'is_array',
			'is_bool',
			'is_callable',
			'is_float',
			'is_double',
			'is_real',
			'is_iterable',
			'is_null',
			'is_object',
			'is_resource',
			'is_scalar',
			'is_string',
			'is_subclass_of',
			'is_countable',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$isAlways = $this->getHelper()->findSpecifiedType(
			$scope,
			$functionCall
		);
		if ($isAlways === null) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		return new ConstantBooleanType($isAlways);
	}

	private function getHelper(): ImpossibleCheckTypeHelper
	{
		if ($this->helper === null) {
			$this->helper = new ImpossibleCheckTypeHelper($this->reflectionProvider, $this->typeSpecifier, $this->universalObjectCratesClasses, $this->treatPhpDocTypesAsCertain);
		}

		return $this->helper;
	}

}
