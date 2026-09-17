<?php declare(strict_types = 1);

namespace ReflectionFamilyFixture;

use Attribute;
use Countable;
use IteratorAggregate;
use Traversable;

interface Shape
{

	public function area(): float;

}

interface HasName
{

	public function name(): string;

}

interface Labeled extends HasName
{

}

/**
 * @template T
 */
interface Repo
{

	/** @return T|null */
	public function find(int $id): mixed;

}

/**
 * @phpstan-require-extends Base
 */
interface RequiresBase
{

}

trait Greets
{

	public function greet(): string
	{
		return 'hi ' . $this->secret();
	}

	private function secret(): int
	{
		return 1;
	}

	public static function make(): static
	{
		return new static();
	}

}

trait Nested
{

	use Greets;

	protected int $fromTrait = 0;

}

abstract class Base implements Shape
{

	public int $pub = 0;

	protected static int $count = 0;

	private string $priv = '';

	public function __construct(public int $x = 0)
	{
	}

	abstract public function area(): float;

	public function base(): int
	{
		return $this->x;
	}

	private function hidden(): void
	{
	}

}

final class Circle extends Base implements Labeled, Countable
{

	use Nested;

	public function area(): float
	{
		return 3.14;
	}

	public function name(): string
	{
		return 'circle';
	}

	public function count(): int
	{
		return 1;
	}

}

class Plain
{

}

class Legacy
{

	public function Legacy(): void
	{
	}

}

#[\AllowDynamicProperties]
class Dynamic
{

}

class DynamicChild extends Dynamic
{

}

class Magic
{

	public function __get(string $name): mixed
	{
		return null;
	}

}

readonly class Frozen
{

	public function __construct(public int $v)
	{
	}

}

enum Suit: string
{

	case Hearts = 'H';
	case Spades = 'S';

	public const DEFAULT = self::Hearts;

	public function label(): string
	{
		return $this->value;
	}

}

enum Pure
{

	case A;
	case B;

}

/**
 * @template T of object
 * @template U
 * @implements IteratorAggregate<int, T>
 */
class Box implements IteratorAggregate
{

	/** @param T $value */
	public function __construct(public object $value)
	{
	}

	/** @return U|null */
	public function extra(): mixed
	{
		return null;
	}

	public function getIterator(): Traversable
	{
		yield $this->value;
	}

}

/**
 * @extends Box<Circle, int>
 */
class CircleBox extends Box
{

}

/**
 * @template T
 * @extends Box<Circle, T>
 */
class BoxOf extends Box
{

}

/**
 * @implements Repo<Circle>
 */
class CircleRepo implements Repo
{

	public function find(int $id): mixed
	{
		return null;
	}

}

/**
 * @template T of Shape
 */
abstract class ShapeRepo implements Repo
{

}

class WithProps implements RequiresBase
{

	public int $a = 0;

	public static int $s = 0;

	private string $p = '';

	protected ?Circle $c = null;

}

#[Attribute(Attribute::TARGET_CLASS)]
final class Marker
{

	public function __construct(public string $v = '')
	{
	}

}

/**
 * @final
 */
class DocFinal
{

}

/**
 * @deprecated Use Plain instead.
 */
class Old
{

}

/**
 * @phpstan-type Id int
 * @phpstan-type Pair array{Id, string}
 */
class Aliases
{

	public const LIMIT = 10;

	/** @var non-empty-string */
	public const NAME = 'alias';

}

/**
 * @phpstan-import-type Id from Aliases
 * @phpstan-import-type Pair from Aliases as Duo
 * @phpstan-import-type Missing from Aliases
 * @phpstan-import-type Whatever from \Nope\Missing
 * @phpstan-type Local float
 */
class ImportsAliases extends Aliases
{

}

interface HasConstants
{

	public const INHERITED = 'i';

	/** @var array<int, string> */
	public const DOCUMENTED = ['a'];

}

/**
 * @template T of Shape
 */
class ConstantsHolder implements HasConstants
{

	/** @var T|null */
	public const TEMPLATED = null;

	/**
	 * @deprecated no longer used
	 * @internal
	 * @final
	 */
	public const OLD = 1;

	public const int TYPED = 3;

}

/**
 * @mixin Circle
 * @property int $magicProp
 * @property-read string $magicReadOnly
 * @method string magicMethod(int $a)
 * @method static int magicStatic()
 * @phpstan-require-implements HasName
 */
interface Mixed_
{

}

/**
 * @template T
 * @mixin Box<Circle, T>
 */
class MixinHolder
{

}

/**
 * @phpstan-sealed Circle|Plain
 * @immutable
 * @internal
 * @phpstan-consistent-constructor
 * @no-named-arguments
 */
abstract class SealedBase
{

}

class ImmutableChild extends SealedBase
{

}

#[Attribute]
final class DefaultFlags
{

	public function __construct(public int $flags = Attribute::TARGET_ALL)
	{
	}

}

#[Attribute(flags: Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class NamedFlags
{

}

#[Marker('x')]
#[\AllowDynamicProperties]
class Decorated
{

}

enum Cards: int
{

	#[Marker('h')]
	case Hearts = 1;

	/** @deprecated use Hearts */
	case Spades = 2;

	public const FIRST = self::Hearts;

}

/**
 * A documented trait, for the trait-context PHPDoc.
 *
 * @template T
 * @property int $traitProp
 */
trait Documented
{

	public function documented(): int
	{
		return 1;
	}

}

class UsesDocumented
{

	use Documented;

}

/**
 * Traits reached twice — directly and through another trait, and named twice
 * in one use — for the trait walks (collectTraits(), getTraits()).
 */
trait Twice
{

	use Greets, Nested;

}

class UsesTwice
{

	use Twice, Greets;

}

class UsesSameTraitTwice
{

	use Greets, Greets;

}
