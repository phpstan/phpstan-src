<?php declare(strict_types = 1);

namespace ResultCacheCollectedDataRulesTracking;

use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function file;
use function in_array;
use function is_file;
use function sprintf;

/**
 * Reports a view without a file in the views directory, unless the view is listed in a file of
 * ignored views or is a constant of ExtraViews. None of them is analysed, so the rule declares
 * all three.
 *
 * @implements Rule<CollectedDataNode>
 */
final class ViewRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private string $viewsDirectory,
		private string $ignoredViewsFile,
	)
	{
	}

	public function getNodeType(): string
	{
		return CollectedDataNode::class;
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$scope->trackDirectoryDependency($this->viewsDirectory, '*.txt');
		$scope->trackFileDependency($this->ignoredViewsFile);
		$scope->trackClassDependency(ExtraViews::class);

		$ignoredViews = file($this->ignoredViewsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$extraViews = $this->reflectionProvider->getClass(ExtraViews::class)->getNativeReflection()->getConstants();
		$errors = [];
		foreach ($node->get(ViewCollector::class) as $file => $views) {
			foreach ($views as [$view, $line]) {
				if (is_file($this->viewsDirectory . '/' . $view . '.txt') || in_array($view, $ignoredViews, true) || in_array($view, $extraViews, true)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf('View %s not found.', $view))
					->file($file)
					->line($line)
					->identifier('view.notFound')
					->build();
			}
		}

		return $errors;
	}

}
