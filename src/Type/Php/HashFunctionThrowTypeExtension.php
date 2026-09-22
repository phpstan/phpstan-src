<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use function count;
use function in_array;
use function strtolower;

/**
 * hash() throws ValueError only for an unknown algorithm. Algorithm names
 * are matched case-insensitively.
 */
#[AutowiredService]
final class HashFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const ALGORITHMS = [
		'md2', 'md4', 'md5', 'sha1', 'sha224', 'sha256', 'sha384', 'sha512/224', 'sha512/256', 'sha512',
		'sha3-224', 'sha3-256', 'sha3-384', 'sha3-512', 'ripemd128', 'ripemd160', 'ripemd256', 'ripemd320',
		'whirlpool', 'tiger128,3', 'tiger160,3', 'tiger192,3', 'tiger128,4', 'tiger160,4', 'tiger192,4',
		'snefru', 'snefru256', 'gost', 'gost-crypto', 'adler32', 'crc32', 'crc32b', 'crc32c',
		'fnv132', 'fnv1a32', 'fnv164', 'fnv1a64', 'joaat',
		'haval128,3', 'haval160,3', 'haval192,3', 'haval224,3', 'haval256,3',
		'haval128,4', 'haval160,4', 'haval192,4', 'haval224,4', 'haval256,4',
		'haval128,5', 'haval160,5', 'haval192,5', 'haval224,5', 'haval256,5',
	];

	private const MURMUR_AND_XXHASH_ALGORITHMS = ['murmur3a', 'murmur3c', 'murmur3f', 'xxh32', 'xxh64', 'xxh3', 'xxh128'];

	/** These throw Error for an invalid seed or secret in $options. */
	private const ALGORITHMS_VALIDATING_OPTIONS = ['xxh3', 'xxh128'];

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'hash';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) === 0) {
			return $functionReflection->getThrowType();
		}

		$algorithmType = $scope->getNativeType($args[0]->value);
		$algorithms = $algorithmType->getConstantStrings();
		if (!$algorithmType->isString()->yes() || count($algorithms) === 0) {
			return $functionReflection->getThrowType();
		}

		foreach ($algorithms as $algorithm) {
			$name = strtolower($algorithm->getValue());
			if (!$this->isKnownAlgorithm($name)) {
				return $functionReflection->getThrowType();
			}

			if (isset($args[3]) && in_array($name, self::ALGORITHMS_VALIDATING_OPTIONS, true)) {
				return $functionReflection->getThrowType();
			}
		}

		return new VoidType();
	}

	private function isKnownAlgorithm(string $name): bool
	{
		if (in_array($name, self::ALGORITHMS, true)) {
			return true;
		}

		return $this->phpVersion->supportsMurmurAndXxHashAlgorithms()
			&& in_array($name, self::MURMUR_AND_XXHASH_ALGORITHMS, true);
	}

}
