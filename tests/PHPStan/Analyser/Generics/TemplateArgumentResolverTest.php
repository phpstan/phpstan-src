<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ResolvedFunctionVariantWithOriginal;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Test\A;
use PHPStan\Type\Test\C;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

class TemplateArgumentResolverTest extends PHPStanTestCase
{

	/** @param non-empty-string $name */
	private static function template(string $className, string $name, ?TemplateTypeVariance $variance = null): TemplateType
	{
		return TemplateTypeFactory::create(
			TemplateTypeScope::createWithClass($className),
			$name,
			new MixedType(),
			$variance ?? TemplateTypeVariance::createInvariant(),
		);
	}

	private static function markerOfA(Expr $site, ?Type $initialType): UnresolvedTemplateArgumentType
	{
		return new UnresolvedTemplateArgumentType($site, self::template(A\A::class, 'T'), $initialType);
	}

	/** @return array{TemplateArgumentConstraints, Expr, GenericObjectType} */
	private static function constraintsWithA(?Type $initialType): array
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$site = new Variable('site');
		$marker = self::markerOfA($site, $initialType);
		$constraints = $constraints->withSite($marker);

		return [$constraints, $site, new GenericObjectType(A\A::class, [$marker])];
	}

	private static function describe(?Type $type): ?string
	{
		return $type !== null ? $type->describe(VerbosityLevel::precise()) : null;
	}

	public function testInvariantSendResolvesToTheFirstAcceptingSend(): void
	{
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new StringType()]), $ofMarker));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [TypeCombinator::union(new IntegerType(), new StringType())]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame('int', self::describe($frame->resolve($site, 'T')), 'string does not accept 1, int is the first accepting send, int|string never widens it');
		$this->assertNull($frame->resolve($site, 'U'));
		$this->assertNull($frame->resolve(new Variable('other'), 'T'));
	}

	public function testNoAcceptingSendKeepsTheInitialType(): void
	{
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new StringType()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));
	}

	public function testNothingInferredResolvesToNeverOrToTheSend(): void
	{
		[$constraints, $site] = self::constraintsWithA(null);
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('*NEVER*', self::describe($frame->resolve($site, 'T')));

		[$constraints, $site, $ofMarker] = self::constraintsWithA(null);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new StringType()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('string', self::describe($frame->resolve($site, 'T')), 'nothing inferred is accepted by every send');
	}

	public function testMixedAndTemplateTargetsAreNotSends(): void
	{
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new MixedType()]), $ofMarker));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [self::template('Other', 'X')]), $ofMarker));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(self::template('Other', 'X'), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));
	}

	public function testLowerBoundsUnionWithTheInitialUnlessASendWins(): void
	{
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$marker = $ofMarker->getTypes()[0];
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectArgument($marker, new ConstantIntegerType(2)));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectArgument($marker, new ConstantStringType('a')));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectArgument(new ArrayType(new IntegerType(), $marker), new ArrayType(new IntegerType(), new NullType())));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectArgument(new CallableType([], $marker, false), new CallableType([], new StringType(), false)));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame("1|2|'a'|null", self::describe($frame->resolve($site, 'T')), 'callable parameters are contravariant and contribute nothing');

		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectArgument($ofMarker->getTypes()[0], new ConstantStringType('a')));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame('int', self::describe($frame->resolve($site, 'T')), 'the send wins; the second pass reports the incompatible lower bound at the call');
	}

	public function testVariance(): void
	{
		// call-site covariant target, known initial: not clamped
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createCovariant()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		// call-site covariant target, nothing inferred: the upper bound is the best information
		[$constraints, $site, $ofMarker] = self::constraintsWithA(null);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createCovariant()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));

		// call-site contravariant target: a lower bound
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createContravariant()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));

		// bivariant target: nothing
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createBivariant()]), $ofMarker));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		// @template-covariant class: the declared variance is the effective one
		$constraints = TemplateArgumentConstraints::createEmpty();
		$site = new Variable('site');
		$covariantMarker = new UnresolvedTemplateArgumentType($site, self::template(C\Covariant::class, 'T', TemplateTypeVariance::createCovariant()), new ConstantIntegerType(1));
		$constraints = $constraints->withSite($covariantMarker);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(C\Covariant::class, [new IntegerType()]), new GenericObjectType(C\Covariant::class, [$covariantMarker])));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		$constraints = TemplateArgumentConstraints::createEmpty();
		$unresolvableCovariantMarker = new UnresolvedTemplateArgumentType($site, self::template(C\Covariant::class, 'T', TemplateTypeVariance::createCovariant()), null);
		$constraints = $constraints->withSite($unresolvableCovariantMarker);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(C\Covariant::class, [new IntegerType()]), new GenericObjectType(C\Covariant::class, [$unresolvableCovariantMarker])));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));
	}

	public function testSendThroughAncestorAndUnionsAndNestedSites(): void
	{
		// SubA<U> extends A<U>: the send to A<int> reaches U through @extends
		$constraints = TemplateArgumentConstraints::createEmpty();
		$site = new Variable('site');
		$marker = new UnresolvedTemplateArgumentType($site, self::template(A\SubA::class, 'U'), new ConstantIntegerType(1));
		$constraints = $constraints->withSite($marker);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(TypeCombinator::union(new GenericObjectType(A\A::class, [new IntegerType()]), new NullType()), TypeCombinator::union(new GenericObjectType(A\SubA::class, [$marker]), new NullType())));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('int', self::describe($frame->resolve($site, 'U')));

		// wrap(new Foo(1)): the outer site's inferred argument carries the inner site
		$constraints = TemplateArgumentConstraints::createEmpty();
		$innerSite = new Variable('inner');
		$outerSite = new Variable('outer');
		$inner = self::markerOfA($innerSite, new ConstantIntegerType(1));
		$outer = self::markerOfA($outerSite, new GenericObjectType(A\A::class, [$inner]));
		$constraints = $constraints->withSite($inner);
		$constraints = $constraints->withSite($outer);
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(
			new GenericObjectType(A\A::class, [new GenericObjectType(A\A::class, [new IntegerType()])]),
			new GenericObjectType(A\A::class, [$outer]),
		));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('PHPStan\Type\Test\A\A<int>', self::describe($frame->resolve($outerSite, 'T')));
		$this->assertSame('int', self::describe($frame->resolve($innerSite, 'T')));

		// array element sends
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$constraints = $constraints->merge((new TemplateArgumentObserver())->collectSend(new ArrayType(new IntegerType(), new GenericObjectType(A\A::class, [new IntegerType()])), new ArrayType(new IntegerType(), $ofMarker)));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));
	}

	public function testInitialTypesUnionAcrossReproducedMarkers(): void
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$site = new Variable('site');
		$constraints = $constraints->withSite(self::markerOfA($site, new ConstantIntegerType(1)));
		$constraints = $constraints->withSite(self::markerOfA($site, new ConstantIntegerType(2)));
		$frame = (new TemplateArgumentResolver())->resolve($constraints, null, []);

		$this->assertSame('1|2', self::describe($frame->resolve($site, 'T')));
	}

	public function testContextsAndConstraintsRemainUnchangedByResolution(): void
	{
		[$constraints, $site, $ofMarker] = self::constraintsWithA(new ConstantIntegerType(1));
		$collecting = new TemplateArgumentFrame(null);
		$branch = $constraints->merge((new TemplateArgumentObserver())->collectSend(new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker));
		$resolver = new TemplateArgumentResolver();
		$parent = $resolver->resolve($branch, null, []);

		$this->assertTrue($collecting->isObserving());
		$this->assertNull($collecting->resolve($site, 'T'));
		$this->assertSame('', $collecting->getResolutionCacheKeySuffix());
		$this->assertSame('1', self::describe($resolver->resolve($constraints, null, [])->resolve($site, 'T')));
		$this->assertSame('int', self::describe($parent->resolve($site, 'T')));
		$this->assertFalse($parent->isObserving());
		$this->assertNotSame('', $parent->getResolutionCacheKeySuffix());

		$child = new TemplateArgumentFrame($parent);
		$this->assertTrue($child->isObserving());
		$this->assertSame('int', self::describe($child->resolve($site, 'T')));
		$this->assertSame($parent->getResolutionCacheKeySuffix(), $child->getResolutionCacheKeySuffix());
		$this->assertNull($child->firstSiteStatementIndex());
	}

	public function testReturnTypeCacheDistinguishesImmutableResolutions(): void
	{
		$template = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('wrap'), 'T', new MixedType(), TemplateTypeVariance::createInvariant());
		$type = new GenericObjectType(A\A::class, [$template]);
		$variant = new ResolvedFunctionVariantWithOriginal(
			new ExtendedFunctionVariant(new TemplateTypeMap(['T' => $template]), null, [], false, $type, $type, new MixedType()),
			new TemplateTypeMap(['T' => new ConstantIntegerType(1)]),
			TemplateTypeVarianceMap::createEmpty(),
			[],
		);
		$site = new Variable('site');
		$marker = new UnresolvedTemplateArgumentType($site, $template, new ConstantIntegerType(1));
		$constraints = TemplateArgumentConstraints::createEmpty()->withSite($marker);
		$resolver = new TemplateArgumentResolver();
		$initial = $resolver->resolve($constraints, null, []);
		$sent = $resolver->resolve($constraints->withSend($marker, new IntegerType(), TemplateTypeVariance::createInvariant()), null, []);
		$collecting = new TemplateArgumentFrame(null);
		$unresolved = $variant->getReturnTypeWithUnresolvedTemplateArguments($site, $collecting, true);

		$this->assertSame('PHPStan\Type\Test\A\A<1>', self::describe($variant->getReturnTypeWithUnresolvedTemplateArguments($site, $initial, true)));
		$this->assertSame('PHPStan\Type\Test\A\A<int>', self::describe($variant->getReturnTypeWithUnresolvedTemplateArguments($site, $sent, true)));
		$this->assertSame('PHPStan\Type\Test\A\A<1>', self::describe($variant->getReturnTypeWithUnresolvedTemplateArguments($site, $initial, true)));
		$this->assertTrue($unresolved->equals($variant->getReturnTypeWithUnresolvedTemplateArguments($site, $collecting, true)));
		$this->assertTrue($collecting->isObserving());
	}

	public function testSiteAttributionByTokenPosition(): void
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$inSecond = new Variable('a', ['startTokenPos' => 15]);
		$inThird = new Variable('b', ['startTokenPos' => 20]);
		$constraints = $constraints->withSite(self::markerOfA($inSecond, null));
		$resolver = new TemplateArgumentResolver();
		$frame = $resolver->resolve($constraints, null, [0, 10, 20]);
		$this->assertSame(1, $frame->firstSiteStatementIndex());
		$this->assertTrue($frame->ownsSiteInStatement(1));
		$this->assertFalse($frame->ownsSiteInStatement(2));
		$this->assertTrue($frame->hasSiteAtOrAfter(1));
		$this->assertFalse($frame->hasSiteAtOrAfter(2));

		$extended = $resolver->resolve($constraints->withSite(self::markerOfA($inThird, null)), null, [0, 10, 20]);
		$this->assertTrue($extended->ownsSiteInStatement(2));
		$this->assertSame(1, $extended->firstSiteStatementIndex());
		$this->assertFalse($frame->ownsSiteInStatement(2));

		// An unpositioned site conservatively starts the re-walk at the beginning.
		$extended = $resolver->resolve($constraints->withSite(self::markerOfA(new Variable('c'), null)), null, [0, 10, 20]);
		$this->assertSame(0, $extended->firstSiteStatementIndex());
	}

}
