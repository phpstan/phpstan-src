<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Nette\DI\Container;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPStan\Analyser\Ignore\IgnoreLexer;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Dependency\ExportedNodeResolver;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\DependencyInjection\Nette\NetteContainer;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Parser\RichParser;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\FileTypeMapper;
use function array_fill_keys;

class EditorModeGetFileTest extends PHPStanTestCase
{

	public function testScopeGetFileReturnsRealPathInEditorMode(): void
	{
		$fileHelper = $this->getFileHelper();
		$realFile = $fileHelper->normalizePath(__DIR__ . '/data/editor-mode-real.php');
		$tmpFile = $fileHelper->normalizePath(__DIR__ . '/data/editor-mode-tmp.php');

		$errors = $this->analyse($tmpFile, [$realFile], $realFile);

		$this->assertCount(2, $errors);
		// getFile() and getFileDescription() both report the real (--instead-of) path,
		// not the temp file that is actually being read.
		$this->assertSame($realFile, $errors[0]->getMessage());
		$this->assertSame($realFile, $errors[1]->getMessage());
		$this->assertSame($realFile, $errors[0]->getFilePath());
	}

	public function testScopeGetFileWithoutEditorMode(): void
	{
		$fileHelper = $this->getFileHelper();
		$realFile = $fileHelper->normalizePath(__DIR__ . '/data/editor-mode-real.php');

		$errors = $this->analyse($realFile, [$realFile], null);

		$this->assertCount(2, $errors);
		$this->assertSame($realFile, $errors[0]->getMessage());
		$this->assertSame($realFile, $errors[0]->getFilePath());
	}

	/**
	 * @param string[] $analysedFiles
	 * @return list<Error>
	 */
	private function analyse(string $file, array $analysedFiles, ?string $reportedFile): array
	{
		$fileAnalyser = $this->createFileAnalyser();
		$rule = new /** @implements Rule<Node\Stmt\Function_> */ class implements Rule {

			public function getNodeType(): string
			{
				return Node\Stmt\Function_::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				return [
					RuleErrorBuilder::message($scope->getFile())
						->identifier('tests.editorModeGetFile')
						->build(),
					RuleErrorBuilder::message($scope->getFileDescription())
						->identifier('tests.editorModeGetFileDescription')
						->build(),
				];
			}

		};

		$result = $fileAnalyser->analyseFile(
			$file,
			array_fill_keys($analysedFiles, true),
			new DirectRuleRegistry([$rule]),
			new CollectorRegistry([]),
			null,
			$reportedFile,
		);

		return $result->getErrors();
	}

	private function createFileAnalyser(): FileAnalyser
	{
		$reflectionProvider = self::createReflectionProvider();
		$fileHelper = $this->getFileHelper();
		$container = self::getContainer();
		$typeSpecifier = $container->getService('typeSpecifier');
		$fileTypeMapper = $container->getByType(FileTypeMapper::class);
		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles([]);

		$lexer = new Lexer();

		return new FileAnalyser(
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$nodeScopeResolver,
			new RichParser(
				new Php7($lexer),
				new NameResolver(),
				$container,
				new IgnoreLexer(),
			),
			new DependencyResolver($fileHelper, $reflectionProvider, new ExportedNodeResolver($reflectionProvider, $fileTypeMapper, new ExprPrinter(new Printer())), $fileTypeMapper),
			new PackageDependencyResolver([], $fileHelper),
			new IgnoreErrorExtensionProvider(new NetteContainer(new Container([]))),
			$container->getByType(RuleErrorTransformer::class),
			new LocalIgnoresProcessor(),
			false,
		);
	}

}
