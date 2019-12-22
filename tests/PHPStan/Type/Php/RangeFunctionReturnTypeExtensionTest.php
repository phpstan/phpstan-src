<?php

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\MockObject\MockObject;

class RangeFunctionReturnTypeExtensionTest extends \PHPStan\Testing\TestCase
{
	/**
	 * @var RangeFunctionReturnTypeExtension
	 */
	private $extension;

	public function testIsFunctionSupported(): void
	{
		/** @var MockObject|FunctionReflection $reflectionFunctionMock */
		$reflectionFunctionMock = $this->createMock(FunctionReflection::class);
		$reflectionFunctionMock->expects($this->once())->method('getName')->willReturn('range');
		$this->extension->isFunctionSupported($reflectionFunctionMock);
	}

	/**
	 * @phpstan-param class-string<Type> $startType
	 * @phpstan-param class-string<Type> $endType
	 * @phpstan-param class-string<Type> $stepType
	 * @phpstan-param class-string<Type> $returnType
	 *
	 * @dataProvider  provideStartEndStopAndReturnType
	 */
	public function testArrayTypesDependingOnStartEndAndStepType(
		string $startType,
		string $endType,
		string $stepType,
		string $returnType
	): void {
		$scope = $this->createMock(Scope::class);
		$scope->method('getType')
			->willReturnOnConsecutiveCalls(
				$this->createMock($startType),
				$this->createMock($endType),
				$this->createMock($stepType)
			);

		$type = $this->extension->getTypeFromFunctionCall($this->createMock(FunctionReflection::class),
			$this->createFuncCallMock(), $scope);

		$this->assertInstanceOf(ArrayType::class, $type);
		$this->assertInstanceOf(IntegerType::class, $type->getIterableKeyType());
		$this->assertInstanceOf($returnType, $type->getIterableValueType());
	}

	private function createFuncCallMock(): FuncCall
	{
		return new class('anything', [
			$this->createExprFromType(),
			$this->createExprFromType(),
			$this->createExprFromType()
		]) extends FuncCall {
		};
	}

	/**
	 * @param string $argType
	 *
	 * @phpstan-param class-string<Type> $className
	 * @return Arg
	 *
	 */
	private function createExprFromType(): Arg
	{
		$expr        = $this->createMock(Arg::class);
		$expr->value = $this->createMock(Expr::class);

		return $expr;
	}

	public function provideStartEndStopAndReturnType(): array
	{
		return [
			'int,int,int'       => [
				'startType'  => IntegerType::class,
				'endType'    => IntegerType::class,
				'stepType'   => IntegerType::class,
				'returnType' => IntegerType::class,
			],
			'float,int,int'     => [
				'startType'  => FloatType::class,
				'endType'    => IntegerType::class,
				'stepType'   => IntegerType::class,
				'returnType' => FloatType::class,
			],
			'int,float,int'     => [
				'startType'  => IntegerType::class,
				'endType'    => FloatType::class,
				'stepType'   => IntegerType::class,
				'returnType' => IntegerType::class,
			],
			'float,float,float' => [
				'startType'  => FloatType::class,
				'endType'    => FloatType::class,
				'stepType'   => FloatType::class,
				'returnType' => FloatType::class,
			],
			'float,int,float'   => [
				'startType'  => FloatType::class,
				'endType'    => IntegerType::class,
				'stepType'   => FloatType::class,
				'returnType' => FloatType::class,
			],
			'int,float,float'   => [
				'startType'  => IntegerType::class,
				'endType'    => FloatType::class,
				'stepType'   => FloatType::class,
				'returnType' => FloatType::class,
			],
			'string,string,int' => [
				'startType'  => StringType::class,
				'endType'    => StringType::class,
				'stepType'   => IntegerType::class,
				'returnType' => StringType::class,
			]
		];
	}

	protected function setUp(): void
	{
		$this->extension = new RangeFunctionReturnTypeExtension();
	}
}
