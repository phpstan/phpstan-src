namespace PHPStanTurbo;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @see https://github.com/composer/composer/pull/10107
 */
final class PhpFileCleaner
{
    private typeConfig;
    private restPattern;
    private contents;
    private len;
    private index;

    public function __construct()
    {
        var type, types, typeKey, typeData, pattern, keys, keysStr;

        let this->typeConfig = [];
        let this->restPattern = "";
        let this->contents = "";
        let this->len = 0;
        let this->index = 0;

        let types = ["class", "interface", "trait", "enum"];

        for type in types {
            let typeKey = substr(type, 0, 1);
            let pattern = "{.\\b(?<![\\$:>])" . type . "\\s++[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+}Ais";

            let typeData = [];
            let typeData["name"] = type;
            let typeData["length"] = strlen(type);
            let typeData["pattern"] = pattern;

            let this->typeConfig[typeKey] = typeData;
        }

        let keys = array_keys(this->typeConfig);
        let keysStr = implode("", keys);
        let this->restPattern = "{[^{}?\"'</d" . keysStr . "]+}A";
    }

    public function clean(string contents, int maxMatches) -> string
    {
        var inType, typeLevel, inDefine, clean, ch, match, type;
        var typeLength, typeName, typePattern, matchLen, cleanTemp;
        var peekResult, matchResult;

        let this->contents = contents;
        let this->len = strlen(contents);
        let this->index = 0;

        let inType = false;
        let typeLevel = 0;
        let inDefine = false;
        let clean = "";

        while this->index < this->len {
            this->skipToPhp();
            let clean .= "<?";

            while this->index < this->len {
                let ch = substr(this->contents, this->index, 1);

                // Check for ?>
                let peekResult = this->peek(">");
                if ch == '?' && peekResult {
                    let clean .= "?>";
                    let this->index += 2;
                    break;
                }

                // Check for quotes
                if ch == '\'' || ch == '"' {
                    if inDefine {
                        let cleanTemp = this->consumeString(ch);
                        let clean .= ch;
                        let clean .= cleanTemp;
                        let inDefine = false;
                    } else {
                        this->skipString(ch);
                        let clean .= "null";
                    }
                    continue;
                }

                // Check for {
                if ch == '{' {
                    if inType {
                        let typeLevel++;
                    }
                    let clean .= ch;
                    let this->index++;
                    continue;
                }

                // Check for }
                if ch == '}' {
                    if inType {
                        let typeLevel--;
                        if typeLevel === 0 {
                            let inType = false;
                        }
                    }
                    let clean .= ch;
                    let this->index++;
                    continue;
                }

                // Check for heredoc
                let peekResult = this->peek("<");
                if ch == '<' && peekResult {
                    let matchResult = this->match("{<<<[ \\t]*+(['\"]?)([a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*+)\\\\1(?:\\r\\n|\\n|\\r)}A", match);
                    if matchResult {
                        let matchLen = strlen(match[0]);
                        let this->index += matchLen;
                        this->skipHeredoc(match[2]);
                        let clean .= "null";
                        continue;
                    }
                }

                // Check for comments
                if ch == '/' {
                    let peekResult = this->peek("/");
                    if peekResult {
                        this->skipToNewline();
                        continue;
                    }
                    let peekResult = this->peek("*");
                    if peekResult {
                        this->skipComment();
                        continue;
                    }
                }

                // Check for const in type
                if inType && ch == 'c' {
                    let matchResult = this->matchWithOffset("~.\\b(?<![\\$:>])const(\\s++[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+)~Ais", match, this->index - 1);
                    if matchResult {
                        let clean .= "class_const";
                        let clean .= match[1];
                        let matchLen = strlen(match[0]);
                        let this->index += matchLen;
                        let this->index -= 1;
                        continue;
                    }
                }

                // Check for define
                if ch == 'd' {
                    let matchResult = this->matchWithOffset("~.\\b(?<![\\$:>])define\\s*+\\(~Ais", match, this->index - 1);
                    if matchResult {
                        let inDefine = true;
                        let clean .= match[0];
                        let matchLen = strlen(match[0]);
                        let this->index += matchLen;
                        let this->index -= 1;
                        continue;
                    }
                }

                // Check for type keywords
                if isset this->typeConfig[ch] {
                    let type = this->typeConfig[ch];
                    let typeName = type["name"];
                    let typeLength = type["length"];
                    let typePattern = type["pattern"];

                    let cleanTemp = substr(this->contents, this->index, typeLength);
                    if cleanTemp === typeName {
                        if maxMatches === 1 {
                            let matchResult = this->matchWithOffset(typePattern, match, this->index - 1);
                            if matchResult {
                                let clean .= match[0];
                                return clean;
                            }
                        }
                        let inType = true;
                    }
                }

                // Default: consume character and try rest pattern
                let this->index += 1;
                let matchResult = this->match(this->restPattern, match);
                if matchResult {
                    let clean .= ch;
                    let clean .= match[0];
                    let matchLen = strlen(match[0]);
                    let this->index += matchLen;
                } else {
                    let clean .= ch;
                }
            }
        }

        return clean;
    }

