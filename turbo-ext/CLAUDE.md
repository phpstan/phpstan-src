# PHPStan Turbo Extension - Comprehensive Developer Guide

## Overview

The **PHPStan Turbo Extension** is an experimental project aimed at improving PHPStan's performance by rewriting performance-critical parts of PHPStan as a native PHP extension using the [Zephir language](https://zephir-lang.com/).

**Status:** Highly experimental work-in-progress (Proof of Concept)

## Purpose & Goals

### Primary Goal
Accelerate PHPStan's analysis by moving computationally expensive operations from userland PHP into compiled C code via a PHP extension.

### Current Focus
The initial implementation targets the `CombinationsHelper::combinations()` method, which is used extensively in type inference for:
- Constant array type operations (`src/Type/Constant/ConstantArrayType.php:1829`)
- sprintf() function return type analysis (`src/Type/Php/SprintfFunctionDynamicReturnTypeExtension.php`)
- implode() function return type analysis (`src/Type/Php/ImplodeFunctionReturnTypeExtension.php`)

### Why This Matters
The `combinations()` method generates Cartesian products of arrays, which can be computationally expensive during type inference. Moving this to native code can provide significant performance improvements.

## Architecture

### Integration Strategy

PHPStan uses a **transparent fallback mechanism** that automatically switches to the native implementation when the extension is available:

1. **Default Implementation**: Pure PHP implementation in `src/Internal/CombinationsHelper.php`
2. **Turbo Implementation**: Native code in `turbo-ext/phpstanturbo/CombinationsHelper.zep`
3. **Auto-Detection**: `src/Turbo/TurboExtensionEnabler.php` checks if the extension is loaded
4. **Class Aliasing**: When loaded, the Zephir class `PHPStanTurbo\CombinationsHelper` replaces `PHPStan\Internal\CombinationsHelper`

### Initialization Flow

```
bin/phpstan (line 21)
  ↓
TurboExtensionEnabler::enableIfLoaded()
  ↓
extension_loaded('phpstanturbo') → true?
  ↓ YES
class_alias('PHPStanTurbo\CombinationsHelper', 'PHPStan\Internal\CombinationsHelper')
  ↓
All subsequent calls to CombinationsHelper::combinations() use native code
```

This also applies to the test suite via `tests/bootstrap.php:8`.

## Directory Structure

```
turbo-ext/
├── README.md                           # User-facing documentation
├── CLAUDE.md                           # This file - comprehensive developer guide
├── .gitignore                          # Excludes /vendor, /ext, /.zephir
├── composer.json                       # Requires phalcon/zephir ^0.19.0
├── composer.lock                       # Locked dependencies
├── config.json                         # Zephir compiler configuration
└── phpstanturbo/                       # Zephir source code directory
    └── CombinationsHelper.zep          # Native implementation of combinations()

Generated during build (git-ignored):
├── vendor/                             # Composer dependencies (Zephir)
├── ext/                                # Generated C code and compiled extension
│   └── modules/phpstanturbo.so         # The final compiled extension
└── .zephir/                            # Zephir build cache
```

---

# Comprehensive Zephir Language Guide

## What is Zephir?

Zephir (Z(end) E(ngine)/PH(P)/I(nte)r(mediate), pronounced "zaefire") is an open-source, domain-specific language designed to simplify PHP extension development. It addresses the needs of PHP developers who want to write and compile code executable by PHP while emphasizing type and memory safety.

### Core Philosophy

**Zephir was not created to replace PHP or C. Instead, it is a complement to them.** It targets the specific use case where base libraries rarely change but must be highly functional and fast—making compilation's overhead worthwhile.

### Key Motivations

1. **Performance Enhancement**: PHP applications need to balance "stability, performance, and functionality," but base frameworks and libraries face performance limitations due to high levels of abstraction. Dynamic languages like PHP struggle with optimization because compilers have fewer type clues available compared to static languages.

2. **Developer Accessibility**: Rather than forcing PHP developers to learn C, Zephir provides a middle ground. It combines static typing benefits with dynamic language familiarity.

3. **Strategic Positioning**: It enables developers to "implement object-oriented libraries/frameworks/applications that can be used from PHP, gaining important seconds" through compilation.

4. **Code Protection**: Compilation produces native binaries that obscure original source code from users.

5. **Selective Optimization**: Developers choose which application portions warrant compilation overhead.

### Key Features

| Feature | Capability |
|---------|-----------|
| **Type System** | Supports both dynamic and static typing |
| **Memory Safety** | Prohibits pointers or direct memory management |
| **Compilation** | Ahead-of-time compilation to C code |
| **Memory Model** | Task-local garbage collection with automatic management |

### How It Works

Zephir code is organized in namespaced classes. Variables must be explicitly declared with types—either `var` for dynamic variables or specific types like `int` for static variables. The compiler transparently converts Zephir code to C, which is then compiled by gcc, clang, or vc++.

The compilation process optimizes static variables and removes redundant operations, while developers need not understand C internals to write effective Zephir code.

---

## Installation & Prerequisites

### System Requirements

**Core Requirements:**
- Zephir parser >= 1.3.0
- C compiler (gcc >= 4.4, clang >= 3.0, Visual C++ >= 11, or Intel C++)
- re2c 0.13.6 or later
- PHP development headers and tools

**Linux-Specific Requirements:**
- GNU make 3.81 or later
- autoconf 2.31 or later
- automake 1.14 or later
- libpcre3
- build-essential package (for Ubuntu/Debian systems using gcc)

### Ubuntu/Debian Installation

```bash
sudo apt-get update
sudo apt-get install git gcc make re2c php php-json php-dev libpcre3-dev build-essential
```

### Verification Steps

1. Confirm PHP version:
   ```bash
   php -v
   ```

2. Verify PHP development libraries:
   ```bash
   phpize -v
   ```

### Installing Zephir

**Three installation methods are available:**

1. **Composer (Recommended)**
   ```bash
   # Project-level
   composer require phalcon/zephir
   composer exec zephir help

   # Global
   composer global require phalcon/zephir
   zephir help
   ```

2. **PHAR Release**
   - Download from GitHub releases and place in your PATH

3. **Git Clone**
   - Clone the repository with dependencies, then create a symlink or reference the binary directly

### Post-Installation

Verify successful setup:
```bash
zephir help
```

---

