<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/NodeCallbackInvoker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\NodeCallbackInvoker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-242a689d0b2640bbfb5f2afb535422a69c9a893e0c4785e3cb32cdb0e5dd7409',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/NodeCallbackInvoker.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
    'shortName' => 'NodeCallbackInvoker',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The interface NodeCallbackInvoker can be typehinted in 2nd parameter of Rule::processNode():
 *
 * ```php
 * public function processNode(Node $node, Scope&NodeCallbackInvoker $scope): array
 * ```
 *
 * It can be used to invoke rules for virtual made-up nodes.
 *
 * For example: You\'re writing a rule for a method with declaration like:
 *
 * ```php
 * public static create(string $class, mixed ...$args)
 * ```
 *
 * And you\'d like to check `Factory::create(Foo::class, 1, 2, 3)` as if it were
 * `new Foo(1, 2, 3)`.
 *
 * You can call `$scope->invokeNodeCallback(new New_(new Name($className), $args))`
 *
 * And PHPStan will call all the registered rules for New_, checking as if the instantiation
 * is actually in the code.
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 32,
    'endLine' => 37,
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
      'invokeNodeCallback' => 
      array (
        'name' => 'invokeNodeCallback',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 37,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
        'implementingClassName' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
        'currentClassName' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
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