When PHPStan 2.0 gets released, this will turn into [releases notes on GitHub](https://github.com/phpstan/phpstan/releases).

Check out the [**UPGRADING guide**](https://github.com/phpstan/phpstan-src/blob/2.0.x/UPGRADING.md)!.

Major new features 🚀
=====================

* **Level 10** - level 9 on steroids, treats all `mixed` types strictly, not just explicit `mixed`
* **Array `list` type** ([#1751](https://github.com/phpstan/phpstan-src/pull/1751)), #3311, #8185, #6243, thanks @rvanvelzen!
    * Lists are arrays with sequential integer keys starting at 0
* **Validate inline PHPDoc `@var` tag** type against native type (level 2) (https://github.com/phpstan/phpstan-src/commit/a69e3bc2f1e87f6da1e65d7935f1cc36bd5c42fe)
    * Set [`reportWrongPhpDocTypeInVarTag`](https://phpstan.org/config-reference#reportwrongphpdoctypeinvartag) to `true` to have all types validated, not just native ones
    * Use config option `reportAnyTypeWideningInVarTag: true` for stricter behaviour ([#2840](https://github.com/phpstan/phpstan-src/pull/2840)), thanks @janedbal!
* **Lower memory consumption** thanks to breaking up of reference cycles
    * [Learn more »](https://phpstan.org/blog/preprocessing-ast-for-custom-rules)
    * In testing the memory consumption was reduced by 50–70 %.
* **Enhancements in handling parameters passed by reference**
    * [Learn more on phpstan.org](https://phpstan.org/blog/enhancements-in-handling-parameters-passed-by-reference)
    * [#2941](https://github.com/phpstan/phpstan-src/pull/2941), thanks @ljmaskey!
* Check too wide private property type (level 4) (https://github.com/phpstan/phpstan-src/commit/7453f4f75fae3d635063589467842aae29d88b54)
* Always report always true conditions, except for last elseif and match arm (https://github.com/phpstan/phpstan-src/commit/565fb0f6da9cdc58e8686598015561a848693972)
* Remove "unreachable branches" rules: UnreachableIfBranchesRule, UnreachableTernaryElseBranchRule, unreachable arm error in MatchExpressionRule
    * Because "always true" is always reported, these are no longer needed
* New option: `polluteScopeWithBlock` (defaults to `true`, `false` in `phpstan-strict-rules`) (https://github.com/phpstan/phpstan-src/commit/946cf180c960930c2c42075d0f28ff9090507272)
* Checking truthiness of `@phpstan-pure` above functions and methods
* Check `new`/function call/method call/static method call on a separate line without any side effects even without `@phpstan-pure` PHPDoc tag on the declaration side
    * https://github.com/phpstan/phpstan-src/commit/281a87d1ab61809076ecfa6dfc2cc86e3babe235
    * [#3020](https://github.com/phpstan/phpstan-src/pull/3020), thanks @staabm!
    * [#3022](https://github.com/phpstan/phpstan-src/pull/3022), thanks @staabm!
    * [#3023](https://github.com/phpstan/phpstan-src/pull/3023), thanks @staabm!
* LogicalXorConstantConditionRule (level 4) (https://github.com/phpstan/phpstan-src/commit/3a12724fd636b1bcf36c22b36e8f765d97150895, https://github.com/phpstan/phpstan-src/commit/3b011f6524254dad0f16840fdcfdbe7421548617), #7539
* Check that each trait is used and analysed at least once (level 4) (https://github.com/phpstan/phpstan-src/commit/c4d05276fb8605d6ac20acbe1cc5df31cd6c10b0)
* Check preg_quote delimiter sanity (level 5) ([#3252](https://github.com/phpstan/phpstan-src/pull/3252)), #11338, thanks @staabm!
* MagicConstantContextRule (level 0) ([#2741](https://github.com/phpstan/phpstan-src/pull/2741)), #10099, thanks @staabm!
* MissingMagicSerializationMethodsRule (level 0) ([#1711](https://github.com/phpstan/phpstan-src/pull/1711)), #7482, thanks @staabm!
* Check vprintf/vsprintf arguments against placeholder count (level 0) ([#3126](https://github.com/phpstan/phpstan-src/pull/3126)), thanks @staabm!
* Report useless return values of function calls like `var_export` without `$return=true` (level 4) ([#3225](https://github.com/phpstan/phpstan-src/pull/3225)), #11320, thanks @staabm!
* Rule for `call_user_func()` (level 5) ([#2479](https://github.com/phpstan/phpstan-src/pull/2479)), thanks @staabm!
* Report useless `array_filter()` calls (level 5) ([#1077](https://github.com/phpstan/phpstan-src/pull/1077)), #6840, thanks @leongersen!
* Report useless `array_values()` calls (level 5) ([#2917](https://github.com/phpstan/phpstan-src/pull/2917)), thanks @kamil-zacek!
* Check if required file exists (level 0) ([#3294](https://github.com/phpstan/phpstan-src/pull/3294)), #3397, thanks @Bellangelo!
* ConstantLooseComparisonRule - level 4 (https://github.com/phpstan/phpstan-src/commit/6ebf2361a3c831dd105a815521889428c295dc9f)
* Check array functions which require stringish values (level 5) ([#3132](https://github.com/phpstan/phpstan-src/pull/3132)), #11141, #5848, #3694, #11111, thanks @schlndh!
* Check variance of template types in properties (level 2) ([#2314](https://github.com/phpstan/phpstan-src/pull/2314)), thanks @jiripudil!
* ArrayUnpackingRule (level 3) ([#856](https://github.com/phpstan/phpstan-src/pull/856)), thanks @canvural!
* Check unresolvable parameters (level 5) ([#1319](https://github.com/phpstan/phpstan-src/pull/1319)), thanks @rvanvelzen!
* Enforce `@no-named-arguments` (level 5) (https://github.com/phpstan/phpstan-src/commit/74ba8c23696948f2647d880df72f375346f41010), #5968
* Support `@readonly` property and `@immutable` class PHPDoc ([#1295](https://github.com/phpstan/phpstan-src/pull/1295), [#1335](https://github.com/phpstan/phpstan-src/pull/1335)), #4082, thanks @herndlm!
* Add `@readonly` rule that disallows default values (level 0) ([#1391](https://github.com/phpstan/phpstan-src/pull/1391)), thanks @herndlm!
* IncompatibleDefaultParameterTypeRule for closures (level 2) (https://github.com/phpstan/phpstan-src/commit/0264f5bc48448c7e02a23b82eef4177d0617a82f)
* Added previously absent type checks (level 0)
  * Check existing classes in `@phpstan-self-out` (https://github.com/phpstan/phpstan-src/commit/6838669976bf20232abde36ecdd52b1770fa50c9)
  * Check nonexistent classes in local type aliases (https://github.com/phpstan/phpstan-src/commit/2485b2e9c129e789ec3b2d7db81ca30f87c63911)
  * Check unresolvable types in local type aliases (https://github.com/phpstan/phpstan-src/commit/5f7d12b2fb2809525ab0e96eeae95093204ea4d3)
  * Check generics in local type aliases (https://github.com/phpstan/phpstan-src/commit/5a2d4416d94ab77a2a2e7e1bfaba4c5ed2a13c25)
  * Check existing classes in `@param-out` (https://github.com/phpstan/phpstan-src/commit/30c4b9e80f51af8b5f166ba3aae93d8409c9c0ea), #10260
  * Check existing classes in `@param-closure-this` (https://github.com/phpstan/phpstan-src/commit/2fa539a39e06bcc3155b109fd8d246703ceb176d), #10933
* Added previously absent type checks (level 2)
  * Check `@mixin` PHPDoc tag above traits (https://github.com/phpstan/phpstan-src/commit/0d0de946900adf4eb3c799b1b547567536e23147)
  * Check `@extends`, `@implements`, `@use` for unresolvable types (https://github.com/phpstan/phpstan-src/commit/2bb528233edb75312614166e282776f279cf2018), #11552
  * Check types in `@method` tags (https://github.com/phpstan/phpstan-src/commit/5b7e474680eaf33874b7ed6a227677adcbed9ca5)
  * Check generics `@method` `@template` tags above traits (https://github.com/phpstan/phpstan-src/commit/aadbf62d3ae4517fc7a212b07130bedcef8d13ac)
  * Check types in `@property` tags (https://github.com/phpstan/phpstan-src/commit/55ea2ae516df22a071ab873fdd6f748a3af0520e), #10752, #9356
* Added previously absent type checks (level 6)
  * Check missing types in `@phpstan-self-out` (https://github.com/phpstan/phpstan-src/commit/892b319f25f04bc1b55c3d0063b607909612fe6d)
  * Check missing types in local type aliases (https://github.com/phpstan/phpstan-src/commit/ce7ffaf02d624a7fb9d38f8e5dffc9739f1233fc)
  * Check missing types in `@mixin` (https://github.com/phpstan/phpstan-src/commit/3175c81f26fd5bcb4a161b24e774921870ed2533)
* Rule about `@phpstan-consistent-constructor` (level 0) ([#1296](https://github.com/phpstan/phpstan-src/pull/1296)), thanks @canvural!
* Check code in custom PHPStan extensions for runtime reflection concepts like `is_a()` or `class_parents()` (level 0) (https://github.com/phpstan/phpstan-src/commit/c4a662ac6c3ec63f063238880b243b5399c34fcc)
* Check code in custom PHPStan extensions for runtime reflection concepts like `new ReflectionMethod()` (level 0) (https://github.com/phpstan/phpstan-src/commit/536306611cbb5877b6565755cd07b87f9ccfdf08)
* ApiInstanceofRule (level 0)
    * Report `instanceof` of classes not covered by backward compatibility promise (https://github.com/phpstan/phpstan-src/commit/ff4d02d62a7a2e2c4d928d48d31d49246dba7139)
    * Report `instanceof` of classes covered by backward compatibility promise but where the assumption might change (https://github.com/phpstan/phpstan-src/commit/996bc69fa977aa64f601bd82b8a0ae39be0cbeef)
* Check that PHPStan class in class constant fetch is covered by backward compatibility promise (level 0) (https://github.com/phpstan/phpstan-src/commit/9e007251ce61788f6a0319a53f1de6cf801ed233)
* Deprecate various `instanceof *Type` in favour of new methods on `Type` interface, (https://github.com/phpstan/phpstan-src/commit/436e6d3015cbeba4645d38bc7a6a865b9c6d7c74), learn more: [Why Is instanceof *Type Wrong and Getting Deprecated?](https://phpstan.org/blog/why-is-instanceof-type-wrong-and-getting-deprecated)
* Report narrowing `PHPStan\Type\Type` interface via `@var` (https://github.com/phpstan/phpstan-src/commit/713b98fb107213c28e3d8c8b4b43c5f5fc47c144), https://github.com/nunomaduro/larastan/issues/1567#issuecomment-1460445389


Improvements 🔧
=====================

* TableErrorFormatter - always output identifiers (https://github.com/phpstan/phpstan-src/commit/fc66c24113e9fe88c3155703224eb03768846fdd)
* Config option `exceptions.check.tooWideThrowType` made true by default (https://github.com/phpstan/phpstan-src/commit/1b1da3e2ce3acf10dde03d9656638cda4f7389a4)
* Use `implicitThrows` to only look for explicit throw points in too-wide `@throws` rules when set to `false` (https://github.com/phpstan/phpstan-src/commit/a0e688c1d1e4c5e82f989b26485eb9162f47aa97)
* Rules about tooWideThrowType moved to level 4 (https://github.com/phpstan/phpstan-src/commit/d7798d7f2c47f426efe91c566e6cafd5a4e2410c)
* Both .php and .neon baselines now include error identifiers (https://github.com/phpstan/phpstan-src/commit/f38addda2b151b6e41a746a37659c0bbe9e2293b, https://github.com/phpstan/phpstan-src/commit/c8b7ea9e8f51c8bbc38dfa6b04f9a0172f5cfea0)
* PHPDoc parser: Require whitespace before description with limited start tokens (https://github.com/phpstan/phpdoc-parser/pull/128), https://github.com/phpstan/phpdoc-parser/issues/125, thanks @rvanvelzen!
* Unescape strings in PHPDoc parser (https://github.com/phpstan/phpstan-src/commit/97786ed8376b478ec541ea9df1c450c1fbfe7461)
* PHPDoc parser: add config for lines in its AST & enable ignoring errors within PHPDocs ([#2807](https://github.com/phpstan/phpstan-src/pull/2807)), thanks @janedbal!
* InvalidPhpDocTagValueRule: include PHPDoc line number in the error message (https://github.com/phpstan/phpstan-src/commit/a04e0be832900749b5b4ba22e2de21db8bfa09a0)
* No implicit wildcard in FileExcluder (https://github.com/phpstan/phpstan-src/commit/e19e6e5f8cfa706cc30e44a17276a6bc269f995c), #10299
* Report invalid exclude paths in PHP config (https://github.com/phpstan/phpstan-src/commit/9718c14f1ffac81ba3d2bf331b4e8b4041a4d004)
* Do not generalize template types, except when in `GenericObjectType` ([#2818](https://github.com/phpstan/phpstan-src/pull/2818), [#2821](https://github.com/phpstan/phpstan-src/pull/2821))
    * This fixes following **20 issues**: #8166, #8127, #7944, #7283, #6653, #6196, #9084, #8683, #8074, #7984, #7301, #7087, #5594, #5592, #9472, #9764, #10092, #11126, #11032, #10653
* Non-static methods cannot be used as static callables in PHP 8+ ([#2420](https://github.com/phpstan/phpstan-src/pull/2420)), thanks @staabm!
* Analysis with zero files results in non-zero exit code (https://github.com/phpstan/phpstan-src/commit/46ff440648e62617df86aa74ba905ffa99897737), #9410
* Fail build when project config uses custom extensions outside of analysed paths
    * This will only occur after a run that uses already present and valid result cache
* Returning plain strings as errors no longer supported, use RuleErrorBuilder
    * Learn more: [Using RuleErrorBuilder to enrich reported errors in custom rules](https://phpstan.org/blog/using-rule-error-builder)
* Require identifier in custom rules (https://github.com/phpstan/phpstan-src/commit/969e6fa31d5484d42dab902703cfc6820a983cfd)
* New `RuleLevelHelper::accepts()` behaviour (https://github.com/phpstan/phpstan-src/commit/941fc815db49315b8783dc466cf593e0d8a85d23), #11119, #4174
* Infer explicit mixed when instantiating generic class with unknown template types (https://github.com/phpstan/phpstan-src/commit/089d4c6fb6eb709c44123548d33990113d174b86), #6398
* Use explicit mixed for global array variables ([#1411](https://github.com/phpstan/phpstan-src/pull/1411)), #7082, thanks @herndlm!
* Consider implicit throw points when the only explicit one is `Throw_` (https://github.com/phpstan/phpstan-src/commit/22eef6d5ab9a4afafb2305258fea273be6cc06e4), #4912
* Run missing type check on `@param-out` (https://github.com/phpstan/phpstan-src/commit/56b20024386d983927c64dfa895ff026bed2798c)
* Report "missing return" error closer to where the return is missing (https://github.com/phpstan/phpstan-src/commit/04f8636e6577cbcaefc944725eed74c0d7865ead)
* Report dead types even in multi-exception catch ([#2399](https://github.com/phpstan/phpstan-src/pull/2399)), thanks @JanTvrdik!
* MethodSignatureRule - look at abstract trait method (https://github.com/phpstan/phpstan-src/commit/5fd8cee591ce1b07daa5f98a1ddcdfc723f1b5eb)
* OverridingMethodRule - include template types in prototype declaring class description (https://github.com/phpstan/phpstan-src/commit/ca2c66cc4dff59ba44d52b82cb9e0aa3256240f3)
* Detect overriding `@final` method in OverridingMethodRule, #9135
* Improve error wording of the NonexistentOffset, BooleanAndConstantConditionRule, and BooleanOrConstantConditionRule ([#1882](https://github.com/phpstan/phpstan-src/pull/1882)), thanks @VincentLanglet!
* Stricter ++/-- operator check ([#3255](https://github.com/phpstan/phpstan-src/pull/3255)), thanks @schlndh!
* Check mixed in binary operator ([#3231](https://github.com/phpstan/phpstan-src/pull/3231)), #7538, #10440, thanks @schlndh!
* Check mixed in unary operator ([#3253](https://github.com/phpstan/phpstan-src/pull/3253)), thanks @schlndh!
* Stub files validation - detect duplicate classes and functions (https://github.com/phpstan/phpstan-src/commit/ddf8d5c3859c2c75c20f525a0e2ca8b99032373a, https://github.com/phpstan/phpstan-src/commit/17e4b74335e5235d7cd6708eb687a774a0eeead4)
* NoopRule - take advantage of impure points (https://github.com/phpstan/phpstan-src/commit/a6470521b65d7424f552633c1f3827704c6262c3), #10389
* Improve impossible type checker for void-returning functions ([#1857](https://github.com/phpstan/phpstan-src/pull/1857)), #8169, thanks @rvanvelzen!
* Check template type variance in `@param-out` (https://github.com/phpstan/phpstan-src/commit/7ceb19d3b42cf4632d10c2babb0fc5a21b6c8352), https://github.com/phpstan/phpstan/issues/8880#issuecomment-1426971473
* Fix position variance of static method parameters ([#2313](https://github.com/phpstan/phpstan-src/pull/2313)), thanks @jiripudil!
* Empty `skipCheckGenericClasses` (https://github.com/phpstan/phpstan-src/commit/28c2c79b16cac6ba6b01f1b4d211541dd49d8a77)
* Report unnecessary nullsafe property fetch inside `??` / `isset` / `empty` with different message ([#1253](https://github.com/phpstan/phpstan-src/pull/1253)), thanks @rajyan!
* Specify explicit mixed array type via `is_array` ([#1191](https://github.com/phpstan/phpstan-src/pull/1191)), thanks @herndlm!
* TooWideMethodReturnTypehintRule - always report for final methods (https://github.com/phpstan/phpstan-src/commit/c30e9a484c8245b8126cd63444607ca74d2af761)
* Move IllegalConstructorMethodCallRule and IllegalConstructorStaticCallRule to phpstan-strict-rules (https://github.com/phpstan/phpstan-src/commit/124b30f98c182193187be0b9c2e151e477429b7a, https://github.com/phpstan/phpstan-strict-rules/commit/0c82c96f2a55d8b91bbc7ee6512c94f68a206b43)
* Check invalid PHPDocs in previously unchecked statement types (https://github.com/phpstan/phpstan-src/commit/9780d352f3264aac09ac7954f691de1877db8e01)
* InvalidPHPStanDocTagRule in StubValidator (https://github.com/phpstan/phpstan-src/commit/9c2552b7e744926d1a74c1ba8fd32c64079eed61)
* CallToConstructorStatementWithoutSideEffectsRule - report class with no constructor (https://github.com/phpstan/phpstan-src/commit/b116d25a6e4ba6c09f59af6569d9e6f6fd20aff4)
* ContainerFactory - always check duplicate files (https://github.com/phpstan/phpstan-src/commit/939a715a0636ed05752659dbe7646c1f1a574765)
* Display parent class name for anonymous class like native PHP does ([#3362](https://github.com/phpstan/phpstan-src/pull/3362)), thanks @mvorisek!
* Always report static property fetch in `isset()`, not just on PHP 8.2+ ([#3476](https://github.com/phpstan/phpstan-src/pull/3476)), thanks @ondrejmirtes!
* Revert "Dumb down parameter types in some recently added stubs" (https://github.com/phpstan/phpstan-src/commit/950a491485c46068074ca3f4f6dc5b970d41465a)
* Do not apply heuristics of `Collection<...>|Foo[]` being resolved to Collection of Foo (https://github.com/phpstan/phpstan-src/commit/fff8f095988a66f298aa4037fe8e6ba98266063c)
* Collected PHP errors cannot be ignored (https://github.com/phpstan/phpstan-src/commit/1d3f4313955dc6fa5c6ce60fa58afe765964e5b0)
* Added missing rules to StubValidator (https://github.com/phpstan/phpstan-src/commit/bf19914cac1682d0eab8bf65a874ba368522311c)
* Report precise offsets in errors ([#3504](https://github.com/phpstan/phpstan-src/pull/3504)), thanks @ruudk!


Bugfixes 🐛
=====================

* Fix invariance composition ([#2054](https://github.com/phpstan/phpstan-src/pull/2054)), thanks @jiripudil!
* Fix checking generic `mixed` type based on config ([#2885](https://github.com/phpstan/phpstan-src/pull/2885)), thanks @schlndh!


Function signature fixes 🤖
=======================

* Countable stub with `0|positive-int` ([#1027](https://github.com/phpstan/phpstan-src/pull/1027)), thanks @staabm!
* More precise types for bcmath function parameters ([#2217](https://github.com/phpstan/phpstan-src/pull/2217)), thanks @Warxcell!
* Specify `Imagick` parameter types ([#2334](https://github.com/phpstan/phpstan-src/pull/2334)), thanks @zonuexe!
* `max()`/`min()` should expect non-empty-array ([#2163](https://github.com/phpstan/phpstan-src/pull/2163)), thanks @staabm!
* Narrow `Closure::bind` `$newScope` param ([#2817](https://github.com/phpstan/phpstan-src/pull/2817)), thanks @mvorisek!
* `error_log` errors with `message_type=2` ([#2428](https://github.com/phpstan/phpstan-src/pull/2428)), #9380, thanks @staabm!
* Update functionMap ([#2699](https://github.com/phpstan/phpstan-src/pull/2699), [#2783](https://github.com/phpstan/phpstan-src/pull/2783)), thanks @zonuexe!
* Improve image related functions signature ([#3127](https://github.com/phpstan/phpstan-src/pull/3127)), thanks @thg2k!
* Support `FILE_NO_DEFAULT_CONTEXT` in `file()` ([#2482](https://github.com/phpstan/phpstan-src/pull/2482)), thanks @staabm!
* Fix ftp related function signatures ([#2551](https://github.com/phpstan/phpstan-src/pull/2551)), thanks @thg2k!
* More precise `file()` flags args ([#2476](https://github.com/phpstan/phpstan-src/pull/2476), [#2482](https://github.com/phpstan/phpstan-src/pull/2482)), thanks @staabm!
* More precise `flock()` operation flags ([#2477](https://github.com/phpstan/phpstan-src/pull/2477)), thanks @staabm!
* More precise `stream_socket_client()` signature ([#2519](https://github.com/phpstan/phpstan-src/pull/2519)), thanks @staabm!
* More precise `scandir()` signature ([#2518](https://github.com/phpstan/phpstan-src/pull/2518)), thanks @staabm!
* More precise `extract()` signature ([#2517](https://github.com/phpstan/phpstan-src/pull/2517)), thanks @staabm!
* More precise `RecursiveIteratorIterator::__construct()` parameter types ([#2835](https://github.com/phpstan/phpstan-src/pull/2835)), thanks @staabm!
* Update `Locale` signatures ([#2880](https://github.com/phpstan/phpstan-src/pull/2880)), thanks @devnix!
* Improved the type of the `$mode` parameter for the `count()` ([#3190](https://github.com/phpstan/phpstan-src/pull/3190)), thanks @kuma3!* Check `filter_input*` type param type ([#2271](https://github.com/phpstan/phpstan-src/pull/2271)), thanks @herndlm!
* Change `curl_setopt` function signature based on 2nd arg ([#1719](https://github.com/phpstan/phpstan-src/pull/1719)), thanks @staabm!


Internals 🔍
=====================

* Tool to make optional parameters required across the codebase (https://github.com/phpstan/phpstan-src/commit/7e366e08f96e2e4095b3f02b5487e8f9531f37bf)
* A few more MutatingScope method parameters made required (https://github.com/phpstan/phpstan-src/commit/2c4c0cde75e637ac323e81def57d4a2ace952429)
* CommandHelper::begin() parameters made required (https://github.com/phpstan/phpstan-src/commit/f17cf9ec43111cb29dd50d620fb6259c0ab0d373)
* MethodTag - constructor parameter `$templateTags` is required (https://github.com/phpstan/phpstan-src/commit/5b58f83e6d8b5044d742caed9729d00178c4a9de)
* InitializerExprTypeResolver - constructor parameter `$usePathConstantsAsConstantString` made required (https://github.com/phpstan/phpstan-src/commit/f88d9ba7f56ef6c3b783aee1c909a3422c0ef3c3)
* `PhpMethodReflectionFactory::create()` - all parameters are required (https://github.com/phpstan/phpstan-src/commit/8bfbf8f254a68e4f1b15419eb950ea677fc2916e)
* FunctionCallParametersCheck - parameters `$nodeType` and `$acceptsNamedArguments` made required (https://github.com/phpstan/phpstan-src/commit/493752737c32eb878de4dfb91817761b952348e4)
* MethodParameterComparisonHelper - parameter `$ignorable` of `compare()` method made required (https://github.com/phpstan/phpstan-src/commit/f85a500288b0b8ef9a19d405c0e3d99ab57ce797)
* Parameter `$dateTimeClass` of DateTimeModifyReturnTypeExtension constructor made required (https://github.com/phpstan/phpstan-src/commit/a8cd423e842deaa7d924580665207a4b1a373115)
* NativeFunctionReflection construct parameters made required (https://github.com/phpstan/phpstan-src/commit/64ff598cd42268d2178d02efd208afe637060978)
* Cover AccessoryArrayListType constructor with BC promise (https://github.com/phpstan/phpstan-src/commit/51de9032c6e98bff2d6eb0e5b7295720ec0276b9)
* Add `PhpVersion` parameter to various `Type` methods ([#3478](https://github.com/phpstan/phpstan-src/pull/3478)), thanks @VincentLanglet!
* Move ContainerDynamicReturnTypeExtension to build/PHPStan (https://github.com/phpstan/phpstan-src/commit/5651bec661582b2d62de1b4ae9d5f27e69e3c524)
* Renamed NewOptimizedDirectorySourceLocator to OptimizedDirectorySourceLocator (https://github.com/phpstan/phpstan-src/commit/db02a30ca11c7b9839c30e0321ed403dd14f6c73)
* Remove unneded abstraction (https://github.com/phpstan/phpstan-src/commit/f302c9069274afa63ec1b4f313ca72340699e9d8)
* Introduce native return types thanks to PHP 7.4 return type covariance (https://github.com/phpstan/phpstan-src/commit/392f090066bfc9946b4ad524ffecf3d420c23114)
* ReadWritePropertiesExtension - use ExtendedPropertyReflection in parameter type (https://github.com/phpstan/phpstan-src/commit/f0a629685de2202687b9f92bd0e1a516daf2443e)
* Declare more precise `getClass()` return types in extension interfaces ([#1754](https://github.com/phpstan/phpstan-src/pull/1754)), thanks @staabm!
*  (https://github.com/phpstan/phpstan-src/commit/38cb5a315e5573231d8695df343c8ee87a8c3b2e)
* HasOffsetType - put constructor parameter type natively (https://github.com/phpstan/phpstan-src/commit/b5accb3f6bbcffc8a44934539b88903e09b6a174)
* Printer is covered by BC promise (https://github.com/phpstan/phpstan-src/commit/b0858332efc7aa2f2fde7544a2a821ba81bde13b)
* More interfaces that are not supposed to be implemented in userland (https://github.com/phpstan/phpstan-src/commit/778af2ed74ba59bfb2a69fd5b45821ccdb1107c9, https://github.com/phpstan/phpstan-src/commit/cb6ab5544a016c52f931fc390bcdf9c627819d8f)
* Refactored `FunctionCallParametersCheck::check()` parameters (https://github.com/phpstan/phpstan-src/commit/710e09c41698efb1d8d3ae31791944077dbb9cc1)
* Spread list usages in Reflection, Scope, Type ([#3530](https://github.com/phpstan/phpstan-src/pull/3530)), thanks @janedbal!
