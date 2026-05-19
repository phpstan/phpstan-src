<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Rule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Rule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-8cffc939a186e935910509bb2a6d64e89442fbacb784d16dbc15452d707960c2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Rule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Rule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules',
    'name' => 'PHPStan\\Rules\\Rule',
    'shortName' => 'Rule',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the interface custom rules implement. To register it in the configuration file
 * use the `phpstan.rules.rule` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\MyRule
 *		tags:
 *			- phpstan.rules.rule
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/rules
 *
 * @api
 * @template TNodeType of Node
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 40,
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
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return class-string<TNodeType>
 */',
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\Rule',
        'implementingClassName' => 'PHPStan\\Rules\\Rule',
        'currentClassName' => 'PHPStan\\Rules\\Rule',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'name' => 'PHPStan\\Analyser\\Scope',
                    'isIdentifier' => false,
                  ),
                  1 => 
                  array (
                    'name' => 'PHPStan\\Analyser\\NodeCallbackInvoker',
                    'isIdentifier' => false,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 42,
            'endColumn' => 73,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param TNodeType $node
 * @return list<IdentifierRuleError>
 */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\Rule',
        'implementingClassName' => 'PHPStan\\Rules\\Rule',
        'currentClassName' => 'PHPStan\\Rules\\Rule',
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