## Zephir Language Fundamentals

### File Organization

Zephir enforces strict file structure: **every file must contain a class (and just one class).** The directory layout must align with namespace and class names, similar to PSR-4 standards but mandatory by the language itself.

The directory structure must match the names of the classes and namespaces used. The compiler raises an exception if the file location or class declaration doesn't match expected patterns.

### Code Organization

Classes require explicit namespaces. Example:

```zephir
namespace PHPStanTurbo;

class CombinationsHelper
{
    // class content
}
```

### Instructions & Syntax

- Statements can be separated using semicolons (like Java, C/C++, PHP), though they're optional
- Multiple expressions can appear on a single line
- No dollar signs required for variables
- The `let` keyword is used for variable assignment (variables are immutable by default)
- Optional parentheses in control structures

### Comments

Two styles are supported:
- Single-line comments: `//`
- Multi-line comments: `/* */`

**Important:** Multi-line comments are also used as docblocks, and they're exported to the generated code, making them integral to the language rather than merely decorative.

### Variable Declarations

All variables must be explicitly declared within their scope using the `var` keyword or by specifying a type. Variables can optionally have an initial compatible default value. Names are case-sensitive and cannot be reserved words.

```zephir
var a, b, c;
let a = "hello";

int counter = 0;
string name = "PHPStan";
```

### Variable Scope

Variables are locally scoped to the method where they were declared, preventing cross-method access.

### Super Globals

Direct global variables aren't supported, but you can access PHP's super-globals like `_POST` and `_SERVER` using standard bracket notation syntax:

```zephir
let value = _POST["username"];
```

---

## Zephir Type System

### Dynamic Types

Variables declared with the `var` keyword can be reassigned to different types freely:

```zephir
var a, b, c;
let a = "hello", b = false;
let a = 10;  // reassignment to different type
```

Dynamic variables support **eight types**:

1. **Array**: An ordered map that associates values to keys
2. **Boolean**: Can be either `true` or `false`
3. **Float/Double**: Platform-dependent floating-point numbers
4. **Integer**: Platform-dependent signed integers
5. **Null**: Represents a variable with no value
6. **Object**: PHP-style object abstraction
7. **Resource**: Holds a reference to an external resource
8. **String**: A series of characters, where a character is the same as a byte

#### Dynamic Type Details

**Arrays:**
- Use square bracket syntax: `[1, 2, 3]`
- Key-value pairs use double colons: `["key": value]`
- Only long and string values function as keys

**Strings:**
- **Must use double quotes** (unlike PHP where single quotes also work)
- Support escape sequences (`\t`, `\n`, `\r`, `\\`, `\"`)
- Variable interpolation isn't supported; concatenation is required instead
- **PHP to Zephir**: Convert all single-quoted PHP strings (`'text'`) to double-quoted (`"text"`)

### Static Types

Static typing prevents type changes after declaration, enabling compiler optimization. Supported static types include:

- Basic types: `boolean`, `char`, `integer`, `long`, `string`
- Unsigned variants: `unsigned char`, `unsigned integer`, `unsigned long`
- `float`/`double`, `array`

#### Static Type Conversion Behaviors

**Boolean:**
- Values auto-cast
- Non-zero numbers become `true`
- Zero/null/false become `false`
- String assignment throws compiler exceptions

**Char:**
- Stores single characters using **single-quote syntax** (`'Z'`)
- **Important**: Accessing a string with array/index access returns a `char` type
- Extracted from strings via indexing: `let ch = str[0];` (ch is char type)
- Must use single quotes for char literals: `'A'`, `'z'`, `'\n'`
- Can be converted to string by concatenation or assignment to string variable

**Integer/Unsigned Integer:**
- Numeric values auto-cast (floats truncate)
- Null/false become 0
- Strings cause compiler errors
- Unsigned variants reject negative assignments

**Long/Unsigned Long:**
- Twice the size of standard integers
- Similar auto-casting rules to integer types

**String:**
- **Must use double-quote syntax** (`"hello"`)
- **Important**: Accessing a string index with `str[i]` returns a `char` (not a string)
- Characters auto-convert to strings when assigned to string variables
- Null becomes empty string
- Cannot use single quotes for strings (single quotes are for char type only)

### String vs Char: Key Distinctions

Understanding the difference between `string` and `char` types is critical in Zephir:

**Syntax Difference:**
- **String**: Uses double quotes `"hello"`
- **Char**: Uses single quotes `'h'`

**Important for PHP to Zephir Conversion:**
When converting PHP code to Zephir, **all single-quoted strings must be converted to double-quoted strings** and properly escaped following the same rules as double-quoted strings in PHP:

```php
// PHP - both single and double quotes work for strings
$str1 = 'hello world';
$str2 = "hello world";
$str3 = 'it\'s a test';
```

```zephir
// Zephir - MUST use double quotes for strings
string str1 = "hello world";
string str2 = "hello world";
string str3 = "it's a test";  // No need to escape single quote in double-quoted string
```

Remember: In Zephir, single quotes create a `char` type (single character), not a `string`!

**Array Access Returns Char:**
When you access a string by index, Zephir returns a `char`, not a `string`:

```zephir
string text = "hello";
char firstChar;
let firstChar = text[0];  // firstChar = 'h' (char type, not string)
```

**Practical Example:**
```zephir
string str = "hello";
char ch;

// Iterating over a string yields char types
for ch in str {
    // ch is of type char ('h', 'e', 'l', 'l', 'o')
    // Use single-quote syntax for comparison
    if ch == 'e' {
        echo "Found 'e'";
    }
}

// Array access also returns char
let ch = str[0];  // ch = 'h' (char type)
```

**Type Conversion:**
```zephir
char ch = 'A';
string str;

// Char to String: Auto-converts when assigned to string
let str = ch;  // str = "A"

// String to Char: Use array access
let str = "Hello";
let ch = str[0];  // ch = 'H'
```

**Common Pitfall:**
```zephir
string text = "test";

// WRONG: This won't work as expected
// text[0] returns char 't', not string "t"
if text[0] == "t" {  // Comparing char to string - type mismatch!
    // This may not work as expected
}

// CORRECT: Use single quotes for char comparison
if text[0] == 't' {  // Comparing char to char - correct!
    // This works correctly
}
```

---

## Operators

### Arithmetic Operators

