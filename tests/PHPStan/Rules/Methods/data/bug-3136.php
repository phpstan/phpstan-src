<?php declare(strict_types = 1);

namespace Bug3136Method;

interface TypeAorB {}
class TypeA implements TypeAorB {}
class TypeB implements TypeAorB {}

/** @template T of TypeAorB */
class Container{
	/** @var T */
	public $value;
	/** @param T $value */
	public function __construct(TypeAorB $value) { $this->value = $value; }
}

class Runner {
	/**
	 * @template T of TypeAorB
	 * @param Container<T> $container
	 */
	public function run(Container $container): void {
		var_dump($container->value);
	}

	/**
	 * @template T of TypeAorB
	 * @param Container<T> $container
	 */
	public static function runStatic(Container $container): void {
		var_dump($container->value);
	}
}

$a = new Container(new TypeA);
$b = new Container(new TypeB);

$runner = new Runner();

foreach ([$a, $b] as $item){
	$runner->run($item);
	Runner::runStatic($item);
}
