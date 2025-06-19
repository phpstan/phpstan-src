<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPUnit\Framework\Attributes\DataProvider;

class InitializerExprTypeResolverTest extends PHPStanTestCase
{

	public static function dataExplicitNever(): iterable
	{
		yield [
			new LNumber(1),
			new String_('foo'),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new ConstantIntegerType(1);
				}
				return new NeverType(true);
			},
			NeverType::class,
			true,
		];
		yield [
			new String_('foo'),
			new LNumber(1),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new ConstantIntegerType(1);
				}
				return new NeverType(true);
			},
			NeverType::class,
			true,
		];

		yield [
			new LNumber(1),
			new String_('foo'),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new ConstantIntegerType(1);
				}
				return new NeverType(false);
			},
			NeverType::class,
			false,
		];
		yield [
			new String_('foo'),
			new LNumber(1),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new ConstantIntegerType(1);
				}
				return new NeverType(false);
			},
			NeverType::class,
			false,
		];

		yield [
			new String_('foo'),
			new LNumber(1),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new NeverType(true);
				}
				return new NeverType(false);
			},
			NeverType::class,
			true,
		];
		yield [
			new LNumber(1),
			new String_('foo'),
			static function (Expr $expr): Type {
				if ($expr instanceof LNumber) {
					return new NeverType(true);
				}
				return new NeverType(false);
			},
			NeverType::class,
			true,
		];

		yield [
			new LNumber(1),
			new LNumber(1),
			static fn (Expr $expr): Type => new ConstantIntegerType(1),
			ConstantIntegerType::class,
		];
	}

	/**
	 *
	 * @param class-string $resultClass
	 * @param callable(Expr): Type $callback
	 */
	#[DataProvider('dataExplicitNever')]
	public function testExplicitNever(Expr $left, Expr $right, callable $callback, string $resultClass, ?bool $resultIsExplicit = null): void
	{
		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);

		$result = $initializerExprTypeResolver->getPlusType(
			$left,
			$right,
			$callback,
		);
		$this->assertInstanceOf($resultClass, $result);

		if (!($result instanceof NeverType)) {
			return;
		}

		if ($resultIsExplicit === null) {
			throw new ShouldNotHappenException();
		}
		$this->assertSame($resultIsExplicit, $result->isExplicit());
	}

}
