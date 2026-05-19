<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/promise/src/PromiseInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\Promise\PromiseInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f406f1b6d4c2d0e7df6d09b0a2ac22fc60415b139ecd149d46037ac285925306-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\Promise\\PromiseInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/promise/src/PromiseInterface.php',
      ),
    ),
    'namespace' => 'React\\Promise',
    'name' => 'React\\Promise\\PromiseInterface',
    'shortName' => 'PromiseInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @template-covariant T
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 152,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'then' => 
      array (
        'name' => 'then',
        'parameters' => 
        array (
          'onFulfilled' => 
          array (
            'name' => 'onFulfilled',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 30,
                'startFilePos' => 1666,
                'endTokenPos' => 30,
                'endFilePos' => 1669,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'callable',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 26,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'onRejected' => 
          array (
            'name' => 'onRejected',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 40,
                'startFilePos' => 1696,
                'endTokenPos' => 40,
                'endFilePos' => 1699,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'callable',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 57,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Transforms a promise\'s value by applying a function to the promise\'s fulfillment
 * or rejection value. Returns a new promise for the transformed result.
 *
 * The `then()` method registers new fulfilled and rejection handlers with a promise
 * (all parameters are optional):
 *
 *  * `$onFulfilled` will be invoked once the promise is fulfilled and passed
 *     the result as the first argument.
 *  * `$onRejected` will be invoked once the promise is rejected and passed the
 *     reason as the first argument.
 *
 * It returns a new promise that will fulfill with the return value of either
 * `$onFulfilled` or `$onRejected`, whichever is called, or will reject with
 * the thrown exception if either throws.
 *
 * A promise makes the following guarantees about handlers registered in
 * the same call to `then()`:
 *
 *  1. Only one of `$onFulfilled` or `$onRejected` will be called,
 *      never both.
 *  2. `$onFulfilled` and `$onRejected` will never be called more
 *      than once.
 *
 * @template TFulfilled
 * @template TRejected
 * @param ?(callable((T is void ? null : T)): (PromiseInterface<TFulfilled>|TFulfilled)) $onFulfilled
 * @param ?(callable(\\Throwable): (PromiseInterface<TRejected>|TRejected)) $onRejected
 * @return PromiseInterface<($onRejected is null ? ($onFulfilled is null ? T : TFulfilled) : ($onFulfilled is null ? T|TRejected : TFulfilled|TRejected))>
 */',
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 104,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
        'aliasName' => NULL,
      ),
      'catch' => 
      array (
        'name' => 'catch',
        'parameters' => 
        array (
          'onRejected' => 
          array (
            'name' => 'onRejected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 27,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Registers a rejection handler for promise. It is a shortcut for:
 *
 * ```php
 * $promise->then(null, $onRejected);
 * ```
 *
 * Additionally, you can type hint the `$reason` argument of `$onRejected` to catch
 * only specific errors.
 *
 * @template TThrowable of \\Throwable
 * @template TRejected
 * @param callable(TThrowable): (PromiseInterface<TRejected>|TRejected) $onRejected
 * @return PromiseInterface<T|TRejected>
 */',
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
        'aliasName' => NULL,
      ),
      'finally' => 
      array (
        'name' => 'finally',
        'parameters' => 
        array (
          'onFulfilledOrRejected' => 
          array (
            'name' => 'onFulfilledOrRejected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 29,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Allows you to execute "cleanup" type tasks in a promise chain.
 *
 * It arranges for `$onFulfilledOrRejected` to be called, with no arguments,
 * when the promise is either fulfilled or rejected.
 *
 * * If `$promise` fulfills, and `$onFulfilledOrRejected` returns successfully,
 *    `$newPromise` will fulfill with the same value as `$promise`.
 * * If `$promise` fulfills, and `$onFulfilledOrRejected` throws or returns a
 *    rejected promise, `$newPromise` will reject with the thrown exception or
 *    rejected promise\'s reason.
 * * If `$promise` rejects, and `$onFulfilledOrRejected` returns successfully,
 *    `$newPromise` will reject with the same reason as `$promise`.
 * * If `$promise` rejects, and `$onFulfilledOrRejected` throws or returns a
 *    rejected promise, `$newPromise` will reject with the thrown exception or
 *    rejected promise\'s reason.
 *
 * `finally()` behaves similarly to the synchronous finally statement. When combined
 * with `catch()`, `finally()` allows you to write code that is similar to the familiar
 * synchronous catch/finally pair.
 *
 * Consider the following synchronous code:
 *
 * ```php
 * try {
 *     return doSomething();
 * } catch(\\Exception $e) {
 *     return handleError($e);
 * } finally {
 *     cleanup();
 * }
 * ```
 *
 * Similar asynchronous code (with `doSomething()` that returns a promise) can be
 * written:
 *
 * ```php
 * return doSomething()
 *     ->catch(\'handleError\')
 *     ->finally(\'cleanup\');
 * ```
 *
 * @param callable(): (void|PromiseInterface<void>) $onFulfilledOrRejected
 * @return PromiseInterface<T>
 */',
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 79,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
        'aliasName' => NULL,
      ),
      'cancel' => 
      array (
        'name' => 'cancel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * The `cancel()` method notifies the creator of the promise that there is no
 * further interest in the results of the operation.
 *
 * Once a promise is settled (either fulfilled or rejected), calling `cancel()` on
 * a promise has no effect.
 *
 * @return void
 */',
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
        'aliasName' => NULL,
      ),
      'otherwise' => 
      array (
        'name' => 'otherwise',
        'parameters' => 
        array (
          'onRejected' => 
          array (
            'name' => 'onRejected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 31,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * [Deprecated] Registers a rejection handler for a promise.
 *
 * This method continues to exist only for BC reasons and to ease upgrading
 * between versions. It is an alias for:
 *
 * ```php
 * $promise->catch($onRejected);
 * ```
 *
 * @template TThrowable of \\Throwable
 * @template TRejected
 * @param callable(TThrowable): (PromiseInterface<TRejected>|TRejected) $onRejected
 * @return PromiseInterface<T|TRejected>
 * @deprecated 3.0.0 Use catch() instead
 * @see self::catch()
 */',
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 70,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
        'aliasName' => NULL,
      ),
      'always' => 
      array (
        'name' => 'always',
        'parameters' => 
        array (
          'onFulfilledOrRejected' => 
          array (
            'name' => 'onFulfilledOrRejected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 28,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * [Deprecated] Allows you to execute "cleanup" type tasks in a promise chain.
 *
 * This method continues to exist only for BC reasons and to ease upgrading
 * between versions. It is an alias for:
 *
 * ```php
 * $promise->finally($onFulfilledOrRejected);
 * ```
 *
 * @param callable(): (void|PromiseInterface<void>) $onFulfilledOrRejected
 * @return PromiseInterface<T>
 * @deprecated 3.0.0 Use finally() instead
 * @see self::finally()
 */',
        'startLine' => 151,
        'endLine' => 151,
        'startColumn' => 5,
        'endColumn' => 78,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Promise',
        'declaringClassName' => 'React\\Promise\\PromiseInterface',
        'implementingClassName' => 'React\\Promise\\PromiseInterface',
        'currentClassName' => 'React\\Promise\\PromiseInterface',
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