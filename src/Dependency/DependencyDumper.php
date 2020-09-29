<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\File\FileFinder;
use PHPStan\Parser\Parser;

class DependencyDumper
{

	private DependencyResolver $dependencyResolver;

	private NodeScopeResolver $nodeScopeResolver;

	private Parser $parser;

	private ScopeFactory $scopeFactory;

	private FileFinder $fileFinder;

	public function __construct(
		DependencyResolver $dependencyResolver,
		NodeScopeResolver $nodeScopeResolver,
		Parser $parser,
		ScopeFactory $scopeFactory,
		FileFinder $fileFinder
	)
	{
		$this->dependencyResolver = $dependencyResolver;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->parser = $parser;
		$this->scopeFactory = $scopeFactory;
		$this->fileFinder = $fileFinder;
	}

	/**
	 * @param string[] $files
	 * @param callable(int $count): void $countCallback
	 * @param callable(): void $progressCallback
	 * @param string[]|null $analysedPaths
	 * @return string[][]
	 */
	public function dumpDependencies(
		array $files,
		callable $countCallback,
		callable $progressCallback,
		?array $analysedPaths
	): array
	{
		$analysedFiles = $files;
		if ($analysedPaths !== null) {
			$analysedFiles = $this->fileFinder->findFiles($analysedPaths)->getFiles();
		}
		$this->nodeScopeResolver->setAnalysedFiles($analysedFiles);
		$analysedFiles = array_fill_keys($analysedFiles, true);

		$dependencies = [];
		$countCallback(count($files));
		foreach ($files as $file) {
			try {
				$parserNodes = $this->parser->parseFile($file);
			} catch (\PHPStan\Parser\ParserErrorsException $e) {
				continue;
			}

			$fileDependencies = [];
			try {
				$this->nodeScopeResolver->processNodes(
					$parserNodes,
					$this->scopeFactory->create(ScopeContext::create($file)),
					function (\PhpParser\Node $node, Scope $scope) use ($analysedFiles, &$fileDependencies): void {
						$dependencies = $this->dependencyResolver->resolveDependencies($node, $scope);
						$fileDependencies = array_merge(
							$fileDependencies,
							$dependencies->getFileDependencies($scope->getFile(), $analysedFiles)
						);
					}
				);
			} catch (\PHPStan\AnalysedCodeException $e) {
				// pass
			}

			foreach (array_unique($fileDependencies) as $fileDependency) {
				$dependencies[$fileDependency][] = $file;
			}

			$progressCallback();
		}

		return $dependencies;
	}

}
