<?php declare(strict_types = 1);

namespace InvalidCallablePropertyType;

class HelloWorld
{

	/** @var callable(): void */
	public callable $a;

	/** @var callable(): void|null */
	public ?callable $b;

	/** @var callable(): void|string */
	public callable|string $c;

    /**
     * @param \Closure(): void $closure
	 * @param callable(): void $callback
     */
    public function __construct(
		\Closure $closure,
		public callable $callback
	)
	{
        $this->a = $closure;
        $this->b = $closure;
        $this->c = $closure;
    }

}
