<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Test\A;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

class UnresolvedTemplateArgumentTypeTest extends PHPStanTestCase
{

	/** @param non-empty-string $name */
	private static function template(string $name, ?TemplateTypeVariance $variance = null): TemplateType
	{
		return TemplateTypeFactory::create(
			TemplateTypeScope::createWithClass(A\A::class),
			$name,
			new MixedType(),
			$variance ?? TemplateTypeVariance::createInvariant(),
		);
	}

	/** @param non-empty-string $name */
	private static function marker(Expr $site, ?Type $initialType, string $name = 'T'): UnresolvedTemplateArgumentType
	{
		return new UnresolvedTemplateArgumentType($site, self::template($name), $initialType);
	}

	public function testEqualsIsOpaqueAndKeyedBySiteAndName(): void
	{
		$site = new Variable('a');
		$otherSite = new Variable('b');
		$marker = self::marker($site, new ConstantIntegerType(1));

		$this->assertTrue($marker->equals(self::marker($site, new ConstantIntegerType(2))), 'initial type is ignored');
		$this->assertTrue($marker->equals(self::marker($site, null)));
		$this->assertFalse($marker->equals(self::marker($otherSite, new ConstantIntegerType(1))));
		$this->assertFalse($marker->equals(self::marker($site, new ConstantIntegerType(1), 'U')));
		$this->assertFalse($marker->equals(new ConstantIntegerType(1)));
		$this->assertFalse((new ConstantIntegerType(1))->equals($marker));
	}

	public function testBehavesAsItsDelegate(): void
	{
		$marker = self::marker(new Variable('a'), new ConstantIntegerType(1));

		$this->assertTrue((new IntegerType())->isSuperTypeOf($marker)->yes());
		$this->assertTrue((new IntegerType())->accepts($marker, true)->yes());
		$this->assertTrue($marker->isSuperTypeOf(new ConstantIntegerType(1))->yes());
		$this->assertTrue((new StringType())->isSuperTypeOf($marker)->no());
		$this->assertTrue($marker->isInteger()->yes());
		$this->assertSame([1], $marker->getConstantScalarValues());

		$unresolvable = self::marker(new Variable('a'), null);
		$this->assertInstanceOf(MixedType::class, $unresolvable->getDelegate());
		$this->assertTrue($unresolvable->isObject()->maybe());
	}

	public function testInvariantPositionIsOpaqueCovariantIsTransparent(): void
	{
		$marker = self::marker(new Variable('a'), new IntegerType());
		$ofInt = new GenericObjectType(A\A::class, [new IntegerType()]);
		$ofMarker = new GenericObjectType(A\A::class, [$marker]);

		$this->assertTrue($ofInt->isSuperTypeOf(new GenericObjectType(A\A::class, [new IntegerType()]))->yes());
		$this->assertTrue($ofInt->isSuperTypeOf($ofMarker)->no(), 'invariant positions compare with equals(), the marker never equals its delegate');
		$this->assertTrue($ofInt->accepts($ofMarker, true)->no());

		$ofCovariantInt = new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createCovariant()]);
		$this->assertTrue($ofCovariantInt->isSuperTypeOf($ofMarker)->yes(), 'call-site covariant positions accept the delegate');
	}

	public function testUnionKeepsMarkersOfDifferentSites(): void
	{
		$site = new Variable('a');
		$ofMarker = new GenericObjectType(A\A::class, [self::marker($site, new ConstantIntegerType(1))]);
		$ofInt = new GenericObjectType(A\A::class, [new IntegerType()]);

		$union = TypeCombinator::union($ofMarker, $ofInt);
		$this->assertInstanceOf(UnionType::class, $union);
		$this->assertCount(2, $union->getTypes());

		$sameSite = TypeCombinator::union($ofMarker, new GenericObjectType(A\A::class, [self::marker($site, new ConstantIntegerType(2))]));
		$this->assertInstanceOf(GenericObjectType::class, $sameSite);

		$nullable = TypeCombinator::union($ofMarker, new NullType());
		$this->assertSame('PHPStan\Type\Test\A\A<unresolved(1)>|null', $nullable->describe(VerbosityLevel::precise()));

		$otherSite = new GenericObjectType(A\A::class, [self::marker(new Variable('b'), new ConstantIntegerType(1))]);
		$twoSites = TypeCombinator::union($ofMarker, $otherSite);
		$this->assertInstanceOf(UnionType::class, $twoSites);
		$this->assertCount(2, $twoSites->getTypes());
	}

	public function testDescribe(): void
	{
		$a = self::marker(new Variable('a'), new ConstantIntegerType(1));
		$b = self::marker(new Variable('b'), new ConstantIntegerType(1));

		$this->assertSame('unresolved(1)', $a->describe(VerbosityLevel::value()));
		$this->assertSame('unresolved(int)', $a->describe(VerbosityLevel::typeOnly()));
		$this->assertStringStartsWith('unresolved#', $a->describe(VerbosityLevel::cache()));
		$this->assertNotSame($a->describe(VerbosityLevel::cache()), $b->describe(VerbosityLevel::cache()), 'cache descriptions are unique per site');
		$this->assertSame('unresolved(mixed)', self::marker(new Variable('a'), null)->describe(VerbosityLevel::precise()));
	}

	public function testTraverseAndGeneralizeKeepTheSite(): void
	{
		$site = new Variable('a');
		$outer = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('f'), 'TOuter', new MixedType(), TemplateTypeVariance::createInvariant());
		$marker = self::marker($site, $outer);

		$this->assertTrue($marker->hasTemplateOrLateResolvableType());
		$resolved = TypeTraverser::map($marker, static function (Type $type, callable $traverse) use ($outer): Type {
			if ($type === $outer) {
				return new IntegerType();
			}

			return $traverse($type);
		});
		$this->assertInstanceOf(UnresolvedTemplateArgumentType::class, $resolved);
		$this->assertSame($site, $resolved->getSite());
		$this->assertInstanceOf(IntegerType::class, $resolved->getInitialType());
		$this->assertTrue($resolved->equals($marker));

		$generalized = self::marker($site, new ConstantIntegerType(1))->generalize(GeneralizePrecision::lessSpecific());
		$this->assertInstanceOf(UnresolvedTemplateArgumentType::class, $generalized);
		$this->assertSame('unresolved(int)', $generalized->describe(VerbosityLevel::precise()));

		$unresolvable = self::marker($site, null);
		$this->assertSame($unresolvable, $unresolvable->generalize(GeneralizePrecision::lessSpecific()));
	}

}