- Negation: `-a`
- Addition: `a + b`
- Subtraction: `a - b`
- Multiplication: `a * b`
- Division: `a / b`
- Modulus: `a % b`

### Bitwise Operators

- And: `a & b`
- Or: `a | b`
- Xor: `a ^ b`
- Not: `~a`
- Left shift: `a << b`
- Right shift: `a >> b`

### Comparison Operators

Type-aware comparisons:
- Equality: `==`
- Identity: `===`
- Inequality: `!=`, `<>`
- Non-identity: `!==`
- Relational: `<`, `>`, `<=`, `>=`

Behavior depends on variable types; dynamic variables follow PHP conventions.

### Logical Operators

- And: `&&`
- Or: `||`
- Not: `!`

### Special Operators

**Empty**: Checks if an expression is `null`, empty string, or empty array.

```zephir
if empty str {
    // str is empty
}
```

**Fetch**: Reduces a common operation in PHP into a single instruction by checking array key existence and assigning the value simultaneously.

```zephir
if fetch value, array["key"] {
    // value is now assigned if key exists
}
```

**Isset**: Verifies property or index definition; behaves like PHP's `array_key_exists()`, returning true even for null values.

**Typeof**: Determines variable type through comparison or retrieval.

```zephir
if typeof str == "string" {
    // str is a string
}
```

**Type Hints**:
- Weak hints (`<ClassName>`) guide compiler checks
- Strict hints (`<ClassName!>`) enforce runtime validation

**Branch Prediction Hints**: `unlikely` keyword optimizes processor branch prediction for rarely-executed code paths.

```zephir
if unlikely errorCondition {
    // rarely executed error handling
}
```

### Operator Precedence

**Precedence** determines parsing order: operators with higher precedence become the operands of operators with lower precedence.

**Associativity** governs grouping when operators have equal precedence. The minus operator is left-associative, so `1 - 2 - 3` evaluates as `(1 - 2) - 3` = -4. Assignment is right-associative: `let a = b = c` groups as `let a = (b = c)`.

**Non-associative operators** cannot be adjacent. The `new` operator is the only non-associative operator in Zephir.

| Precedence | Operators | Type | Associativity |
|---|---|---|---|
| 1 | `->` | Member Access | right-to-left |
| 2 | `~` | Bitwise NOT | right-to-left |
| 3 | `!` | Logical NOT | right-to-left |
| 4 | `new` | new | non-associative |
| 5 | `clone` | clone | right-to-left |
| 6 | `typeof` | Type-of | right-to-left |
| 7 | `..`, `...` | Range | left-to-right |
| 8 | `isset`, `fetch`, `empty` | Special | right-to-left |
| 9 | `*`, `/`, `%` | Arithmetic | left-to-right |
| 10 | `+`, `-`, `.` | Arithmetic/Concat | left-to-right |
| 11 | `<<`, `>>` | Bitwise shift | left-to-right |
| 12-13 | Comparison operators | Comparison | left-to-right |
| 14-16 | Bitwise operators | Bitwise | left-to-right |
| 17-19 | Logical operators | Logical | left-to-right |
| 20-21 | `likely`, `unlikely`, `?`, `=>` | Branch/Logical | right-to-left |

---

## Control Structures

### Conditionals

**If Statement:**
Evaluates expressions with required braces. Supports `else` and `elseif` clauses. Parentheses in the evaluated expression are optional.

```zephir
if count == 0 {
    return [[]];
}

if a == 10 {
    echo "ten";
} elseif a == 20 {
    echo "twenty";
} else {
    echo "other";
}
```

**Switch Statement:**
Evaluates an expression against a series of predefined literal values, executing the corresponding `case` block or falling back to the `default` block case.

```zephir
switch value {
    case 1:
        echo "one";
        break;
    case 2:
        echo "two";
        break;
    default:
        echo "other";
}
```

### Loops

**While Statement:**
Iterates as long as its given condition evaluates as `true`.

```zephir
while index < 10 {
    let index++;
}
```

**Loop Statement:**
Creates infinite loops that require explicit `break` statements to exit.

```zephir
loop {
    if condition {
        break;
    }
}
```

**For Statement:**
Traverses arrays, strings, and ranges. Supports:
- Basic iteration: `for item in array`
- Key-value pairs: `for key, value in items`
- Reverse traversal: `for value in reverse items`
- Anonymous variables using `_` placeholder to suppress unused variable warnings

```zephir
for ch in str {
    // iterate over each character
}

for elem in head {
    // iterate over array elements
}

for key, value in items {
    // iterate with keys and values
}

for value in reverse items {
    // iterate in reverse order
}
```

**Break Statement:**
Ends execution of the current `while`, `for` or `loop` statement.

**Continue Statement:**
Skips the rest of the current loop iteration and continues execution at the condition evaluation.

### Other Structures

**Require:**
Dynamically includes and evaluates a specified PHP file but cannot include other Zephir files at runtime.

```zephir
require "path/to/file.php";
```

**Let Statement:**
Used to mutate variables, properties and arrays since variables are by default immutable. Supports increment/decrement operations and multiple simultaneous assignments.

```zephir
let a = 10;
let a++, b--;
let c = a + b;
```

---

## Arrays

### Declaration

Variables can be declared as arrays using `var` (mutable type) or `array` (fixed type). When using the `array` keyword, the variable's type cannot be changed across execution.

```zephir
var dynamicArray;
array fixedArray;
```

### Creation Methods

Arrays are constructed using square brackets with various structures:

```zephir
// Empty arrays
let arr = [];

// Indexed arrays
let arr = [1, 2, 3, 4];

// Associative arrays
let arr = ["foo": "bar", "baz": "qux"];

// Mixed key types
let arr = [0: "first", "key": "second", 1: "third"];

// Nested/multi-dimensional structures
let arr = [[1, 2], [3, 4]];
```

### Updating Elements

Modification occurs through bracket notation with specific keys:

```zephir
// String-keyed access
let elements["foo"] = "bar";

// Numeric-keyed access
let elements[0] = "bar";

// Nested updates
let elements[0]["foo"] = "bar";
```

### Appending Elements

New items are added to the end using empty brackets:

```zephir
let elements[] = "bar";
```

### Retrieval

Elements are accessed identically to updates, using either string or numeric keys with bracket notation to fetch stored values.

