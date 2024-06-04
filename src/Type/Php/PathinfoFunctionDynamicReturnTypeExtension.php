<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function sprintf;

class PathinfoFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pathinfo';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		Node\Expr\FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount === 0) {
			return null;
		}

		$pathType = $scope->getType($functionCall->getArgs()[0]->value);

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->setOffsetValueType(new ConstantStringType('dirname'), new StringType(), !$pathType->isNonEmptyString()->yes());
		$builder->setOffsetValueType(new ConstantStringType('basename'), new StringType());
		$builder->setOffsetValueType(new ConstantStringType('extension'), new StringType(), true);
		$builder->setOffsetValueType(new ConstantStringType('filename'), new StringType());
		$arrayType = $builder->getArray();

		if ($argsCount === 1) {
			return $arrayType;
		}

		$flagsType = $scope->getType($functionCall->getArgs()[1]->value);

		$scalarValues = $flagsType->getConstantScalarValues();
		if ($scalarValues !== []) {
			$pathInfoAll = $this->getConstant('PATHINFO_ALL');
			if ($pathInfoAll === null) {
				return null;
			}

			$result = [];
			foreach ($scalarValues as $scalarValue) {
				if ($scalarValue === $pathInfoAll) {
					$result[] = $arrayType;
				} else {
					$result[] = new StringType();
				}
			}

			return TypeCombinator::union(...$result);
		}

		return TypeCombinator::union($arrayType, new StringType());
	}

	/**
	 * @param non-empty-string $constantName
	 */
	private function getConstant(string $constantName): ?int
	{
		if (!$this->reflectionProvider->hasConstant(new Node\Name($constantName), null)) {
			return null;
		}

		$constant = $this->reflectionProvider->getConstant(new Node\Name($constantName), null);
		$valueType = $constant->getValueType();
		if (!$valueType instanceof ConstantIntegerType) {
			throw new ShouldNotHappenException(sprintf('Constant %s does not have integer type.', $constantName));
		}

		return $valueType->getValue();
	}

}
