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
 * to it. The `superglobalTypes` parameter narrows it for a project that treats a
 * superglobal as a contract, such as `$_GET` only ever holding request input.
 *
 * The configured type is a PHPDoc type: PHP itself does not enforce it, so the
 * native type stays the default and, with `treatPhpDocTypesAsCertain: false`,
 * a defensive check like `is_string($_GET['id'])` is not reported as always true.
 */
#[AutowiredService]
final class SuperglobalTypeResolver
{

	private ?Type $defaultType = null;

	/** @var array<string, Type> */
	private array $configuredTypes = [];

	/**
	 * @param array<string, string> $superglobalTypes
	 */
	public function __construct(
		#[AutowiredParameter]
		private array $superglobalTypes,
		private Container $container,
	)
	{
	}

	public function getType(string $variableName, bool $nativeTypesPromoted): Type
	{
		if ($nativeTypesPromoted || !array_key_exists($variableName, $this->superglobalTypes)) {
			return $this->defaultType ??= new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType(true));
		}

		// fetched lazily: TypeStringResolver depends on services that depend on the scope factory
		return $this->configuredTypes[$variableName] ??= $this->container->getByType(TypeStringResolver::class)->resolve(
			$this->superglobalTypes[$variableName],
			new NameScope(null, [], className: null),
		);
	}

}