**Key Concept**: Zephir arrays function as hash tables, enabling flexible key-value storage similar to PHP's array implementation, supporting both indexed and associative structures within the same collection.

---

## Functions

### Function Calls

**Built-in PHP Functions:**
Zephir enables direct invocation of PHP functions:

```zephir
if strlen(text) != 0 {
    let encoded = base64_encode(text);
}
```

**Custom Functions:**
The language supports calling user-defined PHP functions. Verify existence using `function_exists()` before invocation to prevent errors:

```zephir
if function_exists("my_custom_encoder") {
    let encoded = my_custom_encoder(text);
}
```

### Parameter Handling

**Dynamic Variables:**
Functions accept dynamic-typed parameters directly. When passing statically-typed arguments, Zephir automatically generates temporary dynamic variables for the function call.

**Return Value Assignment:**
Dynamic return values require explicit casting when assigned to static variables:

```zephir
let encoded = (string) base64_encode(text);
```

### Advanced Function Calls

**Dynamic Function Invocation:**
Zephir supports calling functions stored in variables using the syntax `{callback}(text)`, where the callback parameter holds a function reference.

```zephir
public function process(var callback, string text) -> string
{
    return {callback}(text);
}
```

---

## Object-Oriented Programming

### Class Structure

Zephir enforces OOP principles by requiring that **every Zephir file must implement a class or an interface (and just one).**

```zephir
namespace PHPStanTurbo;

class MyClass
{
    // class content
}
```

### Class Modifiers

Two modifiers control class behavior:
- **final**: Prevents extension by subclasses
- **abstract**: Prevents direct instantiation

```zephir
final class CannotExtend
{
}

abstract class MustExtend
{
}
```

### Interface Implementation

Classes can implement multiple interfaces. When using interfaces from external extensions (like PSR), developers must create stub interfaces that extend the external ones.

**Important**: It is the developer's responsibility to ensure that all external references are present before the extension is loaded.

```zephir
namespace PHPStanTurbo;

class MyClass implements \Countable, \ArrayAccess
{
}
```

### Methods

Methods require explicit visibility declarations (public, protected, or private).

**Parameters:**
Methods support required and optional parameters with default values and type hints. Parameters can be marked as `const` for read-only access.

```zephir
public function process(string text, int limit = 100) -> string
{
    return text;
}

public function readOnly(const array data) -> int
{
    // data cannot be modified
    return count(data);
}
```

**Modifiers:**
- `static`: Class-level methods
- `final`: Cannot be overridden
- `deprecated`: Marks methods as deprecated

```zephir
public static function combinations(array arrays) -> array
{
    // static method
}

final public function cannotOverride() -> void
{
}

deprecated public function oldMethod() -> void
{
}
```

**Return Types:**
Methods support return type hints, including multiple types separated by `|`, and `void` declarations.

```zephir
public function getValue() -> string|int
{
    return "value";
}

public function process() -> void
{
    // no return value
}
```

**Visibility:**
- Public methods export to the PHP extension
- Protected methods are accessible to inheriting classes
- Private methods remain internal to the class

### Properties

Properties require visibility modifiers and can have compile-time default values. They're accessed using the `->` operator.

```zephir
class MyClass
{
    private string name = "default";
    protected int counter = 0;
    public array items = [];

    public function getName() -> string
    {
        return this->name;
    }
}
```

**Dynamic Property Access:**
Use bracket notation for dynamic property names:

```zephir
let value = this->{"propertyName"};
```

### Constants

Classes support immutable constants declared with `const`, accessed via the static operator `::`.

```zephir
class MyClass
{
    const VERSION = "1.0.0";
    const MAX_SIZE = 1024;
}

// Access
let version = MyClass::VERSION;
```

### Advanced Features

**Getter/Setter Shortcuts:**
Properties can use `get`, `set`, and `toString` shortcuts to auto-generate methods.

**Named Parameters:**
Methods support keyword arguments for flexible parameter passing.

---

## Closures (Anonymous Functions)

Zephir supports closures, which are PHP-compatible anonymous functions that can be seamlessly utilized in Zephir and returned to PHP userland code.

### Key Characteristics

**Execution and Parameters:**
Closures can be executed directly within Zephir and passed as parameters to other functions or methods. They accept input parameters and return computed values.

### Three Syntax Forms

**1. Standard Syntax:**
Full closure declaration with explicit parameter and return statement:

```zephir
let closure = function(int number) {
    return number * 2;
};
```

**2. Direct Execution:**
Closures applied to array methods, transforming each element:

```zephir
let results = array->map(function(item) {
    return item * item;
});
```

**3. Arrow Syntax:**
A concise shorthand using the `=>` operator:

```zephir
let squared = array->map(number => number * number);
```

The arrow function syntax provides a more compact way to express closures, enhancing code readability.

---

## Exception Handling

Zephir implements exceptions at a low level with functionality similar to PHP, enabling developers to throw and catch exceptions within try-catch blocks.

### Throwing Exceptions

**Basic Exception Throwing:**
```zephir
throw new \Exception("This is an exception");
```

**Literal Throwing:**
Zephir allows throwing literals or typed variables directly as exception messages:

```zephir
throw "Test";
throw 123;
throw 123.123;
```

### Catching Exceptions

**Standard Catch Blocks:**
```zephir
try {
    // code that might throw
} catch \Exception, e {
    echo e->getMessage();
}
```

**Silent Try Blocks:**
A "silent" try block ignores exceptions without capturing them, requiring no catch clause:

```zephir
try {
    // code that might throw
}
```

**Optional Exception Variable:**
The exception variable in a catch clause is optional if not needed:

```zephir
catch \Exception {
    // handle without accessing exception
}
```

**Multiple Exception Types:**
Single catch blocks can handle multiple exception types using pipe syntax:

```zephir
catch \RuntimeException|\Exception, e {
    // handle multiple types
}
```

### Exception Information

Zephir exceptions provide methods consistent with PHP exceptions:
- `getMessage()`: Get exception message
- `getFile()`: Get file where exception was thrown
- `getLine()`: Get line number where exception was thrown

---

## Built-In Methods

Zephir provides object-oriented methods for various data types that correspond to procedural equivalents. **Important**: Calling methods on static-typed variables has no impact on performance, as Zephir internally transforms the code from the object-oriented version to the procedural version.

### String Methods

