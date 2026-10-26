<?php declare(strict_types = 1);

// Generates src/Type/Regex/Parser/RegexParser.php from RegexGrammar.pp3.
// Run with `make regex-parser`. The phplrt compiler needs a recent PHP,
// but the generated parser only depends on phplrt runtime packages.

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Generator\ClassModifier;
use Phplrt\Compiler\Generator\TargetPhpVersion;
use Phplrt\Source\FileSource;

require __DIR__ . '/vendor/autoload.php';

$output = __DIR__ . '/../../src/Type/Regex/Parser/RegexParser.php';

$code = (string) (new Compiler())
	->load(FileSource::createFromPathname(__DIR__ . '/RegexGrammar.pp3'))
	->generate()
	->withNamespaceName('PHPStan\Type\Regex\Parser')
	->withClassName('RegexParser')
	->withClassImport('Phplrt\Contracts\Lexer\TokenInterface')
	->withClassModifier(ClassModifier::Final)
	->withTargetPhpVersion(TargetPhpVersion::Php81)
	->withoutContractsPreloading();

// PHPStan's source is downgraded to PHP 7.4 for the PHAR,
// and the downgrader does not handle first-class callable syntax.
$code = preg_replace(
	'~\bself::(reduce\w+)\(\.\.\.\)~',
	'\Closure::fromCallable([self::class, \'$1\'])',
	$code,
	-1,
	$count,
);
if ($code === null || $count === 0) {
	fwrite(STDERR, "Could not replace first-class callables in the generated parser.\n");
	exit(1);
}

// ParserInterface::parse() declares @throws \Throwable, narrow it down
// to what this parser and its reducers actually throw.
$code = str_replace(
	"    public function parse(\\Phplrt\\Contracts\\Source\\ReadableInterface \$source): mixed\n",
	"    /**\n     * @throws \\Phplrt\\Contracts\\Parser\\Exception\\ParserExceptionInterface\n     * @throws RegexSyntaxException\n     */\n    public function parse(\\Phplrt\\Contracts\\Source\\ReadableInterface \$source): mixed\n",
	$code,
	$count,
);
if ($count !== 1) {
	fwrite(STDERR, "Could not add @throws to the generated parse() method.\n");
	exit(1);
}

file_put_contents($output, $code);
echo sprintf("Generated %s\n", $output);
