<?php

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;

class PropertyOfType implements CompoundType, LateResolvableType
{

    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;

    public function __construct(private Type $type)
    {
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getReferencedClasses(): array
    {
        return $this->type->getReferencedClasses();
    }

    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return $this->type->getReferencedTemplateTypes($positionVariance);
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self
            && $this->type->equals($type->type);
    }

    public function describe(VerbosityLevel $level): string
    {
        return sprintf('property-of<%s>', $this->type->describe($level));
    }

    public function isResolvable(): bool
    {
        return !TypeUtils::containsTemplateType($this->type);
    }

    protected function getResult(): Type
    {

        $classReflections = $this->type->getObjectClassReflections();
        $classReflection = $classReflections[0] ?? null;

        if ($classReflection !== null) {

            $propertiesReflection = $classReflection->getNativeReflection()->getProperties();

            // get the names of the properties
            // and build a union type from them
            $propertyNames = array_map(
                fn($property) => new ConstantStringType($property->getName()),
                $propertiesReflection
            );

            return new UnionType($propertyNames);

        }

        return new MixedType();
    }

    /**
     * @param callable(Type): Type $cb
     */
    public function traverse(callable $cb): Type
    {
        $type = $cb($this->type);

        if ($this->type === $type) {
            return $this;
        }

        return new self($type);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (!$right instanceof self) {
            return $this;
        }

        $type = $cb($this->type, $right->type);

        if ($this->type === $type) {
            return $this;
        }

        return new self($type);
    }

    public function toPhpDocNode(): TypeNode
    {
        return new GenericTypeNode(new IdentifierTypeNode('property-of'), [$this->type->toPhpDocNode()]);
    }

}