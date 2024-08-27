<?php declare(strict_types = 1);

namespace PHPStan\Reflection\SignatureMap;

use Nette\Utils\Strings;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\PassedByReference;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_slice;
use function str_starts_with;
use function substr;

final class SignatureMapParser
{

	private TypeStringResolver $typeStringResolver;

	public function __construct(
		TypeStringResolver $typeNodeResolver,
	)
	{
		$this->typeStringResolver = $typeNodeResolver;
	}

	/**
	 * @param mixed[] $map
	 */
	public function getFunctionSignature(array $map, ?string $className): FunctionSignature
	{
		$parameterSignatures = $this->getParameters(array_slice($map, 1));
		$hasVariadic = false;
		foreach ($parameterSignatures as $parameterSignature) {
			if ($parameterSignature->isVariadic()) {
				$hasVariadic = true;
				break;
			}
		}
		return new FunctionSignature(
			$parameterSignatures,
			$this->getTypeFromString($map[0], $className),
			new MixedType(),
			$hasVariadic,
		);
	}

	private function getTypeFromString(string $typeString, ?string $className): Type
	{
		if ($typeString === '') {
			return new MixedType(true);
		}

		return $this->typeStringResolver->resolve($typeString, new NameScope(null, [], $className));
	}

	/**
	 * @param array<string, string> $parameterMap
	 * @return array<int, ParameterSignature>
	 */
	private function getParameters(array $parameterMap): array
	{
		$parameterSignatures = [];
		foreach ($parameterMap as $parameterName => $typeString) {
			[$name, $isOptional, $passedByReference, $isVariadic] = $this->getParameterInfoFromName($parameterName);
			$parameterSignatures[] = new ParameterSignature(
				$name,
				$isOptional,
				$this->getTypeFromString($typeString, null),
				new MixedType(),
				$passedByReference,
				$isVariadic,
				null,
				null,
			);
		}

		return $parameterSignatures;
	}

	/**
	 * @return mixed[]
	 */
	private function getParameterInfoFromName(string $parameterNameString): array
	{
		$matches = Strings::match(
			$parameterNameString,
			'#^(?P<reference>&(?:\.\.\.)?r?w?_?)?(?P<variadic>\.\.\.)?(?P<name>[^=]+)?(?P<optional>=)?($)#',
		);
		if ($matches === null || !isset($matches['optional'])) {
			throw new ShouldNotHappenException();
		}

		$isVariadic = $matches['variadic'] !== '';

		$reference = $matches['reference'];
		if (str_starts_with($reference, '&...')) {
			$reference = '&' . substr($reference, 4);
			$isVariadic = true;
		}
		if (str_starts_with($reference, '&rw')) {
			$passedByReference = PassedByReference::createReadsArgument();
		} elseif (str_starts_with($reference, '&')) {
			$passedByReference = PassedByReference::createCreatesNewVariable();
		} else {
			$passedByReference = PassedByReference::createNo();
		}

		$isOptional = $isVariadic || $matches['optional'] !== '';

		$name = $matches['name'] !== '' ? $matches['name'] : '...';

		return [$name, $isOptional, $passedByReference, $isVariadic];
	}

}
