<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use const PHP_VERSION_ID;

final class ArgumentsNormalizerLegacyTest extends PHPStanTestCase
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
		$parameterAcceptor = $functionReflection->getOnlyVariant();

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

		$funcCall = ArgumentsNormalizer::reorderFuncArguments($parameterAcceptor, $funcCall);
		$this->assertNotNull($funcCall);
		$reorderedArgs = $funcCall->getArgs();
		$this->assertCount(2, $reorderedArgs);

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertNull($reorderedArgs[0]->name, 'named-arg turned into regular numeric arg');
		$this->assertInstanceOf(String_::class, $reorderedArgs[0]->value, 'value-arg at the right position');

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertNull($reorderedArgs[1]->name, 'named-arg turned into regular numeric arg');
		$this->assertInstanceOf(LNumber::class, $reorderedArgs[1]->value, 'flags-arg at the right position');
		$this->assertSame(0, $reorderedArgs[1]->value->value);
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
		$parameterAcceptor = $functionReflection->getOnlyVariant();

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

		$funcCall = ArgumentsNormalizer::reorderFuncArguments($parameterAcceptor, $funcCall);
		$this->assertNotNull($funcCall);
		$reorderedArgs = $funcCall->getArgs();
		$this->assertCount(3, $reorderedArgs);

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertNull($reorderedArgs[0]->name, 'named-arg turned into regular numeric arg');
		$this->assertInstanceOf(String_::class, $reorderedArgs[0]->value, 'value-arg at the right position');

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertNull($reorderedArgs[1]->name, 'named-arg turned into regular numeric arg');
		$this->assertInstanceOf(TypeExpr::class, $reorderedArgs[1]->value, 'flags-arg at the right position');
		$this->assertInstanceOf(ConstantIntegerType::class, $reorderedArgs[1]->value->getExprType());
		$this->assertSame(0, $reorderedArgs[1]->value->getExprType()->getValue(), 'flags-arg with default value');

		$this->assertArrayHasKey(2, $reorderedArgs);
		$this->assertNull($reorderedArgs[2]->name, 'named-arg turned into regular numeric arg');
		$this->assertInstanceOf(LNumber::class, $reorderedArgs[2]->value, 'depth-arg at the right position');
		$this->assertSame(128, $reorderedArgs[2]->value->value);
	}

	public function testMissingRequiredParameter(): void
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
		$parameterAcceptor = $functionReflection->getOnlyVariant();

		$args = [
			new Arg(
				new LNumber(128),
				false,
				false,
				[],
				new Identifier('depth'),
			),
		];
		$funcCall = new FuncCall($funcName, $args);

		$this->assertNull(ArgumentsNormalizer::reorderFuncArguments($parameterAcceptor, $funcCall));
	}

	public function testLeaveRegularCallAsIs(): void
	{
		$funcName = new Name('json_encode');
		$reflectionProvider = self::getContainer()->getByType(NativeFunctionReflectionProvider::class);
		$functionReflection = $reflectionProvider->findFunctionReflection('json_encode');
		if ($functionReflection === null) {
			throw new ShouldNotHappenException();
		}
		$parameterAcceptor = $functionReflection->getOnlyVariant();

		$args = [
			new Arg(
				new String_('my json value'),
			),
			new Arg(
				new LNumber(0),
			),
		];
		$funcCall = new FuncCall($funcName, $args);

		$funcCall = ArgumentsNormalizer::reorderFuncArguments($parameterAcceptor, $funcCall);
		$this->assertNotNull($funcCall);
		$reorderedArgs = $funcCall->getArgs();
		$this->assertCount(2, $reorderedArgs);

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertInstanceOf(String_::class, $reorderedArgs[0]->value, 'value-arg at unchanged position');

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertInstanceOf(LNumber::class, $reorderedArgs[1]->value, 'flags-arg at unchanged position');
		$this->assertSame(0, $reorderedArgs[1]->value->value);
	}

}
