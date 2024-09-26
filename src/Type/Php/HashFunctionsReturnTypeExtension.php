<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function hash_algos;
use function in_array;
use function strtolower;

final class HashFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const SUPPORTED_FUNCTIONS = [
		'hash' => [
			'cryptographic' => false,
			'possiblyFalse' => false,
		],
		'hash_file' => [
			'cryptographic' => false,
			'possiblyFalse' => true,
		],
		'hash_hkdf' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
		],
		'hash_hmac' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
		],
		'hash_hmac_file' => [
			'cryptographic' => true,
			'possiblyFalse' => true,
		],
		'hash_pbkdf2' => [
			'cryptographic' => true,
			'possiblyFalse' => false,
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

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();

		if (!isset($functionCall->getArgs()[0])) {
			return $defaultReturnType;
		}

		$algorithmType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($algorithmType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$constantAlgorithmTypes = $algorithmType->getConstantStrings();

		if ($constantAlgorithmTypes === []) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}

		$neverType = new NeverType();
		$falseType = new ConstantBooleanType(false);
		$nonEmptyString = new IntersectionType([
			new StringType(),
			new AccessoryNonEmptyStringType(),
		]);

		$invalidAlgorithmType = $this->phpVersion->throwsValueErrorForInternalFunctions() ? $neverType : $falseType;
		$functionData = self::SUPPORTED_FUNCTIONS[strtolower($functionReflection->getName())];

		$returnTypes = array_map(
			function (ConstantStringType $type) use ($functionData, $nonEmptyString, $invalidAlgorithmType) {
				$algorithm = strtolower($type->getValue());
				if (!in_array($algorithm, $this->hashAlgorithms, true)) {
					return $invalidAlgorithmType;
				}
				if ($functionData['cryptographic'] && in_array($algorithm, self::NON_CRYPTOGRAPHIC_ALGORITHMS, true)) {
					return $invalidAlgorithmType;
				}
				return $nonEmptyString;
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