- `format()` / `sprintf()` - formatting strings
- `index()` / `strpos()` - locating substrings
- `length()` / `strlen()` - measuring string size
- `lower()` / `strtolower()` - converting to lowercase
- `lowerfirst()` / `lcfirst()` - lowercasing initial character
- `md5()` and `sha1()` - cryptographic hashing
- `trim()`, `trimleft()`, `trimright()` - whitespace removal
- `upper()` / `strtoupper()` - uppercase conversion
- `upperfirst()` / `ucfirst()` - capitalizing first character

```zephir
string text = "hello world";
let text = text.upper(); // "HELLO WORLD"
let len = text.length(); // 11
```

### Array Methods

- `combine()`, `merge()` - combining arrays
- `diff()`, `intersect()` - comparing arrays
- `flip()` - swapping keys and values
- `hasKey()` - existence checking
- `join()` - concatenating elements
- `keys()`, `values()` - extracting components
- `pad()` - extending arrays
- `rev()`, `reversed()` - reversing order
- `split()` - partitioning into chunks
- `walk()` - applying functions to elements

```zephir
array items = [1, 2, 3];
let reversed = items.reversed(); // [3, 2, 1]
if items.hasKey(0) {
    // key exists
}
```

### Other Data Types

- **Char**: `toHex()` - hexadecimal conversion
- **Integer**: `abs()` - absolute value calculation

---

## Configuration (config.json)

Zephir extensions use a `config.json` file to control build and compiler behavior.

### Core Settings

```json
{
    "namespace": "phpstanturbo",
    "name": "phpstan_turbo",
    "author": "PHPStan Team",
    "version": "0.0.1",
    "description": "PHPStan Turbo Extension",
    "extension-name": "phpstanturbo"
}
```

- **namespace**: Extension namespace (regex: `[a-zA-Z0-9\_]+`)
- **name**: Extension name in C code (ASCII characters only)
- **author**: Developer/organization information
- **version**: Extension version (format: `[0-9]+\.[0-9]+\.[0-9]+`)
- **description**: Extension description text
- **extension-name**: Base filename (defaults to namespace value)

### Documentation & Output

**API Documentation:**
Controls HTML documentation generation:

```json
{
    "api": {
        "path": "doc/%version%",
        "theme": {
            "name": "zephir",
            "options": {
                "github": null,
                "analytics": null,
                "main_color": "#3E6496"
            }
        }
    }
}
```

**Stubs:**
Configures IDE documentation stub generation:

```json
{
    "stubs": {
        "path": "ide/%version%/%namespace%/",
        "stubs-run-after-generate": false
    }
}
```

**Info:**
Defines phpinfo() section structure with headers and data rows:

```json
{
    "info": [
        {
            "header": ["Directive", "Value"],
            "rows": [
                ["setting1", "value1"],
                ["setting2", "value2"]
            ]
        }
    ]
}
```

### Compilation & Dependencies

**Backend:**
Selects Zend Engine version (ZendEngine2 or ZendEngine3):

```json
{
    "backend": "ZendEngine3"
}
```

**Extra Compilation Options:**
```json
{
    "extra-cflags": "-O2 -Wall",
    "extra-libs": "-lm",
    "extra-sources": ["utils/pi.c"],
    "extra-classes": ["MyPrecompiledClass"],
    "constants-sources": ["kernel/math_constants.h"]
}
```

**Dependencies:**
```json
{
    "package-dependencies": {
        "openssl": ">=1.0.0"
    },
    "requires": {
        "extensions": ["json", "pdo"]
    }
}
```

### Code Optimization

Enable/disable specific compiler optimizations:

```json
{
    "optimizations": {
        "static-type-inference": true,
        "static-type-inference-second-pass": true,
        "local-context-pass": true,
        "constant-folding": true,
        "static-constant-class-folding": true,
        "call-gatherer-pass": true,
        "check-invalid-reads": false,
        "internal-call-transformation": false
    }
}
```

#### Available Optimizations

1. **call-gatherer-pass**: Counts how many times a function or method is called within the same method, enabling inline caches to speed up repeated calls.

2. **check-invalid-reads**: Validates that variables are properly initialized during compilation, preventing potential bugs and memory leaks.

3. **constant-folding**: Simplifies constant expressions at build time, replacing complex calculations with their computed results.

4. **internal-call-transformation**: Generates internal method implementations alongside PHP ones, bypassing PHP userspace for faster internal calls (off by default).

5. **local-context-pass**: Transfers heap-allocated variables to the stack, reducing memory indirections during program execution.

6. **static-constant-class-folding**: Replaces class constant references with their literal values at compile time.

7. **static-type-inference**: Identifies dynamic variables suitable for conversion to static/primitive types, enabling better C compiler optimization.

8. **static-type-inference-second-pass**: Conducts a secondary type inference pass leveraging data from the first pass for improved results.

### Warnings

Control compiler warning generation:

```json
{
    "warnings": {
        "unused-variable": true,
        "unused-variable-external": false,
        "possible-wrong-parameter": true,
        "possible-wrong-parameter-undefined": false,
        "nonexistent-function": true,
        "nonexistent-class": true,
        "non-valid-isset": true,
        "non-array-update": true,
        "non-valid-objectupdate": true,
        "non-valid-fetch": true,
        "invalid-array-index": true,
        "non-array-append": true,
        "invalid-return-type": true,
        "unreachable-code": true,
        "nonexistent-constant": true,
        "not-supported-magic-constant": true,
        "non-valid-decrement": true,
        "non-valid-increment": true,
        "non-valid-clone": true,
        "non-valid-new": true,
        "non-array-access": true,
        "invalid-reference": true,
        "invalid-typeof-comparison": true,
        "conditional-initialization": true
    }
}
```

#### Available Warnings

1. **unused-variable**: Raised when a variable is declared but not used within a method (enabled by default)
2. **unused-variable-external**: Detects function parameters that are declared but never referenced
3. **possible-wrong-parameter-undefined**: Triggers when a method receives arguments of incorrect types
4. **nonexistent-function**: Raised when a function is called that does not exist at compile time
5. **nonexistent-class**: Raised when a class is used that does not exist at compile time
6. **non-valid-isset**: Identifies isset operations performed on non-array or non-object values
7. **non-array-update**: Detects attempts to update array indices on variables that aren't arrays
8. **non-valid-objectupdate**: Raised when an object update operation is made on a non-object value
9. **non-valid-fetch**: Identifies fetch operations on non-array or non-object variables
10. **invalid-array-index**: Triggers when array indexing uses invalid index types
11. **non-array-append**: Detects append operations on non-array variables

