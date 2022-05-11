<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\SignatureMap\NativeFunctionReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

final class NamedArgumentsHelperTest extends PHPStanTestCase
{

	/**
	 * all arguments named and given in order
	 */
	public function testArgumentReorderAllNamed(): void
	{
		$funcName = new Name('json_encode');
		$reflectionProvider = self::getContainer()->getByType(NativeFunctionReflectionProvider::class);
		$functionReflection = $reflectionProvider->findFunctionReflection('json_encode');
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

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertSame('value', $reorderedArgs[0]->name->toString());

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertSame('flags', $reorderedArgs[1]->name->toString());
	}

	/**
	 * all args named, not in order
	 */
	public function testArgumentReorderAllNamedWithSkipped(): void
	{
		$funcName = new Name('json_encode');
		$reflectionProvider = self::getContainer()->getByType(NativeFunctionReflectionProvider::class);
		$functionReflection = $reflectionProvider->findFunctionReflection('json_encode');
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

		$this->assertArrayHasKey(0, $reorderedArgs);
		$this->assertSame('value', $reorderedArgs[0]->name->toString());

		$this->assertArrayHasKey(1, $reorderedArgs);
		$this->assertSame('flags', $reorderedArgs[1]->name->toString());
		$this->assertInstanceOf(LNumber::class, $reorderedArgs[1]->value);
		$this->assertSame(0, $reorderedArgs[1]->value->value);

		$this->assertArrayHasKey(2, $reorderedArgs);
		$this->assertSame('depth', $reorderedArgs[2]->name->toString());
	}

}
