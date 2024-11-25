<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function count;
use function hash_algos;
use function in_array;
use function is_bool;
use function strtolower;

final class HashFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const SUPPORTED_FUNCTIONS = [
		'hash' => [
			'cryptographic' => false,
			'possiblyFalse' => false,
			'binary' => 2,
		],
		'hash_file' => [
			'cryptographic' => false,
			'possiblyFalse' => true,
			'binary' => 2,
		],
		'hash_hkdf' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
			'binary' => true,
		],
		'hash_hmac' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
			'binary' => 3,
		],
		'hash_hmac_file' => [
			'cryptographic' => true,
			'possiblyFalse' => true,
			'binary' => 3,
		],
		'hash_pbkdf2' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
			'binary' => 5,
		],
	];

	private const NON_CRYPTOGRAPHIC_ALGORITHMS = [
		'adler32',
		'crc32',
		'crc32b',
		'crc32c',
		'fnv132',
		'fnv1a32',
		'fnv164',
		'fnv1a64',
		'joaat',
		'murmur3a',
		'murmur3c',
		'murmur3f',
		'xxh32',
		'xxh64',
		'xxh3',
		'xxh128',
	];

	/** @var array<int, non-empty-string> */
	private array $hashAlgorithms;

	public function __construct(private PhpVersion $phpVersion)
	{
		$this->hashAlgorithms = hash_algos();
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		$name = strtolower($functionReflection->getName());
		return isset(self::SUPPORTED_FUNCTIONS[$name]);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$functionData = self::SUPPORTED_FUNCTIONS[strtolower($functionReflection->getName())];
		if (is_bool($functionData['binary'])) {
			$binaryType = new ConstantBooleanType($functionData['binary']);
		} elseif (isset($functionCall->getArgs()[$functionData['binary']])) {
			$binaryType = $scope->getType($functionCall->getArgs()[$functionData['binary']]->value);
		} else {
			$binaryType = new ConstantBooleanType(false);
		}

		$stringTypes = [
			new StringType(),
			new AccessoryNonFalsyStringType(),
		];
		if ($binaryType->isFalse()->yes()) {
			$stringTypes[] = new AccessoryLowercaseStringType();
		}
		$stringReturnType = new IntersectionType($stringTypes);

		$algorithmType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantAlgorithmTypes = $algorithmType->getConstantStrings();
		if (count($constantAlgorithmTypes) === 0) {
			if ($functionData['possiblyFalse'] || !$this->phpVersion->throwsValueErrorForInternalFunctions()) {
				return TypeUtils::toBenevolentUnion(TypeCombinator::union($stringReturnType, new ConstantBooleanType(false)));
			}

			return $stringReturnType;
		}

		$neverType = new NeverType();
		$falseType = new ConstantBooleanType(false);
		$invalidAlgorithmType = $this->phpVersion->throwsValueErrorForInternalFunctions() ? $neverType : $falseType;

		$returnTypes = array_map(
			function (ConstantStringType $type) use ($functionData, $stringReturnType, $invalidAlgorithmType) {
				$algorithm = strtolower($type->getValue());
				if (!in_array($algorithm, $this->hashAlgorithms, true)) {
					return $invalidAlgorithmType;
				}
				if ($functionData['cryptographic'] && in_array($algorithm, self::NON_CRYPTOGRAPHIC_ALGORITHMS, true)) {
					return $invalidAlgorithmType;
				}
				return $stringReturnType;
			},
			$constantAlgorithmTypes,
		);

		$returnType = TypeCombinator::union(...$returnTypes);

		if ($functionData['possiblyFalse'] && !$neverType->isSuperTypeOf($returnType)->yes()) {
			$returnType = TypeCombinator::union($returnType, $falseType);
		}

		return $returnType;
	}

}
