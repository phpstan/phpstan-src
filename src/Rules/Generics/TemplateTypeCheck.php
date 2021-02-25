<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_map;

class TemplateTypeCheck
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	/** @var array<string, string> */
	private array $typeAliases;

	private bool $checkClassCaseSensitivity;

	/**
	 * @param ReflectionProvider $reflectionProvider
	 * @param ClassCaseSensitivityCheck $classCaseSensitivityCheck
	 * @param array<string, string> $typeAliases
	 * @param bool $checkClassCaseSensitivity
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		array $typeAliases,
		bool $checkClassCaseSensitivity
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->typeAliases = $typeAliases;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
	}

	/**
	 * @param \PhpParser\Node $node
	 * @param \PHPStan\Type\Generic\TemplateTypeScope $templateTypeScope
	 * @param array<string, \PHPStan\PhpDoc\Tag\TemplateTag> $templateTags
	 * @return \PHPStan\Rules\RuleError[]
	 */
	public function check(
		Node $node,
		TemplateTypeScope $templateTypeScope,
		array $templateTags,
		string $sameTemplateTypeNameAsClassMessage,
		string $sameTemplateTypeNameAsTypeMessage,
		string $invalidBoundTypeMessage,
		string $notSupportedBoundMessage
	): array
	{
		$messages = [];
		foreach ($templateTags as $templateTag) {
			$templateTagName = $templateTag->getName();
			if ($this->reflectionProvider->hasClass($templateTagName)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsClassMessage,
					$templateTagName
				))->build();
			}
			if (array_key_exists($templateTagName, $this->typeAliases)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					$sameTemplateTypeNameAsTypeMessage,
					$templateTagName
				))->build();
			}
			$boundType = $templateTag->getBound();
			foreach ($boundType->getReferencedClasses() as $referencedClass) {
				if (
					$this->reflectionProvider->hasClass($referencedClass)
					&& !$this->reflectionProvider->getClass($referencedClass)->isTrait()
				) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					$invalidBoundTypeMessage,
					$templateTagName,
					$referencedClass
				))->build();
			}

			if ($this->checkClassCaseSensitivity) {
				$classNameNodePairs = array_map(static function (string $referencedClass) use ($node): ClassNameNodePair {
					return new ClassNameNodePair($referencedClass, $node);
				}, $boundType->getReferencedClasses());
				$messages = array_merge($messages, $this->classCaseSensitivityCheck->checkClassNames($classNameNodePairs));
			}

			TypeTraverser::map($templateTag->getBound(), static function (Type $type, callable $traverse) use (&$messages, $notSupportedBoundMessage, $templateTagName): Type {
				$boundClass = get_class($type);
				if (
					$boundClass === MixedType::class
					|| $boundClass === StringType::class
					|| $boundClass === IntegerType::class
					|| $boundClass === ObjectWithoutClassType::class
					|| $boundClass === ObjectType::class
					|| $type instanceof UnionType
				) {
					return $traverse($type);
				}

				$messages[] = RuleErrorBuilder::message(sprintf($notSupportedBoundMessage, $templateTagName, $type->describe(VerbosityLevel::typeOnly())))->build();

				return $type;
			});
		}

		return $messages;
	}

}
