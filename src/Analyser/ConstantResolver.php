<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Name;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function in_array;
use function sprintf;
use const INF;
use const NAN;
use const PHP_INT_SIZE;

class ConstantResolver
{

	/** @var array<string, true> */
	private array $currentlyResolving = [];

	/**
	 * @param string[] $dynamicConstantNames
	 */
	public function __construct(private ReflectionProviderProvider $reflectionProviderProvider, private array $dynamicConstantNames)
	{
	}

	public function resolveConstant(Name $name, ?NamespaceAnswerer $scope): ?Type
	{
		if (!$this->getReflectionProvider()->hasConstant($name, $scope)) {
			return null;
		}

		/** @var string $resolvedConstantName */
		$resolvedConstantName = $this->getReflectionProvider()->resolveConstantName($name, $scope);

		$constantType = $this->resolvePredefinedConstant($resolvedConstantName);
		if ($constantType !== null) {
			return $constantType;
		}

		if (array_key_exists($resolvedConstantName, $this->currentlyResolving)) {
			return new MixedType();
		}

		$this->currentlyResolving[$resolvedConstantName] = true;

		$constantReflection = $this->getReflectionProvider()->getConstant($name, $scope);
		$constantType = $constantReflection->getValueType();

		$type = $this->resolveConstantType($resolvedConstantName, $constantType);
		unset($this->currentlyResolving[$resolvedConstantName]);

		return $type;
	}