### Extension Behavior

**Globals:**
Extension-wide variables with types and defaults:

```json
{
    "globals": {
        "debug": {
            "type": "bool",
            "default": false,
            "module": true
        }
    }
}
```

**Lifecycle Hooks:**

```json
{
    "initializers": {
        "globals": [{"include": "header.h", "code": "init_globals();"}],
        "module": [{"include": "header.h", "code": "init_module();"}],
        "request": [{"include": "header.h", "code": "init_request();"}]
    },
    "destructors": {
        "request": [{"include": "header.h", "code": "cleanup_request();"}],
        "post-request": [{"include": "header.h", "code": "cleanup_post_request();"}],
        "module": [{"include": "header.h", "code": "cleanup_module();"}],
        "globals": [{"include": "header.h", "code": "cleanup_globals();"}]
    }
}
```

### Miscellaneous

```json
{
    "silent": false,
    "verbose": true,
    "optimizer-dirs": ["optimizers"],
    "prototype-dir": "prototypes"
}
```

- **silent**: Suppresses command output
- **verbose**: Enables detailed error messages
- **optimizer-dirs**: Custom optimizer file locations
- **prototype-dir**: Prototype files for required extensions

---

## Extension Lifecycle

### Initialization Hooks

The `initializers` block manages three setup phases:

- **Globals**: Setting up the global variable space
- **Module**: Prepares functionality the extension requires to operate
- **Request**: Readies the extension to handle individual requests

Each hook is configured in `config.json` as an array containing include/code pairs, where the include references a C header file and code specifies the logic to execute.

### Shutdown Hooks

The `destructors` block handles four teardown phases, executing in reverse order:

- **Request**: Finalizes data before responding to the client
- **Post-request**: Performs cleanup after the response is transmitted
- **Module**: Releases extension resources before PHP process termination
- **Globals**: Cleans up the global variable space

### Best Practice

The documentation recommends placing logic longer than a few lines into separate C source files and calling them with single-line function calls within the `code` value, rather than embedding complex logic directly in the configuration.

---

## Extension Globals

Extension globals enable developers to establish and manage global variables within an extension.

### Key Characteristics

Extension globals support only simple scalar types like `int`, `bool`, `double`, and `char`. They function as configuration options that can alter library behavior.

### Configuration Setup

Add a `globals` structure to your `config.json` file. Each global requires a type and default value. Optional namespacing uses dot notation (e.g., `component.setting`).

The `module` key places the global's initialization process into the module-wide `GINIT` lifecycle event. This ensures the global is set up once per process rather than per request.

### Access Methods

Within Zephir code, use built-in functions `globals_get()` and `globals_set()` to read and modify extension globals:

```zephir
public function isDebugEnabled() -> bool
{
    return globals_get("debug");
}

public function enableDebug() -> void
{
    globals_set("debug", true);
}
```

For PHP-level access, create wrapper methods that call these functions.

### Important Limitation

Extension globals cannot use dynamic variable names. The C code generated by the `globals_get`/`globals_set` optimizers must be resolved at compilation time, meaning variable names must be hardcoded—not computed or fetched from other variables.

---

## Static Analysis Features

Zephir's compiler includes static analysis capabilities designed to identify potential issues before runtime execution.

### Conditional Unassigned Variables

The compiler detects when variables might be used before assignment. When such cases are found, Zephir automatically initializes the variable and generates a warning message. This proactive approach helps developers catch logic errors that could lead to unexpected behavior.

### Dead Code Elimination

Zephir identifies unreachable code branches and removes them from the compiled binary. The tool automatically eliminates code that cannot be executed, such as statements within conditional blocks that always evaluate to false, streamlining the final output.

---

## Custom Optimizers

Optimizers intercept function calls during compilation, replacing PHP userland functions with direct C calls for improved performance and reduced overhead.

### Structure and Naming Convention

Optimizers are PHP classes stored in a configurable directory. They follow a specific naming pattern:
- Zephir function `calculate_pi` → Optimizer class `CalculatePiOptimizer`
- File location: `optimizers/CalculatePiOptimizer.php`
- Corresponding C function: `my_calculate_pi`

### Key Implementation Steps

**1. Validate Parameters:**
The optimizer must verify parameter count and type requirements, throwing `CompilerException` if validation fails.

**2. Process Return Values:**
Check if the value returned will be stored in the correct type; if not, throw a compiler exception.

**3. Resolve Parameters:**
Use `getReadOnlyResolvedParams()` for read-only functions or `getResolvedParams()` when parameters are modified. This returns valid C code for the code printer.

**4. Return CompiledExpression:**
All optimizers must return a `CompiledExpression` instance specifying the return type and generated C code.

### Configuration

Register optimizers in `config.json`:

```json
{
    "optimizer-dirs": ["optimizers"],
    "extra-sources": ["utils/pi.c"]
}
```

The actual C implementation must reside in the `ext/` directory and include necessary Zend Engine headers.

---

## Zephir CLI Commands

### Available Commands

**zephir init**
Creates new Zephir extension project:
```bash
zephir init namespace [--backend=ZendEngine3]
```

**zephir generate**
Converts Zephir code to C:
```bash
zephir generate [--backend=ZendEngine3]
```

**zephir compile**
Builds a Zephir extension:
```bash
zephir compile [--backend=ZendEngine3] [--dev|--no-dev]
```
- `--dev`: Enables development mode with debug symbols
- `--no-dev`: Creates production-ready extension

**zephir build**
Meta command executing generate, compile, and install sequentially:
```bash
zephir build
```

**zephir install**
Deploys extension to system directory:
```bash
zephir install [--dev|--no-dev]
```

**zephir api**
Generates HTML API documentation:
```bash
zephir api [--path=PATH] [--output=OUTPUT] [--url=URL] [--options=OPTIONS]
```

**zephir stubs**
Creates PHP IDE stub files:
```bash
zephir stubs [--backend=ZendEngine3]
```

**zephir clean**
Removes object files from the extension:
```bash
zephir clean
```

