<?php declare(strict_types = 1);

namespace Bug3136;

interface TypeAorB {}
class TypeA implements TypeAorB {}
class TypeB implements TypeAorB {}
class TypeC implements TypeAorB {}

/** @template T of TypeAorB */
class Container{
	/** @var T */
	public $value;
	/** @param T $value */
	public function __construct(TypeAorB $value) { $this->value = $value; }
}

/** @template T of TypeAorB */
class SubContainer extends Container {
	/** @param T $value */
	public function __construct(TypeAorB $value) { parent::__construct($value); }
}

/**
 * @template TKey
 * @template TValue of TypeAorB
 */
class Pair {
	/** @var TKey */
	public $key;
	/** @var TValue */
	public $value;
	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	public function __construct($key, TypeAorB $value) {
		$this->key = $key;
		$this->value = $value;
	}
}

/**
 * @template T of TypeAorB
 * @param Container<T> $container
 */
function run(Container $container): void{
	var_dump($container->value);
}

/**
 * @template TKey
 * @template TValue of TypeAorB
 * @param Pair<TKey, TValue> $pair
 */
function runPair(Pair $pair): void{
	var_dump($pair->key, $pair->value);
}

$a = new Container(new TypeA);
$b = new Container(new TypeB);

run($a);
run($b);

// union of two generic objects
foreach ([$a, $b] as $item){
	run($item);
}

// union of three generic objects
$c = new Container(new TypeC);
foreach ([$a, $b, $c] as $item){
	run($item);
}

// subclass union
$subA = new SubContainer(new TypeA);
$subB = new SubContainer(new TypeB);
foreach ([$subA, $subB] as $item){
	run($item);
}

// multiple template parameters
$p1 = new Pair(1, new TypeA);
$p2 = new Pair(2, new TypeB);
foreach ([$p1, $p2] as $item){
	runPair($item);
}
