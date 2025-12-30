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
        var type, types, typeKey, typeData, pattern;

        let this->typeConfig = [];
        let this->restPattern = "";
        let this->contents = "";
        let this->len = 0;
        let this->index = 0;

        let types = ["class", "interface", "trait", "enum"];

        for type in types {
            let typeKey = "";
            let typeKey .= type[0];
            let pattern = "{.\\b(?<![\\$:>])" . type . "\\s++[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+}Ais";

            let typeData = [
                "name": type,
                "length": strlen(type),
                "pattern": pattern
            ];

            let this->typeConfig[typeKey] = typeData;
        }

        let this->restPattern = "{[^{}?\"'</d" . implode("", array_keys(this->typeConfig)) . "]+}A";
    }

    public function clean(string contents, int maxMatches) -> string
    {
        var inType, typeLevel, inDefine, clean, ch, match, type, charStr;

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
                let ch = this->contents[this->index];

                if ch == '?' && this->peek('>') {
                    let clean .= "?>";
                    let this->index += 2;
                    continue;
                }

                if ch == '"' || ch == '\'' {
                    if inDefine {
                        let charStr = "";
                        let charStr .= ch;
                        let clean .= charStr . this->consumeString(ch);
                        let inDefine = false;
                    } else {
                        this->skipString(ch);
                        let clean .= "null";
                    }
                    continue;
                }

                if ch == '{' {
                    if inType {
                        let typeLevel++;
                    }
                    let charStr = "";
                    let charStr .= ch;
                    let clean .= charStr;
                    let this->index++;
                    continue;
                }

                if ch == '}' {
                    if inType {
                        let typeLevel--;
                        if typeLevel === 0 {
                            let inType = false;
                        }
                    }
                    let charStr = "";
                    let charStr .= ch;
                    let clean .= charStr;
                    let this->index++;
                    continue;
                }

                if ch == '<' && this->peek('<') && this->match("{<<<[ \\t]*+(['\"]?)([a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*+)\\\\1(?:\\r\\n|\\n|\\r)}A", match) {
                    let this->index += strlen(match[0]);
                    this->skipHeredoc(match[2]);
                    let clean .= "null";
                    continue;
                }

                if ch == '/' {
                    if this->peek('/') {
                        this->skipToNewline();
                        continue;
                    }
                    if this->peek('*') {
                        this->skipComment();
                        continue;
                    }
                }

                if inType && ch == 'c' && this->matchWithOffset("~.\\b(?<![\\$:>])const(\\s++[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff\\-]*+)~Ais", match, this->index - 1) {
                    let clean .= "class_const" . match[1];
                    let this->index += strlen(match[0]) - 1;
                    continue;
                }

                if ch == 'd' && this->matchWithOffset("~.\\b(?<![\\$:>])define\\s*+\\(~Ais", match, this->index - 1) {
                    let inDefine = true;
                    let clean .= match[0];
                    let this->index += strlen(match[0]) - 1;
                    continue;
                }

                let charStr = "";
                let charStr .= ch;
                if isset this->typeConfig[charStr] {
                    let type = this->typeConfig[charStr];

                    if substr(this->contents, this->index, type["length"]) === type["name"] {
                        if maxMatches === 1 && this->matchWithOffset(type["pattern"], match, this->index - 1) {
                            return clean . match[0];
                        }
                        let inType = true;
                    }
                }

                let this->index += 1;
                if this->match(this->restPattern, match) {
                    let charStr = "";
                    let charStr .= ch;
                    let clean .= charStr . match[0];
                    let this->index += strlen(match[0]);
                } else {
                    let charStr = "";
                    let charStr .= ch;
                    let clean .= charStr;
                }
            }
        }

        return clean;
    }

    private function skipToPhp() -> void
    {
        while this->index < this->len {
            if this->contents[this->index] == '<' && this->peek('?') {
                let this->index += 2;
                break;
            }
            let this->index += 1;
        }
    }

    private function consumeString(char delimiter) -> string
    {
        var result, currentChar;

        let result = "";
        let this->index += 1;

        while this->index < this->len {
            let currentChar = this->contents[this->index];

            if currentChar == '\\' && (this->peek('\\') || this->peekChar(delimiter)) {
                let result .= currentChar;
                let result .= this->contents[this->index + 1];
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

    private function skipString(char delimiter) -> void
    {
        let this->index += 1;

        while this->index < this->len {
            if this->contents[this->index] == '\\' && (this->peek('\\') || this->peekChar(delimiter)) {
                let this->index += 2;
                continue;
            }
            if this->contents[this->index] == delimiter {
                let this->index += 1;
                break;
            }
            let this->index += 1;
        }
    }

    private function skipComment() -> void
    {
        let this->index += 2;

        while this->index < this->len {
            if this->contents[this->index] == '*' && this->peek('/') {
                let this->index += 2;
                break;
            }
            let this->index += 1;
        }
    }

    private function skipToNewline() -> void
    {
        var currentChar;

        while this->index < this->len {
            let currentChar = this->contents[this->index];
            if currentChar == '\r' || currentChar == '\n' {
                return;
            }
            let this->index += 1;
        }
    }

    private function skipHeredoc(string delimiter) -> void
    {
        var firstDelimiterChar, delimiterLength, delimiterPattern, currentChar;

        let firstDelimiterChar = delimiter[0];
        let delimiterLength = strlen(delimiter);
        let delimiterPattern = "{" . preg_quote(delimiter) . "(?![a-zA-Z0-9_\\x80-\\xff])}A";

        while this->index < this->len {
            let currentChar = this->contents[this->index];

            if currentChar == '\t' || currentChar == ' ' {
                let this->index += 1;
                continue;
            }

            if currentChar == firstDelimiterChar {
                if substr(this->contents, this->index, delimiterLength) === delimiter && this->match(delimiterPattern) {
                    let this->index += delimiterLength;
                    return;
                }
            }

            while this->index < this->len {
                this->skipToNewline();

                while this->index < this->len {
                    let currentChar = this->contents[this->index];
                    if currentChar == '\r' || currentChar == '\n' {
                        let this->index += 1;
                    } else {
                        break;
                    }
                }

                break;
            }
        }
    }

    private function peek(char charToCheck) -> bool
    {
        if this->index + 1 < this->len {
            return this->contents[this->index + 1] == charToCheck;
        }
        return false;
    }

    private function peekChar(char charToCheck) -> bool
    {
        if this->index + 1 < this->len {
            return this->contents[this->index + 1] == charToCheck;
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
