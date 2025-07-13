<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use function sprintf;
use function ucfirst;

/**
 * @api
 */
final class ClassNameUsageLocation
{

	public const TRAIT_USE = 'traitUse';
	public const STATIC_PROPERTY_ACCESS = 'staticProperty';
	public const PHPDOC_TAG_ASSERT = 'assert';
	public const ATTRIBUTE = 'attribute';
	public const EXCEPTION_CATCH = 'catch';
	public const CLASS_CONSTANT_ACCESS = 'classConstant';
	public const CLASS_IMPLEMENTS = 'classImplements';
	public const ENUM_IMPLEMENTS = 'enumImplements';
	public const INTERFACE_EXTENDS = 'interfaceExtends';
	public const CLASS_EXTENDS = 'classExtends';
	public const INSTANCEOF = 'instanceof';
	public const PROPERTY_TYPE = 'property';
	public const PARAMETER_TYPE = 'parameter';
	public const RETURN_TYPE = 'return';
	public const PHPDOC_TAG_SELF_OUT = 'selfOut';
	public const PHPDOC_TAG_VAR = 'varTag';
	public const INSTANTIATION = 'new';
	public const TYPE_ALIAS = 'typeAlias';
	public const PHPDOC_TAG_METHOD = 'methodTag';
	public const PHPDOC_TAG_MIXIN = 'mixin';
	public const PHPDOC_TAG_PROPERTY = 'propertyTag';
	public const PHPDOC_TAG_REQUIRE_EXTENDS = 'requireExtends';
	public const PHPDOC_TAG_REQUIRE_IMPLEMENTS = 'requireImplements';
	public const PHPDOC_TAG_SEALED = 'sealed';
	public const STATIC_METHOD_CALL = 'staticMethod';
	public const PHPDOC_TAG_TEMPLATE_BOUND = 'templateBound';
	public const PHPDOC_TAG_TEMPLATE_DEFAULT = 'templateDefault';

	/**
	 * @param self::* $value
	 * @param mixed[] $data
	 */
	private function __construct(public readonly string $value, public readonly array $data)
	{
	}

	/**
	 * @param self::* $value
	 * @param mixed[] $data
	 */
	public static function from(string $value, array $data = []): self
	{
		return new self($value, $data);
	}

	public function getMethod(): ?ExtendedMethodReflection
	{
		return $this->data['method'] ?? null;
	}

	public function getProperty(): ?ExtendedPropertyReflection
	{
		return $this->data['property'] ?? null;
	}

	public function getFunction(): ?FunctionReflection
	{
		return $this->data['function'] ?? null;
	}

	public function getPhpDocTagName(): ?string
	{
		return $this->data['phpDocTagName'] ?? null;
	}

	public function getAssertedExprString(): ?string
	{
		return $this->data['assertedExprString'] ?? null;
	}

	public function getClassConstant(): ?ClassConstantReflection
	{
		return $this->data['classConstant'] ?? null;
	}

	public function getCurrentClassName(): ?string
	{
		return $this->data['currentClassName'] ?? null;
	}

	public function getParameterName(): ?string
	{
		return $this->data['parameterName'] ?? null;
	}

	public function getTypeAliasName(): ?string
	{
		return $this->data['typeAliasName'] ?? null;
	}

	public function getMethodTagName(): ?string
	{
		return $this->data['methodTagName'] ?? null;
	}

	public function getPropertyTagName(): ?string
	{
		return $this->data['propertyTagName'] ?? null;
	}

	public function getTemplateTagName(): ?string
	{
		return $this->data['templateTagName'] ?? null;
	}

	public function isInAnomyousFunction(): bool
	{
		return $this->data['isInAnonymousFunction'] ?? false;
	}

