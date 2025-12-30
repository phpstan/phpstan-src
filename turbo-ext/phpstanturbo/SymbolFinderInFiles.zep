namespace PHPStanTurbo;

final class SymbolFinderInFiles
{
    private cleaner;

    public function __construct(<PhpFileCleaner> cleaner)
    {
        let this->cleaner = cleaner;
    }

    /**
     * @param string[] files
     * @return array<string, array{string[], string[], string[]}>
     */
    public function findSymbols(array files, bool supportsEnums) -> array
    {
        var result, file, symbols;

        let result = [];
        for file in files {
            let symbols = this->findSymbolsInFile(file, supportsEnums);
            let result[file] = symbols;
        }

        return result;
    }

    /**
     * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
     * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
     *
     * @return array{string[], string[], string[]}
     */
    private function findSymbolsInFile(string file, bool supportsEnums) -> array
    {
        var contents, extraTypes, matches, matchResults, classes, functions, constants;
        var ns, i, len, name, constantName, pattern, cleanedContents;
        var matchCnt, fname, cname, dname, nsname;
        var hasNs, hasFunction, hasConstant, hasDefine, isExtends, isImplements;
        array emptyResult;

        let contents = php_strip_whitespace(file);
        if contents === "" || contents === false {
            let emptyResult = [[], [], []];
            return emptyResult;
        }

        let extraTypes = supportsEnums ? "|enum" : "";
        let pattern = "{\\b(?:(?:class|interface|trait|const|function" . extraTypes . ")\\s)|(?:define\\s*\\()}i";
        let matchResults = preg_match_all(pattern, contents, matches);

        if !matchResults {
            let emptyResult = [[], [], []];
            return emptyResult;
        }

        let matchCnt = count(matches[0]);
        let cleanedContents = this->cleaner->clean(contents, matchCnt);

        let pattern = "{
            (?:
                \\b(?<![\\$:>])(?:
                    (?: (?P<type>class|interface|trait" . extraTypes . ") \\s++ (?P<name>[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+) )
                    | (?: (?P<function>function) \\s++ (?:&\\s*)? (?P<fname>[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+) \\s*+ [&\\(] )
                    | (?: (?P<constant>const) \\s++ (?P<cname>[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+) \\s*+ [^;] )
                    | (?: (?:\\\\)? (?P<define>define) \\s*+ \\( \\s*+ ['\"] (?P<dname>[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*+(?:[\\\\]{1,2}[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*+)*+) )
                    | (?: (?P<ns>namespace) (?P<nsname>\\s++[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*+(?:\\s*+\\\\\\s*+[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*+)*+)? \\s*+ [\\{;] )
                )
            )
        }ix";

        preg_match_all(pattern, cleanedContents, matches);

        let classes = [];
        let functions = [];
        let constants = [];
        let ns = "";

        let len = count(matches["type"]);
        let i = 0;

        while i < len {
            let hasNs = isset matches["ns"][i] && matches["ns"][i] !== "";

            if hasNs {
                let nsname = matches["nsname"][i];
                let ns = preg_replace("~\\s+~", "", strtolower(nsname));
                let ns .= "\\";
                let i++;
                continue;
            }

            let hasFunction = isset matches["function"][i] && matches["function"][i] !== "";

            if hasFunction {
                let fname = matches["fname"][i];
                let fname = ns . fname;
                let fname = ltrim(fname, "\\");
                let fname = strtolower(fname);
                let functions[] = fname;
                let i++;
                continue;
            }

            let hasConstant = isset matches["constant"][i] && matches["constant"][i] !== "";

            if hasConstant {
                let cname = matches["cname"][i];
                let constantName = ns . cname;
                let constantName = ltrim(constantName, "\\");
                let constantName = this->normalizeConstantName(constantName);
                let constants[] = constantName;
            }

            let hasDefine = isset matches["define"][i] && matches["define"][i] !== "";

            if hasDefine {
                let dname = matches["dname"][i];
                let constantName = this->normalizeConstantName(dname);
                let constants[] = constantName;
                let i++;
                continue;
            }

            if isset matches["name"][i] {
                let name = matches["name"][i];

                // skip anon classes extending/implementing
                let isExtends = (name === "extends");
                let isImplements = (name === "implements");

                if isExtends || isImplements {
                    let i++;
                    continue;
                }

                let name = ns . name;
                let name = ltrim(name, "\\");
                let name = strtolower(name);
                let classes[] = name;
            }

            let i++;
        }

        return [
            classes,
            functions,
            constants
        ];
    }

    private function normalizeConstantName(string name) -> string
    {
        var nameParts, lastPart, prefix, part;
        var cnt, i, hasBackslash;
        array filtered;

        let hasBackslash = str_contains(name, "\\");
        if !hasBackslash {
            return name;
        }

        let nameParts = explode("\\", name);
        let filtered = [];

        for part in nameParts {
            if part !== "" {
                let filtered[] = part;
            }
        }

        let cnt = count(filtered);
        if cnt === 0 {
            return name;
        }

        let lastPart = filtered[cnt - 1];

        if cnt > 1 {
            let prefix = "";
            let i = 0;
            while i < cnt - 1 {
                if i > 0 {
                    let prefix .= "\\";
                }
                let prefix .= strtolower(filtered[i]);
                let i++;
            }
            let prefix .= "\\";
            let prefix .= lastPart;
            return prefix;
        }

        return lastPart;
    }
}
