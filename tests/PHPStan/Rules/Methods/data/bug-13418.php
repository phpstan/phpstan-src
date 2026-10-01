<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13418;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 */
class HasOne
{
	public function __construct(
		/** @var class-string<TRelatedModel> */
		public string $related,
		/** @var TDeclaringModel */
		public Model $declaring,
	) {}
}

abstract class Model {}

abstract class AbstractSignature extends Model {}

/** @template TSignature of AbstractSignature */
abstract class AbstractContract extends Model
{
    /**
     * Eloquent relation to the contract's signature class to return latest Signatures.
     *
     * @return HasOne<TSignature, $this>
     */
    abstract public function signature(): HasOne;
}

final class AgreementSignature extends AbstractSignature {}

/** @extends AbstractContract<AgreementSignature> */
final class Agreement extends AbstractContract
{
    #[\Override]
    public function signature(): HasOne
    {
		return new HasOne(AgreementSignature::class, $this);
    }

}

namespace Bug13418Implements;

/** @template T of object */
final class Rel
{
}

/** @template TDeclaring of object */
interface Billable
{

	/** @return Rel<TDeclaring> */
	public function rel(): Rel;

	/** @param Rel<TDeclaring> $rel */
	public function accept(Rel $rel): void;

}

trait IsBillable
{

	/** @return Rel<$this> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<$this> $rel */
	public function accept(Rel $rel): void
	{
	}

}

/** @implements Billable<$this> */
final class ViaTrait implements Billable
{

	use IsBillable;

}

/** @implements Billable<$this> */
final class Direct implements Billable
{

	/** @return Rel<$this> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<$this> $rel */
	public function accept(Rel $rel): void
	{
	}

}

/** @implements Billable<$this> */
final class ConcreteClass implements Billable
{

	/** @return Rel<ConcreteClass> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<ConcreteClass> $rel */
	public function accept(Rel $rel): void
	{
	}

}

final class Other
{
}

/** @implements Billable<$this> */
final class WrongClass implements Billable
{

	/** @return Rel<Other> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<Other> $rel */
	public function accept(Rel $rel): void
	{
	}

}

/** @implements Billable<$this> */
abstract class NotFinal implements Billable
{

	/** @return Rel<$this> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<$this> $rel */
	public function accept(Rel $rel): void
	{
	}

}

/** @implements Billable<NamedParent> */
final class NamedParent implements Billable
{

	/** @return Rel<$this> */
	public function rel(): Rel
	{
		return new Rel();
	}

	/** @param Rel<$this> $rel */
	public function accept(Rel $rel): void
	{
	}

}
