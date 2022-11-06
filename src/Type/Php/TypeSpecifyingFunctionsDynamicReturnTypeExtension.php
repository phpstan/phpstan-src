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
use function count;
use function in_array;

class TypeSpecifyingFunctionsDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	private ?ImpossibleCheckTypeHelper $helper = null;

	/**
	 * @param string[] $universalObjectCratesClasses
	 */
	public function __construct(private ReflectionProvider $reflectionProvider, private bool $treatPhpDocTypesAsCertain, private array $universalObjectCratesClasses, private bool $nullContextForVoidReturningFunctions)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'array_key_exists',
			'key_exists',
			'in_array',
			'is_subclass_of',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$isAlways = $this->getHelper()->findSpecifiedType(
			$scope,
			$functionCall,
		);
		if ($isAlways === null) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		return new ConstantBooleanType($isAlways);
	}

	private function getHelper(): ImpossibleCheckTypeHelper
	{
		if ($this->helper === null) {
			$this->helper = new ImpossibleCheckTypeHelper($this->reflectionProvider, $this->typeSpecifier, $this->universalObjectCratesClasses, $this->treatPhpDocTypesAsCertain, $this->nullContextForVoidReturningFunctions);
		}

		return $this->helper;
	}

}
