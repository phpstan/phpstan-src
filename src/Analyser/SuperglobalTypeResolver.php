<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function array_key_exists;

/**
 * Types a superglobal variable whose type the scope does not track, i.e. its
 * value at the start of every function and after anything that may have written
 * to it.
 *
 * By default every superglobal is `array<mixed>`, since a script can write
 * anything into it. A project that treats superglobals as a contract opts in to
 * narrower types: `readonlySuperglobals` assumes the shapes PHP populates them
 * with (READONLY_SUPERGLOBAL_TYPES), and `superglobalTypes` declares the type of
 * any superglobal, taking precedence over those shapes. Either type stays sound
 * only while what is written into the superglobal fits it, which
 * SuperglobalAssignTypeRule checks.
 *
 * Either type is a PHPDoc type: PHP itself does not enforce it, so the native
 * type stays the default and, with `treatPhpDocTypesAsCertain: false`, a
 * defensive check like `is_string($_GET['id'])` is not reported as always true.
 */
#[AutowiredService]
final class SuperglobalTypeResolver
{

	/**
	 * Request input is strings, nested into arrays by brackets in a name
	 * (`?a[]=1&a[b][]=2`); the nesting is unbounded, so the type stops at two levels.
	 * `$_SERVER` is strings too, the request variables, headers and environment,
	 * except for the few entries PHP adds itself; `argc` is 0 and `argv` empty on
	 * the web when `register_argc_argv` is on and there is no query string.
	 * `REQUEST_TIME` and `REQUEST_TIME_FLOAT` are set for every request by every
	 * SAPI, unless `variables_order` leaves out `S` and `$_SERVER` stays empty;
	 * any other key, a header in particular, is up to the SAPI, the web server or the client.
	 * The keys are not narrowed to strings: like any array key, a numeric name
	 * (`?1=a`, an environment variable named `123`) becomes an int.
	 * `$_SESSION` and `$GLOBALS` are left out: what they hold is up to the application.
	 */
	private const READONLY_SUPERGLOBAL_TYPES = [
		'_GET' => self::REQUEST_INPUT_TYPE,
		'_POST' => self::REQUEST_INPUT_TYPE,
		'_COOKIE' => self::REQUEST_INPUT_TYPE,
		'_REQUEST' => self::REQUEST_INPUT_TYPE,
		'_FILES' => 'array<array{name: string, full_path?: string, type: string, tmp_name: string, error: int<0, 8>, size: int<0, max>}|array{name: array<mixed>, full_path?: array<mixed>, type: array<mixed>, tmp_name: array<mixed>, error: array<mixed>, size: array<mixed>}>',
		'_SERVER' => 'array{REQUEST_TIME: int, REQUEST_TIME_FLOAT: float, argc?: int<0, max>, argv?: list<string>, ...<string>}',
		'_ENV' => 'array<string>',
	];

	private const REQUEST_INPUT_TYPE = 'array<string|array<string|array<mixed>>>';

	/** @var array<string, string> */
	private array $typeStrings;

	private ?Type $defaultType = null;

	/** @var array<string, Type> */
	private array $resolvedTypes = [];

	/**
	 * @param array<string, string> $superglobalTypes
	 */
	public function __construct(
		#[AutowiredParameter]
		bool $readonlySuperglobals,
		#[AutowiredParameter]
		array $superglobalTypes,
		private Container $container,
	)
	{
		$this->typeStrings = $readonlySuperglobals ? $superglobalTypes + self::READONLY_SUPERGLOBAL_TYPES : $superglobalTypes;
	}

	public function getType(string $variableName, bool $nativeTypesPromoted): Type
	{
		$contractType = $nativeTypesPromoted ? null : $this->getContractType($variableName);

		return $contractType ?? $this->defaultType ??= new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
	}

	/**
	 * The type the project holds the superglobal to, declared or assumed;
	 * null when it has none and anything may be written into it.
	 */
	public function getContractType(string $variableName): ?Type
	{
		if (!array_key_exists($variableName, $this->typeStrings)) {
			return null;
		}

		// fetched lazily: TypeStringResolver depends on services that depend on the scope factory
		return $this->resolvedTypes[$variableName] ??= $this->container->getByType(TypeStringResolver::class)->resolve(
			$this->typeStrings[$variableName],
			new NameScope(null, [], className: null),
		);
	}

}
