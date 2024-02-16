<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use stdClass;
use function count;
use function strtolower;

class SetTypeFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'settype'
			&& count($node->getArgs()) > 1;
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$value = $node->getArgs()[0]->value;
		$valueType = $scope->getType($value);
		$castType = $scope->getType($node->getArgs()[1]->value);

		$constantStrings = $castType->getConstantStrings();
		$types = [];

		foreach ($constantStrings as $constantString) {
			switch ($constantString->getValue()) {
				case 'bool':
				case 'boolean':
					$types[] = $valueType->toBoolean();
					break;
				case 'int':
				case 'integer':
					$types[] = $valueType->toInteger();
					break;
				case 'float':
				case 'double':
					$types[] = $valueType->toFloat();
					break;
				case 'string':
					$types[] = $valueType->toString();
					break;
				case 'array':
					$types[] = $valueType->toArray();
					break;
				case 'object':
					$types[] = new ObjectType(stdClass::class);
					break;
				case 'null':
					$types[] = new NullType();
					break;
				default:
					$types[] = new ErrorType();
			}
		}

		return new SpecifiedTypes(['$value' => [$value, TypeCombinator::union(...$types)]], [], true);
	}

}
