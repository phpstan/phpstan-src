<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use ApiGen\Index\FileIndex;
use ApiGen\Index\NamespaceIndex;
use ApiGen\Info\ClassLikeInfo;
use ApiGen\Info\FunctionInfo;
use ApiGen\Renderer\Filter;
use Nette\Utils\Strings;

class RendererFilter extends Filter
{

	public function filterTreePage(): bool
	{
		return parent::filterTreePage();
	}

	public function filterNamespacePage(NamespaceIndex $namespace): bool
	{
		foreach ($namespace->children as $child) {
			if ($this->filterNamespacePage($child)) {
				return true;
			}
		}

		foreach ($namespace->class as $class) {
			if ($this->filterClassLikePage($class)) {
				return true;
			}
		}

		foreach ($namespace->interface as $interface) {
			if ($this->filterClassLikePage($interface)) {
				return true;
			}
		}

		foreach ($namespace->trait as $trait) {
			if ($this->filterClassLikePage($trait)) {
				return true;
			}
		}

		foreach ($namespace->enum as $enum) {
			if ($this->filterClassLikePage($enum)) {
				return true;
			}
		}

		foreach ($namespace->exception as $exception) {
			if ($this->filterClassLikePage($exception)) {
				return true;
			}
		}

		foreach ($namespace->function as $function) {
			if ($this->filterFunctionPage($function)) {
				return true;
			}
		}

		return false;
	}

	public function filterClassLikePage(ClassLikeInfo $classLike): bool
	{
		return $this->isClassRendered($classLike);
	}

	private function isClassRendered(ClassLikeInfo $classLike): bool
	{
		$className = $classLike->name->full;
		if (Strings::startsWith($className, 'PhpParser\\')) {
			return true;
		}
		if (Strings::startsWith($className, 'PHPStan\\PhpDocParser\\')) {
			return true;
		}

		if (Strings::startsWith($className, 'PHPStan\\BetterReflection\\')) {
			return true;
		}

		if (!Strings::startsWith($className, 'PHPStan\\')) {
			return false;
		}

		if (isset($classLike->tags['api'])) {
			return true;
		}

		foreach ($classLike->methods as $method) {
			if (isset($method->tags['api'])) {
				return true;
			}
		}

		return false;
	}

	public function filterFunctionPage(FunctionInfo $function): bool
	{
		return parent::filterFunctionPage($function); // todo
	}

	public function filterSourcePage(FileIndex $file): bool
	{
		return parent::filterSourcePage($file);
	}

}