	public function resolvePredefinedConstant(string $resolvedConstantName): ?Type
	{
		// core, https://www.php.net/manual/en/reserved.constants.php
		if ($resolvedConstantName === 'PHP_VERSION') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_MAJOR_VERSION') {
			return IntegerRangeType::fromInterval(5, null);
		}
		if ($resolvedConstantName === 'PHP_MINOR_VERSION') {
			return IntegerRangeType::fromInterval(0, null);
		}
		if ($resolvedConstantName === 'PHP_RELEASE_VERSION') {
			return IntegerRangeType::fromInterval(0, null);
		}
		if ($resolvedConstantName === 'PHP_VERSION_ID') {
			return IntegerRangeType::fromInterval(50207, null);
		}
		if ($resolvedConstantName === 'PHP_ZTS') {
			return new UnionType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			]);
		}
		if ($resolvedConstantName === 'PHP_DEBUG') {
			return new UnionType([
				new ConstantIntegerType(0),
				new ConstantIntegerType(1),
			]);
		}
		if ($resolvedConstantName === 'PHP_MAXPATHLEN') {
			return IntegerRangeType::fromInterval(1, null);
		}
		if ($resolvedConstantName === 'PHP_OS') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_OS_FAMILY') {
			return new UnionType([
				new ConstantStringType('Windows'),
				new ConstantStringType('BSD'),
				new ConstantStringType('Darwin'),
				new ConstantStringType('Solaris'),
				new ConstantStringType('Linux'),
				new ConstantStringType('Unknown'),
			]);
		}
		if ($resolvedConstantName === 'PHP_SAPI') {
			return new UnionType([
				new ConstantStringType('apache'),
				new ConstantStringType('apache2handler'),
				new ConstantStringType('cgi'),
				new ConstantStringType('cli'),
				new ConstantStringType('cli-server'),
				new ConstantStringType('embed'),
				new ConstantStringType('fpm-fcgi'),
				new ConstantStringType('litespeed'),
				new ConstantStringType('phpdbg'),
				new IntersectionType([
					new StringType(),
					new AccessoryNonFalsyStringType(),
				]),
			]);
		}
		if ($resolvedConstantName === 'PHP_EOL') {
			return new UnionType([
				new ConstantStringType("\n"),
				new ConstantStringType("\r\n"),
			]);
		}
		if ($resolvedConstantName === 'PHP_INT_MAX') {
			return PHP_INT_SIZE === 8
				? new UnionType([new ConstantIntegerType(2147483647), new ConstantIntegerType(9223372036854775807)])
				: new ConstantIntegerType(2147483647);
		}
		if ($resolvedConstantName === 'PHP_INT_MIN') {
			// Why the -1 you might wonder, the answer is to fit it into an int :/ see https://3v4l.org/4SHIQ
			return PHP_INT_SIZE === 8
				? new UnionType([new ConstantIntegerType(-9223372036854775807 - 1), new ConstantIntegerType(-2147483647 - 1)])
				: new ConstantIntegerType(-2147483647 - 1);
		}
		if ($resolvedConstantName === 'PHP_INT_SIZE') {
			return new UnionType([
				new ConstantIntegerType(4),
				new ConstantIntegerType(8),
			]);
		}
		if ($resolvedConstantName === 'PHP_FLOAT_DIG') {
			return IntegerRangeType::fromInterval(1, null);
		}
		if ($resolvedConstantName === 'PHP_EXTENSION_DIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_PREFIX') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_BINDIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_BINARY') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_MANDIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_LIBDIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_DATADIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_SYSCONFDIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_LOCALSTATEDIR') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_CONFIG_FILE_PATH') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		if ($resolvedConstantName === 'PHP_SHLIB_SUFFIX') {
			return new UnionType([
				new ConstantStringType('so'),
				new ConstantStringType('dll'),
			]);
		}
		if ($resolvedConstantName === 'PHP_FD_SETSIZE') {
			return IntegerRangeType::fromInterval(1, null);
		}
		if ($resolvedConstantName === '__COMPILER_HALT_OFFSET__') {
			return IntegerRangeType::fromInterval(1, null);
		}
		// core other, https://www.php.net/manual/en/info.constants.php
		if ($resolvedConstantName === 'PHP_WINDOWS_VERSION_MAJOR') {
			return IntegerRangeType::fromInterval(4, null);
		}
		if ($resolvedConstantName === 'PHP_WINDOWS_VERSION_MINOR') {
			return IntegerRangeType::fromInterval(0, null);
		}
		if ($resolvedConstantName === 'PHP_WINDOWS_VERSION_BUILD') {
			return IntegerRangeType::fromInterval(1, null);
		}
		// dir, https://www.php.net/manual/en/dir.constants.php
		if ($resolvedConstantName === 'DIRECTORY_SEPARATOR') {
			return new UnionType([
				new ConstantStringType('/'),
				new ConstantStringType('\\'),
			]);
		}
		if ($resolvedConstantName === 'PATH_SEPARATOR') {
			return new UnionType([
				new ConstantStringType(':'),
				new ConstantStringType(';'),
			]);
		}
		// iconv, https://www.php.net/manual/en/iconv.constants.php
		if ($resolvedConstantName === 'ICONV_IMPL') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		// libxml, https://www.php.net/manual/en/libxml.constants.php
		if ($resolvedConstantName === 'LIBXML_VERSION') {
			return IntegerRangeType::fromInterval(1, null);
		}
		if ($resolvedConstantName === 'LIBXML_DOTTED_VERSION') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}
		// openssl, https://www.php.net/manual/en/openssl.constants.php
		if ($resolvedConstantName === 'OPENSSL_VERSION_NUMBER') {
			return IntegerRangeType::fromInterval(1, null);
		}

		// pcre, https://www.php.net/manual/en/pcre.constants.php
		if ($resolvedConstantName === 'PCRE_VERSION') {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}

		if (in_array($resolvedConstantName, ['STDIN', 'STDOUT', 'STDERR'], true)) {
			return new ResourceType();
		}
		if ($resolvedConstantName === 'NAN') {
			return new ConstantFloatType(NAN);
		}
		if ($resolvedConstantName === 'INF') {
			return new ConstantFloatType(INF);
		}

		return null;
	}

	public function resolveConstantType(string $constantName, Type $constantType): Type
	{
		if ($constantType->isConstantValue()->yes() && in_array($constantName, $this->dynamicConstantNames, true)) {
			return $constantType->generalize(GeneralizePrecision::lessSpecific());
		}

		return $constantType;
	}

	public function resolveClassConstantType(string $className, string $constantName, Type $constantType, ?Type $nativeType): Type
	{
		$lookupConstantName = sprintf('%s::%s', $className, $constantName);
		if (in_array($lookupConstantName, $this->dynamicConstantNames, true)) {
			if ($nativeType !== null) {
				return $nativeType;
			}

			if ($constantType->isConstantValue()->yes()) {
				return $constantType->generalize(GeneralizePrecision::lessSpecific());
			}
		}

		return $constantType;
	}

	private function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProviderProvider->getReflectionProvider();
	}

}
