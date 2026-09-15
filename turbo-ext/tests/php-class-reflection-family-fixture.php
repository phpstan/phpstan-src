<?php declare(strict_types = 1);

/**
 * Fixture for tests/php-class-reflection-family.php: classes whose members
 * exercise every branch of PhpClassReflectionExtension — inherited members,
 * trait members (including aliased/abstract ones), magic __get/__call
 * properties and methods, promoted constructor properties, a private
 * property whose type is inferred from the constructor, property hooks,
 * enums (pure and backed), interfaces, readonly, final, attributes and
 * @deprecated/@internal/@var/@param tags.
 */

namespace PhpClassReflectionFamilyFixture;

use PHPStan\Reflection\Attribute\PrivateProperty;
use PHPStan\Reflection\Attribute\ProtectedProperty;

#[\Attribute(\Attribute::TARGET_ALL)]
class FixtureAttribute
{

	public function __construct(public string $label = 'x', public int $weight = 0)
	{
	}

}

trait FixtureTrait
{

	/** @var non-empty-string */
	public string $fromTrait = 'trait';

	/** @param positive-int $times */
	public function traitMethod(int $times): string
	{
		return str_repeat($this->fromTrait, $times);
	}

	public function aliasedMethod(): int
	{
		return 1;
	}

}

interface FixtureInterface
{

	/** @return non-empty-string */
	public function fromInterface(): string;

}

/**
 * @property-read string $magicRead
 * @property int $magicWrite
 * @method string magicMethod(int $a)
 * @template T of object
 */
abstract class FixtureBase implements FixtureInterface
{

	use FixtureTrait { aliasedMethod as protected renamedMethod; }

	/**
	 * @var array<int, string>
	 * @deprecated use something else
	 */
	public array $deprecatedProperty = [];

	/** @internal */
	protected int $internalProperty = 0;

	#[FixtureAttribute(label: 'attributed', weight: 3)]
	public ?string $attributed = null;

	/** the type of this one is inferred from the constructor body */
	private $inferredFromConstructor;

	#[PrivateProperty]
	public int $publicButPrivate = 1;

	#[ProtectedProperty]
	public int $publicButProtected = 2;

	public function __construct(
		/** @var non-empty-string */
		public readonly string $promoted = 'p',
		protected int $promotedProtected = 7,
	)
	{
		$this->inferredFromConstructor = ['a' => 1, 'b' => 2];
	}

	public function __get(string $name): mixed
	{
		return null;
	}

	public function __call(string $name, array $arguments): mixed
	{
		return null;
	}

	/** @return non-empty-string */
	public function fromInterface(): string
	{
		return 'i';
	}

	/**
	 * @param callable(int): string $callback
	 * @param-immediately-invoked-callable $callback
	 * @param-out int $counter
	 * @throws \RuntimeException
	 * @phpstan-assert-if-true non-empty-string $input
	 * @deprecated do not
	 */
	abstract public function rich(callable $callback, int &$counter, mixed $input): bool;

	/** @return static */
	public function fluent(): static
	{
		return $this;
	}

}

final class FixtureChild extends FixtureBase
{

	public string $hooked = 'h' {
		get => $this->hooked . '!';
		set (string $value) {
			$this->hooked = $value;
		}
	}

	public function rich(callable $callback, int &$counter, mixed $input): bool
	{
		return true;
	}

	/** @param-closure-this self $closure */
	public function withClosureThis(\Closure $closure): void
	{
	}

}

enum FixturePureEnum
{

	case Alpha;
	case Beta;

	public function label(): string
	{
		return $this->name;
	}

}

enum FixtureBackedEnum: string implements FixtureInterface
{

	case One = 'one';
	case Two = 'two';

	/** @return non-empty-string */
	public function fromInterface(): string
	{
		return $this->value;
	}

}

/** @immutable */
final class FixtureImmutable
{

	public function __construct(public int $value = 0)
	{
	}

}

/**
 * The annotation properties here sit closer in the hierarchy than the real
 * ones they shadow, so createProperty() takes its annotation branch.
 *
 * @property int $deprecatedProperty
 * @property-read string $publicButProtected
 * @method int traitMethod(int $times)
 */
#[\AllowDynamicProperties]
class FixtureAnnotated extends FixtureBase
{

	public function rich(callable $callback, int &$counter, mixed $input): bool
	{
		return true;
	}

}

/**
 * The same annotations without #[AllowDynamicProperties]: the branch is
 * then gated on the scope being able to read the native property.
 *
 * @property int $deprecatedProperty
 * @property-read string $internalProperty
 */
class FixtureAnnotatedStrict extends FixtureBase
{

	public function rich(callable $callback, int &$counter, mixed $input): bool
	{
		return true;
	}

}
