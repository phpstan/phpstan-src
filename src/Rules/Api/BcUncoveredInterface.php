<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Node\VirtualNode;
use PHPStan\PhpDoc\Tag\TypedTag;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\NamespaceAnswerer;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

final class BcUncoveredInterface
{

	public const CLASSES = [
		Type::class,
		CompoundType::class,
		TemplateType::class,
		TypedTag::class,
		TypeWithClassName::class,
		VirtualNode::class,
		ReflectionProvider::class,
		Scope::class,
		FunctionReflection::class,
		ExtendedMethodReflection::class,
		ExtendedPropertyReflection::class,
		ParametersAcceptorWithPhpDocs::class,
		ParameterReflectionWithPhpDocs::class,
		CallableParametersAcceptor::class,
		FileRuleError::class,
		IdentifierRuleError::class,
		LineRuleError::class,
		MetadataRuleError::class,
		NonIgnorableRuleError::class,
		RuleError::class,
		TipRuleError::class,
		Output::class,
		ClassMemberReflection::class,
		ConstantReflection::class,
		ClassConstantReflection::class,
		ClassMemberAccessAnswerer::class,
		NamespaceAnswerer::class,
		Container::class,
		OutputStyle::class,
		ReturnStatementsNode::class,
	];

}
