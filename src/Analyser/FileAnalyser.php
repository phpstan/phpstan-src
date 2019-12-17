<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\FileNode;
use PHPStan\Parser\Parser;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\Registry;
use PHPStan\Rules\TipRuleError;

class FileAnalyser
{

	/** @var \PHPStan\Analyser\ScopeFactory */
	private $scopeFactory;

	/** @var \PHPStan\Analyser\NodeScopeResolver */
	private $nodeScopeResolver;

	/** @var \PHPStan\Parser\Parser */
	private $parser;

	public function __construct(
		ScopeFactory $scopeFactory,
		NodeScopeResolver $nodeScopeResolver,
		Parser $parser
	)
	{
		$this->scopeFactory = $scopeFactory;
		$this->nodeScopeResolver = $nodeScopeResolver;
		$this->parser = $parser;
	}

	/**
	 * @param string $file
	 * @param Registry $registry
	 * @param \Closure(\PhpParser\Node $node, Scope $scope): void|null $outerNodeCallback
	 * @return \PHPStan\Analyser\Error[]
	 */
	public function analyseFile(
		string $file,
		Registry $registry,
		?\Closure $outerNodeCallback
	): array
	{
		$fileErrors = [];
		if (is_file($file)) {
			try {
				$parserNodes = $this->parser->parseFile($file);
				$nodeCallback = static function (\PhpParser\Node $node, Scope $scope) use (&$fileErrors, $file, $registry, $outerNodeCallback): void {
					if ($outerNodeCallback !== null) {
						$outerNodeCallback($node, $scope);
					}
					$uniquedAnalysedCodeExceptionMessages = [];
					foreach ($registry->getRules(get_class($node)) as $rule) {
						try {
							$ruleErrors = $rule->processNode($node, $scope);
						} catch (\PHPStan\AnalysedCodeException $e) {
							if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
								continue;
							}

							$uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = true;
							$fileErrors[] = new Error($e->getMessage(), $file, $node->getLine(), false);
							continue;
						}

						foreach ($ruleErrors as $ruleError) {
							$line = $node->getLine();
							$fileName = $scope->getFileDescription();
							$filePath = $scope->getFile();
							$traitFilePath = null;
							$tip = null;
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
							}
							$fileErrors[] = new Error(
								$message,
								$fileName,
								$line,
								true,
								$filePath,
								$traitFilePath,
								$tip
							);
						}
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
			}
		} elseif (is_dir($file)) {
			$fileErrors[] = new Error(sprintf('File %s is a directory.', $file), $file, null, false);
		} else {
			$fileErrors[] = new Error(sprintf('File %s does not exist.', $file), $file, null, false);
		}

		return $fileErrors;
	}

}
