<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use function count;
use function in_array;
use function strtolower;

final class FileFamilyTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var array<string> */
	private array $fileFunctions = [
		'dir',
		'chdir',
		'chroot',
		'opendir',
		'scandir',
		'file_exists',
		'is_file',
		'is_dir',
		'is_link',
		'is_writable',
		'is_readable',
		'is_executable',
		'file',
		'file_get_contents',
		'file_put_contents',
		'fileatime',
		'filectime',
		'filegroup',
		'fileinode',
		'filemtime',
		'fileowner',
		'fileperms',
		'filesize',
		'filetype',
		'fopen',
		'linkinfo',
		'lstat',
		'mkdir',
		'readfile',
		'readlink',
		'realpath',
		'rmdir',
		'stat',
		'touch',
		'unlink',
	];

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array(strtolower($functionReflection->getName()), $this->fileFunctions, true)
			&& $context->truthy();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();

		if (count($args) >= 1) {
			$fileType = $scope->getType($args[0]->value);

			if ($fileType->isString()->yes()) {
				$nonEmptyString = [
					new StringType(),
					new AccessoryNonEmptyStringType(),
				];

				return $this->typeSpecifier->create(
					$args[0]->value,
					new IntersectionType($nonEmptyString),
					$context,
					false,
					$scope,
				);
			}
		}

		return new SpecifiedTypes();
	}

}
