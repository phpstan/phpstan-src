<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Dependency\DependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\Node\FileNode;
use PHPStan\Parser\Parser;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\Registry;
use PHPStan\Rules\TipRuleError;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function array_unique;

class FileAnalyser
{

	/** @var \PHPStan\Analyser\ScopeFactory */
	private $scopeFactory;

	/** @var \PHPStan\Analyser\NodeScopeResolver */
	private $nodeScopeResolver;

	/** @var \PHPStan\Parser\Parser */
	private $parser;

	/** @var DependencyResolver */
	private $dependencyResolver;

	/** @var FileHelper */
	private $fileHelper;

	public function __construct(
		ScopeFactory $scopeFactory,
		NodeScopeResolver $nodeScopeResolver,
		Parser $parser,
		DependencyResolver $dependencyResolver,
		FileHelper $fileHelper
	)
	{
		$this->scopeFactory = $scopeFactory;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->parser = $parser;
		$this->dependencyResolver = $dependencyResolver;
		$this->fileHelper = $fileHelper;
	}

	/**
	 * @param string $file
	 * @param array<string, true> $analysedFiles
	 * @param Registry $registry
	 * @param callable(\PhpParser\Node $node, Scope $scope): void|null $outerNodeCallback
	 * @return FileAnalyserResult
	 */
	public function analyseFile(
		string $file,
		array $analysedFiles,
		Registry $registry,
		?callable $outerNodeCallback
	): FileAnalyserResult
	{
		$fileErrors = [];
		$fileDependencies = [];
		if (is_file($file)) {
			try {
				$parserNodes = $this->parser->parseFile($file);
				$nodeCallback = function (\PhpParser\Node $node, Scope $scope) use (&$fileErrors, &$fileDependencies, $file, $registry, $outerNodeCallback, $analysedFiles): void {
					if ($outerNodeCallback !== null) {
						$outerNodeCallback($node, $scope);
					}
					$uniquedAnalysedCodeExceptionMessages = [];
					$nodeType = get_class($node);
					foreach ($registry->getRules($nodeType) as $rule) {
						try {
							$ruleErrors = $rule->processNode($node, $scope);
						} catch (\PHPStan\AnalysedCodeException $e) {
							if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
								continue;
							}

							$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
							$fileErrors[] = new Error($e->getMessage(), $file, $node->getLine(), false);
							continue;
						} catch (IdentifierNotFound $e) {
							$fileErrors[] = new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getLine(), false);
							continue;
						}

						foreach ($ruleErrors as $ruleError) {
							$nodeLine = $node->getLine();
							$line = $nodeLine;
							$fileName = $scope->getFileDescription();
							$filePath = $scope->getFile();
							$traitFilePath = null;
							$tip = null;
							$identifier = null;
							if ($scope->isInTrait()) {
								$traitReflection = $scope->getTraitReflection();
								if ($traitReflection->getFileName() !== false) {
									$traitFilePath = $traitReflection->getFileName();
								}
							}
							if (is_string($ruleError)) {
								$message = $ruleError;
							} else {
								$message = $ruleError->getMessage();
								if (
									$ruleError instanceof LineRuleError
									&& $ruleError->getLine() !== -1
								) {
									$line = $ruleError->getLine();
								}
								if (
									$ruleError instanceof FileRuleError
									&& $ruleError->getFile() !== ''
								) {
									$fileName = $ruleError->getFile();
									$filePath = $ruleError->getFile();
									$traitFilePath = null;
								}

								if ($ruleError instanceof TipRuleError) {
									$tip = $ruleError->getTip();
								}

								if ($ruleError instanceof IdentifierRuleError) {
									$identifier = $ruleError->getIdentifier();
								}
							}
							$fileErrors[] = new Error(
								$message,
								$fileName,
								$line,
								true,
								$filePath,
								$traitFilePath,
								$tip,
								false,
								$nodeLine,
								$nodeType,
								$identifier
							);
						}
					}

					try {
						foreach ($this->resolveDependencies($node, $scope, $analysedFiles) as $dependentFile) {
							$fileDependencies[] = $dependentFile;
						}
					} catch (\PHPStan\AnalysedCodeException $e) {
						// pass
					} catch (IdentifierNotFound $e) {
						// pass
					}
				};

				$scope = $this->scopeFactory->create(ScopeContext::create($file));
				$nodeCallback(new FileNode($parserNodes), $scope);
				$this->nodeScopeResolver->processNodes(
					$parserNodes,
					$scope,
					$nodeCallback
				);
			} catch (\PhpParser\Error $e) {
				$fileErrors[] = new Error($e->getMessage(), $file, $e->getStartLine() !== -1 ? $e->getStartLine() : null, false);
			} catch (\PHPStan\Parser\ParserErrorsException $e) {
				foreach ($e->getErrors() as $error) {
					$fileErrors[] = new Error($error->getMessage(), $file, $error->getStartLine() !== -1 ? $error->getStartLine() : null, false);
				}
			} catch (\PHPStan\AnalysedCodeException $e) {
				$fileErrors[] = new Error($e->getMessage(), $file, null, false);
			} catch (IdentifierNotFound $e) {
				$fileErrors[] = new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, null, false);
			}
		} elseif (is_dir($file)) {
			$fileErrors[] = new Error(sprintf('File %s is a directory.', $file), $file, null, false);
		} else {
			$fileErrors[] = new Error(sprintf('File %s does not exist.', $file), $file, null, false);
		}

		return new FileAnalyserResult($fileErrors, array_values(array_unique($fileDependencies)));
	}

	/**
	 * @param \PhpParser\Node $node
	 * @param Scope $scope
	 * @param array<string, true> $analysedFiles
	 * @return string[]
	 */
	private function resolveDependencies(
		\PhpParser\Node $node,
		Scope $scope,
		array $analysedFiles
	): array
	{
		$dependencies = [];

		foreach ($this->dependencyResolver->resolveDependencies($node, $scope) as $dependencyReflection) {
			$dependencyFile = $dependencyReflection->getFileName();
			if ($dependencyFile === false) {
				continue;
			}
			$dependencyFile = $this->fileHelper->normalizePath($dependencyFile);

			if ($scope->getFile() === $dependencyFile) {
				continue;
			}

			if (!isset($analysedFiles[$dependencyFile])) {
				continue;
			}

			$dependencies[$dependencyFile] = $dependencyFile;
		}

		return array_values($dependencies);
	}

}
