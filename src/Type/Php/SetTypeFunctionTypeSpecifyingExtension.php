<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use stdClass;
use function count;
use function strtolower;

#[AutowiredService]
final class SetTypeFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return strtolower($functionReflection->getName()) === 'settype'
			&& count($node->getArgs()) > 1
			&& $context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		$value = $args[0]->value;
		$valueType = $scope->getType($value);
		$castType = $scope->getType($args[1]->value);

		$constantStrings = $castType->getConstantStrings();
		if (count($constantStrings) < 1) {
			return $this->typeSpecifier->create(
				$value,
				TypeCombinator::union(
					new BooleanType(),
					new IntegerType(),
					new FloatType(),
					new StringType(),
					new ArrayType(new MixedType(), new MixedType()),
					new ObjectType(stdClass::class),
					new NullType(),
				),
				TypeSpecifierContext::createTruthy(),
				$scope,
			)->setAlwaysOverwriteTypes();
		}

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

		return $this->typeSpecifier->create(
			$value,
			TypeCombinator::union(...$types),
			TypeSpecifierContext::createTruthy(),
			$scope,
		)->setAlwaysOverwriteTypes();
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
