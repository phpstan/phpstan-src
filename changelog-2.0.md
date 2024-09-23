This document is a work in progress.

When PHPStan 2.0 gets released, this will turn into [releases notes on GitHub](https://github.com/phpstan/phpstan/releases) + a separate [UPGRADING](./UPGRADING.md) document.

Major new features 🚀
=====================

* **Array `list` type** ([#1751](https://github.com/phpstan/phpstan-src/pull/1751)), #3311, #8185, #6243, thanks @rvanvelzen!
    * Lists are arrays with sequential integer keys starting at 0
* **Enhancements in Handling Parameters Passed by Reference**
    * [Learn more on phpstan.org](https://phpstan.org/blog/enhancements-in-handling-parameters-passed-by-reference)
    * [#2941](https://github.com/phpstan/phpstan-src/pull/2941), thanks @ljmaskey!
* LogicalXorConstantConditionRule (level 4) (https://github.com/phpstan/phpstan-src/commit/3a12724fd636b1bcf36c22b36e8f765d97150895, https://github.com/phpstan/phpstan-src/commit/3b011f6524254dad0f16840fdcfdbe7421548617), #7539
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

Bleeding edge (TODO move to other sections)
=====================

* Report useless `array_filter()` calls ([#1077](https://github.com/phpstan/phpstan-src/pull/1077)), #6840, thanks @leongersen!
* Specify explicit mixed array type via `is_array` ([#1191](https://github.com/phpstan/phpstan-src/pull/1191)), thanks @herndlm!
* Lower memory consumption thanks to breaking up of reference cycles
    * This is a BC break for rules that use `'parent'`, `'next'`, and `'previous'` node attributes. [Learn more »](https://phpstan.org/blog/preprocessing-ast-for-custom-rules)
    * In testing the memory consumption was reduced by 50–70 %.
* ArrayUnpackingRule (level 3) ([#856](https://github.com/phpstan/phpstan-src/pull/856)), thanks @canvural!
* Rules for checking direct calls to `__construct()` (level 2) ([#1208](https://github.com/phpstan/phpstan-src/pull/1208)), #7022, thanks @muno92!
* ConstantLooseComparisonRule - level 4 (https://github.com/phpstan/phpstan-src/commit/6ebf2361a3c831dd105a815521889428c295dc9f)
* Unresolvable parameters ([#1319](https://github.com/phpstan/phpstan-src/pull/1319)), thanks @rvanvelzen!
* Support `@readonly` property and `@immutable` class PHPDoc ([#1295](https://github.com/phpstan/phpstan-src/pull/1295), [#1335](https://github.com/phpstan/phpstan-src/pull/1335)), #4082, thanks @herndlm!
* Move IllegalConstructorMethodCallRule and IllegalConstructorStaticCallRule to phpstan-strict-rules (https://github.com/phpstan/phpstan-src/commit/124b30f98c182193187be0b9c2e151e477429b7a, https://github.com/phpstan/phpstan-strict-rules/commit/0c82c96f2a55d8b91bbc7ee6512c94f68a206b43)
* Check that each trait is used and analysed at least once - level 4 (https://github.com/phpstan/phpstan-src/commit/c4d05276fb8605d6ac20acbe1cc5df31cd6c10b0)
* Check that PHPStan class in class constant fetch is covered by backward compatibility promise (https://github.com/phpstan/phpstan-src/commit/9e007251ce61788f6a0319a53f1de6cf801ed233)
* Check code in custom PHPStan extensions for runtime reflection concepts like `is_a()` or `class_parents()` (https://github.com/phpstan/phpstan-src/commit/c4a662ac6c3ec63f063238880b243b5399c34fcc)
* Check code in custom PHPStan extensions for runtime reflection concepts like `new ReflectionMethod()` (https://github.com/phpstan/phpstan-src/commit/536306611cbb5877b6565755cd07b87f9ccfdf08)
* ApiInstanceofRule
    * Report `instanceof` of classes not covered by backward compatibility promise (https://github.com/phpstan/phpstan-src/commit/ff4d02d62a7a2e2c4d928d48d31d49246dba7139)
    * Report `instanceof` of classes covered by backward compatibility promise but where the assumption might change (https://github.com/phpstan/phpstan-src/commit/996bc69fa977aa64f601bd82b8a0ae39be0cbeef)
* Add `@readonly` rule that disallows default values ([#1391](https://github.com/phpstan/phpstan-src/pull/1391)), thanks @herndlm!
* MissingMagicSerializationMethodsRule ([#1711](https://github.com/phpstan/phpstan-src/pull/1711)), #7482, thanks @staabm!
* Change `curl_setopt` function signature based on 2nd arg ([#1719](https://github.com/phpstan/phpstan-src/pull/1719)), thanks @staabm!
* Empty `skipCheckGenericClasses` (https://github.com/phpstan/phpstan-src/commit/28c2c79b16cac6ba6b01f1b4d211541dd49d8a77)
* Validate inline PHPDoc `@var` tag type against native type (https://github.com/phpstan/phpstan-src/commit/a69e3bc2f1e87f6da1e65d7935f1cc36bd5c42fe)
    * Set [`reportWrongPhpDocTypeInVarTag`](https://phpstan.org/config-reference#reportwrongphpdoctypeinvartag) to `true` to have all types validated, not just native ones
* Always report always true conditions, except for last elseif and match arm ([#2105](https://github.com/phpstan/phpstan-src/pull/2105)), thanks @staabm!
* Disable "unreachable branches" rules: UnreachableIfBranchesRule, UnreachableTernaryElseBranchRule, unreachable arm error in MatchExpressionRule
    * Because "always true" is always reported, these are no longer needed
* IncompatibleDefaultParameterTypeRule for closures (https://github.com/phpstan/phpstan-src/commit/0264f5bc48448c7e02a23b82eef4177d0617a82f)
* Check template type variance in `@param-out` (https://github.com/phpstan/phpstan-src/commit/7ceb19d3b42cf4632d10c2babb0fc5a21b6c8352), https://github.com/phpstan/phpstan/issues/8880#issuecomment-1426971473
* Deprecate various `instanceof *Type` in favour of new methods on `Type` interface, (https://github.com/phpstan/phpstan-src/commit/436e6d3015cbeba4645d38bc7a6a865b9c6d7c74), learn more: [Why Is instanceof *Type Wrong and Getting Deprecated?](https://phpstan.org/blog/why-is-instanceof-type-wrong-and-getting-deprecated)
* Fix position variance of static method parameters ([#2313](https://github.com/phpstan/phpstan-src/pull/2313)), thanks @jiripudil!
* Check variance of template types in properties ([#2314](https://github.com/phpstan/phpstan-src/pull/2314)), thanks @jiripudil!
* Report narrowing `PHPStan\Type\Type` interface via `@var` (https://github.com/phpstan/phpstan-src/commit/713b98fb107213c28e3d8c8b4b43c5f5fc47c144), https://github.com/nunomaduro/larastan/issues/1567#issuecomment-1460445389
* Check invalid PHPDocs in previously unchecked statement types (https://github.com/phpstan/phpstan-src/commit/9780d352f3264aac09ac7954f691de1877db8e01)
* InvalidPHPStanDocTagRule in StubValidator (https://github.com/phpstan/phpstan-src/commit/9c2552b7e744926d1a74c1ba8fd32c64079eed61)
* Rule for `call_user_func()` ([#2479](https://github.com/phpstan/phpstan-src/pull/2479)), thanks @staabm!
* MagicConstantContextRule ([#2741](https://github.com/phpstan/phpstan-src/pull/2741)), #10099, thanks @staabm!
* TooWideMethodReturnTypehintRule - always report for final methods (https://github.com/phpstan/phpstan-src/commit/c30e9a484c8245b8126cd63444607ca74d2af761)
* NoopRule - report top-level `xor` because that's probably not what the user intended to do (https://github.com/phpstan/phpstan-src/commit/a1fffb3346e09f1e8e8d987d4282263295a55142), #10267
* Report unused results of `and` and `or` (https://github.com/phpstan/phpstan-src/commit/1d8fff637d70a9e9ed3f11dee5d61b9f796cbf1a)
* Report unused result of ternary (https://github.com/phpstan/phpstan-src/commit/9664f7a9d2223c07e750f0dfc949c3accfa6b65e)
* Report unused results of `&&` and `||` (https://github.com/phpstan/phpstan-src/commit/cf2c8bbd9ebd2ebe300dbd310e136ad603d7def3)
* Add option `reportAnyTypeWideningInVarTag` ([#2840](https://github.com/phpstan/phpstan-src/pull/2840)), thanks @janedbal!
* `array_values` rule (report when a `list` type is always passed in) ([#2917](https://github.com/phpstan/phpstan-src/pull/2917)), thanks @kamil-zacek!
* Fix checking generic `mixed` type based on config ([#2885](https://github.com/phpstan/phpstan-src/pull/2885)), thanks @schlndh!
* Checking truthiness of `@phpstan-pure` above functions and methods
* Check `new`/function call/method call/static method call on a separate line without any side effects even without `@phpstan-pure` PHPDoc tag on the declaration side
    * https://github.com/phpstan/phpstan-src/commit/281a87d1ab61809076ecfa6dfc2cc86e3babe235
    * [#3020](https://github.com/phpstan/phpstan-src/pull/3020), thanks @staabm!
    * [#3022](https://github.com/phpstan/phpstan-src/pull/3022), thanks @staabm!
    * [#3023](https://github.com/phpstan/phpstan-src/pull/3023), thanks @staabm!
* BetterNoopRule - take advantage of impure points (https://github.com/phpstan/phpstan-src/commit/a6470521b65d7424f552633c1f3827704c6262c3), #10389
* CallToConstructorStatementWithoutSideEffectsRule - report class with no constructor (https://github.com/phpstan/phpstan-src/commit/b116d25a6e4ba6c09f59af6569d9e6f6fd20aff4)
* Check if required file exists ([#3294](https://github.com/phpstan/phpstan-src/pull/3294)), #3397, thanks @Bellangelo!
* Enforce `@no-named-arguments` (https://github.com/phpstan/phpstan-src/commit/74ba8c23696948f2647d880df72f375346f41010), #5968
* Check too wide private property type (https://github.com/phpstan/phpstan-src/commit/7453f4f75fae3d635063589467842aae29d88b54)
* Check `@param-immediately-invoked-callable` and `@param-later-invoked-callable` (https://github.com/phpstan/phpstan-src/commit/580a6add422f4e34191df9e7a77ba1655e914bda), #10932
* RegularExpressionPatternRule: validate preg_quote'd patterns ([#3270](https://github.com/phpstan/phpstan-src/pull/3270)), thanks @staabm!
* Report useless return values of function calls like `var_export` without `$return=true` ([#3225](https://github.com/phpstan/phpstan-src/pull/3225)), #11320, thanks @staabm!
* Check vprintf/vsprintf arguments against placeholder count ([#3126](https://github.com/phpstan/phpstan-src/pull/3126)), thanks @staabm!
* Check preg_quote delimiter sanity ([#3252](https://github.com/phpstan/phpstan-src/pull/3252)), #11338, thanks @staabm!
* Check array functions which require stringish values ([#3132](https://github.com/phpstan/phpstan-src/pull/3132)), #11141, #5848, #3694, #11111, thanks @schlndh!

Improvements 🔧
=====================

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

Bugfixes 🐛
=====================

* Fix invariance composition ([#2054](https://github.com/phpstan/phpstan-src/pull/2054)), thanks @jiripudil!

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

Internals 🔍
=====================