    private function skipToPhp() -> void
    {
        var ch, peekResult;

        while this->index < this->len {
            let ch = substr(this->contents, this->index, 1);
            let peekResult = this->peek("?");
            if ch == '<' && peekResult {
                let this->index += 2;
                break;
            }
            let this->index += 1;
        }
    }

    private function consumeString(string delimiter) -> string
    {
        var result, currentChar, peekBackslash, peekDelim;

        let result = "";
        let this->index += 1;

        while this->index < this->len {
            let currentChar = substr(this->contents, this->index, 1);

            let peekBackslash = this->peek("\\");
            let peekDelim = this->peek(delimiter);

            if currentChar == '\\' && (peekBackslash || peekDelim) {
                let result .= currentChar;
                let result .= substr(this->contents, this->index + 1, 1);
                let this->index += 2;
                continue;
            }

            if currentChar == delimiter {
                let result .= delimiter;
                let this->index += 1;
                break;
            }

            let result .= currentChar;
            let this->index += 1;
        }

        return result;
    }

    private function skipString(string delimiter) -> void
    {
        var currentChar, peekBackslash, peekDelim;

        let this->index += 1;

        while this->index < this->len {
            let currentChar = substr(this->contents, this->index, 1);

            let peekBackslash = this->peek("\\");
            let peekDelim = this->peek(delimiter);

            if currentChar == '\\' && (peekBackslash || peekDelim) {
                let this->index += 2;
                continue;
            }

            if currentChar == delimiter {
                let this->index += 1;
                break;
            }

            let this->index += 1;
        }
    }

    private function skipComment() -> void
    {
        var currentChar, peekResult;

        let this->index += 2;

        while this->index < this->len {
            let currentChar = substr(this->contents, this->index, 1);
            let peekResult = this->peek("/");
            if currentChar == '*' && peekResult {
                let this->index += 2;
                break;
            }
            let this->index += 1;
        }
    }

    private function skipToNewline() -> void
    {
        var currentChar, isNewline;

        while this->index < this->len {
            let currentChar = substr(this->contents, this->index, 1);
            let isNewline = (currentChar == '\r' || currentChar == '\n');
            if isNewline {
                return;
            }
            let this->index += 1;
        }
    }

    private function skipHeredoc(string delimiter) -> void
    {
        var firstDelimiterChar, delimiterLength, delimiterPattern, currentChar;
        var substrResult, isTab, isSpace, matchResult, isNewline;

        let firstDelimiterChar = substr(delimiter, 0, 1);
        let delimiterLength = strlen(delimiter);
        let delimiterPattern = "{" . preg_quote(delimiter) . "(?![a-zA-Z0-9_\\x80-\\xff])}A";

        while this->index < this->len {
            let currentChar = substr(this->contents, this->index, 1);

            let isTab = (currentChar == '\t');
            let isSpace = (currentChar == ' ');

            if isTab || isSpace {
                let this->index += 1;
                continue;
            }

            if currentChar == firstDelimiterChar {
                let substrResult = substr(this->contents, this->index, delimiterLength);
                let matchResult = this->match(delimiterPattern);

                if substrResult === delimiter && matchResult {
                    let this->index += delimiterLength;
                    return;
                }
            }

            // Skip the rest of the line
            while this->index < this->len {
                this->skipToNewline();

                // Skip newlines
                while this->index < this->len {
                    let currentChar = substr(this->contents, this->index, 1);
                    let isNewline = (currentChar == '\r' || currentChar == '\n');
                    if isNewline {
                        let this->index += 1;
                    } else {
                        break;
                    }
                }

                break;
            }
        }
    }

    private function peek(string charToCheck) -> bool
    {
        var nextChar;

        if this->index + 1 < this->len {
            let nextChar = substr(this->contents, this->index + 1, 1);
            return nextChar == charToCheck;
        }

        return false;
    }

    private function match(string regex, var match = null) -> bool
    {
        var result;

        let result = preg_match(regex, this->contents, match, 0, this->index);
        return result === 1;
    }

    private function matchWithOffset(string regex, var match = null, int offset = 0) -> bool
    {
        var result;

        let result = preg_match(regex, this->contents, match, 0, offset);
        return result === 1;
    }
}