**zephir fullclean**
Removes all object files including phpize-generated files:
```bash
zephir fullclean
```

**zephir help**
Shows command documentation:
```bash
zephir help
```

**zephir list**
Displays all available commands:
```bash
zephir list
```

---

## PHPInfo() Integration

### Automatic Functionality

Zephir extensions automatically display basic information in phpinfo() output, including the extension version and any INI options the extension supports.

### Custom Configuration

Developers can expand phpinfo() output by adding a configuration section to `config.json`. This allows creation of custom information tables with defined headers and row data.

The configuration uses a JSON array format where each object represents a separate table. Each table specifies:
- **header**: Column titles (e.g., "Directive" and "Value")
- **rows**: Data entries organized as key-value pairs

Custom directives configured this way appear as formatted tables within the phpinfo() output, making extension-specific settings and environment data easily accessible to developers during debugging and deployment.

---

# PHPStan Turbo Extension Development

## Current Implementation

### CombinationsHelper.zep

**Location**: `turbo-ext/phpstanturbo/CombinationsHelper.zep`

**Purpose**: Generates Cartesian product of arrays (all possible combinations)

**Signature**:
```zephir
public static function combinations(array arrays) -> array
```

**Algorithm**:
- Recursive implementation
- Base case: empty input returns `[[]]`
- Recursive case: Takes first array (head), processes remaining arrays
- Builds combinations by prepending each element from head to sub-combinations

**Implementation**:
```zephir
namespace PHPStanTurbo;

final class CombinationsHelper
{
    /**
     * @param array arrays
     * @return array
     */
    public static function combinations(array arrays) -> array
    {
        var head, elem, combination, c, comb, subResult, results;
        array remaining;

        if count(arrays) === 0 {
            return [[]];
        }

        let remaining = arrays;
        let head = array_shift(remaining);
        let results = [];

        for elem in head {
            let subResult = self::combinations(remaining);
            for combination in subResult {
                let comb = [elem];
                for c in combination {
                    let comb[] = c;
                }
                let results[] = comb;
            }
        }

        return results;
    }
}
```

**Key Difference from PHP Version**:
- PHP version uses generators (`yield`) for memory efficiency
- Zephir version returns full array (generators not yet implemented in Zephir)
- This trade-off may impact memory usage for large combination sets

## Zephir Configuration (`config.json`)

Our current configuration for the PHPStan Turbo Extension:

```json
{
    "stubs": {
        "path": "ide/%version%/%namespace%/",
        "stubs-run-after-generate": false,
        "banner": ""
    },
    "api": {
        "path": "doc/%version%",
        "theme": {
            "name": "zephir",
            "options": {
                "github": null,
                "analytics": null,
                "main_color": "#3E6496",
                "link_color": "#3E6496",
                "link_hover_color": "#5F9AE7"
            }
        }
    },
    "warnings": {
        "unused-variable": true,
        "unused-variable-external": false,
        "possible-wrong-parameter": true,
        "possible-wrong-parameter-undefined": false,
        "nonexistent-function": true,
        "nonexistent-class": true,
        "non-valid-isset": true,
        "non-array-update": true,
        "non-valid-objectupdate": true,
        "non-valid-fetch": true,
        "invalid-array-index": true,
        "non-array-append": true,
        "invalid-return-type": true,
        "unreachable-code": true,
        "nonexistent-constant": true,
        "not-supported-magic-constant": true,
        "non-valid-decrement": true,
        "non-valid-increment": true,
        "non-valid-clone": true,
        "non-valid-new": true,
        "non-array-access": true,
        "invalid-reference": true,
        "invalid-typeof-comparison": true,
        "conditional-initialization": true
    },
    "optimizations": {
        "static-type-inference": true,
        "static-type-inference-second-pass": true,
        "local-context-pass": true,
        "constant-folding": true,
        "static-constant-class-folding": true,
        "call-gatherer-pass": true,
        "check-invalid-reads": false,
        "internal-call-transformation": false
    },
    "extra": {
        "indent": "spaces",
        "export-classes": false
    },
    "namespace": "phpstanturbo",
    "name": "phpstan_turbo",
    "description": "",
    "author": "PHPStan",
    "version": "0.0.1",
    "verbose": false,
    "requires": {
        "extensions": []
    }
}
```

Key configuration points:
- **Namespace**: `phpstanturbo` (maps to PHP namespace `PHPStanTurbo`)
- **Extension Name**: `phpstan_turbo`
- **Version**: `0.0.1`
- **Optimizations**: Enabled static type inference, constant folding, call gatherer pass
- **Warnings**: Comprehensive warning set enabled for code quality

## Development Workflow

### Building the Extension

```bash
cd turbo-ext
vendor/bin/zephir generate && vendor/bin/zephir compile
```

This generates:
- C source code in `ext/`
- Compiled `phpstanturbo.so` in `ext/modules/`

### Enabling the Extension

Add to your `php.ini`:
```ini
extension=/absolute/path/to/phpstan-src/turbo-ext/ext/modules/phpstanturbo.so
```

Verify loading:
```bash
php -m | grep phpstanturbo
```

### Testing Integration

1. With extension enabled, run PHPStan:
   ```bash
   bin/phpstan analyse ...
   ```

2. Verify extension is being used by checking that `TurboExtensionEnabler::isLoaded()` returns true

3. Run PHPStan's test suite:
   ```bash
   vendor/bin/phpunit
   ```

## Integration Points

### Files That Use CombinationsHelper

1. **src/Type/Constant/ConstantArrayType.php:1829**
   - Heavy usage during constant array type inference
   - Processes combinations of possible types

2. **src/Type/Php/SprintfFunctionDynamicReturnTypeExtension.php**
   - Analyzes sprintf format string possibilities

3. **src/Type/Php/ImplodeFunctionReturnTypeExtension.php**
   - Analyzes implode array possibilities

### Files in Turbo Infrastructure

1. **src/Turbo/TurboExtensionEnabler.php**
   - Extension detection and activation
   - Class aliasing logic

2. **src/Internal/CombinationsHelper.php**
   - Fallback PHP implementation
   - Gets replaced when extension is loaded

3. **bin/phpstan**
   - Early initialization of turbo extension

4. **tests/bootstrap.php**
   - Ensures tests use extension when available

5. **src/DependencyInjection/Configurator.php**
   - Uses TurboExtensionEnabler

