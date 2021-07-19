<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

class ArrayFlipFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_flip';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->args) != 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$array = $functionCall->args[0]->value;
		$argType = $scope->getType($array);
    
    if ($argType instanceof ArrayType) {
        $keyTypes = $argType->getItemType();
		    $valueTypes = $argType->getValueTypes();

        $flippedArrayType = new ArrayType(
          $valuesParamType,
		    	$keysParamType
	    	);

        if ($keysParamType->isIterableAtLeastOnce()->yes()) {
		  	    $flippedArrayType = TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		    }
      
        return $flippedArrayType;
    }
    
    return new NullType();
	}

}
