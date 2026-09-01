<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ArrayType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
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

class TemplateArgumentFrameTest extends PHPStanTestCase
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

	/** @return array{TemplateArgumentFrame, Expr, GenericObjectType} */
	private static function frameWithA(?Type $initialType): array
	{
		$frame = new TemplateArgumentFrame(null, []);
		$site = new Variable('site');
		$marker = self::markerOfA($site, $initialType);
		$frame->noteSite($marker);

		return [$frame, $site, new GenericObjectType(A\A::class, [$marker])];
	}

	private static function describe(?Type $type): ?string
	{
		return $type?->describe(VerbosityLevel::precise());
	}

	public function testInvariantSendResolvesToTheFirstAcceptingSend(): void
	{
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new StringType()]), $ofMarker);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [TypeCombinator::union(new IntegerType(), new StringType())]), $ofMarker);
		$frame->finishObserving();

		$this->assertSame('int', self::describe($frame->resolve($site, 'T')), 'string does not accept 1, int is the first accepting send, int|string never widens it');
		$this->assertNull($frame->resolve($site, 'U'));
		$this->assertNull($frame->resolve(new Variable('other'), 'T'));
	}

	public function testNoAcceptingSendKeepsTheInitialType(): void
	{
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new StringType()]), $ofMarker);
		$frame->finishObserving();

		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));
	}

	public function testNothingInferredResolvesToNeverOrToTheSend(): void
	{
		[$frame, $site] = self::frameWithA(null);
		$frame->finishObserving();
		$this->assertSame('*NEVER*', self::describe($frame->resolve($site, 'T')));

		[$frame, $site, $ofMarker] = self::frameWithA(null);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new StringType()]), $ofMarker);
		$frame->finishObserving();
		$this->assertSame('string', self::describe($frame->resolve($site, 'T')), 'nothing inferred is accepted by every send');
	}

	public function testMixedAndTemplateTargetsAreNotSends(): void
	{
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new MixedType()]), $ofMarker);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [self::template('Other', 'X')]), $ofMarker);
		TemplateArgumentObserver::observeSend($frame, self::template('Other', 'X'), $ofMarker);
		$frame->finishObserving();

		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));
	}

	public function testLowerBoundsUnionWithTheInitialUnlessASendWins(): void
	{
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		$marker = $ofMarker->getTypes()[0];
		TemplateArgumentObserver::observeArgument($frame, $marker, new ConstantIntegerType(2));
		TemplateArgumentObserver::observeArgument($frame, $marker, new ConstantStringType('a'));
		TemplateArgumentObserver::observeArgument($frame, new ArrayType(new IntegerType(), $marker), new ArrayType(new IntegerType(), new NullType()));
		TemplateArgumentObserver::observeArgument($frame, new CallableType([], $marker, false), new CallableType([], new StringType(), false));
		$frame->finishObserving();

		$this->assertSame("1|2|'a'|null", self::describe($frame->resolve($site, 'T')), 'callable parameters are contravariant and contribute nothing');

		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeArgument($frame, $ofMarker->getTypes()[0], new ConstantStringType('a'));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker);
		$frame->finishObserving();

		$this->assertSame('int', self::describe($frame->resolve($site, 'T')), 'the send wins; the second pass reports the incompatible lower bound at the call');
	}

	public function testVariance(): void
	{
		// call-site covariant target, known initial: not clamped
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createCovariant()]), $ofMarker);
		$frame->finishObserving();
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		// call-site covariant target, nothing inferred: the upper bound is the best information
		[$frame, $site, $ofMarker] = self::frameWithA(null);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createCovariant()]), $ofMarker);
		$frame->finishObserving();
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));

		// call-site contravariant target: a lower bound
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createContravariant()]), $ofMarker);
		$frame->finishObserving();
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));

		// bivariant target: nothing
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(A\A::class, [new IntegerType()], variances: [TemplateTypeVariance::createBivariant()]), $ofMarker);
		$frame->finishObserving();
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		// @template-covariant class: the declared variance is the effective one
		$frame = new TemplateArgumentFrame(null, []);
		$site = new Variable('site');
		$covariantMarker = new UnresolvedTemplateArgumentType($site, self::template(C\Covariant::class, 'T', TemplateTypeVariance::createCovariant()), new ConstantIntegerType(1));
		$frame->noteSite($covariantMarker);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(C\Covariant::class, [new IntegerType()]), new GenericObjectType(C\Covariant::class, [$covariantMarker]));
		$frame->finishObserving();
		$this->assertSame('1', self::describe($frame->resolve($site, 'T')));

		$frame = new TemplateArgumentFrame(null, []);
		$unresolvableCovariantMarker = new UnresolvedTemplateArgumentType($site, self::template(C\Covariant::class, 'T', TemplateTypeVariance::createCovariant()), null);
		$frame->noteSite($unresolvableCovariantMarker);
		TemplateArgumentObserver::observeSend($frame, new GenericObjectType(C\Covariant::class, [new IntegerType()]), new GenericObjectType(C\Covariant::class, [$unresolvableCovariantMarker]));
		$frame->finishObserving();
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));
	}

	public function testSendThroughAncestorAndUnionsAndNestedSites(): void
	{
		// SubA<U> extends A<U>: the send to A<int> reaches U through @extends
		$frame = new TemplateArgumentFrame(null, []);
		$site = new Variable('site');
		$marker = new UnresolvedTemplateArgumentType($site, self::template(A\SubA::class, 'U'), new ConstantIntegerType(1));
		$frame->noteSite($marker);
		TemplateArgumentObserver::observeSend($frame, TypeCombinator::union(new GenericObjectType(A\A::class, [new IntegerType()]), new NullType()), TypeCombinator::union(new GenericObjectType(A\SubA::class, [$marker]), new NullType()));
		$frame->finishObserving();
		$this->assertSame('int', self::describe($frame->resolve($site, 'U')));

		// wrap(new Foo(1)): the outer site's inferred argument carries the inner site
		$frame = new TemplateArgumentFrame(null, []);
		$innerSite = new Variable('inner');
		$outerSite = new Variable('outer');
		$inner = self::markerOfA($innerSite, new ConstantIntegerType(1));
		$outer = self::markerOfA($outerSite, new GenericObjectType(A\A::class, [$inner]));
		$frame->noteSite($inner);
		$frame->noteSite($outer);
		TemplateArgumentObserver::observeSend(
			$frame,
			new GenericObjectType(A\A::class, [new GenericObjectType(A\A::class, [new IntegerType()])]),
			new GenericObjectType(A\A::class, [$outer]),
		);
		$frame->finishObserving();
		$this->assertSame('PHPStan\Type\Test\A\A<int>', self::describe($frame->resolve($outerSite, 'T')));
		$this->assertSame('int', self::describe($frame->resolve($innerSite, 'T')));

		// array element sends
		[$frame, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($frame, new ArrayType(new IntegerType(), new GenericObjectType(A\A::class, [new IntegerType()])), new ArrayType(new IntegerType(), $ofMarker));
		$frame->finishObserving();
		$this->assertSame('int', self::describe($frame->resolve($site, 'T')));
	}

	public function testInitialTypesUnionAcrossReproducedMarkers(): void
	{
		$frame = new TemplateArgumentFrame(null, []);
		$site = new Variable('site');
		$frame->noteSite(self::markerOfA($site, new ConstantIntegerType(1)));
		$frame->noteSite(self::markerOfA($site, new ConstantIntegerType(2)));
		$frame->finishObserving();

		$this->assertSame('1|2', self::describe($frame->resolve($site, 'T')));
	}

	public function testParentChainAndCacheKeySuffix(): void
	{
		[$parent, $site, $ofMarker] = self::frameWithA(new ConstantIntegerType(1));
		TemplateArgumentObserver::observeSend($parent, new GenericObjectType(A\A::class, [new IntegerType()]), $ofMarker);
		$this->assertSame('', $parent->getResolutionCacheKeySuffix());
		$parent->finishObserving();
		$this->assertNotSame('', $parent->getResolutionCacheKeySuffix());

		$child = new TemplateArgumentFrame($parent, []);
		$this->assertTrue($child->isObserving());
		$this->assertSame('int', self::describe($child->resolve($site, 'T')));
		$this->assertSame($parent->getResolutionCacheKeySuffix(), $child->getResolutionCacheKeySuffix());
		$this->assertFalse($child->hasSites());
	}

	public function testSiteAttributionByTokenPosition(): void
	{
		$frame = new TemplateArgumentFrame(null, [0, 10, 20]);
		$frame->setCurrentStatementIndex(2);

		$inSecond = new Variable('a', ['startTokenPos' => 15]);
		$inThird = new Variable('b', ['startTokenPos' => 20]);
		$positionless = new Variable('c');
		$frame->noteSite(self::markerOfA($inSecond, null));
		$this->assertSame(1, $frame->firstSiteStatementIndex());
		$this->assertTrue($frame->ownsSiteInStatement(1));
		$this->assertFalse($frame->ownsSiteInStatement(2));
		$this->assertTrue($frame->hasSiteAtOrAfter(1));
		$this->assertFalse($frame->hasSiteAtOrAfter(2));

		$frame->noteSite(self::markerOfA($inThird, null));
		$frame->noteSite(self::markerOfA($positionless, null));
		$this->assertTrue($frame->ownsSiteInStatement(2));
		$this->assertSame(1, $frame->firstSiteStatementIndex());

		$frame = new TemplateArgumentFrame(null, [0, 10, 20]);
		$frame->setCurrentStatementIndex(0);
		$frame->noteSite(self::markerOfA(new Variable('d', ['startTokenPos' => 0]), null));
		$this->assertSame(0, $frame->firstSiteStatementIndex());
	}

}