## Performance Considerations

### Expected Benefits
- Reduced CPU time for combination generation
- Lower overhead from PHP VM interpretation
- Potential for better memory locality in native code

### Current Limitations
- No generator support in Zephir version (memory trade-off)
- Only one function optimized so far
- Extension build/deployment complexity

### Benchmarking
TODO: Add benchmark suite to measure actual performance gains

## Future Expansion Areas

### High-Priority Candidates
Functions that are:
- Called frequently during analysis
- Computationally intensive
- Work with primitive data structures
- Don't require complex PHP ecosystem features

### Potential Targets

1. **Array/String Operations**
   - Array intersection/union operations
   - String manipulation utilities
   - Hash computations

2. **Type System Operations**
   - Type comparison/equality checks
   - Simple type transformations
   - Type acceptability checks

3. **Scope/Variable Tracking**
   - Scope merging operations
   - Variable state tracking

### Investigation Workflow

To identify new optimization targets:

1. **Profile PHPStan**: Find hotspots using Xdebug or Blackfire
2. **Identify Pure Functions**: Focus on methods without side effects
3. **Assess Complexity**: Ensure Zephir can express the logic
4. **Prototype**: Implement in Zephir
5. **Benchmark**: Measure actual performance impact
6. **Integrate**: Add to TurboExtensionEnabler aliasing

## Development Best Practices

### Adding New Zephir Classes

1. Create `.zep` file in `turbo-ext/phpstanturbo/`
2. Use namespace `PHPStanTurbo`
3. Match the interface of the PHP class being replaced
4. Update `TurboExtensionEnabler.php` with new class_alias
5. Keep fallback PHP implementation in sync

### Zephir Coding Guidelines

**Variable Declarations:**
- Always declare all variables at the beginning of methods
- Use static types when possible for optimization
- Use `var` for dynamic types when necessary

**Type Safety:**
- Specify return types on all methods
- Use type hints for parameters
- Be aware of automatic type conversions
- **Remember**: String indexing (`str[i]`) returns `char`, not `string`
- Use single quotes for `char` literals (`'a'`), double quotes for `string` literals (`"a"`)

**Memory Efficiency:**
- Prefer static types over dynamic for performance
- Be mindful that arrays are not generators (full materialization)
- Use `const` parameters when values shouldn't be modified

**Error Handling:**
- Use exceptions for error conditions
- Validate parameters early
- Provide clear error messages

### Testing Strategy

1. Ensure PHP implementation has comprehensive tests
2. Extension should pass same test suite
3. Add specific tests for edge cases in Zephir
4. Test both with and without extension loaded

### Compatibility

- Maintain API compatibility with PHP version
- Document any behavioral differences (e.g., generators vs arrays)
- Ensure graceful fallback when extension not available

## Debugging

### Extension Not Loading

```bash
# Check if extension file exists
ls -l turbo-ext/ext/modules/phpstanturbo.so

# Check PHP configuration
php --ini

# Check for loading errors
php -d extension=/path/to/phpstanturbo.so -m

# Verify extension info
php --re phpstanturbo
```

### Rebuild from Scratch

```bash
cd turbo-ext
rm -rf ext/ .zephir/
vendor/bin/zephir generate && vendor/bin/zephir compile
```

### Zephir Compilation Errors

- Check Zephir syntax against [Zephir Language Reference](https://docs.zephir-lang.com/)
- Ensure C compiler and PHP dev headers are available
- Review generated C code in `ext/` directory
- Check that all variables are declared before use
- Verify type compatibility in assignments

### Common Pitfalls

1. **Undeclared Variables**: All variables must be declared with `var` or a type
2. **Type Mismatches**: Static types cannot be reassigned to different types
3. **String vs Char Confusion**:
   - String indexing returns `char`, not `string`: `str[0]` is char type
   - Use single quotes for char: `'a'`, double quotes for string: `"a"`
   - When iterating strings, loop variable is `char` type
   - Comparing `str[0] == "a"` is wrong; use `str[0] == 'a'` instead
   - **PHP to Zephir**: Convert all single-quoted PHP strings to double-quoted Zephir strings
4. **Missing Semicolons**: While optional, they can help avoid ambiguity
5. **File/Class Mismatch**: File structure must match namespace and class names
6. **Immutable Variables**: Use `let` to assign values, not direct assignment

## Resources

### Documentation
- [Zephir Official Documentation](https://docs.zephir-lang.com/)
- [Zephir GitHub Repository](https://github.com/zephir-lang/zephir)
- [Zephir Tutorial](https://docs.zephir-lang.com/latest/tutorial/)
- [PHP Extension Development](https://www.phpinternalsbook.com/)

### Tools
- [Zephir Parser](https://github.com/zephir-lang/php-zephir-parser)
- [Zephir Compiler](https://github.com/zephir-lang/zephir)

### Community
- [Zephir Documentation Repository](https://github.com/zephir-lang/documentation)

## Contributing to Turbo Extension

When adding new optimizations:

1. **Measure First**: Profile to confirm bottleneck
2. **Start Simple**: Pick pure functions with simple data types
3. **Maintain Compatibility**: Keep same interface as PHP version
4. **Test Thoroughly**: Run full test suite with/without extension
5. **Document Trade-offs**: Note any behavioral differences
6. **Benchmark Results**: Provide performance measurements

## Known Issues & Limitations

1. **Generator Support**: Zephir version uses arrays instead of generators
   - Impact: Higher memory usage for large combination sets
   - Mitigation: Monitor memory usage in production

2. **Build Complexity**: Requires Zephir toolchain and C compiler
   - Impact: Development setup more complex
   - Mitigation: Clear documentation and prerequisites

3. **Distribution**: Extension must be compiled per environment
   - Impact: Cannot distribute as pure PHP package
   - Mitigation: Keep extension optional with fallback

4. **Debugging**: Native code harder to debug than PHP
   - Impact: Longer development cycles
   - Mitigation: Comprehensive PHP tests before porting

## Version History

- **0.0.1** (Current): Initial PoC with CombinationsHelper only

## Contact & Support

For issues or questions:
- Main PHPStan repo: https://github.com/phpstan/phpstan
- Zephir issues: https://github.com/zephir-lang/zephir/issues

---

**Last Updated**: 2025-12-30
**Maintainers**: PHPStan Team
