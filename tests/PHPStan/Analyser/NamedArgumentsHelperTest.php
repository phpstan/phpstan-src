<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use const PHP_VERSION_ID;

final class NamedArgumentsHelperTest extends PHPStanTestCase
{

	/**
	 * function call, all arguments named and given in order
	 */
	public function testArgumentReorderAllNamed(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$funcName = new Name('json_encode');
		$reflectionProvider = self::getContainer()->getByType(NativeFunctionReflectionProvider::class);
		$functionReflection = $reflectionProvider->findFunctionReflection('json_encode');
		if ($functionReflection === null) {
			throw new ShouldNotHappenException();
		}
		$parameterAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

		$args = [
			new Arg(
				new LNumber(0),
				false,
				false,
				[],
				new Identifier('flags'),
			),
			new Arg(
				new String_('my json value'),
				false,
				false,
				[],
				new Identifier('value'),
			),
		];
		$funcCall = new FuncCall($funcName, $args);

		$funcCall = NamedArgumentsHelper::reorderFuncArguments($parameterAcceptor, $funcCall);
		$reorderedArgs = $funcCall->getArgs();
		$this->assertCount(3, $reorderedArgs);

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertNotNull($reorderedArgs[0]->name);
		$this->assertSame('value', $reorderedArgs[0]->name->toString());

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertNotNull($reorderedArgs[1]->name);
		$this->assertSame('flags', $reorderedArgs[1]->name->toString());

		// "depths" arg was added with its default value based on the signature
		$this->assertArrayHasKey(2, $reorderedArgs);
		$this->assertInstanceOf(TypeExpr::class, $reorderedArgs[2]->value);
		$this->assertInstanceOf(ConstantIntegerType::class, $reorderedArgs[2]->value->getExprType());
		$this->assertSame(512, $reorderedArgs[2]->value->getExprType()->getValue());
	}

	/**
	 * function call, all args named, not in order
	 */
	public function testArgumentReorderAllNamedWithSkipped(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$funcName = new Name('json_encode');
		$reflectionProvider = self::getContainer()->getByType(NativeFunctionReflectionProvider::class);
		$functionReflection = $reflectionProvider->findFunctionReflection('json_encode');
		if ($functionReflection === null) {
			throw new ShouldNotHappenException();
		}
		$parameterAcceptor = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());

		$args = [
			new Arg(
				new LNumber(128),
				false,
				false,
				[],
				new Identifier('depth'),
			),
			new Arg(
				new String_('my json value'),
				false,
				false,
				[],
				new Identifier('value'),
			),
		];
		$funcCall = new FuncCall($funcName, $args);

		$funcCall = NamedArgumentsHelper::reorderFuncArguments($parameterAcceptor, $funcCall);
		$reorderedArgs = $funcCall->getArgs();
		$this->assertCount(3, $reorderedArgs);

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertNotNull($reorderedArgs[0]->name);
		$this->assertSame('value', $reorderedArgs[0]->name->toString());

		// "flags" arg was added with its default value based on the signature
		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertInstanceOf(TypeExpr::class, $reorderedArgs[1]->value);
		$this->assertInstanceOf(ConstantIntegerType::class, $reorderedArgs[1]->value->getExprType());
		$this->assertSame(0, $reorderedArgs[1]->value->getExprType()->getValue());

		$this->assertArrayHasKey(2, $reorderedArgs);
		$this->assertNotNull($reorderedArgs[2]->name);
		$this->assertSame('depth', $reorderedArgs[2]->name->toString());
	}

}
