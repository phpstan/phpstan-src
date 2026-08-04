<?php declare(strict_types = 1);

/**
 * Cross-build probe for the native parser's T_* token-id portability
 * (https://github.com/phpstan/phpstan/issues/15037): the userland T_* values
 * are assigned by the bison that generated the interpreter's
 * zend_language_parser.c, and the official php.net tarballs ship whatever
 * numbering the release manager's bison produced — the 8.3.21 tarball
 * carries the Bison 3.8.2 numbering (T_COMMENT=387), 8.3.22 the Bison 3.0.4
 * one (T_COMMENT=392). A T_* id baked into the extension at compile time is
 * therefore meaningless on a build with the other numbering, silently: the
 * parse stays valid, only id comparisons stop matching.
 *
 * Parses a snippet covering every comment shape with both
 * PHPStanTurbo\ParserRunner (native) and $parser->parse() (PHP) and requires
 * byte-identical serialized ASTs, comments included. Run it on an
 * interpreter whose token numbering differs from the build host's to prove
 * the binary is portable across official builds of the same PHP version.
 *
 * Run with the extension loaded and vendor/ installed:
 *   php -d extension=phpstan_turbo.so turbo-ext/tests/token-id-probe.php
 *
 * The enabler is NOT run; PHPStanTurbo\ParserRunner is called directly.
 */

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

$root = dirname(__DIR__, 2);
chdir($root);

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "the phpstan_turbo extension is not loaded\n");
	exit(1);
}

require $root . '/vendor/autoload.php';

printf(
	"php %s, phpstan_turbo %s\nruntime token ids: T_COMMENT=%d T_DOC_COMMENT=%d T_WHITESPACE=%d T_ATTRIBUTE=%d T_INC=%d\n",
	PHP_VERSION,
	phpversion('phpstan_turbo'),
	T_COMMENT,
	T_DOC_COMMENT,
	T_WHITESPACE,
	T_ATTRIBUTE,
	T_INC,
);

// every comment shape, plus the tokens that alias T_COMMENT/T_DOC_COMMENT
// under the other known numbering (T_ATTRIBUTE and T_INC), so both losing
// comments and mis-attaching non-comments as comments would show up
$src = <<<'SRC'
<?php declare(strict_types=1);

/** @param array<int, string> $items */
function repro(array $items): void
{
}

// line comment
/* block comment */
#[MyAttr]
function attributed(): void
{
	$i = 0;
	$i++;
}

/**
 * @method static string magicStatic(int $v)
 * @property int $magicProperty
 */
class Magic
{
	/** @var array<string, string> */
	private static $map = [];
}
SRC;

$parser = (new ParserFactory())->createForVersion(
	PhpVersion::fromString(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION),
);

$phpAst = $parser->parse($src, new Collecting());
$nativeAst = PHPStanTurbo\ParserRunner::parse($parser, $src, new Collecting());

/**
 * @param Node|list<Node>|mixed $node
 * @param list<string> $rows
 */
function collectComments($node, array &$rows): void
{
	if ($node instanceof Node) {
		foreach ($node->getComments() as $comment) {
			$rows[] = sprintf(
				'%s @ line %d: [%s] %s',
				$node->getType(),
				$node->getStartLine(),
				get_class($comment),
				str_replace("\n", '\n', $comment->getText()),
			);
		}
		foreach ($node->getSubNodeNames() as $name) {
			collectComments($node->$name, $rows);
		}
	} elseif (is_array($node)) {
		foreach ($node as $sub) {
			collectComments($sub, $rows);
		}
	}
}

$phpComments = [];
collectComments($phpAst ?? [], $phpComments);
$nativeComments = [];
collectComments($nativeAst ?? [], $nativeComments);

$exit = 0;
if ($phpComments !== $nativeComments) {
	fwrite(STDERR, "FAIL: comment attachment differs between the native and the PHP parser\n");
	fwrite(STDERR, sprintf("--- php-parser (%d comments):\n%s\n", count($phpComments), implode("\n", $phpComments)));
	fwrite(STDERR, sprintf("--- PHPStanTurbo\\ParserRunner (%d comments):\n%s\n", count($nativeComments), implode("\n", $nativeComments)));
	fwrite(STDERR, "the extension was compiled against a PHP build with a different T_* token numbering than this one\n");
	$exit = 1;
}

$phpSer = $phpAst === null ? 'NULL' : serialize($phpAst);
$nativeSer = $nativeAst === null ? 'NULL' : serialize($nativeAst);
if ($phpSer !== $nativeSer) {
	fwrite(STDERR, "FAIL: serialized ASTs differ between the native and the PHP parser\n");
	$exit = 1;
}

if ($exit === 0) {
	echo "OK: native parse is byte-identical, comments attached on all shapes\n";
}

exit($exit);
