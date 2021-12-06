<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function implode;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Class_>
 */
class MixinRule implements Rule
{

	private FileTypeMapper $fileTypeMapper;

	private ReflectionProvider $reflectionProvider;

	private ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private GenericObjectTypeCheck $genericObjectTypeCheck;

	private MissingTypehintCheck $missingTypehintCheck;

	private UnresolvableTypeHelper $unresolvableTypeHelper;

	private bool $checkClassCaseSensitivity;

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		ReflectionProvider $reflectionProvider,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		GenericObjectTypeCheck $genericObjectTypeCheck,
		MissingTypehintCheck $missingTypehintCheck,
		UnresolvableTypeHelper $unresolvableTypeHelper,
		bool $checkClassCaseSensitivity
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->reflectionProvider = $reflectionProvider;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->genericObjectTypeCheck = $genericObjectTypeCheck;
		$this->missingTypehintCheck = $missingTypehintCheck;
		$this->unresolvableTypeHelper = $unresolvableTypeHelper;
		$this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!isset($node->namespacedName)) {
			// anonymous class
			return [];
		}

		$className = (string) $node->namespacedName;
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return [];
		}
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$className,
			null,
			null,
			$docComment->getText()
		);
		$mixinTags = $resolvedPhpDoc->getMixinTags();
		$errors = [];
		foreach ($mixinTags as $mixinTag) {
			$type = $mixinTag->getType();
			if (!$type->canCallMethods()->yes() || !$type->canAccessProperties()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains non-object type %s.', $type->describe(VerbosityLevel::typeOnly())))->build();
				continue;
			}

			if (
				$this->unresolvableTypeHelper->containsUnresolvableType($type)
			) {
				$errors[] = RuleErrorBuilder::message('PHPDoc tag @mixin contains unresolvable type.')->build();
				continue;
			}

			$errors = array_merge($errors, $this->genericObjectTypeCheck->check(
				$type,
				'PHPDoc tag @mixin contains generic type %s but class %s is not generic.',
				'Generic type %s in PHPDoc tag @mixin does not specify all template types of class %s: %s',
				'Generic type %s in PHPDoc tag @mixin specifies %d template types, but class %s supports only %d: %s',
				'Type %s in generic type %s in PHPDoc tag @mixin is not subtype of template type %s of class %s.'
			));

			foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($type) as [$innerName, $genericTypeNames]) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @mixin contains generic %s but does not specify its types: %s',
					$innerName,
					implode(', ', $genericTypeNames)
				))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
			}

			foreach ($type->getReferencedClasses() as $class) {
				if (!$this->reflectionProvider->hasClass($class)) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains unknown class %s.', $class))->discoveringSymbolsTip()->build();
				} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
					$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @mixin contains invalid type %s.', $class))->build();
				} elseif ($this->checkClassCaseSensitivity) {
					$errors = array_merge(
						$errors,
						$this->classCaseSensitivityCheck->checkClassNames([
							new ClassNameNodePair($class, $node),
						])
					);
				}
			}
		}

		return $errors;
	}

}
