<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/event-loop/src/StreamSelectLoop.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\EventLoop\StreamSelectLoop
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-2b70bdfc38351ed148f5e7ea85c4f5701460d3ccb1d304c613eaac060d772416-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\EventLoop\\StreamSelectLoop',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/event-loop/src/StreamSelectLoop.php',
      ),
    ),
    'namespace' => 'React\\EventLoop',
    'name' => 'React\\EventLoop\\StreamSelectLoop',
    'shortName' => 'StreamSelectLoop',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * A `stream_select()` based event loop.
 *
 * This uses the [`stream_select()`](https://www.php.net/manual/en/function.stream-select.php)
 * function and is the only implementation that works out of the box with PHP.
 *
 * This event loop works out of the box on PHP 5.4 through PHP 8+ and HHVM.
 * This means that no installation is required and this library works on all
 * platforms and supported PHP versions.
 * Accordingly, the [`Loop` class](#loop) and the deprecated [`Factory`](#factory)
 * will use this event loop by default if you do not install any of the event loop
 * extensions listed below.
 *
 * Under the hood, it does a simple `select` system call.
 * This system call is limited to the maximum file descriptor number of
 * `FD_SETSIZE` (platform dependent, commonly 1024) and scales with `O(m)`
 * (`m` being the maximum file descriptor number passed).
 * This means that you may run into issues when handling thousands of streams
 * concurrently and you may want to look into using one of the alternative
 * event loop implementations listed below in this case.
 * If your use case is among the many common use cases that involve handling only
 * dozens or a few hundred streams at once, then this event loop implementation
 * performs really well.
 *
 * If you want to use signal handling (see also [`addSignal()`](#addsignal) below),
 * this event loop implementation requires `ext-pcntl`.
 * This extension is only available for Unix-like platforms and does not support
 * Windows.
 * It is commonly installed as part of many PHP distributions.
 * If this extension is missing (or you\'re running on Windows), signal handling is
 * not supported and throws a `BadMethodCallException` instead.
 *
 * This event loop is known to rely on wall-clock time to schedule future timers
 * when using any version before PHP 7.3, because a monotonic time source is
 * only available as of PHP 7.3 (`hrtime()`).
 * While this does not affect many common use cases, this is an important
 * distinction for programs that rely on a high time precision or on systems
 * that are subject to discontinuous time adjustments (time jumps).
 * This means that if you schedule a timer to trigger in 30s on PHP < 7.3 and
 * then adjust your system time forward by 20s, the timer may trigger in 10s.
 * See also [`addTimer()`](#addtimer) for more details.
 *
 * @link https://www.php.net/manual/en/function.stream-select.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 53,
    'endLine' => 330,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'React\\EventLoop\\LoopInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'MICROSECONDS_PER_SECOND' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'MICROSECONDS_PER_SECOND',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1000000',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 44,
            'startFilePos' => 2691,
            'endTokenPos' => 44,
            'endFilePos' => 2697,
          ),
        ),
        'docComment' => '/** @internal */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
      'futureTickQueue' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'futureTickQueue',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'timers' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'timers',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'readStreams' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'readStreams',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'array()',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 63,
            'startFilePos' => 2779,
            'endTokenPos' => 65,
            'endFilePos' => 2785,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'readListeners' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'readListeners',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'array()',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 74,
            'startFilePos' => 2817,
            'endTokenPos' => 76,
            'endFilePos' => 2823,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'writeStreams' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'writeStreams',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'array()',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 85,
            'startFilePos' => 2854,
            'endTokenPos' => 87,
            'endFilePos' => 2860,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'writeListeners' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'writeListeners',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'array()',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 96,
            'startFilePos' => 2893,
            'endTokenPos' => 98,
            'endFilePos' => 2899,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'running' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'running',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pcntl' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'pcntl',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 112,
            'startFilePos' => 2945,
            'endTokenPos' => 112,
            'endFilePos' => 2949,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'pcntlPoll' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'pcntlPoll',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 121,
            'startFilePos' => 2977,
            'endTokenPos' => 121,
            'endFilePos' => 2981,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'signals' => 
      array (
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'name' => 'signals',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 69,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'addReadStream' => 
      array (
        'name' => 'addReadStream',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 35,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 44,
            'endColumn' => 52,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 83,
        'endLine' => 91,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'addWriteStream' => 
      array (
        'name' => 'addWriteStream',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 36,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 45,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 93,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'removeReadStream' => 
      array (
        'name' => 'removeReadStream',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 103,
            'endLine' => 103,
            'startColumn' => 38,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 103,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'removeWriteStream' => 
      array (
        'name' => 'removeWriteStream',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 39,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 113,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'addTimer' => 
      array (
        'name' => 'addTimer',
        'parameters' => 
        array (
          'interval' => 
          array (
            'name' => 'interval',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 30,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 41,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 123,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'addPeriodicTimer' => 
      array (
        'name' => 'addPeriodicTimer',
        'parameters' => 
        array (
          'interval' => 
          array (
            'name' => 'interval',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 38,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 49,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 132,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'cancelTimer' => 
      array (
        'name' => 'cancelTimer',
        'parameters' => 
        array (
          'timer' => 
          array (
            'name' => 'timer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'React\\EventLoop\\TimerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 141,
            'endLine' => 141,
            'startColumn' => 33,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 141,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'futureTick' => 
      array (
        'name' => 'futureTick',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 32,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 146,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'addSignal' => 
      array (
        'name' => 'addSignal',
        'parameters' => 
        array (
          'signal' => 
          array (
            'name' => 'signal',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 31,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 40,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 151,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'removeSignal' => 
      array (
        'name' => 'removeSignal',
        'parameters' => 
        array (
          'signal' => 
          array (
            'name' => 'signal',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 34,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 43,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 165,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 178,
        'endLine' => 215,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'stop' => 
      array (
        'name' => 'stop',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 217,
        'endLine' => 220,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'waitForStreamActivity' => 
      array (
        'name' => 'waitForStreamActivity',
        'parameters' => 
        array (
          'timeout' => 
          array (
            'name' => 'timeout',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 44,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Wait/check for stream activity, or until the next timer is due.
 *
 * @param integer|null $timeout Activity timeout in microseconds, or null to wait forever.
 */',
        'startLine' => 227,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
      'streamSelect' => 
      array (
        'name' => 'streamSelect',
        'parameters' => 
        array (
          'read' => 
          array (
            'name' => 'read',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 35,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'write' => 
          array (
            'name' => 'write',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'timeout' => 
          array (
            'name' => 'timeout',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 64,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Emulate a stream_select() implementation that does not break when passed
 * empty stream arrays.
 *
 * @param array    $read    An array of read streams to select upon.
 * @param array    $write   An array of write streams to select upon.
 * @param int|null $timeout Activity timeout in microseconds, or null to wait forever.
 *
 * @return int|false The total number of streams that are ready for read/write.
 *     Can return false if stream_select() is interrupted by a signal.
 */',
        'startLine' => 270,
        'endLine' => 329,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 4,
        'namespace' => 'React\\EventLoop',
        'declaringClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'implementingClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'currentClassName' => 'React\\EventLoop\\StreamSelectLoop',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));