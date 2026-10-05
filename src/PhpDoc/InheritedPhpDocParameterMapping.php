<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDoc\Tag\AssertTagParameter;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function substr;

final class InheritedPhpDocParameterMapping
{

	/**
	 * @param array<string, string> $parameterNameMapping
	 */
	public function __construct(
		private array $parameterNameMapping,
	)
	{
	}

	/**
	 * @template T
	 * @param array<string, T> $array
	 * @return array<string, T>
	 */
	public function transformArrayKeysWithParameterNameMapping(array $array): array
	{
		$newArray = [];
		foreach ($array as $key => $value) {
			if (!array_key_exists($key, $this->parameterNameMapping)) {
				continue;
			}
			$newArray[$this->parameterNameMapping[$key]] = $value;
		}

		return $newArray;
	}

	public function transformConditionalReturnTypeWithParameterNameMapping(Type $type): Type
	{
		$nameMapping = $this->parameterNameMapping;
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($nameMapping): Type {
			if ($type instanceof ConditionalTypeForParameter) {
				$parameterName = substr($type->getParameterName(), 1);
				if (array_key_exists($parameterName, $nameMapping)) {
					$type = $type->changeParameterName('$' . $nameMapping[$parameterName]);
				}
			}

			return $traverse($type);
		});
	}

	public function transformAssertTagParameterWithParameterNameMapping(AssertTagParameter $parameter): AssertTagParameter
	{
		$parameterName = substr($parameter->getParameterName(), 1);
		if (array_key_exists($parameterName, $this->parameterNameMapping)) {
			$parameter = $parameter->changeParameterName('$' . $this->parameterNameMapping[$parameterName]);
		}

		return $parameter;
	}

}