	public function createMessage(string $part): string
	{
		switch ($this->value) {
			case self::TRAIT_USE:
				if ($this->getCurrentClassName() !== null) {
					return sprintf('Usage of %s in class %s.', $part, $this->getCurrentClassName());
				}
				return sprintf('Usage of %s.', $part);
			case self::STATIC_PROPERTY_ACCESS:
				$property = $this->getProperty();
				if ($property !== null) {
					return sprintf('Access to static property $%s on %s.', $property->getName(), $part);
				}

				return sprintf('Access to static property on %s.', $part);
			case self::PHPDOC_TAG_ASSERT:
				$phpDocTagName = $this->getPhpDocTagName();
				$assertExprString = $this->getAssertedExprString();
				if ($phpDocTagName !== null && $assertExprString !== null) {
					return sprintf('PHPDoc tag %s for %s references %s.', $phpDocTagName, $assertExprString, $part);
				}

				return sprintf('Assert tag references %s.', $part);
			case self::ATTRIBUTE:
				return sprintf('Attribute references %s.', $part);
			case self::EXCEPTION_CATCH:
				return sprintf('Catching %s.', $part);
			case self::CLASS_CONSTANT_ACCESS:
				if ($this->getClassConstant() !== null) {
					return sprintf('Access to constant %s on %s.', $this->getClassConstant()->getName(), $part);
				}
				return sprintf('Access to constant on %s.', $part);
			case self::CLASS_IMPLEMENTS:
				if ($this->getCurrentClassName() !== null) {
					return sprintf('Class %s implements %s.', $this->getCurrentClassName(), $part);
				}

				return sprintf('Anonymous class implements %s.', $part);
			case self::ENUM_IMPLEMENTS:
				if ($this->getCurrentClassName() !== null) {
					return sprintf('Enum %s implements %s.', $this->getCurrentClassName(), $part);
				}

				return sprintf('Enum implements %s.', $part);
			case self::INTERFACE_EXTENDS:
				if ($this->getCurrentClassName() !== null) {
					return sprintf('Interface %s extends %s.', $this->getCurrentClassName(), $part);
				}

				return sprintf('Interface extends %s.', $part);
			case self::CLASS_EXTENDS:
				if ($this->getCurrentClassName() !== null) {
					return sprintf('Class %s extends %s.', $this->getCurrentClassName(), $part);
				}

				return sprintf('Anonymous class extends %s.', $part);
			case self::INSTANCEOF:
				return sprintf('Instanceof references %s.', $part);
			case self::PROPERTY_TYPE:
				$property = $this->getProperty();
				if ($property !== null) {
					return sprintf('Property $%s references %s in its type.', $property->getName(), $part);
				}
				return sprintf('Property references %s in its type.', $part);
			case self::PARAMETER_TYPE:
				$parameterName = $this->getParameterName();
				if ($parameterName !== null) {
					if ($this->isInAnomyousFunction()) {
						return sprintf('Parameter $%s of anonymous function has typehint with %s.', $parameterName, $part);
					}
					if ($this->getMethod() !== null) {
						if ($this->getCurrentClassName() !== null) {
							return sprintf('Parameter $%s of method %s::%s() has typehint with %s.', $parameterName, $this->getCurrentClassName(), $this->getMethod()->getName(), $part);
						}

						return sprintf('Parameter $%s of method %s() in anonymous class has typehint with %s.', $parameterName, $this->getMethod()->getName(), $part);
					}

					if ($this->getFunction() !== null) {
						return sprintf('Parameter $%s of function %s() has typehint with %s.', $parameterName, $this->getFunction()->getName(), $part);
					}

					return sprintf('Parameter $%s has typehint with %s.', $parameterName, $part);
				}

				return sprintf('Parameter has typehint with %s.', $part);
			case self::RETURN_TYPE:
				if ($this->isInAnomyousFunction()) {
					return sprintf('Return type of anonymous function has typehint with %s.', $part);
				}
				if ($this->getMethod() !== null) {
					if ($this->getCurrentClassName() !== null) {
						return sprintf('Return type of method %s::%s() has typehint with %s.', $this->getCurrentClassName(), $this->getMethod()->getName(), $part);
					}

					return sprintf('Return type of method %s() in anonymous class has typehint with %s.', $this->getMethod()->getName(), $part);
				}

				if ($this->getFunction() !== null) {
					return sprintf('Return type of function %s() has typehint with %s.', $this->getFunction()->getName(), $part);
				}

				return sprintf('Return type has typehint with %s.', $part);
			case self::PHPDOC_TAG_SELF_OUT:
				return sprintf('PHPDoc tag @phpstan-self-out references %s.', $part);
			case self::PHPDOC_TAG_VAR:
				return sprintf('PHPDoc tag @var references %s.', $part);
			case self::INSTANTIATION:
				return sprintf('Instantiation of %s.', $part);
			case self::TYPE_ALIAS:
				if ($this->getTypeAliasName() !== null) {
					return sprintf('Type alias %s references %s.', $this->getTypeAliasName(), $part);
				}

				return sprintf('Type alias references %s.', $part);
			case self::PHPDOC_TAG_METHOD:
				if ($this->getMethodTagName() !== null) {
					return sprintf('PHPDoc tag @method for %s() references %s.', $this->getMethodTagName(), $part);
				}
				return sprintf('PHPDoc tag @method references %s.', $part);
			case self::PHPDOC_TAG_MIXIN:
				return sprintf('PHPDoc tag @mixin references %s.', $part);
			case self::PHPDOC_TAG_PROPERTY:
				if ($this->getPropertyTagName() !== null) {
					return sprintf('PHPDoc tag @property for $%s references %s.', $this->getPropertyTagName(), $part);
				}
				return sprintf('PHPDoc tag @property references %s.', $part);
			case self::PHPDOC_TAG_REQUIRE_EXTENDS:
				return sprintf('PHPDoc tag @phpstan-require-extends references %s.', $part);
			case self::PHPDOC_TAG_REQUIRE_IMPLEMENTS:
				return sprintf('PHPDoc tag @phpstan-require-implements references %s.', $part);
			case self::PHPDOC_TAG_SEALED:
				return sprintf('PHPDoc tag @phpstan-sealed references %s.', $part);
			case self::STATIC_METHOD_CALL:
				$method = $this->getMethod();
				if ($method !== null) {
					return sprintf('Call to static method %s() on %s.', $method->getName(), $part);
				}

				return sprintf('Call to static method on %s.', $part);
			case self::PHPDOC_TAG_TEMPLATE_BOUND:
				if ($this->getTemplateTagName() !== null) {
					return sprintf('PHPDoc tag @template %s bound references %s.', $this->getTemplateTagName(), $part);
				}

				return sprintf('PHPDoc tag @template bound references %s.', $part);
			case self::PHPDOC_TAG_TEMPLATE_DEFAULT:
				if ($this->getTemplateTagName() !== null) {
					return sprintf('PHPDoc tag @template %s default references %s.', $this->getTemplateTagName(), $part);
				}

				return sprintf('PHPDoc tag @template default references %s.', $part);
		}
	}

	public function createIdentifier(string $secondPart): string
	{
		if ($this->value === self::CLASS_IMPLEMENTS) {
			return sprintf('class.implements%s', ucfirst($secondPart));
		}
		if ($this->value === self::ENUM_IMPLEMENTS) {
			return sprintf('enum.implements%s', ucfirst($secondPart));
		}
		if ($this->value === self::INTERFACE_EXTENDS) {
			return sprintf('interface.extends%s', ucfirst($secondPart));
		}
		if ($this->value === self::CLASS_EXTENDS) {
			return sprintf('class.extends%s', ucfirst($secondPart));
		}
		if ($this->value === self::PHPDOC_TAG_TEMPLATE_BOUND) {
			return sprintf('generics.%sBound', $secondPart);
		}
		if ($this->value === self::PHPDOC_TAG_TEMPLATE_DEFAULT) {
			return sprintf('generics.%sDefault', $secondPart);
		}

		return sprintf('%s.%s', $this->value, $secondPart);
	}

}
