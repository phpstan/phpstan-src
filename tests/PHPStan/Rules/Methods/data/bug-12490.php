<?php declare(strict_types = 1);

namespace Bug12490;

function route(string $name, int $id): string
{
	return "{$name}/{$id}";
}

/**
 * @template TGet
 * @template TSet
 */
class Attribute
{
    /** @var (callable(mixed, array<string, mixed>): TGet)|null */
    public $get;

    /** @var (callable(TSet, array<string, mixed>): mixed)|null*/
    public $set;

    /**
     * Create a new attribute accessor / mutator.
     *
     * @param  (callable(mixed, array<string, mixed>): TGet)|null  $get
     * @param  (callable(TSet, array<string, mixed>): mixed)|null  $set
     */
    public function __construct(?callable $get = null, ?callable $set = null)
    {
        $this->get = $get;
        $this->set = $set;
    }

    /**
     * @template TMakeGet
     * @template TMakeSet
     * @param  (callable(mixed, array<string, mixed>): TMakeGet)|null  $get
     * @param  (callable(TMakeSet, array<string, mixed>): mixed)|null  $set
     * @return Attribute<TMakeGet, TMakeSet>
     */
    public static function make(?callable $get = null, ?callable $set = null): self
    {
        return new self($get, $set);
    }

    /**
     * @template T
     * @param  callable(mixed, array<string, mixed>): T  $get
     * @return Attribute<T, never>
     */
    public static function get(callable $get): self
    {
        return new self($get);
    }

    /**
     * @template T
     * @param  callable(T, array<string, mixed>): mixed $set
     * @return Attribute<never, T>
     */
    public static function set(callable $set): self
    {
        return new self(null, $set);
    }
}


class Foo
{
	public ?int $id = null;
	public ?string $surveyable_type = null;
	
	/** @return Attribute<string, never> */
    protected function uri(): Attribute
    {
        return Attribute::get(fn (): string => "fOo/{$this->id}");
    }
	
	/** @return Attribute<string, never> */
    protected function uri2(): Attribute
    {
        return Attribute::get(fn (): string => "foo/{$this->id}");
    }

    /**
     * @return Attribute<null|string, never>
     */
    protected function surveyedLink(): Attribute
    {
        return Attribute::get(fn () => $this->surveyable_type);
    }


    /** @return Attribute<null|float, never> */
    protected function packageWeightCalculated(): Attribute
    {
        return Attribute::get(fn () => $this->id === null ? null : round(50 * .15, 2));
    }

	
    /** @return Attribute<?int, never> */
    protected function durationMs(): Attribute
    {
        return Attribute::get(fn () => $this->id);
    }
}
