# PHPStan Turbo Extension - Developer Guide

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
├── CLAUDE.md                           # This file - developer guide
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

## Zephir Configuration (`config.json`)

Key configuration points:

- **Namespace**: `phpstanturbo` (maps to PHP namespace `PHPStanTurbo`)
- **Extension Name**: `phpstan_turbo`
- **Version**: `0.0.1`
- **Optimizations**: Enabled static type inference, constant folding, call gatherer pass
- **Warnings**: Comprehensive warning set enabled for code quality

## Development Workflow

### Prerequisites

1. **Zephir Installation**: Follow [Zephir Installation Guide](https://docs.zephir-lang.com/latest/installation/#prerequisites)
2. **Zephir Parser Extension**: Install [php-zephir-parser](https://github.com/zephir-lang/php-zephir-parser)
3. **Build Tools**: C compiler (gcc/clang), PHP development headers
4. **Dependencies**: Run `composer install` in `turbo-ext/` directory

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

## Resources

### Documentation
- [Zephir Official Documentation](https://docs.zephir-lang.com/)
- [Zephir Language Tutorial](https://docs.zephir-lang.com/latest/tutorial/)
- [PHP Extension Development](https://www.phpinternalsbook.com/)

### Tools
- [Zephir Parser](https://github.com/zephir-lang/php-zephir-parser)
- [Zephir Compiler](https://github.com/zephir-lang/zephir)

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
