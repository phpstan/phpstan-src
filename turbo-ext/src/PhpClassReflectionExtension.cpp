/*
 * PHPStanTurbo\PhpClassReflectionExtension — native implementation of
 * PHPStan\Reflection\Php\PhpClassReflectionExtension, declared under the
 * twin's real name at activation (final, like the twin).
 *
 * Why. ClassReflection asks this service for every member it is asked for:
 * hasMethod() 807K, hasNativeMethod() 746K, touchMemberCacheKey() 399K,
 * getNativeMethod() 292K, hasProperty() 156K and getNativeProperty() 78K
 * times in a self-analysis of src/Analyser, src/Rules and src/Type. Those
 * bodies are three lines each around a memo array, and they call back into
 * ClassReflection::getCacheKey() 1.24M and ::getNativeReflection() 1.01M
 * times — trivial memo getters that the pt_class_reflection_*() direct
 * calls answer without a frame — and into the already
 * native LruCache 0.40M times. The member construction below them
 * (createProperty 10K, createMethod 22K, createUserlandMethodReflection
 * 17K) is memoized and cold, but a shadowed class has no PHP body left to
 * fall back to, so it is ported too.
 *
 * Design
 * ------
 * Class shape. The twin is final: no PHP subclass exists, so every
 * `$this->method()` is a direct C++ call — no Z_OBJCE dispatch. The class
 * is an #[AutowiredService]: Nette reflects __construct while it compiles
 * the container and pairs #[AutowiredParameter] by name, so the arginfo
 * declares the twin's exact parameter names AND class names (README rule
 * 6). $memberCacheKeysMax is the one non-promoted parameter.
 *
 * Layout. The twin's properties are typed property slots in the twin's
 * declaration order: the seven class-body properties first (the LruCache
 * and the six memo arrays), then the seventeen promoted constructor
 * properties in parameter order. The std object handlers do GC/clone/free.
 * The names are load-bearing — the differential harness
 * (tests/php-class-reflection-family.php) reads the constructor arguments
 * and the memo state of both sides by reflection.
 *
 * Collaborators. The fifteen injected services and the BetterReflection
 * adapters ($classReflection->getNativeReflection(), the property and
 * method reflections, their tags and ResolvedPhpDocBlocks) are PHP objects
 * called by name (pt_type_call). ClassReflection's two hot getters go
 * through the direct calls ClassReflection.cpp exports
 * (pt_class_reflection_get_cache_key / _get_native_reflection); every
 * other ClassReflection method is a by-name call, as is
 * ClassMemberAccessAnswerer's isInClass()/getClassReflection() (through
 * pt_scope_is_in_class / pt_scope_get_class_reflection, which fast-path a
 * MutatingScope and call the method on anything else). The shared member
 * LRU is the native LruCache through its exported helpers. The Type kernel
 * is reached natively: TypeCombinator::union()/intersect(),
 * TemplateTypeHelper::resolveTemplateTypes(),
 * TypehintHelper::decideTypeFromReflection()/decideType(),
 * ConstantArrayTypeBuilder, TemplateTypeMap::createEmpty(), the
 * TemplateTypeVariance singletons and `new` of the shadowing
 * ConstantStringType / StringType / MixedType / ArrayType /
 * AccessoryNonFalsyStringType / AccessoryDecimalIntegerStringType /
 * EnumCaseObjectType; `instanceof` against MixedType / TemplateMixedType /
 * ErrorType / NeverType / UnionType uses their class entries directly.
 * Everything else (PhpPropertyReflection, NativeMethodReflection,
 * ExtendedNativeParameterReflection, EnumCasesMethodReflection,
 * ExtendedFunctionVariant, Assertions, InitializerExprContext,
 * OutOfClassScope, ShouldNotHappenException, the PrivateProperty /
 * ProtectedProperty attribute classes, the BetterReflection adapter's
 * ReflectionMethod and the parser nodes read by the constructor-inference
 * pass) goes through pt_type_new / pt_type_call_static / pt_type_instanceof
 * on class-map keys.
 *
 * getCacheKey() is called once per method where the twin calls it three or
 * four times: it memoizes into $cacheKey and is a pure read afterwards, so
 * the repeated calls are the same string by construction.
 *
 * The logic lives in the PhpClassReflectionExtension handle class below,
 * structured to mirror src/Reflection/Php/PhpClassReflectionExtension.php
 * method for method and in the same order; the registration at the bottom
 * is only the engine ABI glue (parameter parsing + delegation).
 */

#include "support.h"
#include "generated/PhpClassReflectionExtension.h"

namespace slots = ptdecl::PhpClassReflectionExtension::slot;
namespace sigs = ptdecl::PhpClassReflectionExtension::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

#include <cstring>
#include <vector>

zend_class_entry *pt_ce_php_class_reflection_extension = nullptr;

namespace {

/* {{{ small engine helpers */

/* $object->method(...$args); UNDEF = pending exception */
inline zv::Val call(zval *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

/* a ResolvedPhpDocBlock getter coerced to bool; false with `ok` cleared on a
 * pending exception */
inline bool resolvedPhpDocBool(zval *block, pt_resolved_php_doc_member member, bool &ok)
{
	bool out = false;
	ok = pt_resolved_php_doc_block_bool(block, member, out);
	return ok && out;
}

/* $object->method(...$args) coerced to bool; false with `ok` cleared on a
 * pending exception */
inline bool callBool(zval *object, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &ok)
{
	zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) {
		ok = false;
		return false;
	}
	ok = true;
	return zend_is_true(result.raw());
}

/* new ShouldNotHappenException() — the twin's default message ("Internal
 * error.") comes from its own constructor; thrown, never returned */
void throwShouldNotHappen()
{
	zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
	if (exception.isUndef()) return;
	zval thrown = exception.take();
	zend_throw_exception_object(&thrown);
}

void throwShouldNotHappenStr(zend_string *message)
{
	zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
	if (ce == NULL) return;
	zend_throw_exception(ce, ZSTR_VAL(message), 0);
}

/* the twin's two "Internal error: Expected to find an ancestor …" messages */
zv::Str ancestorMessage(zend_string *declaringClassName, zend_string *className)
{
	return zv::Str::adopt(zend_strpprintf(0, "Internal error: Expected to find an ancestor with class name %s on %s, but none was found.", ZSTR_VAL(declaringClassName), ZSTR_VAL(className)));
}

/* the result of a call declared `: array`; false (an exception pending) when
 * the call threw or when the callee broke its signature — the engine's
 * return-type TypeError, which a PHP callee would have thrown itself */
[[nodiscard]] bool arrayResult(zv::Val &result, const char *method)
{
	if (UNEXPECTED(result.isUndef())) return false;
	if (EXPECTED(Z_TYPE_P(result.raw()) == IS_ARRAY)) return true;
	zend_type_error("%s(): Return value must be of type array, %s returned", method, zend_zval_value_name(result.raw()));
	return false;
}

/* isset($array[$key]) over a symtable-keyed array: the value, or NULL when
 * the key is absent or holds null */
zval *issetIn(zval *array, zend_string *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) return NULL;
	zval *found = zend_symtable_find(Z_ARRVAL_P(array), key);
	if (found == NULL) return NULL;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) == IS_NULL ? NULL : found;
}

/* $array[$key] ?? null over an array keyed by strings (array_key_exists
 * semantics: a stored null is returned as the null zval) */
zval *keyIn(zval *array, zend_string *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) return NULL;
	zval *found = zend_symtable_find(Z_ARRVAL_P(array), key);
	if (found != NULL) {
		ZVAL_DEREF(found);
	}
	return found;
}

/* $array[$key] = $value on a property slot holding an array */
void setIn(zval *array, zend_string *key, zv::Val value)
{
	SEPARATE_ARRAY(array);
	zval v = value.take();
	zend_symtable_update(Z_ARRVAL_P(array), key, &v);
}

/* $array[$key][$subKey] = $value, creating the inner array when missing */
void setNested(zval *array, zend_string *key, zend_string *subKey, zv::Val value)
{
	SEPARATE_ARRAY(array);
	zval *inner = zend_symtable_find(Z_ARRVAL_P(array), key);
	if (inner != NULL) {
		ZVAL_DEREF(inner);
	}
	if (inner == NULL || Z_TYPE_P(inner) != IS_ARRAY) {
		zval fresh;
		array_init(&fresh);
		inner = zend_symtable_update(Z_ARRVAL_P(array), key, &fresh);
	} else {
		SEPARATE_ARRAY(inner);
	}
	zval v = value.take();
	zend_symtable_update(Z_ARRVAL_P(inner), subKey, &v);
}

/* isset($array[$key][$subKey]) — the value or NULL */
zval *issetNested(zval *array, zend_string *key, zend_string *subKey)
{
	zval *inner = issetIn(array, key);
	if (inner == NULL) return NULL;
	return issetIn(inner, subKey);
}

/* unset($array[$key]) */
void unsetIn(zval *array, zend_string *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) return;
	SEPARATE_ARRAY(array);
	zend_symtable_del(Z_ARRVAL_P(array), key);
}

/* a string-returning call ($x->getName(), ->toString(), …); the Str is
 * null with an exception pending */
zv::Str callString(zval *object, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	zv::Val result = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef()) || Z_TYPE_P(result.raw()) != IS_STRING) return zv::Str::adopt(NULL);
	return zv::Str::adopt(zend_string_copy(Z_STR_P(result.raw())));
}

/* a string getter's answer as an owned string; null with the exception
 * pending when the getter threw */
zv::Str stringOf(zv::Val result)
{
	if (UNEXPECTED(result.isUndef()) || Z_TYPE_P(result.raw()) != IS_STRING) return zv::Str::adopt(NULL);
	return zv::Str::copyOf(Z_STR_P(result.raw()));
}

/* }}} */

/* {{{ the BetterReflection adapter's member memos
 *
 * hasMethod() and hasProperty() are what this service is asked for most
 * (0.96M calls in a self-analysis of src/Analyser, src/Rules and src/Type)
 * and their whole body is
 * $classReflection->getNativeReflection()->has*($name) — three PHP frames
 * around one array lookup:
 *
 *   Adapter\ReflectionClass::hasMethod()  '' => false, else delegate
 *   ReflectionClass::hasMethod()          getMethod() !== null
 *   ReflectionClass::getMethod()          ($this->cachedMethods ?? compute())[strtolower($name)] ?? null
 *
 * (hasProperty() is the same shape over $cachedProperties, keyed by the
 * exact name, and getMethod() adds `new Adapter\ReflectionMethod($m)`.)
 *
 * The memos are private properties of
 * PHPStan\BetterReflection\Reflection\ReflectionClass filled lazily, so
 * these readers answer from them once they are filled and leave every
 * other case — an unfilled memo, an adapter of some other class, the
 * empty name, the exception getMethod() raises for a missing method — to
 * the adapter's own methods (the class entries and offsets are the shared
 * BetterReflection readers', BetterReflectionAccess.cpp). */

/* $betterReflection->cachedMethods / ->cachedProperties behind an adapter
 * the readers know once the library filled it, NULL while it is still null */
zval *adapterMemo(zval *adapter, bool methods)
{
	zend_object *betterReflection = pt_better_reflection_class_of_adapter(adapter);
	if (betterReflection == NULL) return NULL;
	return methods ? pt_better_reflection_class_cached_methods(betterReflection) : pt_better_reflection_class_cached_properties(betterReflection);
}

/* $methods[strtolower($name)] ?? null over the lowercased-name memo */
zval *memoFindLowercased(zval *memo, zend_string *name)
{
	size_t len = ZSTR_LEN(name);
	char buffer[128];
	if (EXPECTED(len < sizeof(buffer))) {
		zend_str_tolower_copy(buffer, ZSTR_VAL(name), len);
		zval *found = zend_symtable_str_find(Z_ARRVAL_P(memo), buffer, len);
		return found != NULL && Z_TYPE_P(found) != IS_NULL ? found : NULL;
	}
	zend_string *lower = zend_string_tolower(name);
	zval *found = zend_symtable_find(Z_ARRVAL_P(memo), lower);
	zend_string_release(lower);
	return found != NULL && Z_TYPE_P(found) != IS_NULL ? found : NULL;
}

/* }}} */

} // namespace

namespace {

/* the constructor's values, borrowed; $memberCacheKeysMax is the one
 * parameter the twin does not promote */
struct ConstructorArgs
{
	zval *scopeFactory;
	zval *phpDocsResolver;
	zval *nodeScopeResolver;
	zval *methodReflectionFactory;
	zval *phpDocInheritanceResolver;
	zval *deprecationProvider;
	zval *annotationsMethodsClassReflectionExtension;
	zval *annotationsPropertiesClassReflectionExtension;
	zval *signatureMapProvider;
	zval *parser;
	zval *stubPhpDocProvider;
	zval *reflectionProviderProvider;
	zval *fileTypeMapper;
	zval *attributeReflectionFactory;
	zval *allowedConstantsMapProvider;
	bool inferPrivatePropertyTypeFromConstructor;
	zval *phpVersion;
	zend_long memberCacheKeysMax;
};

} // namespace

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Reflection\Php\PhpClassReflectionExtension. State lives
 * in the PHP object's property slots. Methods returning zv::Val use UNDEF
 * to signal a pending exception, a legitimate PHP null is zv::Val::null();
 * methods returning bool with an `out` parameter return false on a pending
 * exception.
 */
class PhpClassReflectionExtension
{
public:
	explicit PhpClassReflectionExtension(zend_object *self) : self(self) {}

	/* {{{ the slots */

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* }}} */

	/* Mirrors __construct(): the promoted properties, then
	 * $this->memberCacheOrder = new LruCache($memberCacheKeysMax).
	 * false = pending exception */
	[[nodiscard]] static bool construct(zend_object *object, const ConstructorArgs &a)
	{
		static const struct { uint32_t slot; size_t offset; } promoted[] = {
			{ slots::scopeFactory, offsetof(ConstructorArgs, scopeFactory) },
			{ slots::phpDocsResolver, offsetof(ConstructorArgs, phpDocsResolver) },
			{ slots::nodeScopeResolver, offsetof(ConstructorArgs, nodeScopeResolver) },
			{ slots::methodReflectionFactory, offsetof(ConstructorArgs, methodReflectionFactory) },
			{ slots::phpDocInheritanceResolver, offsetof(ConstructorArgs, phpDocInheritanceResolver) },
			{ slots::deprecationProvider, offsetof(ConstructorArgs, deprecationProvider) },
			{ slots::annotationsMethodsClassReflectionExtension, offsetof(ConstructorArgs, annotationsMethodsClassReflectionExtension) },
			{ slots::annotationsPropertiesClassReflectionExtension, offsetof(ConstructorArgs, annotationsPropertiesClassReflectionExtension) },
			{ slots::signatureMapProvider, offsetof(ConstructorArgs, signatureMapProvider) },
			{ slots::parser, offsetof(ConstructorArgs, parser) },
			{ slots::stubPhpDocProvider, offsetof(ConstructorArgs, stubPhpDocProvider) },
			{ slots::reflectionProviderProvider, offsetof(ConstructorArgs, reflectionProviderProvider) },
			{ slots::fileTypeMapper, offsetof(ConstructorArgs, fileTypeMapper) },
			{ slots::attributeReflectionFactory, offsetof(ConstructorArgs, attributeReflectionFactory) },
			{ slots::allowedConstantsMapProvider, offsetof(ConstructorArgs, allowedConstantsMapProvider) },
			{ slots::phpVersion, offsetof(ConstructorArgs, phpVersion) },
		};
		for (const auto &entry : promoted) {
			zval *value = *(zval *const *) ((const char *) &a + entry.offset);
			ZVAL_COPY(OBJ_PROP_NUM(object, entry.slot), value);
		}
		ZVAL_BOOL(OBJ_PROP_NUM(object, slots::inferPrivatePropertyTypeFromConstructor), a.inferPrivatePropertyTypeFromConstructor);

		zval lru;
		if (UNEXPECTED(!pt_lru_cache_new(&lru, a.memberCacheKeysMax))) return false;
		ZVAL_COPY_VALUE(OBJ_PROP_NUM(object, slots::memberCacheOrder), &lru);
		return true;
	}

	/*
	 * Mirrors touchMemberCacheKey(): moves the key to the most recently
	 * used position of the shared LRU and drops the evicted keys' entries
	 * from all four member caches. false = pending exception
	 */
	[[nodiscard]] bool touchMemberCacheKey(zend_string *cacheKey)
	{
		zval *order = slot(slots::memberCacheOrder);
		zv::Val current = pt_lru_cache_get(order, cacheKey);
		if (UNEXPECTED(current.isUndef())) return false;
		if (Z_TYPE_P(current.raw()) != IS_NULL) return true;

		zval trueValue;
		ZVAL_TRUE(&trueValue);
		zv::Val evicted = pt_lru_cache_set(order, cacheKey, &trueValue, 0);
		if (UNEXPECTED(evicted.isUndef())) return false;
		if (Z_TYPE_P(evicted.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(evicted.raw())) == 0) return true;
		for (zv::ArrayEntry entry : zv::ArrRef(evicted.raw())) {
			zval *evictKey = entry.value().raw();
			if (UNEXPECTED(Z_TYPE_P(evictKey) != IS_STRING)) continue;
			/* the key lives in the evicted list, which outlives the loop */
			zend_string *key = Z_STR_P(evictKey);
			unsetIn(slot(slots::methodsIncludingAnnotations), key);
			unsetIn(slot(slots::nativeMethods), key);
			unsetIn(slot(slots::propertiesIncludingAnnotations), key);
			unsetIn(slot(slots::nativeProperties), key);
		}
		return true;
	}

	/* Mirrors hasProperty(). false with `ok` cleared = pending exception */
	[[nodiscard]] bool hasProperty(zval *classReflection, zend_string *propertyName, bool &ok)
	{
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) {
			ok = false;
			return false;
		}
		zval *memo = adapterMemo(nativeReflection.raw(), false);
		if (EXPECTED(memo != NULL)) {
			ok = true;
			if (ZSTR_LEN(propertyName) == 0) return false;
			zval *found = zend_symtable_find(Z_ARRVAL_P(memo), propertyName);
			return found != NULL && Z_TYPE_P(found) != IS_NULL;
		}
		zval name;
		ZVAL_STR(&name, propertyName);
		return callBool(nativeReflection.raw(), PT_LC("hasproperty"), 1, &name, ok);
	}

	/* Mirrors getProperty(). */
	zv::Val getProperty(zval *classReflection, zend_string *propertyName, zval *scope)
	{
		zv::Val classCacheKey = pt_class_reflection_get_cache_key(Z_OBJ_P(classReflection));
		if (UNEXPECTED(classCacheKey.isUndef())) return zv::Val();
		zv::Str cacheKey = zv::Str::copyOf(Z_STR_P(classCacheKey.raw()));

		bool isInClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), isInClass))) return zv::Val();
		if (isInClass) {
			zv::Val scopeClassReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(scopeClassReflection.isUndef())) return zv::Val();
			zv::Val scopeCacheKey = pt_class_reflection_get_cache_key(Z_OBJ_P(scopeClassReflection.raw()));
			if (UNEXPECTED(scopeCacheKey.isUndef())) return zv::Val();
			cacheKey = zv::Str::adopt(zend_strpprintf(0, "%s-%s", ZSTR_VAL(cacheKey.get()), Z_STRVAL_P(scopeCacheKey.raw())));
		}

		if (UNEXPECTED(!touchMemberCacheKey(cacheKey.get()))) return zv::Val();
		zval *cache = slot(slots::propertiesIncludingAnnotations);
		zval *cached = issetNested(cache, cacheKey.get(), propertyName);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val property = createProperty(classReflection, propertyName, scope, true);
		if (UNEXPECTED(property.isUndef())) return zv::Val();
		zv::Val result = zv::Val::copyOf(zv::Ref(property.raw()));
		setNested(slot(slots::propertiesIncludingAnnotations), cacheKey.get(), propertyName, std::move(property));
		return result;
	}

	/* Mirrors getNativeProperty(). */
	zv::Val getNativeProperty(zval *classReflection, zend_string *propertyName)
	{
		zv::Val classCacheKey = pt_class_reflection_get_cache_key(Z_OBJ_P(classReflection));
		if (UNEXPECTED(classCacheKey.isUndef())) return zv::Val();
		zv::Str cacheKey = zv::Str::copyOf(Z_STR_P(classCacheKey.raw()));
		if (UNEXPECTED(!touchMemberCacheKey(cacheKey.get()))) return zv::Val();

		zval *cached = issetNested(slot(slots::nativeProperties), cacheKey.get(), propertyName);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val outOfClassScope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(outOfClassScope.isUndef())) return zv::Val();
		zv::Val property = createProperty(classReflection, propertyName, outOfClassScope.raw(), false);
		if (UNEXPECTED(property.isUndef())) return zv::Val();
		zv::Val result = zv::Val::copyOf(zv::Ref(property.raw()));
		setNested(slot(slots::nativeProperties), cacheKey.get(), propertyName, std::move(property));
		return result;
	}

	/*
	 * `$value instanceof <ShadowedTypeClass>` — the native class entry
	 * first, and under the prefixed activation of the differential tests
	 * also the PHP twin that still carries the real name (the container's
	 * Type objects are the twins there). In a production run the native
	 * class carries the real name and the second lookup never runs.
	 */
	static bool instanceOfShadowed(zval *value, zend_class_entry *ce, const char *realName, size_t len)
	{
		if (Z_TYPE_P(value) != IS_OBJECT) return false;
		if (ce != NULL && instanceof_function(Z_OBJCE_P(value), ce)) return true;
		if (ce != NULL && zend_string_equals_cstr(ce->name, realName, len)) return false;
		zend_string *name = zend_string_init(realName, len, 0);
		zend_class_entry *twin = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
		zend_string_release(name);
		return twin != NULL && instanceof_function(Z_OBJCE_P(value), twin);
	}

	/*
	 * Class::method(...$args) by the class's REAL name: the native class in
	 * a production run, the PHP twin's under the prefixed activation of the
	 * differential tests. The singletons below all end up in typed
	 * parameters of PHP collaborators (PhpMethodReflection's TrinaryLogic
	 * maps, the factory's TemplateTypeMap, StaticType's positionVariance),
	 * which would refuse a PHPStanTurbo\* instance there. UNDEF = pending
	 * exception.
	 */
	static zv::Val kernelStatic(const char *className, size_t classLen, const char *lcmethod, size_t methodLen, uint32_t argc = 0, zval *argv = NULL)
	{
		zend_string *name = zend_string_init(className, classLen, 0);
		zend_class_entry *ce = zend_lookup_class(name);
		zend_string_release(name);
		if (UNEXPECTED(ce == NULL)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "phpstan_turbo: class %s not found", className);
			}
			return zv::Val();
		}
		zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcmethod, methodLen);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", className, lcmethod);
			return zv::Val();
		}
		zval result;
		zend_call_known_function(fn, NULL, ce, &result, argc, argv, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&result);
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	/*
	 * new <Class>(...$args) by the class's REAL name, for the same reason as
	 * kernelStatic(): the native class in a production run, the PHP twin
	 * under the prefixed activation — the Type instances built here end up
	 * in PHP collaborators that test them with `instanceof`
	 * (PhpPropertyReflection::hasNativeType() against MixedType), which a
	 * PHPStanTurbo\* instance would answer differently there. Every site is
	 * cold (member construction is memoized). UNDEF = pending exception.
	 */
	static zv::Val kernelNew(const char *className, size_t classLen, uint32_t argc = 0, zval *argv = NULL)
	{
		zend_string *name = zend_string_init(className, classLen, 0);
		zend_class_entry *ce = zend_lookup_class(name);
		zend_string_release(name);
		if (UNEXPECTED(ce == NULL)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "phpstan_turbo: class %s not found", className);
			}
			return zv::Val();
		}
		zval object;
		if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
		if (ce->constructor != NULL) {
			zend_call_known_instance_method(ce->constructor, Z_OBJ(object), NULL, argc, argv);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&object);
				return zv::Val();
			}
		}
		return zv::Val::adopt(object);
	}

	/* Class::method(...$args) by the real name, arguments spread from a PHP
	 * list (TypeCombinator::union(...$types)) */
	static zv::Val kernelStaticSpread(const char *className, size_t classLen, const char *lcmethod, size_t methodLen, HashTable *args)
	{
		uint32_t argc = zend_hash_num_elements(args);
		std::vector<zval> argv;
		argv.reserve(argc);
		for (zv::ArrayEntry entry : zv::TableRef(args)) {
			argv.push_back(*entry.value().raw());
		}
		return kernelStatic(className, classLen, lcmethod, methodLen, argc, argv.empty() ? NULL : argv.data());
	}

	/* TypehintHelper::decideTypeFromReflection($reflectionType, null,
	 * $selfClass) / ::decideType($type, $phpDocType) by the real name: the
	 * native helper builds its unions out of the native Type family, which
	 * the PHP twins next to it cannot describe under the prefixed
	 * activation (a native VerbosityLevel reaches a PHP Type::describe()).
	 * In a production run the real name IS the native class. */
	static zv::Val decideTypeFromReflection(zval *reflectionType, zval *selfClass)
	{
		zv::Args args{reflectionType, zv::null, selfClass};
		return kernelStatic(PT_LC("PHPStan\\Type\\TypehintHelper"), PT_LC("decidetypefromreflection"), 3, args);
	}

	static zv::Val mixedType() { return kernelNew(PT_LC("PHPStan\\Type\\MixedType")); }

	static zv::Val explicitMixedType()
	{
		zval isExplicit;
		ZVAL_TRUE(&isExplicit);
		return kernelNew(PT_LC("PHPStan\\Type\\MixedType"), 1, &isExplicit);
	}

	static zv::Val stringType() { return kernelNew(PT_LC("PHPStan\\Type\\StringType")); }

	static zv::Val constantStringType(zend_string *value)
	{
		zval arg;
		ZVAL_STR(&arg, value);
		return kernelNew(PT_LC("PHPStan\\Type\\Constant\\ConstantStringType"), 1, &arg);
	}

	static zv::Val enumCaseObjectType(zend_string *className, zend_string *caseName)
	{
		zv::Args args{className, caseName};
		return kernelNew(PT_LC("PHPStan\\Type\\Enum\\EnumCaseObjectType"), 2, args);
	}

	static zv::Val arrayType(zval *keyType, zval *itemType)
	{
		zv::Args args{keyType, itemType};
		return kernelNew(PT_LC("PHPStan\\Type\\ArrayType"), 2, args);
	}

	static zv::Val trinaryMaybe() { return kernelStatic(PT_LC("PHPStan\\TrinaryLogic"), PT_LC("createmaybe")); }
	static zv::Val trinaryNo() { return kernelStatic(PT_LC("PHPStan\\TrinaryLogic"), PT_LC("createno")); }
	static zv::Val trinaryFromBoolean(bool value)
	{
		return value
			? kernelStatic(PT_LC("PHPStan\\TrinaryLogic"), PT_LC("createyes"))
			: kernelStatic(PT_LC("PHPStan\\TrinaryLogic"), PT_LC("createno"));
	}
	static zv::Val varianceInvariant() { return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeVariance"), PT_LC("createinvariant")); }
	static zv::Val varianceCovariant() { return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeVariance"), PT_LC("createcovariant")); }
	static zv::Val varianceContravariant() { return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeVariance"), PT_LC("createcontravariant")); }
	static zv::Val templateTypeMapEmpty() { return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeMap"), PT_LC("createempty")); }

	static bool isMixedType(zval *value) { return instanceOfShadowed(value, pt_ce_mixed_type, PT_LC("PHPStan\\Type\\MixedType")); }
	static bool isTemplateMixedType(zval *value) { return instanceOfShadowed(value, pt_ce_template_mixed_type, PT_LC("PHPStan\\Type\\Generic\\TemplateMixedType")); }
	static bool isErrorType(zval *value) { return instanceOfShadowed(value, pt_ce_error_type, PT_LC("PHPStan\\Type\\ErrorType")); }
	static bool isNeverType(zval *value) { return instanceOfShadowed(value, pt_ce_never_type, PT_LC("PHPStan\\Type\\NeverType")); }
	static bool isUnionType(zval *value) { return instanceOfShadowed(value, pt_ce_union_type, PT_LC("PHPStan\\Type\\UnionType")); }

	/* $type->isSuperTypeOf($other)->yes(); false with `ok` cleared on a
	 * pending exception */
	static bool isSuperTypeOfYes(zval *type, zval *other, bool &ok)
	{
		zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
		if (UNEXPECTED(result.isUndef())) {
			ok = false;
			return false;
		}
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) {
			ok = false;
			return false;
		}
		ok = true;
		return value == PT_TRI_YES;
	}

	/* $type->isVoid()->yes() / ->isConstantArray()->yes() */
	static bool typeOpYes(zval *type, pt_type_op_id op, bool &ok)
	{
		zend_long value = pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
		if (UNEXPECTED(value < 0)) {
			ok = false;
			return false;
		}
		ok = true;
		return value == PT_TRI_YES;
	}

	/* the ?string a getDocComment() returns as string|false */
	static zv::Val docCommentOf(zval *reflection)
	{
		zv::Val docComment = pt_better_reflection_method_of_adapter(reflection) != NULL
			? pt_method_adapter_get_doc_comment(reflection)
			: pt_property_adapter_get_doc_comment(reflection);
		if (UNEXPECTED(docComment.isUndef())) return zv::Val();
		if (Z_TYPE_P(docComment.raw()) != IS_STRING) return zv::Val::null();
		return docComment;
	}

	/* $this->fileTypeMapper->getResolvedPhpDoc($fileName, $className, $traitName, $functionName, $docComment) */
	zv::Val getResolvedPhpDoc(zval *fileName, zval *className, zval *traitName, zval *functionName, zval *docComment)
	{
		zv::Args args{fileName, className, traitName, functionName, docComment};
		return call(slot(slots::fileTypeMapper), PT_LC("getresolvedphpdoc"), 5, args);
	}

	/* InitializerExprContext::fromClass($className, $fileName) */
	static zv::Val initializerExprContextFromClass(zval *className, zval *fileName)
	{
		return pt_initializer_expr_context_from_class(className, fileName);
	}

	/* $this->attributeReflectionFactory->fromNativeReflection($reflection->getAttributes(), $context) */
	zv::Val attributesOf(zval *reflection, zval *context)
	{
		zv::Val attributes = call(reflection, PT_LC("getattributes"));
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Args args{attributes.raw(), context};
		return call(slot(slots::attributeReflectionFactory), PT_LC("fromnativereflection"), 2, args);
	}

	/* the first of a @var tag list the twin picks: $varTags[0] when it is
	 * the only one, else $varTags[$propertyName]; NULL for neither */
	static zval *varTagFor(zval *varTags, zend_string *propertyName)
	{
		if (Z_TYPE_P(varTags) != IS_ARRAY) return NULL;
		zval *first = zend_hash_index_find(Z_ARRVAL_P(varTags), 0);
		if (first != NULL) {
			ZVAL_DEREF(first);
			if (Z_TYPE_P(first) != IS_NULL && zend_hash_num_elements(Z_ARRVAL_P(varTags)) == 1) return first;
		}
		return issetIn(varTags, propertyName);
	}

	/* Mirrors createProperty(). */
	zv::Val createProperty(zval *classReflection, zend_string *requestedPropertyName, zval *scope, bool includingAnnotations)
	{
		bool ok;
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		zval requestedNameArg;
		ZVAL_STR(&requestedNameArg, requestedPropertyName);
		zv::Val propertyReflection = call(nativeReflection.raw(), PT_LC("getproperty"), 1, &requestedNameArg);
		if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();

		zv::Str propertyNameStr = stringOf(pt_property_adapter_get_name(propertyReflection.raw()));
		if (UNEXPECTED(propertyNameStr.isNull())) return zv::Val();
		zend_string *propertyName = propertyNameStr.get();
		zval propertyNameArg;
		ZVAL_STR(&propertyNameArg, propertyName);

		zv::Str declaringClassNameStr = stringOf(pt_member_adapter_get_declaring_class_name(propertyReflection.raw()));
		if (UNEXPECTED(declaringClassNameStr.isNull())) return zv::Val();
		zval declaringClassNameArg;
		ZVAL_STR(&declaringClassNameArg, declaringClassNameStr.get());

		zv::Val declaringClassReflection = call(classReflection, PT_LC("getancestorwithclassname"), 1, &declaringClassNameArg);
		if (UNEXPECTED(declaringClassReflection.isUndef())) return zv::Val();
		if (Z_TYPE_P(declaringClassReflection.raw()) != IS_OBJECT) {
			zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Str message = ancestorMessage(declaringClassNameStr.get(), Z_STR_P(className.raw()));
			throwShouldNotHappenStr(message.get());
			return zv::Val();
		}
		zval *declaringClass = declaringClassReflection.raw();

		bool supportsEnums;
		if (UNEXPECTED(!pt_php_version_answer(slot(slots::phpVersion), PT_PHP_VERSION_SUPPORTS_ENUMS, supportsEnums))) return zv::Val();
		bool isNameProperty = zend_string_equals_literal(propertyName, "name");
		bool isUnitEnumInterfaceNameProperty = supportsEnums
			&& isNameProperty
			&& zend_string_equals_literal(declaringClassNameStr.get(), "UnitEnum");

		bool declaringIsEnum;
		if (UNEXPECTED(!pt_class_reflection_is_enum(Z_OBJ_P(declaringClass), declaringIsEnum))) return zv::Val();
		if (declaringIsEnum || isUnitEnumInterfaceNameProperty) {
			bool enumMemberProperty = isNameProperty;
			if (!enumMemberProperty) {
				bool isBackedEnum = callBool(declaringClass, PT_LC("isbackedenum"), 0, NULL, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				enumMemberProperty = isBackedEnum && zend_string_equals_literal(propertyName, "value");
			}
			if (enumMemberProperty) {
				zv::Val phpDocType;
				zv::Val nativeType;
				if (declaringIsEnum) {
					zv::Val enumCases = call(classReflection, PT_LC("getenumcases"));
					if (UNEXPECTED(!arrayResult(enumCases, "PHPStan\\Reflection\\ClassReflection::getEnumCases"))) return zv::Val();
					zv::Arr types = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(enumCases.raw())));
					for (zv::ArrayEntry entry : zv::ArrRef(enumCases.raw())) {
						if (isNameProperty) {
							zend_string *caseName = entry.stringKeyOrNull();
							zv::Str owned;
							if (caseName == NULL) {
								owned = zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
								caseName = owned.get();
							}
							zv::Val constantString = constantStringType(caseName);
							if (UNEXPECTED(constantString.isUndef())) return zv::Val();
							types.push(std::move(constantString));
							continue;
						}
						zv::Val backingValue = call(entry.value().raw(), PT_LC("getbackingvaluetype"));
						if (UNEXPECTED(backingValue.isUndef())) return zv::Val();
						if (Z_TYPE_P(backingValue.raw()) == IS_NULL) {
							throwShouldNotHappen();
							return zv::Val();
						}
						types.push(zv::Ref(backingValue.raw()));
					}
					phpDocType = kernelStaticSpread(PT_LC("PHPStan\\Type\\TypeCombinator"), PT_LC("union"), types.table());
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
					nativeType = mixedType();
					if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				} else {
					zv::Val ownedString = stringType();
					if (UNEXPECTED(ownedString.isUndef())) return zv::Val();
					zv::Val ownedNonFalsy = kernelNew(PT_LC("PHPStan\\Type\\Accessory\\AccessoryNonFalsyStringType"));
					if (UNEXPECTED(ownedNonFalsy.isUndef())) return zv::Val();
					zval inverse;
					ZVAL_TRUE(&inverse);
					zv::Val ownedDecimal = kernelNew(PT_LC("PHPStan\\Type\\Accessory\\AccessoryDecimalIntegerStringType"), 1, &inverse);
					if (UNEXPECTED(ownedDecimal.isUndef())) return zv::Val();
					zv::Args parts{ownedString.raw(), ownedNonFalsy.raw(), ownedDecimal.raw()};
					phpDocType = kernelStatic(PT_LC("PHPStan\\Type\\TypeCombinator"), PT_LC("intersect"), 3, parts);
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
					nativeType = stringType();
					if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				}

				zv::Val reflectionProperty = call(nativeReflection.raw(), PT_LC("getproperty"), 1, &propertyNameArg);
				if (UNEXPECTED(reflectionProperty.isUndef())) return zv::Val();
				zval args[20];
				ZVAL_COPY_VALUE(&args[0], declaringClass);
				ZVAL_NULL(&args[1]);
				ZVAL_COPY_VALUE(&args[2], nativeType.raw());
				ZVAL_COPY_VALUE(&args[3], phpDocType.raw());
				ZVAL_COPY_VALUE(&args[4], phpDocType.raw());
				ZVAL_COPY_VALUE(&args[5], reflectionProperty.raw());
				ZVAL_NULL(&args[6]);
				ZVAL_NULL(&args[7]);
				ZVAL_NULL(&args[8]);
				ZVAL_NULL(&args[9]);
				ZVAL_FALSE(&args[10]);
				ZVAL_FALSE(&args[11]);
				ZVAL_FALSE(&args[12]);
				ZVAL_FALSE(&args[13]);
				ZVAL_EMPTY_ARRAY(&args[14]);
				ZVAL_FALSE(&args[15]);
				ZVAL_TRUE(&args[16]);
				ZVAL_FALSE(&args[17]);
				ZVAL_FALSE(&args[18]);
				ZVAL_TRUE(&args[19]);
				return pt_php_property_reflection_new(args);
			}
		}

		zv::Val deprecation = call(slot(slots::deprecationProvider), PT_LC("getpropertydeprecation"), 1, propertyReflection.raw());
		if (UNEXPECTED(deprecation.isUndef())) return zv::Val();
		bool isDeprecated = Z_TYPE_P(deprecation.raw()) != IS_NULL;
		zv::Val deprecatedDescription = zv::Val::null();
		if (isDeprecated) {
			deprecatedDescription = call(deprecation.raw(), PT_LC("getdescription"));
			if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
		}
		bool isInternal = false;
		bool isReadOnlyByPhpDoc = callBool(classReflection, PT_LC("isimmutable"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		bool isFinal = callBool(classReflection, PT_LC("isfinal"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!isFinal) {
			if (UNEXPECTED(!pt_property_adapter_is_final(propertyReflection.raw(), isFinal))) return zv::Val();
		}
		bool isAllowedPrivateMutation = false;

		zv::Val docComment = docCommentOf(propertyReflection.raw());
		if (UNEXPECTED(docComment.isUndef())) return zv::Val();

		zv::Val phpDocType = zv::Val::null();
		zv::Val resolvedPhpDoc = zv::Val::null();
		zv::Val declaringTraitName = findPropertyTrait(propertyReflection.raw());
		if (UNEXPECTED(declaringTraitName.isUndef())) return zv::Val();
		zv::Val constructorName = zv::Val::null();
		bool isPromoted;
		if (UNEXPECTED(!pt_property_adapter_is_promoted(propertyReflection.raw(), isPromoted))) return zv::Val();
		if (isPromoted) {
			bool hasConstructor = callBool(declaringClass, PT_LC("hasconstructor"), 0, NULL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (hasConstructor) {
				zv::Val constructor = call(declaringClass, PT_LC("getconstructor"));
				if (UNEXPECTED(constructor.isUndef())) return zv::Val();
				constructorName = call(constructor.raw(), PT_LC("getname"));
				if (UNEXPECTED(constructorName.isUndef())) return zv::Val();
			}
		}

		if (Z_TYPE_P(constructorName.raw()) == IS_NULL) {
			zv::Args stubArgs{&declaringClassNameArg, &propertyNameArg};
			zv::Val currentResolvedPhpDoc = call(slot(slots::stubPhpDocProvider), PT_LC("findpropertyphpdoc"), 2, stubArgs);
			if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();
			if (Z_TYPE_P(currentResolvedPhpDoc.raw()) == IS_NULL && Z_TYPE_P(declaringTraitName.raw()) != IS_NULL) {
				ZVAL_COPY_VALUE(&stubArgs[0], declaringTraitName.raw());
				currentResolvedPhpDoc = call(slot(slots::stubPhpDocProvider), PT_LC("findpropertyphpdoc"), 2, stubArgs);
				if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();
			}
			if (Z_TYPE_P(currentResolvedPhpDoc.raw()) == IS_NULL && Z_TYPE_P(docComment.raw()) != IS_NULL) {
				zv::Val fileName = call(declaringClass, PT_LC("getfilename"));
				if (UNEXPECTED(fileName.isUndef())) return zv::Val();
				zval nullArg = {};
				ZVAL_NULL(&nullArg);
				currentResolvedPhpDoc = getResolvedPhpDoc(fileName.raw(), &declaringClassNameArg, declaringTraitName.raw(), &nullArg, docComment.raw());
				if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();
			}
			zv::Args resolveArgs{declaringClass, &propertyNameArg, currentResolvedPhpDoc.raw()};
			resolvedPhpDoc = call(slot(slots::phpDocInheritanceResolver), PT_LC("resolvephpdocforproperty"), 3, resolveArgs);
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		} else if (Z_TYPE_P(docComment.raw()) != IS_NULL) {
			zv::Val fileName = call(declaringClass, PT_LC("getfilename"));
			if (UNEXPECTED(fileName.isUndef())) return zv::Val();
			resolvedPhpDoc = getResolvedPhpDoc(fileName.raw(), &declaringClassNameArg, declaringTraitName.raw(), constructorName.raw(), docComment.raw());
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		}

		if (Z_TYPE_P(resolvedPhpDoc.raw()) != IS_NULL) {
			zv::Val varTags = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_VAR_TAGS);
			if (UNEXPECTED(varTags.isUndef())) return zv::Val();
			zval *varTag = varTagFor(varTags.raw(), propertyName);
			if (varTag != NULL) {
				phpDocType = call(varTag, PT_LC("gettype"));
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
			}

			if (Z_TYPE_P(phpDocType.raw()) != IS_NULL) {
				zv::Val activeTemplateTypeMap = call(declaringClass, PT_LC("getactivetemplatetypemap"));
				if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
				zv::Val callSiteVarianceMap = call(declaringClass, PT_LC("getcallsitevariancemap"));
				if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
				zv::Val invariant = varianceInvariant();
				if (UNEXPECTED(invariant.isUndef())) return zv::Val();
				phpDocType = pt_type_template_type_helper_resolve_template_types(phpDocType.raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), invariant.raw(), false);
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
			}

			if (!isDeprecated) {
				zv::Val deprecatedTag = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_DEPRECATED_TAG);
				if (UNEXPECTED(deprecatedTag.isUndef())) return zv::Val();
				if (Z_TYPE_P(deprecatedTag.raw()) != IS_NULL) {
					deprecatedDescription = call(deprecatedTag.raw(), PT_LC("getmessage"));
					if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
				} else {
					deprecatedDescription = zv::Val::null();
				}
				isDeprecated = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_DEPRECATED, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
			}
			isInternal = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_INTERNAL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!isReadOnlyByPhpDoc) {
				isReadOnlyByPhpDoc = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_READ_ONLY, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
			}
			if (!isFinal) {
				isFinal = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_FINAL, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
			}
			isAllowedPrivateMutation = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_ALLOWED_PRIVATE_MUTATION, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
		}

		if (Z_TYPE_P(phpDocType.raw()) == IS_NULL && Z_TYPE_P(constructorName.raw()) != IS_NULL) {
			zv::Val constructor = call(declaringClass, PT_LC("getconstructor"));
			if (UNEXPECTED(constructor.isUndef())) return zv::Val();
			zv::Val resolvedConstructorPhpDoc = call(constructor.raw(), PT_LC("getresolvedphpdoc"));
			if (UNEXPECTED(resolvedConstructorPhpDoc.isUndef())) return zv::Val();
			if (Z_TYPE_P(resolvedConstructorPhpDoc.raw()) != IS_NULL) {
				zv::Val paramTags = pt_resolved_php_doc_block_call(resolvedConstructorPhpDoc.raw(), PT_RPD_GET_PARAM_TAGS);
				if (UNEXPECTED(paramTags.isUndef())) return zv::Val();
				zv::Str reflectionName = stringOf(pt_property_adapter_get_name(propertyReflection.raw()));
				if (UNEXPECTED(reflectionName.isNull())) return zv::Val();
				zval *paramTag = issetIn(paramTags.raw(), reflectionName.get());
				if (paramTag != NULL) {
					phpDocType = call(paramTag, PT_LC("gettype"));
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
				}
			}
		}

		if (Z_TYPE_P(phpDocType.raw()) == IS_NULL && Z_TYPE_P(slot(slots::inferPrivatePropertyTypeFromConstructor)) == IS_TRUE) {
			zv::Val fileName = call(declaringClass, PT_LC("getfilename"));
			if (UNEXPECTED(fileName.isUndef())) return zv::Val();
			bool eligible = Z_TYPE_P(fileName.raw()) != IS_NULL;
			if (eligible) {
				if (UNEXPECTED(!pt_property_adapter_is_private(propertyReflection.raw(), eligible))) return zv::Val();
			}
			if (eligible) {
				eligible = !isPromoted;
			}
			if (eligible) {
				bool hasType = callBool(propertyReflection.raw(), PT_LC("hastype"), 0, NULL, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				eligible = !hasType;
			}
			if (eligible) {
				eligible = callBool(declaringClass, PT_LC("hasconstructor"), 0, NULL, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
			}
			if (eligible) {
				zv::Val constructor = call(declaringClass, PT_LC("getconstructor"));
				if (UNEXPECTED(constructor.isUndef())) return zv::Val();
				zv::Val constructorDeclaringClass = call(constructor.raw(), PT_LC("getdeclaringclass"));
				if (UNEXPECTED(constructorDeclaringClass.isUndef())) return zv::Val();
				zv::Val constructorDeclaringName = pt_class_reflection_get_name(Z_OBJ_P(constructorDeclaringClass.raw()));
				if (UNEXPECTED(constructorDeclaringName.isUndef())) return zv::Val();
				zv::Val declaringName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass));
				if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
				if (zend_string_equals(Z_STR_P(constructorDeclaringName.raw()), Z_STR_P(declaringName.raw()))) {
					zv::Str reflectionName = stringOf(pt_property_adapter_get_name(propertyReflection.raw()));
					if (UNEXPECTED(reflectionName.isNull())) return zv::Val();
					phpDocType = inferPrivatePropertyType(reflectionName.get(), constructor.raw());
					if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
				}
			}
		}

		zv::Val reflectionType = call(propertyReflection.raw(), PT_LC("gettype"));
		if (UNEXPECTED(reflectionType.isUndef())) return zv::Val();
		zv::Val nativeType = decideTypeFromReflection(reflectionType.raw(), declaringClass);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();

		zv::Val declaringTrait = zv::Val::null();
		zv::Val reflectionProvider = call(slot(slots::reflectionProviderProvider), PT_LC("getreflectionprovider"));
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		if (Z_TYPE_P(declaringTraitName.raw()) != IS_NULL) {
			bool hasClass;
			if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider.raw()), declaringTraitName.raw(), hasClass))) return zv::Val();
			if (hasClass) {
				declaringTrait = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), declaringTraitName.raw());
				if (UNEXPECTED(declaringTrait.isUndef())) return zv::Val();
			}
		}

		zv::Val getHook = zv::Val::null();
		zv::Val setHook = zv::Val::null();
		zv::Val betterReflection = pt_reflection_adapter_get_better_reflection(propertyReflection.raw());
		if (UNEXPECTED(betterReflection.isUndef())) return zv::Val();
		static const char *const hookKinds[] = { "get", "set" };
		for (int kind = 0; kind < 2; kind++) {
			zval hookKind;
			ZVAL_STRING(&hookKind, hookKinds[kind]);
			zv::Val ownedKind = zv::Val::adopt(hookKind);
			bool hasHook = callBool(betterReflection.raw(), PT_LC("hashook"), 1, ownedKind.raw(), ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!hasHook) continue;
			zv::Val betterReflectionHook = call(betterReflection.raw(), PT_LC("gethook"), 1, ownedKind.raw());
			if (UNEXPECTED(betterReflectionHook.isUndef())) return zv::Val();
			if (Z_TYPE_P(betterReflectionHook.raw()) == IS_NULL) {
				throwShouldNotHappen();
				return zv::Val();
			}
			zv::Val adapterMethod = pt_type_new(PT_CLASS_ADAPTER_REFLECTION_METHOD, 1, betterReflectionHook.raw());
			if (UNEXPECTED(adapterMethod.isUndef())) return zv::Val();
			zv::Val hook = createUserlandMethodReflection(declaringClass, declaringClass, adapterMethod.raw(), declaringTraitName.raw());
			if (UNEXPECTED(hook.isUndef())) return zv::Val();

			if (Z_TYPE_P(phpDocType.raw()) != IS_NULL) {
				zv::Val variant = call(hook.raw(), PT_LC("getonlyvariant"));
				if (UNEXPECTED(variant.isUndef())) return zv::Val();
				if (kind == 0) {
					zv::Val returnType = call(variant.raw(), PT_LC("getphpdocreturntype"));
					if (UNEXPECTED(returnType.isUndef())) return zv::Val();
					if (isMixedType(returnType.raw()) && !isTemplateMixedType(returnType.raw())) {
						bool explicitMixed = callBool(returnType.raw(), PT_LC("isexplicitmixed"), 0, NULL, ok);
						if (UNEXPECTED(!ok)) return zv::Val();
						if (!explicitMixed) {
							zv::Val changed = call(hook.raw(), PT_LC("changepropertygethookphpdoctype"), 1, phpDocType.raw());
							if (UNEXPECTED(changed.isUndef())) return zv::Val();
							hook = std::move(changed);
						}
					}
				} else {
					zv::Val parameters = call(variant.raw(), PT_LC("getparameters"));
					if (UNEXPECTED(parameters.isUndef())) return zv::Val();
					zval *parameter = Z_TYPE_P(parameters.raw()) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(parameters.raw()), 0) : NULL;
					if (parameter != NULL) {
						ZVAL_DEREF(parameter);
					}
					if (parameter != NULL && Z_TYPE_P(parameter) != IS_NULL) {
						zv::Val parameterPhpDocType = call(parameter, PT_LC("getphpdoctype"));
						if (UNEXPECTED(parameterPhpDocType.isUndef())) return zv::Val();
						if (isMixedType(parameterPhpDocType.raw()) && !isTemplateMixedType(parameterPhpDocType.raw())) {
							bool explicitMixed = callBool(parameterPhpDocType.raw(), PT_LC("isexplicitmixed"), 0, NULL, ok);
							if (UNEXPECTED(!ok)) return zv::Val();
							if (!explicitMixed) {
								zv::Val parameterName = call(parameter, PT_LC("getname"));
								if (UNEXPECTED(parameterName.isUndef())) return zv::Val();
								zv::Args changeArgs{parameterName.raw(), phpDocType.raw()};
								zv::Val changed = call(hook.raw(), PT_LC("changepropertysethookphpdoctype"), 2, changeArgs);
								if (UNEXPECTED(changed.isUndef())) return zv::Val();
								hook = std::move(changed);
							}
						}
					}
				}
			}

			if (kind == 0) {
				getHook = std::move(hook);
			} else {
				setHook = std::move(hook);
			}
		}

		// a property the phar build made public for its inlined getters keeps its source visibility here
		bool isPrivate;
		if (UNEXPECTED(!pt_property_adapter_is_private(propertyReflection.raw(), isPrivate))) return zv::Val();
		bool isPublic;
		if (UNEXPECTED(!pt_property_adapter_is_public(propertyReflection.raw(), isPublic))) return zv::Val();
		if (isPublic) {
			bool hasPrivateAttribute;
			if (UNEXPECTED(!hasAttribute(propertyReflection.raw(), PT_CLASS_PRIVATE_PROPERTY_ATTRIBUTE, hasPrivateAttribute))) return zv::Val();
			if (hasPrivateAttribute) {
				isPrivate = true;
				isPublic = false;
			} else {
				bool hasProtectedAttribute;
				if (UNEXPECTED(!hasAttribute(propertyReflection.raw(), PT_CLASS_PROTECTED_PROPERTY_ATTRIBUTE, hasProtectedAttribute))) return zv::Val();
				if (hasProtectedAttribute) {
					isPublic = false;
				}
			}
		}

		zv::Val declaringName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass));
		if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
		zv::Val declaringFileName = call(declaringClass, PT_LC("getfilename"));
		if (UNEXPECTED(declaringFileName.isUndef())) return zv::Val();
		zv::Val context = initializerExprContextFromClass(declaringName.raw(), declaringFileName.raw());
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Val attributes = attributesOf(propertyReflection.raw(), context.raw());
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();

		zval args[20];
		ZVAL_COPY_VALUE(&args[0], declaringClass);
		ZVAL_COPY_VALUE(&args[1], declaringTrait.raw());
		ZVAL_COPY_VALUE(&args[2], nativeType.raw());
		ZVAL_COPY_VALUE(&args[3], phpDocType.raw());
		ZVAL_COPY_VALUE(&args[4], phpDocType.raw());
		ZVAL_COPY_VALUE(&args[5], propertyReflection.raw());
		ZVAL_COPY_VALUE(&args[6], getHook.raw());
		ZVAL_COPY_VALUE(&args[7], setHook.raw());
		ZVAL_COPY_VALUE(&args[8], resolvedPhpDoc.raw());
		ZVAL_COPY_VALUE(&args[9], deprecatedDescription.raw());
		ZVAL_BOOL(&args[10], isDeprecated);
		ZVAL_BOOL(&args[11], isInternal);
		ZVAL_BOOL(&args[12], isReadOnlyByPhpDoc);
		ZVAL_BOOL(&args[13], isAllowedPrivateMutation);
		ZVAL_COPY_VALUE(&args[14], attributes.raw());
		ZVAL_BOOL(&args[15], isFinal);
		ZVAL_TRUE(&args[16]);
		ZVAL_TRUE(&args[17]);
		ZVAL_BOOL(&args[18], isPrivate);
		ZVAL_BOOL(&args[19], isPublic);
		zv::Val nativeProperty = pt_php_property_reflection_new(args);
		if (UNEXPECTED(nativeProperty.isUndef())) return zv::Val();

		zv::Val annotationProperty = annotationPropertyFor(classReflection, propertyName, scope, includingAnnotations, declaringIsEnum, propertyReflection.raw(), nativeProperty.raw());
		if (UNEXPECTED(annotationProperty.isUndef())) return zv::Val();
		if (Z_TYPE_P(annotationProperty.raw()) == IS_NULL) return nativeProperty;

		/* the annotation property wins: the twin rebuilds the reflection
		 * from its types and the native property's resolved PHPDoc */
		zv::Val annotationReadableType = call(annotationProperty.raw(), PT_LC("getreadabletype"));
		if (UNEXPECTED(annotationReadableType.isUndef())) return zv::Val();
		bool superTypeOfReadable = isSuperTypeOfYes(nativeType.raw(), annotationReadableType.raw(), ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		bool widenToMixed = superTypeOfReadable;
		if (!widenToMixed) {
			bool canRead = callBool(scope, PT_LC("canreadproperty"), 1, nativeProperty.raw(), ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			widenToMixed = !canRead;
		}
		if (widenToMixed) {
			nativeType = mixedType();
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		}

		zv::Val annotationDeclaringClass = call(annotationProperty.raw(), PT_LC("getdeclaringclass"));
		if (UNEXPECTED(annotationDeclaringClass.isUndef())) return zv::Val();
		zv::Val annotationWritableType = call(annotationProperty.raw(), PT_LC("getwritabletype"));
		if (UNEXPECTED(annotationWritableType.isUndef())) return zv::Val();
		zv::Val nativeResolvedPhpDoc = call(nativeProperty.raw(), PT_LC("getresolvedphpdoc"));
		if (UNEXPECTED(nativeResolvedPhpDoc.isUndef())) return zv::Val();
		zv::Val annotationContext = initializerExprContextFromClass(declaringName.raw(), declaringFileName.raw());
		if (UNEXPECTED(annotationContext.isUndef())) return zv::Val();
		zv::Val annotationAttributes = attributesOf(propertyReflection.raw(), annotationContext.raw());
		if (UNEXPECTED(annotationAttributes.isUndef())) return zv::Val();
		bool annotationReadable = callBool(annotationProperty.raw(), PT_LC("isreadable"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		bool annotationWritable = callBool(annotationProperty.raw(), PT_LC("iswritable"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();

		zval annotationArgs[20];
		ZVAL_COPY_VALUE(&annotationArgs[0], annotationDeclaringClass.raw());
		ZVAL_COPY_VALUE(&annotationArgs[1], declaringTrait.raw());
		ZVAL_COPY_VALUE(&annotationArgs[2], nativeType.raw());
		ZVAL_COPY_VALUE(&annotationArgs[3], annotationReadableType.raw());
		ZVAL_COPY_VALUE(&annotationArgs[4], annotationWritableType.raw());
		ZVAL_COPY_VALUE(&annotationArgs[5], propertyReflection.raw());
		ZVAL_COPY_VALUE(&annotationArgs[6], getHook.raw());
		ZVAL_COPY_VALUE(&annotationArgs[7], setHook.raw());
		ZVAL_COPY_VALUE(&annotationArgs[8], nativeResolvedPhpDoc.raw());
		ZVAL_COPY_VALUE(&annotationArgs[9], deprecatedDescription.raw());
		ZVAL_BOOL(&annotationArgs[10], isDeprecated);
		ZVAL_BOOL(&annotationArgs[11], isInternal);
		ZVAL_BOOL(&annotationArgs[12], isReadOnlyByPhpDoc);
		ZVAL_BOOL(&annotationArgs[13], isAllowedPrivateMutation);
		ZVAL_COPY_VALUE(&annotationArgs[14], annotationAttributes.raw());
		ZVAL_BOOL(&annotationArgs[15], isFinal);
		ZVAL_BOOL(&annotationArgs[16], annotationReadable);
		ZVAL_BOOL(&annotationArgs[17], annotationWritable);
		ZVAL_FALSE(&annotationArgs[18]);
		ZVAL_TRUE(&annotationArgs[19]);
		return pt_php_property_reflection_new(annotationArgs);
	}

	/* count($reflection->getAttributes(<class-map class>)) > 0 */
	bool hasAttribute(zval *reflection, int classIdx, bool &out)
	{
		zend_class_entry *ce = pt_class(classIdx);
		if (UNEXPECTED(ce == NULL)) return false;
		zval nameArg;
		ZVAL_STR(&nameArg, ce->name);
		zv::Val attributes = call(reflection, PT_LC("getattributes"), 1, &nameArg);
		if (UNEXPECTED(attributes.isUndef())) return false;
		out = Z_TYPE_P(attributes.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(attributes.raw())) > 0;
		return true;
	}

	/*
	 * The annotation property createProperty() prefers over the native one:
	 * the twin's guard plus the hierarchy-distance comparison. PHP null
	 * means "keep the native property", UNDEF a pending exception.
	 */
	zv::Val annotationPropertyFor(zval *classReflection, zend_string *propertyName, zval *scope, bool includingAnnotations, bool declaringIsEnum, zval *propertyReflection, zval *nativeProperty)
	{
		bool ok;
		if (!includingAnnotations || declaringIsEnum) return zv::Val::null();
		bool isStatic;
		if (UNEXPECTED(!pt_property_adapter_is_static(propertyReflection, isStatic))) return zv::Val();
		if (isStatic) return zv::Val::null();
		bool allowsDynamicProperties = callBool(classReflection, PT_LC("allowsdynamicproperties"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!allowsDynamicProperties) {
			bool canRead = callBool(scope, PT_LC("canreadproperty"), 1, nativeProperty, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!canRead) return zv::Val::null();
		}

		zval propertyNameArg;
		ZVAL_STR(&propertyNameArg, propertyName);
		zv::Args extensionArgs{classReflection, &propertyNameArg};
		bool hasAnnotationProperty = callBool(slot(slots::annotationsPropertiesClassReflectionExtension), PT_LC("hasproperty"), 2, extensionArgs, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!hasAnnotationProperty) return zv::Val::null();

		/* the adapter's declaring class name; createProperty() looked
		 * $declaringClassReflection up with it through
		 * getAncestorWithClassName(), which answers out of an ancestor map
		 * keyed by the very same name — so this is also
		 * $declaringClassReflection->getName(), which the twin compares the
		 * scope's class against below */
		zv::Str propertyDeclaringClassName = stringOf(pt_member_adapter_get_declaring_class_name(propertyReflection));
		if (UNEXPECTED(propertyDeclaringClassName.isNull())) return zv::Val();

		bool nativeIsPublic = callBool(nativeProperty, PT_LC("ispublic"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!nativeIsPublic) {
			bool isInClass;
			if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), isInClass))) return zv::Val();
			if (isInClass) {
				zv::Val scopeClassReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
				if (UNEXPECTED(scopeClassReflection.isUndef())) return zv::Val();
				zv::Val scopeClassName = pt_class_reflection_get_name(Z_OBJ_P(scopeClassReflection.raw()));
				if (UNEXPECTED(scopeClassName.isUndef())) return zv::Val();
				if (zend_string_equals(Z_STR_P(scopeClassName.raw()), propertyDeclaringClassName.get())) return zv::Val::null();
			}
		}

		zv::Val hierarchyDistances = call(classReflection, PT_LC("getclasshierarchydistances"));
		if (UNEXPECTED(hierarchyDistances.isUndef())) return zv::Val();
		zv::Val annotationProperty = call(slot(slots::annotationsPropertiesClassReflectionExtension), PT_LC("getproperty"), 2, extensionArgs);
		if (UNEXPECTED(annotationProperty.isUndef())) return zv::Val();
		zv::Val annotationDeclaringClass = call(annotationProperty.raw(), PT_LC("getdeclaringclass"));
		if (UNEXPECTED(annotationDeclaringClass.isUndef())) return zv::Val();
		zv::Val annotationDeclaringClassName = pt_class_reflection_get_name(Z_OBJ_P(annotationDeclaringClass.raw()));
		if (UNEXPECTED(annotationDeclaringClassName.isUndef())) return zv::Val();
		zval *annotationDistance = issetIn(hierarchyDistances.raw(), Z_STR_P(annotationDeclaringClassName.raw()));
		if (annotationDistance == NULL) {
			throwShouldNotHappen();
			return zv::Val();
		}

		zv::Str distanceDeclaringClass = zv::Str::copyOf(propertyDeclaringClassName.get());
		zv::Val propertyTrait = findPropertyTrait(propertyReflection);
		if (UNEXPECTED(propertyTrait.isUndef())) return zv::Val();
		if (Z_TYPE_P(propertyTrait.raw()) != IS_NULL) {
			distanceDeclaringClass = zv::Str::copyOf(Z_STR_P(propertyTrait.raw()));
		}
		zval *nativeDistance = issetIn(hierarchyDistances.raw(), distanceDeclaringClass.get());
		if (nativeDistance == NULL) {
			throwShouldNotHappen();
			return zv::Val();
		}

		if (zval_get_long(annotationDistance) <= zval_get_long(nativeDistance)) return annotationProperty;
		return zv::Val::null();
	}

	/* $adapter->getMethod($name) — the memo entry wrapped the way the
	 * adapter wraps it; the adapter's method (which raises its
	 * ReflectionException for a missing one) otherwise */
	static zv::Val adapterGetMethod(zval *adapter, zend_string *methodName)
	{
		zval *memo = adapterMemo(adapter, true);
		if (EXPECTED(memo != NULL) && ZSTR_LEN(methodName) != 0) {
			zval *found = memoFindLowercased(memo, methodName);
			if (EXPECTED(found != NULL)) return pt_type_new(PT_CLASS_ADAPTER_REFLECTION_METHOD, 1, found);
		}
		zval name;
		ZVAL_STR(&name, methodName);
		return call(adapter, PT_LC("getmethod"), 1, &name);
	}

	/* Mirrors hasMethod(). false with `ok` cleared = pending exception */
	[[nodiscard]] bool hasMethod(zval *classReflection, zend_string *methodName, bool &ok)
	{
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) {
			ok = false;
			return false;
		}
		zval *memo = adapterMemo(nativeReflection.raw(), true);
		if (EXPECTED(memo != NULL)) {
			ok = true;
			return ZSTR_LEN(methodName) != 0 && memoFindLowercased(memo, methodName) != NULL;
		}
		zval name;
		ZVAL_STR(&name, methodName);
		return callBool(nativeReflection.raw(), PT_LC("hasmethod"), 1, &name, ok);
	}

	/* Mirrors getMethod(). */
	zv::Val getMethod(zval *classReflection, zend_string *methodName)
	{
		zv::Val classCacheKey = pt_class_reflection_get_cache_key(Z_OBJ_P(classReflection));
		if (UNEXPECTED(classCacheKey.isUndef())) return zv::Val();
		zv::Str cacheKey = zv::Str::copyOf(Z_STR_P(classCacheKey.raw()));
		if (UNEXPECTED(!touchMemberCacheKey(cacheKey.get()))) return zv::Val();
		zval *cached = issetNested(slot(slots::methodsIncludingAnnotations), cacheKey.get(), methodName);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		zv::Val nativeMethodReflection = adapterGetMethod(nativeReflection.raw(), methodName);
		if (UNEXPECTED(nativeMethodReflection.isUndef())) return zv::Val();
		zv::Str realName = stringOf(pt_method_adapter_get_name(nativeMethodReflection.raw()));
		if (UNEXPECTED(realName.isNull())) return zv::Val();

		zval *cachedByRealName = issetNested(slot(slots::methodsIncludingAnnotations), cacheKey.get(), realName.get());
		if (cachedByRealName != NULL) return zv::Val::copyOf(zv::Ref(cachedByRealName));

		zv::Val method = createMethod(classReflection, methodName, nativeMethodReflection.raw(), true);
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		zv::Val result = zv::Val::copyOf(zv::Ref(method.raw()));
		if (!zend_string_equals(realName.get(), methodName)) {
			setNested(slot(slots::methodsIncludingAnnotations), cacheKey.get(), realName.get(), zv::Val::copyOf(zv::Ref(method.raw())));
			setNested(slot(slots::methodsIncludingAnnotations), cacheKey.get(), methodName, std::move(method));
		} else {
			setNested(slot(slots::methodsIncludingAnnotations), cacheKey.get(), realName.get(), std::move(method));
		}
		return result;
	}

	/* Mirrors hasNativeMethod(): $this->hasMethod() — the class is final,
	 * so the twin's `$this->` is this body */
	bool hasNativeMethod(zval *classReflection, zend_string *methodName, bool &ok)
	{
		return hasMethod(classReflection, methodName, ok);
	}

	/* Mirrors getNativeMethod(). */
	zv::Val getNativeMethod(zval *classReflection, zend_string *methodName)
	{
		zv::Val classCacheKey = pt_class_reflection_get_cache_key(Z_OBJ_P(classReflection));
		if (UNEXPECTED(classCacheKey.isUndef())) return zv::Val();
		zv::Str cacheKey = zv::Str::copyOf(Z_STR_P(classCacheKey.raw()));
		if (UNEXPECTED(!touchMemberCacheKey(cacheKey.get()))) return zv::Val();
		zval *cached = issetNested(slot(slots::nativeMethods), cacheKey.get(), methodName);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection));
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		bool ok;
		bool exists = hasMethod(classReflection, methodName, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!exists) {
			throwShouldNotHappen();
			return zv::Val();
		}
		zv::Val nativeMethodReflection = adapterGetMethod(nativeReflection.raw(), methodName);
		if (UNEXPECTED(nativeMethodReflection.isUndef())) return zv::Val();
		zv::Str realName = stringOf(pt_method_adapter_get_name(nativeMethodReflection.raw()));
		if (UNEXPECTED(realName.isNull())) return zv::Val();
		zval *cachedByRealName = issetNested(slot(slots::nativeMethods), cacheKey.get(), realName.get());
		if (cachedByRealName != NULL) return zv::Val::copyOf(zv::Ref(cachedByRealName));

		zv::Val method = createMethod(classReflection, methodName, nativeMethodReflection.raw(), false);
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		zv::Val result = zv::Val::copyOf(zv::Ref(method.raw()));
		setNested(slot(slots::nativeMethods), cacheKey.get(), realName.get(), std::move(method));
		return result;
	}

	/* Mirrors findPropertyTrait(): the trait name or PHP null; UNDEF =
	 * pending exception */
	zv::Val findPropertyTrait(zval *propertyReflection)
	{
		return findMemberTrait(propertyReflection);
	}

	/* Mirrors findMethodTrait(). */
	zv::Val findMethodTrait(zval *methodReflection)
	{
		return findMemberTrait(methodReflection);
	}

	/* The shared body of findPropertyTrait() and findMethodTrait(): the
	 * better-reflection declaring class's name when the member comes from a
	 * trait used elsewhere, PHP null otherwise. */
	static zv::Val findMemberTrait(zval *memberReflection)
	{
		zv::Val betterReflection = pt_reflection_adapter_get_better_reflection(memberReflection);
		if (UNEXPECTED(betterReflection.isUndef())) return zv::Val();
		zv::Val declaringClass = pt_better_reflection_member_get_declaring_class(betterReflection.raw());
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		bool isTrait;
		if (UNEXPECTED(!pt_better_reflection_class_is_trait(declaringClass.raw(), isTrait))) return zv::Val();
		if (!isTrait) return zv::Val::null();
		bool adapterIsTrait;
		zv::Val adapterDeclaringClass = adapterDeclaringClassOf(memberReflection, betterReflection.raw(), adapterIsTrait);
		if (UNEXPECTED(adapterDeclaringClass.isUndef())) return zv::Val();
		zv::Str declaringClassName = stringOf(pt_better_reflection_class_get_name(declaringClass.raw()));
		if (UNEXPECTED(declaringClassName.isNull())) return zv::Val();
		if (adapterIsTrait) {
			zv::Str adapterDeclaringClassName = adapterDeclaringClassNameOf(memberReflection, adapterDeclaringClass.raw());
			if (UNEXPECTED(adapterDeclaringClassName.isNull())) return zv::Val();
			if (zend_string_equals(adapterDeclaringClassName.get(), declaringClassName.get())) return zv::Val::null();
		}
		return zv::Val::adoptString(declaringClassName.take());
	}

	/* $memberReflection->getDeclaringClass() and its isTrait(): for exactly a
	 * method / property adapter — `new ReflectionClass($this->betterReflection…
	 * ->getImplementingClass())` — the wrapped implementing class, asked
	 * directly; the adapter's getter otherwise. UNDEF = pending exception */
	static zv::Val adapterDeclaringClassOf(zval *memberReflection, zval *betterReflection, bool &isTrait)
	{
		if (EXPECTED(pt_reflection_adapter_is_member_adapter(memberReflection))) {
			zv::Val implementingClass = pt_better_reflection_member_get_implementing_class(betterReflection);
			if (UNEXPECTED(implementingClass.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_better_reflection_class_is_trait(implementingClass.raw(), isTrait))) return zv::Val();
			return implementingClass;
		}
		zv::Val adapterDeclaringClass = call(memberReflection, PT_LC("getdeclaringclass"));
		if (UNEXPECTED(adapterDeclaringClass.isUndef())) return zv::Val();
		bool ok;
		isTrait = callBool(adapterDeclaringClass.raw(), PT_LC("istrait"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		return adapterDeclaringClass;
	}

	/* ->getName() of what adapterDeclaringClassOf() returned */
	static zv::Str adapterDeclaringClassNameOf(zval *memberReflection, zval *declaringClass)
	{
		if (EXPECTED(pt_reflection_adapter_is_member_adapter(memberReflection))) return stringOf(pt_better_reflection_class_get_name(declaringClass));
		return callString(declaringClass, PT_LC("getname"));
	}

	/* $this->signatureMapProvider->$method($className, $methodName) as a bool */
	bool signatureMapBool(const char *lcname, size_t len, zval *className, zval *methodName, bool &ok)
	{
		zv::Args args{className, methodName};
		return callBool(slot(slots::signatureMapProvider), lcname, len, 2, args, ok);
	}

	/* Mirrors createMethod(). */
	zv::Val createMethod(zval *classReflection, zend_string *requestedMethodName, zval *methodReflection, bool includingAnnotations)
	{
		bool ok;
		zv::Str methodNameStr = stringOf(pt_method_adapter_get_name(methodReflection));
		if (UNEXPECTED(methodNameStr.isNull())) return zv::Val();
		zval methodNameArg;
		ZVAL_STR(&methodNameArg, methodNameStr.get());

		if (includingAnnotations) {
			zv::Args extensionArgs{classReflection, &methodNameArg};
			bool hasAnnotationMethod = callBool(slot(slots::annotationsMethodsClassReflectionExtension), PT_LC("hasmethod"), 2, extensionArgs, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (hasAnnotationMethod) {
				zv::Val hierarchyDistances = call(classReflection, PT_LC("getclasshierarchydistances"));
				if (UNEXPECTED(hierarchyDistances.isUndef())) return zv::Val();
				zv::Val annotationMethod = call(slot(slots::annotationsMethodsClassReflectionExtension), PT_LC("getmethod"), 2, extensionArgs);
				if (UNEXPECTED(annotationMethod.isUndef())) return zv::Val();
				zv::Val annotationDeclaringClass = call(annotationMethod.raw(), PT_LC("getdeclaringclass"));
				if (UNEXPECTED(annotationDeclaringClass.isUndef())) return zv::Val();
				zv::Val annotationDeclaringClassName = pt_class_reflection_get_name(Z_OBJ_P(annotationDeclaringClass.raw()));
				if (UNEXPECTED(annotationDeclaringClassName.isUndef())) return zv::Val();
				zval *annotationDistance = issetIn(hierarchyDistances.raw(), Z_STR_P(annotationDeclaringClassName.raw()));
				if (annotationDistance == NULL) {
					throwShouldNotHappen();
					return zv::Val();
				}

				zv::Str distanceDeclaringClass = stringOf(pt_member_adapter_get_declaring_class_name(methodReflection));
				if (UNEXPECTED(distanceDeclaringClass.isNull())) return zv::Val();
				zv::Val methodTrait = findMethodTrait(methodReflection);
				if (UNEXPECTED(methodTrait.isUndef())) return zv::Val();
				if (Z_TYPE_P(methodTrait.raw()) != IS_NULL) {
					distanceDeclaringClass = zv::Str::copyOf(Z_STR_P(methodTrait.raw()));
				}
				zval *methodDistance = issetIn(hierarchyDistances.raw(), distanceDeclaringClass.get());
				if (methodDistance == NULL) {
					throwShouldNotHappen();
					return zv::Val();
				}
				if (zval_get_long(annotationDistance) <= zval_get_long(methodDistance)) return annotationMethod;
			}

			return getNativeMethod(classReflection, requestedMethodName);
		}

		zv::Str declaringClassNameStr = stringOf(pt_member_adapter_get_declaring_class_name(methodReflection));
		if (UNEXPECTED(declaringClassNameStr.isNull())) return zv::Val();
		zval declaringClassNameArg;
		ZVAL_STR(&declaringClassNameArg, declaringClassNameStr.get());

		zv::Val declaringClassReflection = call(classReflection, PT_LC("getancestorwithclassname"), 1, &declaringClassNameArg);
		if (UNEXPECTED(declaringClassReflection.isUndef())) return zv::Val();
		if (Z_TYPE_P(declaringClassReflection.raw()) != IS_OBJECT) {
			zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Str message = ancestorMessage(declaringClassNameStr.get(), Z_STR_P(className.raw()));
			throwShouldNotHappenStr(message.get());
			return zv::Val();
		}
		zval *declaringClass = declaringClassReflection.raw();

		bool declaringIsEnum;
		if (UNEXPECTED(!pt_class_reflection_is_enum(Z_OBJ_P(declaringClass), declaringIsEnum))) return zv::Val();
		if (declaringIsEnum) {
			zv::Val declaringName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass));
			if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
			if (!zend_string_equals_literal(Z_STR_P(declaringName.raw()), "UnitEnum")) {
				zv::Str lowered = zv::Str::adopt(zend_string_tolower(methodNameStr.get()));
				if (zend_string_equals_literal(lowered.get(), "cases")) {
					zv::Val builder = pt_constant_array_type_builder_create_empty();
					if (UNEXPECTED(builder.isUndef())) return zv::Val();
					zv::Val enumCases = call(classReflection, PT_LC("getenumcases"));
					if (UNEXPECTED(!arrayResult(enumCases, "PHPStan\\Reflection\\ClassReflection::getEnumCases"))) return zv::Val();
					zv::Val ownerName = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
					if (UNEXPECTED(ownerName.isUndef())) return zv::Val();
					for (zv::ArrayEntry entry : zv::ArrRef(enumCases.raw())) {
						zend_string *caseName = entry.stringKeyOrNull();
						zv::Str owned;
						if (caseName == NULL) {
							owned = zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
							caseName = owned.get();
						}
						zv::Val ownedCaseType = enumCaseObjectType(Z_STR_P(ownerName.raw()), caseName);
						if (UNEXPECTED(ownedCaseType.isUndef())) return zv::Val();
						if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), NULL, ownedCaseType.raw()))) return zv::Val();
					}
					zv::Val array = pt_constant_array_type_builder_get_array(builder.raw());
					if (UNEXPECTED(array.isUndef())) return zv::Val();
					zv::Args args{declaringClass, array.raw()};
					return pt_type_new(PT_CLASS_ENUM_CASES_METHOD_REFLECTION, 2, args);
				}
			}
		}

		bool isBuiltin = callBool(declaringClass, PT_LC("isbuiltin"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		bool signatureMapped = false;
		if (isBuiltin || declaringIsEnum) {
			signatureMapped = signatureMapBool(PT_LC("hasmethodsignature"), &declaringClassNameArg, &methodNameArg, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
		}
		if (signatureMapped) return createNativeMethod(declaringClass, methodReflection, &declaringClassNameArg, &methodNameArg);

		zv::Val methodTrait = findMethodTrait(methodReflection);
		if (UNEXPECTED(methodTrait.isUndef())) return zv::Val();
		return createUserlandMethodReflection(declaringClass, declaringClass, methodReflection, methodTrait.raw());
	}

	/*
	 * The signature-map branch of createMethod(): builds the variants from
	 * the signature map, merged with the stub/inherited PHPDoc, and returns
	 * a NativeMethodReflection.
	 */
	zv::Val createNativeMethod(zval *declaringClass, zval *methodReflection, zval *declaringClassNameArg, zval *methodNameArg)
	{
		bool ok;
		zv::Arr positionalVariants = zv::Arr::create(1);
		zv::Val namedVariants = zv::Val::null();
		zv::Val throwType = zv::Val::null();
		zv::Val asserts = kernelStatic(PT_LC("PHPStan\\Reflection\\Assertions"), PT_LC("createempty"));
		if (UNEXPECTED(asserts.isUndef())) return zv::Val();
		bool acceptsNamedArguments = true;
		zv::Val selfOutType = zv::Val::null();
		zv::Val phpDocComment = zv::Val::null();

		/* the twin's ?bool $isPure */
		int isPure = -1;
		bool hasMetadata = signatureMapBool(PT_LC("hasmethodmetadata"), declaringClassNameArg, methodNameArg, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (hasMetadata) {
			zv::Args metadataArgs{declaringClassNameArg, methodNameArg};
			zv::Val metadata = call(slot(slots::signatureMapProvider), PT_LC("getmethodmetadata"), 2, metadataArgs);
			if (UNEXPECTED(metadata.isUndef())) return zv::Val();
			bool hasSideEffects = true;
			zval *stored = Z_TYPE_P(metadata.raw()) == IS_ARRAY ? zend_hash_str_find(Z_ARRVAL_P(metadata.raw()), PT_LC("hasSideEffects")) : NULL;
			if (stored != NULL) {
				ZVAL_DEREF(stored);
				if (Z_TYPE_P(stored) != IS_NULL) {
					hasSideEffects = zend_is_true(stored);
				}
			}
			isPure = hasSideEffects ? 0 : 1;
		}

		zv::Args signatureArgs{declaringClassNameArg, methodNameArg, methodReflection};
		zv::Val signaturesResult = call(slot(slots::signatureMapProvider), PT_LC("getmethodsignatures"), 3, signatureArgs);
		if (UNEXPECTED(!arrayResult(signaturesResult, "PHPStan\\Reflection\\SignatureMap\\SignatureMapProvider::getMethodSignatures"))) return zv::Val();

		/* the twin reads $currentResolvedPhpDoc after the loops: the value
		 * the last inner iteration left, or null when none ran */
		zv::Val lastResolvedPhpDoc = zv::Val::null();

		for (zv::ArrayEntry group : zv::ArrRef(signaturesResult.raw())) {
			zend_string *signatureType = group.stringKeyOrNull();
			zval *methodSignatures = group.value().raw();
			ZVAL_DEREF(methodSignatures);
			if (Z_TYPE_P(methodSignatures) == IS_NULL || Z_TYPE_P(methodSignatures) != IS_ARRAY) continue;
			bool isNamed = signatureType != NULL && zend_string_equals_literal(signatureType, "named");
			uint32_t signatureCount = zend_hash_num_elements(Z_ARRVAL_P(methodSignatures));
			zv::Arr variants = zv::Arr::create(signatureCount);

			zv::Val ownedSignatures = zv::Val::copyOf(zv::Ref(methodSignatures));
			for (zv::ArrayEntry signatureEntry : zv::ArrRef(ownedSignatures.raw())) {
				zval *methodSignature = signatureEntry.value().raw();
				ZVAL_DEREF(methodSignature);

				zv::Val signatureParameters = call(methodSignature, PT_LC("getparameters"));
				if (UNEXPECTED(signatureParameters.isUndef())) return zv::Val();
				zv::Arr phpDocParameterNameMapping = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(signatureParameters.raw())));
				for (zv::ArrayEntry parameterEntry : zv::ArrRef(signatureParameters.raw())) {
					zv::Str parameterName = callString(parameterEntry.value().raw(), PT_LC("getname"));
					if (UNEXPECTED(parameterName.isNull())) return zv::Val();
					phpDocParameterNameMapping.set(parameterName.get(), zv::Val::string(parameterName.get()));
				}
				zv::Arr phpDocParameterTypes = zv::Arr::create(0);
				zv::Val phpDocReturnType = zv::Val::null();
				zv::Arr phpDocParameterOutTypes = zv::Arr::create(0);
				zv::Val immediatelyInvokedCallableParameters = zv::Val(zv::Arr::empty());
				zv::Val closureThisParameters = zv::Val(zv::Arr::empty());
				zv::Val currentResolvedPhpDoc = zv::Val::null();
				zv::Val phpDocDeclaringClass = zv::Val::copyOf(zv::Ref(declaringClass));
				bool phpDocFromStubs = false;

				if (signatureCount == 1) {
					zv::Arr positionalParameterNames = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(signatureParameters.raw())));
					for (zv::ArrayEntry parameterEntry : zv::ArrRef(signatureParameters.raw())) {
						zv::Str parameterName = callString(parameterEntry.value().raw(), PT_LC("getname"));
						if (UNEXPECTED(parameterName.isNull())) return zv::Val();
						positionalParameterNames.push(zv::Val::string(parameterName.get()));
					}
					zv::Val stubPhpDocPair = findMethodPhpDocIncludingAncestors(declaringClass, declaringClass, Z_STR_P(methodNameArg), positionalParameterNames.raw());
					if (UNEXPECTED(stubPhpDocPair.isUndef())) return zv::Val();
					if (Z_TYPE_P(stubPhpDocPair.raw()) == IS_ARRAY) {
						zval *resolved = zend_hash_index_find(Z_ARRVAL_P(stubPhpDocPair.raw()), 0);
						zval *owner = zend_hash_index_find(Z_ARRVAL_P(stubPhpDocPair.raw()), 1);
						if (resolved != NULL && owner != NULL) {
							currentResolvedPhpDoc = zv::Val::copyOf(zv::Ref(resolved));
							phpDocDeclaringClass = zv::Val::copyOf(zv::Ref(owner));
							phpDocFromStubs = true;
						}
					}
				}

				zv::Val methodDocComment = docCommentOf(methodReflection);
				if (UNEXPECTED(methodDocComment.isUndef())) return zv::Val();
				if (Z_TYPE_P(currentResolvedPhpDoc.raw()) == IS_NULL && Z_TYPE_P(methodDocComment.raw()) != IS_NULL) {
					zv::Val methodFileName = call(methodReflection, PT_LC("getfilename"));
					if (UNEXPECTED(methodFileName.isUndef())) return zv::Val();
					zval fileNameArg = {};
					if (Z_TYPE_P(methodFileName.raw()) == IS_STRING) {
						ZVAL_COPY_VALUE(&fileNameArg, methodFileName.raw());
					} else {
						ZVAL_NULL(&fileNameArg);
					}
					zval nullArg = {};
					ZVAL_NULL(&nullArg);
					zv::Val fileResolved = getResolvedPhpDoc(&fileNameArg, declaringClassNameArg, &nullArg, methodNameArg, methodDocComment.raw());
					if (UNEXPECTED(fileResolved.isUndef())) return zv::Val();
					zv::Val reflectionParameters = call(methodReflection, PT_LC("getparameters"));
					if (UNEXPECTED(reflectionParameters.isUndef())) return zv::Val();
					zv::Arr reflectionParameterNames = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(reflectionParameters.raw())));
					for (zv::ArrayEntry parameterEntry : zv::ArrRef(reflectionParameters.raw())) {
						zv::Str parameterName = callString(parameterEntry.value().raw(), PT_LC("getname"));
						if (UNEXPECTED(parameterName.isNull())) return zv::Val();
						reflectionParameterNames.push(zv::Val::string(parameterName.get()));
					}
					zv::Args resolveArgs{declaringClass, methodNameArg, fileResolved.raw(), reflectionParameterNames.raw()};
					currentResolvedPhpDoc = call(slot(slots::phpDocInheritanceResolver), PT_LC("resolvephpdocformethod"), 4, resolveArgs);
					if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();
				}

				if (Z_TYPE_P(currentResolvedPhpDoc.raw()) != IS_NULL) {
					zv::Val templateTypeMap = call(phpDocDeclaringClass.raw(), PT_LC("getactivetemplatetypemap"));
					if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
					zv::Val callSiteVarianceMap = call(phpDocDeclaringClass.raw(), PT_LC("getcallsitevariancemap"));
					if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
					zv::Val returnTag = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_RETURN_TAG);
					if (UNEXPECTED(returnTag.isUndef())) return zv::Val();
					zv::Val immediatelyInvoked = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_PARAMS_IMMEDIATELY_INVOKED_CALLABLE);
					if (UNEXPECTED(immediatelyInvoked.isUndef())) return zv::Val();
					immediatelyInvokedCallableParameters = trinaryMapOf(immediatelyInvoked.raw());
					if (UNEXPECTED(immediatelyInvokedCallableParameters.isUndef())) return zv::Val();
					if (Z_TYPE_P(returnTag.raw()) != IS_NULL && signatureCount == 1) {
						zv::Val tagType = call(returnTag.raw(), PT_LC("gettype"));
						if (UNEXPECTED(tagType.isUndef())) return zv::Val();
						zv::Val covariantVal = varianceCovariant();
						if (UNEXPECTED(covariantVal.isUndef())) return zv::Val();
						zval *covariant = covariantVal.raw();
						phpDocReturnType = pt_type_template_type_helper_resolve_template_types(tagType.raw(), templateTypeMap.raw(), callSiteVarianceMap.raw(), covariant, false);
						if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
					}

					zv::Val closureThisTags = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_PARAM_CLOSURE_THIS_TAGS);
					if (UNEXPECTED(closureThisTags.isUndef())) return zv::Val();
					closureThisParameters = tagTypeMapOf(closureThisTags.raw());
					if (UNEXPECTED(closureThisParameters.isUndef())) return zv::Val();

					zv::Val paramTags = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_PARAM_TAGS);
					if (UNEXPECTED(paramTags.isUndef())) return zv::Val();
					zv::Val contravariantVal = varianceContravariant();
					if (UNEXPECTED(contravariantVal.isUndef())) return zv::Val();
					zval *contravariant = contravariantVal.raw();
					for (zv::ArrayEntry tagEntry : zv::ArrRef(paramTags.raw())) {
						zend_string *name = tagEntry.stringKeyOrNull();
						if (name == NULL) continue;
						zv::Val tagType = call(tagEntry.value().raw(), PT_LC("gettype"));
						if (UNEXPECTED(tagType.isUndef())) return zv::Val();
						zv::Val resolved = pt_type_template_type_helper_resolve_template_types(tagType.raw(), templateTypeMap.raw(), callSiteVarianceMap.raw(), contravariant, false);
						if (UNEXPECTED(resolved.isUndef())) return zv::Val();
						phpDocParameterTypes.set(name, std::move(resolved));
					}

					zv::Val throwsTag = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_THROWS_TAG);
					if (UNEXPECTED(throwsTag.isUndef())) return zv::Val();
					if (Z_TYPE_P(throwsTag.raw()) != IS_NULL) {
						throwType = call(throwsTag.raw(), PT_LC("gettype"));
						if (UNEXPECTED(throwType.isUndef())) return zv::Val();
					}

					asserts = kernelStatic(PT_LC("PHPStan\\Reflection\\Assertions"), PT_LC("createfromresolvedphpdocblock"), 1, currentResolvedPhpDoc.raw());
					if (UNEXPECTED(asserts.isUndef())) return zv::Val();
					acceptsNamedArguments = resolvedPhpDocBool(currentResolvedPhpDoc.raw(), PT_RPD_ACCEPTS_NAMED_ARGUMENTS, ok);
					if (UNEXPECTED(!ok)) return zv::Val();
					if (isPure < 0) {
						/* isPure() is ?bool: `??=` leaves $isPure null when the
						 * block says nothing */
						zv::Val pure = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_IS_PURE);
						if (UNEXPECTED(pure.isUndef())) return zv::Val();
						if (Z_TYPE_P(pure.raw()) != IS_NULL) {
							isPure = zend_is_true(pure.raw()) ? 1 : 0;
						}
					}

					zv::Val selfOutTag = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_SELF_OUT_TAG);
					if (UNEXPECTED(selfOutTag.isUndef())) return zv::Val();
					if (Z_TYPE_P(selfOutTag.raw()) != IS_NULL) {
						selfOutType = call(selfOutTag.raw(), PT_LC("gettype"));
						if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
					}

					zv::Val paramOutTags = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_PARAM_OUT_TAGS);
					if (UNEXPECTED(paramOutTags.isUndef())) return zv::Val();
					zv::Val covariantVal = varianceCovariant();
					if (UNEXPECTED(covariantVal.isUndef())) return zv::Val();
					zval *covariant = covariantVal.raw();
					for (zv::ArrayEntry tagEntry : zv::ArrRef(paramOutTags.raw())) {
						zend_string *name = tagEntry.stringKeyOrNull();
						if (name == NULL) continue;
						zv::Val tagType = call(tagEntry.value().raw(), PT_LC("gettype"));
						if (UNEXPECTED(tagType.isUndef())) return zv::Val();
						zv::Val resolved = pt_type_template_type_helper_resolve_template_types(tagType.raw(), templateTypeMap.raw(), callSiteVarianceMap.raw(), covariant, false);
						if (UNEXPECTED(resolved.isUndef())) return zv::Val();
						phpDocParameterOutTypes.set(name, std::move(resolved));
					}

					bool hasPhpDocString = resolvedPhpDocBool(currentResolvedPhpDoc.raw(), PT_RPD_HAS_PHP_DOC_STRING, ok);
					if (UNEXPECTED(!ok)) return zv::Val();
					if (hasPhpDocString) {
						phpDocComment = pt_resolved_php_doc_block_call(currentResolvedPhpDoc.raw(), PT_RPD_GET_PHP_DOC_STRING);
						if (UNEXPECTED(phpDocComment.isUndef())) return zv::Val();
					}

					if (!phpDocFromStubs) {
						zv::Val reflectionParameters = call(methodReflection, PT_LC("getparameters"));
						if (UNEXPECTED(reflectionParameters.isUndef())) return zv::Val();
						zend_ulong index = 0;
						for (zv::ArrayEntry parameterEntry : zv::ArrRef(reflectionParameters.raw())) {
							zval *signatureParameter = zend_hash_index_find(Z_ARRVAL_P(signatureParameters.raw()), index);
							index++;
							if (signatureParameter == NULL) continue;
							ZVAL_DEREF(signatureParameter);
							zv::Str signatureName = callString(signatureParameter, PT_LC("getname"));
							if (UNEXPECTED(signatureName.isNull())) return zv::Val();
							zv::Str reflectionName = callString(parameterEntry.value().raw(), PT_LC("getname"));
							if (UNEXPECTED(reflectionName.isNull())) return zv::Val();
							phpDocParameterNameMapping.set(signatureName.get(), zv::Val::string(reflectionName.get()));
						}
					}
				}

				zv::Val variant = createNativeMethodVariant(declaringClassNameArg, methodNameArg, methodSignature, phpDocParameterTypes.raw(), phpDocReturnType.raw(), phpDocParameterNameMapping.raw(), phpDocParameterOutTypes.raw(), immediatelyInvokedCallableParameters.raw(), closureThisParameters.raw(), phpDocFromStubs, !isNamed);
				if (UNEXPECTED(variant.isUndef())) return zv::Val();
				variants.push(std::move(variant));
				lastResolvedPhpDoc = std::move(currentResolvedPhpDoc);
			}

			if (isNamed) {
				/* the twin's `$variantsByType[$signatureType][] = …` only
				 * creates the key when a signature was actually built, so an
				 * empty group leaves getNamedArgumentsVariants() null */
				if (zend_hash_num_elements(variants.table()) > 0) {
					namedVariants = zv::Val(std::move(variants));
				}
			} else if (signatureType != NULL && zend_string_equals_literal(signatureType, "positional")) {
				positionalVariants = std::move(variants);
			}
		}

		if (isPure < 0) {
			zv::Val classResolvedPhpDoc = call(declaringClass, PT_LC("getresolvedphpdoc"));
			if (UNEXPECTED(classResolvedPhpDoc.isUndef())) return zv::Val();
			if (Z_TYPE_P(classResolvedPhpDoc.raw()) != IS_NULL) {
				bool allPure = resolvedPhpDocBool(classResolvedPhpDoc.raw(), PT_RPD_ARE_ALL_METHODS_PURE, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				if (allPure) {
					isPure = 1;
				} else {
					bool allImpure = resolvedPhpDocBool(classResolvedPhpDoc.raw(), PT_RPD_ARE_ALL_METHODS_IMPURE, ok);
					if (UNEXPECTED(!ok)) return zv::Val();
					if (allImpure) {
						isPure = 0;
					}
				}
			}
		}

		zv::Val reflectionProvider = call(slot(slots::reflectionProviderProvider), PT_LC("getreflectionprovider"));
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		zv::Val hasSideEffects = isPure < 0 ? trinaryMaybe() : trinaryFromBoolean(isPure != 1);
		if (UNEXPECTED(hasSideEffects.isUndef())) return zv::Val();
		zval contextNull;
		ZVAL_NULL(&contextNull);
		zv::Val context = pt_initializer_expr_context_from_class_method(declaringClassNameArg, &contextNull, methodNameArg, &contextNull);
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Val attributes = attributesOf(methodReflection, context.raw());
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();

		zval args[13];
		ZVAL_COPY_VALUE(&args[0], reflectionProvider.raw());
		ZVAL_COPY_VALUE(&args[1], declaringClass);
		ZVAL_COPY_VALUE(&args[2], methodReflection);
		ZVAL_COPY_VALUE(&args[3], lastResolvedPhpDoc.raw());
		ZVAL_COPY_VALUE(&args[4], positionalVariants.raw());
		ZVAL_COPY_VALUE(&args[5], namedVariants.raw());
		ZVAL_COPY_VALUE(&args[6], hasSideEffects.raw());
		ZVAL_COPY_VALUE(&args[7], throwType.raw());
		ZVAL_COPY_VALUE(&args[8], asserts.raw());
		ZVAL_BOOL(&args[9], acceptsNamedArguments);
		ZVAL_COPY_VALUE(&args[10], selfOutType.raw());
		ZVAL_COPY_VALUE(&args[11], phpDocComment.raw());
		ZVAL_COPY_VALUE(&args[12], attributes.raw());
		return pt_type_new(PT_CLASS_NATIVE_METHOD_REFLECTION, 13, args);
	}

	/* array_map(static fn (bool $immediate) => TrinaryLogic::createFromBoolean($immediate), $map) */
	static zv::Val trinaryMapOf(zval *map)
	{
		if (Z_TYPE_P(map) != IS_ARRAY) return zv::Val(zv::Arr::empty());
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(map)));
		for (zv::ArrayEntry entry : zv::ArrRef(map)) {
			zv::Val trinary = trinaryFromBoolean(zend_is_true(entry.value().raw()));
			if (UNEXPECTED(trinary.isUndef())) return zv::Val();
			zend_string *key = entry.stringKeyOrNull();
			if (key == NULL) {
				result.push(std::move(trinary));
				continue;
			}
			result.set(key, std::move(trinary));
		}
		return zv::Val(std::move(result));
	}

	/* array_map(static fn ($tag) => $tag->getType(), $tags) */
	static zv::Val tagTypeMapOf(zval *tags)
	{
		if (Z_TYPE_P(tags) != IS_ARRAY) return zv::Val(zv::Arr::empty());
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(tags)));
		for (zv::ArrayEntry entry : zv::ArrRef(tags)) {
			zv::Val type = call(entry.value().raw(), PT_LC("gettype"));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zend_string *key = entry.stringKeyOrNull();
			if (key == NULL) {
				result.push(std::move(type));
				continue;
			}
			result.set(key, std::move(type));
		}
		return zv::Val(std::move(result));
	}

	/* TypehintHelper::decideType($type, $phpDocType) — by the real name,
	 * see decideTypeFromReflection() */
	static zv::Val decideType(zval *type, zval *phpDocType)
	{
		zv::Args args{type, phpDocType};
		return kernelStatic(PT_LC("PHPStan\\Type\\TypehintHelper"), PT_LC("decidetype"), 2, args);
	}

	/* Mirrors createNativeMethodVariant(). */
	zv::Val createNativeMethodVariant(zval *declaringClassName, zval *methodName, zval *methodSignature, zval *phpDocParameterTypes, zval *phpDocReturnType, zval *phpDocParameterNameMapping, zval *phpDocParameterOutTypes, zval *immediatelyInvokedCallableParameters, zval *closureThisParameters, bool phpDocFromStubs, bool usePhpDocParameterNames)
	{
		bool ok;
		zv::Val signatureParameters = call(methodSignature, PT_LC("getparameters"));
		if (UNEXPECTED(signatureParameters.isUndef())) return zv::Val();
		zv::Arr parameters = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(signatureParameters.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(signatureParameters.raw())) {
			zval *parameterSignature = entry.value().raw();
			ZVAL_DEREF(parameterSignature);
			zv::Str signatureName = callString(parameterSignature, PT_LC("getname"));
			if (UNEXPECTED(signatureName.isNull())) return zv::Val();
			zval *mapped = keyIn(phpDocParameterNameMapping, signatureName.get());
			zv::Str phpDocParameterName = mapped != NULL && Z_TYPE_P(mapped) == IS_STRING
				? zv::Str::copyOf(Z_STR_P(mapped))
				: zv::Str::copyOf(signatureName.get());

			zv::Val signatureType = call(parameterSignature, PT_LC("gettype"));
			if (UNEXPECTED(signatureType.isUndef())) return zv::Val();
			zv::Val type;
			zv::Val phpDocType;
			zval *storedPhpDocType = issetIn(phpDocParameterTypes, phpDocParameterName.get());
			if (storedPhpDocType != NULL) {
				phpDocType = zv::Val::copyOf(zv::Ref(storedPhpDocType));
				if (phpDocFromStubs) {
					type = zv::Val::copyOf(zv::Ref(storedPhpDocType));
				} else {
					type = decideType(signatureType.raw(), storedPhpDocType);
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				}
			}

			zv::Val parameterOutType;
			zval *storedOutType = issetIn(phpDocParameterOutTypes, phpDocParameterName.get());
			if (storedOutType != NULL) {
				parameterOutType = zv::Val::copyOf(zv::Ref(storedOutType));
			}

			zv::Val immediatelyInvoked;
			zval *storedImmediate = issetIn(immediatelyInvokedCallableParameters, phpDocParameterName.get());
			if (storedImmediate != NULL) {
				immediatelyInvoked = zv::Val::copyOf(zv::Ref(storedImmediate));
			} else {
				immediatelyInvoked = trinaryMaybe();
				if (UNEXPECTED(immediatelyInvoked.isUndef())) return zv::Val();
			}

			zv::Val closureThisType = zv::Val::null();
			zval *storedClosureThis = issetIn(closureThisParameters, phpDocParameterName.get());
			if (storedClosureThis != NULL) {
				closureThisType = zv::Val::copyOf(zv::Ref(storedClosureThis));
			}

			bool isOptional = callBool(parameterSignature, PT_LC("isoptional"), 0, NULL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			zv::Val nativeType = call(parameterSignature, PT_LC("getnativetype"));
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
			zv::Val passedByReference = call(parameterSignature, PT_LC("passedbyreference"));
			if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
			bool isVariadic = callBool(parameterSignature, PT_LC("isvariadic"), 0, NULL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			zv::Val defaultValue = call(parameterSignature, PT_LC("getdefaultvalue"));
			if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
			zv::Val outType = call(parameterSignature, PT_LC("getouttype"));
			if (UNEXPECTED(outType.isUndef())) return zv::Val();
			zv::Args allowedConstantsArgs{declaringClassName, methodName, signatureName.get()};
			zv::Val allowedConstants = call(slot(slots::allowedConstantsMapProvider), PT_LC("getformethodparameter"), 3, allowedConstantsArgs);
			if (UNEXPECTED(allowedConstants.isUndef())) return zv::Val();
			zv::Val pureUnlessCallableIsImpure = trinaryNo();
			if (UNEXPECTED(pureUnlessCallableIsImpure.isUndef())) return zv::Val();
			zv::Val mixedPhpDocType;
			if (phpDocType.isUndef()) {
				mixedPhpDocType = mixedType();
				if (UNEXPECTED(mixedPhpDocType.isUndef())) return zv::Val();
			}

			zval args[14];
			if (usePhpDocParameterNames) {
				ZVAL_STR(&args[0], phpDocParameterName.get());
			} else {
				ZVAL_STR(&args[0], signatureName.get());
			}
			ZVAL_BOOL(&args[1], isOptional);
			ZVAL_COPY_VALUE(&args[2], type.isUndef() ? signatureType.raw() : type.raw());
			ZVAL_COPY_VALUE(&args[3], phpDocType.isUndef() ? mixedPhpDocType.raw() : phpDocType.raw());
			ZVAL_COPY_VALUE(&args[4], nativeType.raw());
			ZVAL_COPY_VALUE(&args[5], passedByReference.raw());
			ZVAL_BOOL(&args[6], isVariadic);
			ZVAL_COPY_VALUE(&args[7], defaultValue.raw());
			ZVAL_COPY_VALUE(&args[8], parameterOutType.isUndef() ? outType.raw() : parameterOutType.raw());
			ZVAL_COPY_VALUE(&args[9], immediatelyInvoked.raw());
			ZVAL_COPY_VALUE(&args[10], closureThisType.raw());
			ZVAL_EMPTY_ARRAY(&args[11]);
			ZVAL_COPY_VALUE(&args[12], allowedConstants.raw());
			ZVAL_COPY_VALUE(&args[13], pureUnlessCallableIsImpure.raw());
			zv::Val parameter = pt_extended_native_parameter_reflection_new(14, args);
			if (UNEXPECTED(parameter.isUndef())) return zv::Val();
			parameters.push(std::move(parameter));
		}

		zv::Val signatureReturnType = call(methodSignature, PT_LC("getreturntype"));
		if (UNEXPECTED(signatureReturnType.isUndef())) return zv::Val();
		zv::Val returnType;
		if (phpDocFromStubs && Z_TYPE_P(phpDocReturnType) != IS_NULL) {
			returnType = zv::Val::copyOf(zv::Ref(phpDocReturnType));
		} else {
			returnType = decideType(signatureReturnType.raw(), phpDocReturnType);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		}

		zv::Val ownedEmptyMap = templateTypeMapEmpty();
		if (UNEXPECTED(ownedEmptyMap.isUndef())) return zv::Val();
		bool isVariadicSignature = callBool(methodSignature, PT_LC("isvariadic"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		zv::Val nativeReturnType = call(methodSignature, PT_LC("getnativereturntype"));
		if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
		zv::Val mixedReturnType;
		if (Z_TYPE_P(phpDocReturnType) == IS_NULL) {
			mixedReturnType = mixedType();
			if (UNEXPECTED(mixedReturnType.isUndef())) return zv::Val();
		}

		zval variantArgs[7];
		ZVAL_COPY_VALUE(&variantArgs[0], ownedEmptyMap.raw());
		ZVAL_NULL(&variantArgs[1]);
		ZVAL_COPY_VALUE(&variantArgs[2], parameters.raw());
		ZVAL_BOOL(&variantArgs[3], isVariadicSignature);
		ZVAL_COPY_VALUE(&variantArgs[4], returnType.raw());
		ZVAL_COPY_VALUE(&variantArgs[5], Z_TYPE_P(phpDocReturnType) == IS_NULL ? mixedReturnType.raw() : phpDocReturnType);
		ZVAL_COPY_VALUE(&variantArgs[6], nativeReturnType.raw());
		return pt_extended_function_variant_new(7, variantArgs);
	}

	/* array_map(static fn (ReflectionParameter $p): string => $p->getName(), $reflection->getParameters()) */
	static zv::Val parameterNamesOf(zval *methodReflection)
	{
		return pt_method_adapter_get_parameter_names(methodReflection);
	}

	/* Mirrors createUserlandMethodReflection(). */
	zv::Val createUserlandMethodReflection(zval *fileDeclaringClass, zval *actualDeclaringClass, zval *methodReflection, zval *declaringTraitName)
	{
		bool ok;
		zv::Val deprecation = call(slot(slots::deprecationProvider), PT_LC("getmethoddeprecation"), 1, methodReflection);
		if (UNEXPECTED(deprecation.isUndef())) return zv::Val();
		bool isDeprecated = Z_TYPE_P(deprecation.raw()) != IS_NULL;
		zv::Val deprecatedDescription = zv::Val::null();
		if (isDeprecated) {
			deprecatedDescription = call(deprecation.raw(), PT_LC("getdescription"));
			if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
		}

		zv::Str methodNameStr = stringOf(pt_method_adapter_get_name(methodReflection));
		if (UNEXPECTED(methodNameStr.isNull())) return zv::Val();
		zval methodNameArg;
		ZVAL_STR(&methodNameArg, methodNameStr.get());

		zv::Val parameterNames = parameterNamesOf(methodReflection);
		if (UNEXPECTED(parameterNames.isUndef())) return zv::Val();
		zv::Val currentResolvedPhpDoc = zv::Val::null();
		zv::Val stubPhpDocPair = findMethodPhpDocIncludingAncestors(fileDeclaringClass, fileDeclaringClass, methodNameStr.get(), parameterNames.raw());
		if (UNEXPECTED(stubPhpDocPair.isUndef())) return zv::Val();
		zv::Val phpDocBlockClassReflection = zv::Val::copyOf(zv::Ref(fileDeclaringClass));

		zv::Val betterReflection = pt_reflection_adapter_get_better_reflection(methodReflection);
		if (UNEXPECTED(betterReflection.isUndef())) return zv::Val();
		zv::Val methodDeclaringClass = pt_better_reflection_member_get_declaring_class(betterReflection.raw());
		if (UNEXPECTED(methodDeclaringClass.isUndef())) return zv::Val();

		if (Z_TYPE_P(stubPhpDocPair.raw()) == IS_NULL) {
			bool isTrait;
			if (UNEXPECTED(!pt_better_reflection_class_is_trait(methodDeclaringClass.raw(), isTrait))) return zv::Val();
			if (isTrait) {
				bool adapterIsTrait;
				zv::Val adapterDeclaringClass = adapterDeclaringClassOf(methodReflection, betterReflection.raw(), adapterIsTrait);
				if (UNEXPECTED(adapterDeclaringClass.isUndef())) return zv::Val();
				zv::Str betterName = stringOf(pt_better_reflection_class_get_name(methodDeclaringClass.raw()));
				if (UNEXPECTED(betterName.isNull())) return zv::Val();
				zv::Str adapterName = adapterDeclaringClassNameOf(methodReflection, adapterDeclaringClass.raw());
				if (UNEXPECTED(adapterName.isNull())) return zv::Val();
				if (!adapterIsTrait || !zend_string_equals(betterName.get(), adapterName.get())) {
					zv::Val reflectionProvider = call(slot(slots::reflectionProviderProvider), PT_LC("getreflectionprovider"));
					if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
					zval betterNameArg;
					ZVAL_STR(&betterNameArg, betterName.get());
					zv::Val traitClass = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), &betterNameArg);
					if (UNEXPECTED(traitClass.isUndef())) return zv::Val();
					zv::Val reflectionProvider2 = call(slot(slots::reflectionProviderProvider), PT_LC("getreflectionprovider"));
					if (UNEXPECTED(reflectionProvider2.isUndef())) return zv::Val();
					zval adapterNameArg;
					ZVAL_STR(&adapterNameArg, adapterName.get());
					zv::Val implementingClass = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider2.raw()), &adapterNameArg);
					if (UNEXPECTED(implementingClass.isUndef())) return zv::Val();
					zv::Val traitParameterNames = parameterNamesOf(methodReflection);
					if (UNEXPECTED(traitParameterNames.isUndef())) return zv::Val();
					stubPhpDocPair = findMethodPhpDocIncludingAncestors(traitClass.raw(), implementingClass.raw(), methodNameStr.get(), traitParameterNames.raw());
					if (UNEXPECTED(stubPhpDocPair.isUndef())) return zv::Val();
				}
			}
		}

		if (Z_TYPE_P(stubPhpDocPair.raw()) == IS_ARRAY) {
			zval *resolved = zend_hash_index_find(Z_ARRVAL_P(stubPhpDocPair.raw()), 0);
			zval *owner = zend_hash_index_find(Z_ARRVAL_P(stubPhpDocPair.raw()), 1);
			if (resolved != NULL && owner != NULL) {
				currentResolvedPhpDoc = zv::Val::copyOf(zv::Ref(resolved));
				phpDocBlockClassReflection = zv::Val::copyOf(zv::Ref(owner));
			}
		}

		zv::Val methodDocComment = docCommentOf(methodReflection);
		if (UNEXPECTED(methodDocComment.isUndef())) return zv::Val();
		if (Z_TYPE_P(currentResolvedPhpDoc.raw()) == IS_NULL && Z_TYPE_P(methodDocComment.raw()) != IS_NULL) {
			zv::Val fileName = call(actualDeclaringClass, PT_LC("getfilename"));
			if (UNEXPECTED(fileName.isUndef())) return zv::Val();
			zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(actualDeclaringClass));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			currentResolvedPhpDoc = getResolvedPhpDoc(fileName.raw(), className.raw(), declaringTraitName, &methodNameArg, methodDocComment.raw());
			if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();
		}

		zv::Val inheritanceParameterNames = parameterNamesOf(methodReflection);
		if (UNEXPECTED(inheritanceParameterNames.isUndef())) return zv::Val();
		zv::Args resolveArgs{actualDeclaringClass, &methodNameArg, currentResolvedPhpDoc.raw(), inheritanceParameterNames.raw()};
		zv::Val resolvedPhpDoc = call(slot(slots::phpDocInheritanceResolver), PT_LC("resolvephpdocformethod"), 4, resolveArgs);
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();

		zv::Val declaringTrait = zv::Val::null();
		zv::Val reflectionProvider = call(slot(slots::reflectionProviderProvider), PT_LC("getreflectionprovider"));
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		if (Z_TYPE_P(declaringTraitName) != IS_NULL) {
			bool hasClass;
			if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider.raw()), declaringTraitName, hasClass))) return zv::Val();
			if (hasClass) {
				declaringTrait = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), declaringTraitName);
				if (UNEXPECTED(declaringTrait.isUndef())) return zv::Val();
			}
		}

		zv::Arr phpDocParameterTypes = zv::Arr::create(0);
		bool isConstructor;
		if (UNEXPECTED(!pt_method_adapter_is_constructor(methodReflection, isConstructor))) return zv::Val();
		if (isConstructor) {
			zv::Val parameters = call(methodReflection, PT_LC("getparameters"));
			if (UNEXPECTED(!arrayResult(parameters, "PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionMethod::getParameters"))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
				zval *parameter = entry.value().raw();
				ZVAL_DEREF(parameter);
				bool isPromoted = callBool(parameter, PT_LC("ispromoted"), 0, NULL, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				if (!isPromoted) continue;
				zv::Str parameterName = callString(parameter, PT_LC("getname"));
				if (UNEXPECTED(parameterName.isNull())) return zv::Val();
				zval parameterNameArg;
				ZVAL_STR(&parameterNameArg, parameterName.get());
				zv::Val adapterDeclaringClass = call(methodReflection, PT_LC("getdeclaringclass"));
				if (UNEXPECTED(adapterDeclaringClass.isUndef())) return zv::Val();
				bool hasParameterProperty = callBool(adapterDeclaringClass.raw(), PT_LC("hasproperty"), 1, &parameterNameArg, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				if (!hasParameterProperty) continue;
				zv::Val adapterDeclaringClass2 = call(methodReflection, PT_LC("getdeclaringclass"));
				if (UNEXPECTED(adapterDeclaringClass2.isUndef())) return zv::Val();
				zv::Val parameterProperty = call(adapterDeclaringClass2.raw(), PT_LC("getproperty"), 1, &parameterNameArg);
				if (UNEXPECTED(parameterProperty.isUndef())) return zv::Val();
				bool propertyPromoted;
				if (UNEXPECTED(!pt_property_adapter_is_promoted(parameterProperty.raw(), propertyPromoted))) return zv::Val();
				if (!propertyPromoted) continue;
				zv::Val propertyDocComment = docCommentOf(parameterProperty.raw());
				if (UNEXPECTED(propertyDocComment.isUndef())) return zv::Val();
				if (Z_TYPE_P(propertyDocComment.raw()) == IS_NULL) continue;
				zv::Val fileName = call(fileDeclaringClass, PT_LC("getfilename"));
				if (UNEXPECTED(fileName.isUndef())) return zv::Val();
				zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(fileDeclaringClass));
				if (UNEXPECTED(className.isUndef())) return zv::Val();
				zv::Val propertyDocblock = getResolvedPhpDoc(fileName.raw(), className.raw(), declaringTraitName, &methodNameArg, propertyDocComment.raw());
				if (UNEXPECTED(propertyDocblock.isUndef())) return zv::Val();
				zv::Val varTags = pt_resolved_php_doc_block_call(propertyDocblock.raw(), PT_RPD_GET_VAR_TAGS);
				if (UNEXPECTED(varTags.isUndef())) return zv::Val();
				zval *varTag = varTagFor(varTags.raw(), parameterName.get());
				if (varTag == NULL) continue;
				zv::Val phpDocType = call(varTag, PT_LC("gettype"));
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
				phpDocParameterTypes.set(parameterName.get(), std::move(phpDocType));
			}
		}

		zv::Val reflectionReturnType = call(methodReflection, PT_LC("getreturntype"));
		if (UNEXPECTED(reflectionReturnType.isUndef())) return zv::Val();
		zv::Val nativeReturnType = decideTypeFromReflection(reflectionReturnType.raw(), actualDeclaringClass);
		if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();

		int isPure = -1;
		zv::Arr pureUnlessCallableIsImpureParameters = zv::Arr::create(0);
		bool isBuiltin = callBool(actualDeclaringClass, PT_LC("isbuiltin"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		bool actualIsEnum = false;
		if (!isBuiltin) {
			if (UNEXPECTED(!pt_class_reflection_is_enum(Z_OBJ_P(actualDeclaringClass), actualIsEnum))) return zv::Val();
		}
		if (isBuiltin || actualIsEnum) {
			zv::Val ancestors = call(actualDeclaringClass, PT_LC("getancestors"));
			if (UNEXPECTED(!arrayResult(ancestors, "PHPStan\\Reflection\\ClassReflection::getAncestors"))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(ancestors.raw())) {
				zend_string *ancestorName = entry.stringKeyOrNull();
				if (ancestorName == NULL) continue;
				zval ancestorNameArg;
				ZVAL_STR(&ancestorNameArg, ancestorName);
				bool hasMetadata = signatureMapBool(PT_LC("hasmethodmetadata"), &ancestorNameArg, &methodNameArg, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				if (!hasMetadata) continue;
				zv::Args metadataArgs{&ancestorNameArg, &methodNameArg};
				zv::Val metadata = call(slot(slots::signatureMapProvider), PT_LC("getmethodmetadata"), 2, metadataArgs);
				if (UNEXPECTED(metadata.isUndef())) return zv::Val();
				bool hasSideEffects = true;
				zval *stored = Z_TYPE_P(metadata.raw()) == IS_ARRAY ? zend_hash_str_find(Z_ARRVAL_P(metadata.raw()), PT_LC("hasSideEffects")) : NULL;
				if (stored != NULL) {
					ZVAL_DEREF(stored);
					if (Z_TYPE_P(stored) != IS_NULL) {
						hasSideEffects = zend_is_true(stored);
					}
				}
				isPure = hasSideEffects ? 0 : 1;
				zval *pureUnless = Z_TYPE_P(metadata.raw()) == IS_ARRAY ? zend_hash_str_find(Z_ARRVAL_P(metadata.raw()), PT_LC("pureUnlessCallableIsImpureParameters")) : NULL;
				if (pureUnless != NULL) {
					ZVAL_DEREF(pureUnless);
					if (Z_TYPE_P(pureUnless) == IS_ARRAY) {
						for (zv::ArrayEntry pureEntry : zv::ArrRef(pureUnless)) {
							zend_string *key = pureEntry.stringKeyOrNull();
							if (key == NULL) continue;
							if (!zend_symtable_exists(pureUnlessCallableIsImpureParameters.table(), key)) {
								pureUnlessCallableIsImpureParameters.set(key, zv::Val::copyOf(pureEntry.value()));
							}
						}
					}
				}
				break;
			}
		}

		zv::Arr phpDocParameterOutTypes = zv::Arr::create(0);
		zv::Val phpDocReturnType = zv::Val::null();
		zv::Val templateTypeMap = templateTypeMapEmpty();
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val immediatelyInvokedCallableParameters = zv::Val(zv::Arr::empty());
		zv::Val closureThisParameters = zv::Val(zv::Arr::empty());
		zv::Val phpDocThrowType = zv::Val::null();
		bool isInternal = false;
		bool isFinal = false;
		zv::Val asserts = kernelStatic(PT_LC("PHPStan\\Reflection\\Assertions"), PT_LC("createempty"));
		if (UNEXPECTED(asserts.isUndef())) return zv::Val();
		bool acceptsNamedArguments = true;
		zv::Val selfOutType = zv::Val::null();
		zv::Val phpDocComment = zv::Val::null();

		if (Z_TYPE_P(resolvedPhpDoc.raw()) != IS_NULL) {
			templateTypeMap = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_TEMPLATE_TYPE_MAP);
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			zv::Val immediatelyInvoked = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PARAMS_IMMEDIATELY_INVOKED_CALLABLE);
			if (UNEXPECTED(immediatelyInvoked.isUndef())) return zv::Val();
			immediatelyInvokedCallableParameters = trinaryMapOf(immediatelyInvoked.raw());
			if (UNEXPECTED(immediatelyInvokedCallableParameters.isUndef())) return zv::Val();
			zv::Val closureThisTags = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PARAM_CLOSURE_THIS_TAGS);
			if (UNEXPECTED(closureThisTags.isUndef())) return zv::Val();
			closureThisParameters = tagTypeMapOf(closureThisTags.raw());
			if (UNEXPECTED(closureThisParameters.isUndef())) return zv::Val();
			zv::Val pureUnless = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PARAMS_PURE_UNLESS_CALLABLE_IS_IMPURE);
			if (UNEXPECTED(pureUnless.isUndef())) return zv::Val();
			if (Z_TYPE_P(pureUnless.raw()) == IS_ARRAY) {
				for (zv::ArrayEntry pureEntry : zv::ArrRef(pureUnless.raw())) {
					zend_string *key = pureEntry.stringKeyOrNull();
					if (key == NULL) continue;
					pureUnlessCallableIsImpureParameters.set(key, zv::Val::copyOf(pureEntry.value()));
				}
			}

			phpDocReturnType = getPhpDocReturnType(phpDocBlockClassReflection.raw(), resolvedPhpDoc.raw(), nativeReturnType.raw());
			if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
			zv::Val throwsTag = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_THROWS_TAG);
			if (UNEXPECTED(throwsTag.isUndef())) return zv::Val();
			if (Z_TYPE_P(throwsTag.raw()) != IS_NULL) {
				phpDocThrowType = call(throwsTag.raw(), PT_LC("gettype"));
				if (UNEXPECTED(phpDocThrowType.isUndef())) return zv::Val();
			}

			zv::Val paramTags = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PARAM_TAGS);
			if (UNEXPECTED(paramTags.isUndef())) return zv::Val();
			if (Z_TYPE_P(paramTags.raw()) == IS_ARRAY) {
				for (zv::ArrayEntry tagEntry : zv::ArrRef(paramTags.raw())) {
					zend_string *key = tagEntry.stringKeyOrNull();
					if (key == NULL) continue;
					if (zend_symtable_exists(phpDocParameterTypes.table(), key)) continue;
					zv::Val tagType = call(tagEntry.value().raw(), PT_LC("gettype"));
					if (UNEXPECTED(tagType.isUndef())) return zv::Val();
					phpDocParameterTypes.set(key, std::move(tagType));
				}
			}

			zv::Val paramOutTags = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PARAM_OUT_TAGS);
			if (UNEXPECTED(paramOutTags.isUndef())) return zv::Val();
			if (Z_TYPE_P(paramOutTags.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(paramOutTags.raw())) > 0) {
				zv::Val activeTemplateTypeMap = call(phpDocBlockClassReflection.raw(), PT_LC("getactivetemplatetypemap"));
				if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
				zv::Val callSiteVarianceMap = call(phpDocBlockClassReflection.raw(), PT_LC("getcallsitevariancemap"));
				if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
				zv::Val covariantVal = varianceCovariant();
				if (UNEXPECTED(covariantVal.isUndef())) return zv::Val();
				zval *covariant = covariantVal.raw();
				for (zv::ArrayEntry tagEntry : zv::ArrRef(paramOutTags.raw())) {
					zend_string *key = tagEntry.stringKeyOrNull();
					if (key == NULL) continue;
					zv::Val tagType = call(tagEntry.value().raw(), PT_LC("gettype"));
					if (UNEXPECTED(tagType.isUndef())) return zv::Val();
					zv::Val resolved = pt_type_template_type_helper_resolve_template_types(tagType.raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), covariant, false);
					if (UNEXPECTED(resolved.isUndef())) return zv::Val();
					phpDocParameterOutTypes.set(key, std::move(resolved));
				}
			}

			if (!isDeprecated) {
				zv::Val deprecatedTag = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_DEPRECATED_TAG);
				if (UNEXPECTED(deprecatedTag.isUndef())) return zv::Val();
				if (Z_TYPE_P(deprecatedTag.raw()) != IS_NULL) {
					deprecatedDescription = call(deprecatedTag.raw(), PT_LC("getmessage"));
					if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
				} else {
					deprecatedDescription = zv::Val::null();
				}
				isDeprecated = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_DEPRECATED, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
			}
			isInternal = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_INTERNAL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			isFinal = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_IS_FINAL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (isPure < 0) {
				/* isPure() is ?bool: `??=` leaves $isPure null when the block
				 * says nothing */
				zv::Val pure = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_IS_PURE);
				if (UNEXPECTED(pure.isUndef())) return zv::Val();
				if (Z_TYPE_P(pure.raw()) != IS_NULL) {
					isPure = zend_is_true(pure.raw()) ? 1 : 0;
				}
			}
			asserts = kernelStatic(PT_LC("PHPStan\\Reflection\\Assertions"), PT_LC("createfromresolvedphpdocblock"), 1, resolvedPhpDoc.raw());
			if (UNEXPECTED(asserts.isUndef())) return zv::Val();
			acceptsNamedArguments = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_ACCEPTS_NAMED_ARGUMENTS, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			zv::Val selfOutTag = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_SELF_OUT_TAG);
			if (UNEXPECTED(selfOutTag.isUndef())) return zv::Val();
			if (Z_TYPE_P(selfOutTag.raw()) != IS_NULL) {
				selfOutType = call(selfOutTag.raw(), PT_LC("gettype"));
				if (UNEXPECTED(selfOutType.isUndef())) return zv::Val();
			}
			bool hasPhpDocString = resolvedPhpDocBool(resolvedPhpDoc.raw(), PT_RPD_HAS_PHP_DOC_STRING, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (hasPhpDocString) {
				phpDocComment = pt_resolved_php_doc_block_call(resolvedPhpDoc.raw(), PT_RPD_GET_PHP_DOC_STRING);
				if (UNEXPECTED(phpDocComment.isUndef())) return zv::Val();
			}
		}

		if (isPure < 0) {
			zv::Val classResolvedPhpDoc = call(phpDocBlockClassReflection.raw(), PT_LC("getresolvedphpdoc"));
			if (UNEXPECTED(classResolvedPhpDoc.isUndef())) return zv::Val();
			if (Z_TYPE_P(classResolvedPhpDoc.raw()) != IS_NULL) {
				bool allPure = resolvedPhpDocBool(classResolvedPhpDoc.raw(), PT_RPD_ARE_ALL_METHODS_PURE, ok);
				if (UNEXPECTED(!ok)) return zv::Val();
				if (allPure) {
					zv::Str lowered = zv::Str::adopt(zend_string_tolower(methodNameStr.get()));
					bool pure = zend_string_equals_literal(lowered.get(), "__construct");
					if (!pure) {
						bool phpDocVoid = false;
						if (Z_TYPE_P(phpDocReturnType.raw()) != IS_NULL) {
							phpDocVoid = typeOpYes(phpDocReturnType.raw(), PT_OP_IS_VOID, ok);
							if (UNEXPECTED(!ok)) return zv::Val();
						}
						if (!phpDocVoid) {
							bool nativeVoid = typeOpYes(nativeReturnType.raw(), PT_OP_IS_VOID, ok);
							if (UNEXPECTED(!ok)) return zv::Val();
							pure = !nativeVoid;
						}
					}
					if (pure) {
						isPure = 1;
					}
				} else {
					bool allImpure = resolvedPhpDocBool(classResolvedPhpDoc.raw(), PT_RPD_ARE_ALL_METHODS_IMPURE, ok);
					if (UNEXPECTED(!ok)) return zv::Val();
					if (allImpure) {
						isPure = 0;
					}
				}
			}
		}

		if (zend_hash_num_elements(phpDocParameterTypes.table()) > 0) {
			zv::Val activeTemplateTypeMap = call(phpDocBlockClassReflection.raw(), PT_LC("getactivetemplatetypemap"));
			if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
			zv::Val callSiteVarianceMap = call(phpDocBlockClassReflection.raw(), PT_LC("getcallsitevariancemap"));
			if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
			zv::Val contravariantVal = varianceContravariant();
			if (UNEXPECTED(contravariantVal.isUndef())) return zv::Val();
			zval *contravariant = contravariantVal.raw();
			zv::Arr resolvedParameterTypes = zv::Arr::create(zend_hash_num_elements(phpDocParameterTypes.table()));
			for (zv::ArrayEntry entry : zv::ArrRef(phpDocParameterTypes.raw())) {
				zv::Val resolved = pt_type_template_type_helper_resolve_template_types(entry.value().raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), contravariant, false);
				if (UNEXPECTED(resolved.isUndef())) return zv::Val();
				zend_string *key = entry.stringKeyOrNull();
				if (key == NULL) {
					resolvedParameterTypes.push(std::move(resolved));
					continue;
				}
				resolvedParameterTypes.set(key, std::move(resolved));
			}
			phpDocParameterTypes = std::move(resolvedParameterTypes);
		}

		zv::Val actualClassName = pt_class_reflection_get_name(Z_OBJ_P(actualDeclaringClass));
		if (UNEXPECTED(actualClassName.isUndef())) return zv::Val();
		zv::Val actualFileName = call(actualDeclaringClass, PT_LC("getfilename"));
		if (UNEXPECTED(actualFileName.isUndef())) return zv::Val();
		zv::Val context = pt_initializer_expr_context_from_class_method(actualClassName.raw(), declaringTraitName, &methodNameArg, actualFileName.raw());
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Val attributes = attributesOf(methodReflection, context.raw());
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();

		zval args[22];
		ZVAL_COPY_VALUE(&args[0], actualDeclaringClass);
		ZVAL_COPY_VALUE(&args[1], declaringTrait.raw());
		ZVAL_COPY_VALUE(&args[2], methodReflection);
		ZVAL_COPY_VALUE(&args[3], templateTypeMap.raw());
		ZVAL_COPY_VALUE(&args[4], phpDocParameterTypes.raw());
		ZVAL_COPY_VALUE(&args[5], phpDocReturnType.raw());
		ZVAL_COPY_VALUE(&args[6], phpDocThrowType.raw());
		ZVAL_COPY_VALUE(&args[7], resolvedPhpDoc.raw());
		ZVAL_COPY_VALUE(&args[8], deprecatedDescription.raw());
		ZVAL_BOOL(&args[9], isDeprecated);
		ZVAL_BOOL(&args[10], isInternal);
		ZVAL_BOOL(&args[11], isFinal);
		if (isPure < 0) {
			ZVAL_NULL(&args[12]);
		} else {
			ZVAL_BOOL(&args[12], isPure == 1);
		}
		ZVAL_COPY_VALUE(&args[13], asserts.raw());
		ZVAL_COPY_VALUE(&args[14], selfOutType.raw());
		ZVAL_COPY_VALUE(&args[15], phpDocComment.raw());
		ZVAL_COPY_VALUE(&args[16], phpDocParameterOutTypes.raw());
		ZVAL_COPY_VALUE(&args[17], immediatelyInvokedCallableParameters.raw());
		ZVAL_COPY_VALUE(&args[18], closureThisParameters.raw());
		ZVAL_BOOL(&args[19], acceptsNamedArguments);
		ZVAL_COPY_VALUE(&args[20], attributes.raw());
		ZVAL_COPY_VALUE(&args[21], pureUnlessCallableIsImpureParameters.raw());
		return call(slot(slots::methodReflectionFactory), PT_LC("create"), 22, args);
	}

	/* Mirrors getPhpDocReturnType(). */
	zv::Val getPhpDocReturnType(zval *phpDocBlockClassReflection, zval *resolvedPhpDoc, zval *nativeReturnType)
	{
		bool ok;
		zv::Val returnTag = pt_resolved_php_doc_block_call(resolvedPhpDoc, PT_RPD_GET_RETURN_TAG);
		if (UNEXPECTED(returnTag.isUndef())) return zv::Val();
		if (Z_TYPE_P(returnTag.raw()) == IS_NULL) return zv::Val::null();
		zv::Val tagType = call(returnTag.raw(), PT_LC("gettype"));
		if (UNEXPECTED(tagType.isUndef())) return zv::Val();
		zv::Val activeTemplateTypeMap = call(phpDocBlockClassReflection, PT_LC("getactivetemplatetypemap"));
		if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val callSiteVarianceMap = call(phpDocBlockClassReflection, PT_LC("getcallsitevariancemap"));
		if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
		zv::Val covariantVal = varianceCovariant();
		if (UNEXPECTED(covariantVal.isUndef())) return zv::Val();
		zval *covariant = covariantVal.raw();
		zv::Val phpDocReturnType = pt_type_template_type_helper_resolve_template_types(tagType.raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), covariant, false);
		if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();

		bool isExplicit = callBool(returnTag.raw(), PT_LC("isexplicit"), 0, NULL, ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (isExplicit) return phpDocReturnType;
		bool superType = isSuperTypeOfYes(nativeReturnType, phpDocReturnType.raw(), ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (superType) return phpDocReturnType;
		if (!isUnionType(phpDocReturnType.raw())) return zv::Val::null();
		zv::Val innerTypes = pt_type_op(Z_OBJ_P(phpDocReturnType.raw()), PT_OP_GET_TYPES, 0, NULL);
		if (UNEXPECTED(!arrayResult(innerTypes, "PHPStan\\Type\\UnionType::getTypes"))) return zv::Val();
		zv::Arr kept = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(innerTypes.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(innerTypes.raw())) {
			bool accepted = isSuperTypeOfYes(nativeReturnType, entry.value().raw(), ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!accepted) continue;
			kept.push(entry.value());
		}
		if (zend_hash_num_elements(kept.table()) == 0) return zv::Val::null();
		return kernelStaticSpread(PT_LC("PHPStan\\Type\\TypeCombinator"), PT_LC("union"), kept.table());
	}

	/* Mirrors findMethodPhpDocIncludingAncestors(): [ResolvedPhpDocBlock,
	 * ClassReflection] or PHP null. */
	zv::Val findMethodPhpDocIncludingAncestors(zval *declaringClass, zval *implementingClass, zend_string *methodName, zval *positionalParameterNames)
	{
		bool ok;
		zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass));
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zv::Val implementingClassName = pt_class_reflection_get_name(Z_OBJ_P(implementingClass));
		if (UNEXPECTED(implementingClassName.isUndef())) return zv::Val();
		zval methodNameArg;
		ZVAL_STR(&methodNameArg, methodName);

		zv::Args args{declaringClassName.raw(), implementingClassName.raw(), &methodNameArg, positionalParameterNames};
		zv::Val resolved = call(slot(slots::stubPhpDocProvider), PT_LC("findmethodphpdoc"), 4, args);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		if (Z_TYPE_P(resolved.raw()) != IS_NULL) {
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Val::copyOf(zv::Ref(resolved.raw())));
			pair.push(zv::Val::copyOf(zv::Ref(declaringClass)));
			return zv::Val(std::move(pair));
		}

		bool isKnownClass = callBool(slot(slots::stubPhpDocProvider), PT_LC("isknownclass"), 1, declaringClassName.raw(), ok);
		if (UNEXPECTED(!ok)) return zv::Val();
		if (!isKnownClass) {
			bool isBuiltin = callBool(declaringClass, PT_LC("isbuiltin"), 0, NULL, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!isBuiltin) return zv::Val::null();
		}

		zv::Val ancestors = call(declaringClass, PT_LC("getancestors"));
		if (UNEXPECTED(!arrayResult(ancestors, "PHPStan\\Reflection\\ClassReflection::getAncestors"))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(ancestors.raw())) {
			zval *ancestor = entry.value().raw();
			ZVAL_DEREF(ancestor);
			if (Z_TYPE_P(ancestor) != IS_OBJECT) continue;
			zv::Val ancestorName = pt_class_reflection_get_name(Z_OBJ_P(ancestor));
			if (UNEXPECTED(ancestorName.isUndef())) return zv::Val();
			if (zend_string_equals(Z_STR_P(ancestorName.raw()), Z_STR_P(declaringClassName.raw()))) continue;
			bool hasNative = callBool(ancestor, PT_LC("hasnativemethod"), 1, &methodNameArg, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!hasNative) continue;
			zv::Args ancestorArgs{ancestorName.raw(), ancestorName.raw(), &methodNameArg, positionalParameterNames};
			zv::Val ancestorResolved = call(slot(slots::stubPhpDocProvider), PT_LC("findmethodphpdoc"), 4, ancestorArgs);
			if (UNEXPECTED(ancestorResolved.isUndef())) return zv::Val();
			if (Z_TYPE_P(ancestorResolved.raw()) == IS_NULL) continue;
			if (!isKnownClass) {
				bool isGeneric;
				if (UNEXPECTED(!pt_class_reflection_is_generic(Z_OBJ_P(ancestor), isGeneric))) return zv::Val();
				if (isGeneric) continue;
			}
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Val::copyOf(zv::Ref(ancestorResolved.raw())));
			pair.push(zv::Val::copyOf(zv::Ref(ancestor)));
			return zv::Val(std::move(pair));
		}

		return zv::Val::null();
	}

	/* Mirrors inferPrivatePropertyType(). */
	zv::Val inferPrivatePropertyType(zend_string *propertyName, zval *constructor)
	{
		zv::Val declaringClass = call(constructor, PT_LC("getdeclaringclass"));
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zend_string *className = Z_STR_P(declaringClassName.raw());
		if (issetIn(slot(slots::inferClassConstructorPropertyTypesInProcess), className) != NULL) return zv::Val::null();
		setIn(slot(slots::inferClassConstructorPropertyTypesInProcess), className, zv::Val::boolean(true));
		zv::Val propertyTypes = inferAndCachePropertyTypes(constructor);
		/* like the twin, the marker is removed on the normal return only */
		if (UNEXPECTED(propertyTypes.isUndef())) return zv::Val();
		unsetIn(slot(slots::inferClassConstructorPropertyTypesInProcess), className);
		zval *found = keyIn(propertyTypes.raw(), propertyName);
		if (found != NULL) return zv::Val::copyOf(zv::Ref(found));
		return zv::Val::null();
	}

	/* Mirrors inferAndCachePropertyTypes(): an array<string, Type>. */
	zv::Val inferAndCachePropertyTypes(zval *constructor)
	{
		zv::Val declaringClass = call(constructor, PT_LC("getdeclaringclass"));
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val declaringClassNameVal = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
		if (UNEXPECTED(declaringClassNameVal.isUndef())) return zv::Val();
		zv::Str className = zv::Str::copyOf(Z_STR_P(declaringClassNameVal.raw()));
		zval *cached = issetIn(slot(slots::propertyTypesCache), className.get());
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val fileName = call(declaringClass.raw(), PT_LC("getfilename"));
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		if (Z_TYPE_P(fileName.raw()) != IS_STRING) return cachePropertyTypes(className.get(), zv::Val(zv::Arr::empty()));

		zv::Val nodes = call(slot(slots::parser), PT_LC("parsefile"), 1, fileName.raw());
		if (UNEXPECTED(nodes.isUndef())) return zv::Val();
		zv::Val classNode = findClassNode(className.get(), nodes.raw());
		if (UNEXPECTED(classNode.isUndef())) return zv::Val();
		if (Z_TYPE_P(classNode.raw()) != IS_OBJECT) return cachePropertyTypes(className.get(), zv::Val(zv::Arr::empty()));

		zv::Str constructorName = callString(constructor, PT_LC("getname"));
		if (UNEXPECTED(constructorName.isNull())) return zv::Val();
		zv::Ref classStmts = zv::ObjRef(Z_OBJ_P(classNode.raw())).prop(PT_LC("stmts"));
		if (classStmts.raw() == NULL || Z_TYPE_P(classStmts.raw()) != IS_ARRAY) return cachePropertyTypes(className.get(), zv::Val(zv::Arr::empty()));
		zv::Val methodNode = findConstructorNode(constructorName.get(), classStmts.raw());
		if (UNEXPECTED(methodNode.isUndef())) return zv::Val();
		if (Z_TYPE_P(methodNode.raw()) != IS_OBJECT) return cachePropertyTypes(className.get(), zv::Val(zv::Arr::empty()));
		zv::Ref methodStmts = zv::ObjRef(Z_OBJ_P(methodNode.raw())).prop(PT_LC("stmts"));
		if (methodStmts.raw() == NULL || Z_TYPE_P(methodStmts.raw()) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(methodStmts.raw())) == 0) {
			return cachePropertyTypes(className.get(), zv::Val(zv::Arr::empty()));
		}

		zv::Val scopeContext = pt_type_call_static_ce(pt_ce_scope_context, PT_LC("create"), 1, fileName.raw());
		if (UNEXPECTED(scopeContext.isUndef())) return zv::Val();
		zv::Val classScope = call(slot(slots::scopeFactory), PT_LC("create"), 1, scopeContext.raw());
		if (UNEXPECTED(classScope.isUndef())) return zv::Val();
		const char *lastSeparator = zend_memnrstr(ZSTR_VAL(className.get()), "\\", 1, ZSTR_VAL(className.get()) + ZSTR_LEN(className.get()));
		if (lastSeparator != NULL) {
			zv::Val ns = zv::Val::string(ZSTR_VAL(className.get()), (size_t) (lastSeparator - ZSTR_VAL(className.get())));
			classScope = call(classScope.raw(), PT_LC("enternamespace"), 1, ns.raw());
			if (UNEXPECTED(classScope.isUndef())) return zv::Val();
		}
		classScope = call(classScope.raw(), PT_LC("enterclass"), 1, declaringClass.raw());
		if (UNEXPECTED(classScope.isUndef())) return zv::Val();

		/* [$templateTypeMap, ..., $acceptsNamedArguments, , $phpDocComment, ...,
		 * $phpDocParameterOutTypes, , , , $phpDocPureUnlessCallableIsImpureParameters] */
		pt_php_docs phpDocs;
		if (UNEXPECTED(!pt_php_docs_resolver_get_php_docs(slot(slots::phpDocsResolver), classScope.raw(), methodNode.raw(), 0x1EFFFu | (1u << 20), phpDocs))) return zv::Val();
		zval *docs[PT_PHP_DOCS_COUNT];
		for (uint32_t i = 0; i < PT_PHP_DOCS_COUNT; i++) docs[i] = &phpDocs.items[i];

		zval enterArgs[20];
		ZVAL_COPY_VALUE(&enterArgs[0], methodNode.raw());
		ZVAL_COPY_VALUE(&enterArgs[1], docs[0]);  /* templateTypeMap */
		ZVAL_COPY_VALUE(&enterArgs[2], docs[1]);  /* phpDocParameterTypes */
		ZVAL_COPY_VALUE(&enterArgs[3], docs[4]);  /* phpDocReturnType */
		ZVAL_COPY_VALUE(&enterArgs[4], docs[5]);  /* phpDocThrowType */
		ZVAL_COPY_VALUE(&enterArgs[5], docs[6]);  /* deprecatedDescription */
		ZVAL_COPY_VALUE(&enterArgs[6], docs[7]);  /* isDeprecated */
		ZVAL_COPY_VALUE(&enterArgs[7], docs[8]);  /* isInternal */
		ZVAL_COPY_VALUE(&enterArgs[8], docs[9]);  /* isFinal */
		ZVAL_COPY_VALUE(&enterArgs[9], docs[10]); /* isPure */
		ZVAL_COPY_VALUE(&enterArgs[10], docs[11]); /* acceptsNamedArguments */
		ZVAL_COPY_VALUE(&enterArgs[11], docs[14]); /* asserts */
		ZVAL_COPY_VALUE(&enterArgs[12], docs[15]); /* selfOutType */
		ZVAL_COPY_VALUE(&enterArgs[13], docs[13]); /* phpDocComment */
		ZVAL_COPY_VALUE(&enterArgs[14], docs[16]); /* phpDocParameterOutTypes */
		ZVAL_COPY_VALUE(&enterArgs[15], docs[2]);  /* immediatelyInvokedCallableParameters */
		ZVAL_COPY_VALUE(&enterArgs[16], docs[3]);  /* phpDocClosureThisTypeParameters */
		ZVAL_FALSE(&enterArgs[17]);
		ZVAL_NULL(&enterArgs[18]);
		ZVAL_COPY_VALUE(&enterArgs[19], docs[20]); /* pureUnlessCallableIsImpureParameters */
		zv::Val methodScope = call(classScope.raw(), PT_LC("enterclassmethod"), 20, enterArgs);
		if (UNEXPECTED(methodScope.isUndef())) return zv::Val();

		zend_class_entry *expressionStmtCe = pt_class(PT_CLASS_EXPRESSION_STMT);
		zend_class_entry *assignCe = pt_class(PT_CLASS_ASSIGN_EXPR);
		zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(expressionStmtCe == NULL || assignCe == NULL || propertyFetchCe == NULL || variableCe == NULL || identifierCe == NULL)) return zv::Val();

		zv::Arr propertyTypes = zv::Arr::create(0);
		zv::Val ownedStmts = zv::Val::copyOf(methodStmts);
		for (zv::ArrayEntry entry : zv::ArrRef(ownedStmts.raw())) {
			zval *statement = entry.value().raw();
			ZVAL_DEREF(statement);
			if (Z_TYPE_P(statement) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(statement), expressionStmtCe)) continue;
			zv::Ref exprRef = zv::ObjRef(Z_OBJ_P(statement)).prop(PT_LC("expr"));
			if (exprRef.raw() == NULL) continue;
			zval *expr = exprRef.deref().raw();
			if (Z_TYPE_P(expr) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(expr), assignCe)) continue;
			zv::Ref varRef = zv::ObjRef(Z_OBJ_P(expr)).prop(PT_LC("var"));
			if (varRef.raw() == NULL) continue;
			zval *propertyFetch = varRef.deref().raw();
			if (Z_TYPE_P(propertyFetch) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(propertyFetch), propertyFetchCe)) continue;
			zv::Ref fetchVarRef = zv::ObjRef(Z_OBJ_P(propertyFetch)).prop(PT_LC("var"));
			if (fetchVarRef.raw() == NULL) continue;
			zval *fetchVar = fetchVarRef.deref().raw();
			if (Z_TYPE_P(fetchVar) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(fetchVar), variableCe)) continue;
			zv::Ref fetchVarName = zv::ObjRef(Z_OBJ_P(fetchVar)).prop(PT_LC("name"));
			if (fetchVarName.raw() == NULL || !fetchVarName.deref().stringEquals("this")) continue;
			zv::Ref fetchNameRef = zv::ObjRef(Z_OBJ_P(propertyFetch)).prop(PT_LC("name"));
			if (fetchNameRef.raw() == NULL) continue;
			zval *fetchName = fetchNameRef.deref().raw();
			if (Z_TYPE_P(fetchName) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(fetchName), identifierCe)) continue;

			// an independent lazy pass on its own scope - never read through
			// Scope::getType(), which is reserved for the file's main walk
			zv::Ref assignExprRef = zv::ObjRef(Z_OBJ_P(expr)).prop(PT_LC("expr"));
			if (assignExprRef.raw() == NULL) continue;
			zv::Val storage = pt_expression_result_storage_new();
			if (UNEXPECTED(storage.isUndef())) return zv::Val();
			zv::Val result = pt_node_scope_resolver_process_expr_on_demand(slot(slots::nodeScopeResolver), assignExprRef.deref().raw(), methodScope.raw(), storage.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zv::Val propertyType = call(result.raw(), PT_LC("gettype"));
			if (UNEXPECTED(propertyType.isUndef())) return zv::Val();
			if (isErrorType(propertyType.raw()) || isNeverType(propertyType.raw())) continue;

			zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
			if (UNEXPECTED(precision.isUndef())) return zv::Val();
			propertyType = call(propertyType.raw(), PT_LC("generalize"), 1, precision.raw());
			if (UNEXPECTED(propertyType.isUndef())) return zv::Val();
			bool ok;
			bool isConstantArray = typeOpYes(propertyType.raw(), PT_OP_IS_CONSTANT_ARRAY, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
			if (isConstantArray) {
				zv::Val ownedKeyType = explicitMixedType();
				if (UNEXPECTED(ownedKeyType.isUndef())) return zv::Val();
				zv::Val ownedItemType = explicitMixedType();
				if (UNEXPECTED(ownedItemType.isUndef())) return zv::Val();
				propertyType = arrayType(ownedKeyType.raw(), ownedItemType.raw());
				if (UNEXPECTED(propertyType.isUndef())) return zv::Val();
			}

			zv::Str propertyKey = callString(fetchName, PT_LC("tostring"));
			if (UNEXPECTED(propertyKey.isNull())) return zv::Val();
			propertyTypes.set(propertyKey.get(), std::move(propertyType));
		}

		return cachePropertyTypes(className.get(), zv::Val(std::move(propertyTypes)));
	}

	/* $this->propertyTypesCache[$className] = $types; return $types; */
	zv::Val cachePropertyTypes(zend_string *className, zv::Val types)
	{
		zv::Val result = zv::Val::copyOf(zv::Ref(types.raw()));
		setIn(slot(slots::propertyTypesCache), className, std::move(types));
		return result;
	}

	/* Mirrors findClassNode(): the Class_ node or PHP null. */
	zv::Val findClassNode(zend_string *className, zval *nodes)
	{
		if (Z_TYPE_P(nodes) != IS_ARRAY) return zv::Val::null();
		zend_class_entry *classStmtCe = pt_class(PT_CLASS_CLASS_STMT);
		zend_class_entry *namespaceCe = pt_class(PT_CLASS_NAMESPACE_STMT);
		zend_class_entry *declareCe = pt_class(PT_CLASS_DECLARE_STMT);
		if (UNEXPECTED(classStmtCe == NULL || namespaceCe == NULL || declareCe == NULL)) return zv::Val();
		zv::Val ownedNodes = zv::Val::copyOf(zv::Ref(nodes));
		for (zv::ArrayEntry entry : zv::ArrRef(ownedNodes.raw())) {
			zval *node = entry.value().raw();
			ZVAL_DEREF(node);
			if (Z_TYPE_P(node) != IS_OBJECT) continue;
			if (instanceof_function(Z_OBJCE_P(node), classStmtCe)) {
				zv::Ref namespacedName = zv::ObjRef(Z_OBJ_P(node)).prop(PT_LC("namespacedName"));
				if (namespacedName.raw() != NULL && namespacedName.deref().isObject()) {
					zv::Str asString = callString(namespacedName.deref().raw(), PT_LC("tostring"));
					if (UNEXPECTED(asString.isNull())) return zv::Val();
					if (zend_string_equals(asString.get(), className)) return zv::Val::copyOf(zv::Ref(node));
				}
			}
			if (!instanceof_function(Z_OBJCE_P(node), namespaceCe) && !instanceof_function(Z_OBJCE_P(node), declareCe)) continue;
			zv::Val subNodeNames = call(node, PT_LC("getsubnodenames"));
			if (UNEXPECTED(!arrayResult(subNodeNames, "PhpParser\\Node::getSubNodeNames"))) return zv::Val();
			for (zv::ArrayEntry nameEntry : zv::ArrRef(subNodeNames.raw())) {
				zval *subNodeName = nameEntry.value().raw();
				ZVAL_DEREF(subNodeName);
				if (Z_TYPE_P(subNodeName) != IS_STRING) continue;
				zv::Ref subNode = zv::ObjRef(Z_OBJ_P(node)).prop(ZSTR_VAL(Z_STR_P(subNodeName)), ZSTR_LEN(Z_STR_P(subNodeName)));
				if (subNode.raw() == NULL) continue;
				zv::Val wrapped;
				zval *subNodeArray;
				if (subNode.deref().isArray()) {
					subNodeArray = subNode.deref().raw();
				} else {
					zv::Arr single = zv::Arr::create(1);
					single.push(subNode.deref());
					wrapped = zv::Val(std::move(single));
					subNodeArray = wrapped.raw();
				}
				zv::Val result = findClassNode(className, subNodeArray);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				if (Z_TYPE_P(result.raw()) == IS_NULL) continue;
				return result;
			}
		}
		return zv::Val::null();
	}

	/* Mirrors findConstructorNode(): the ClassMethod node or PHP null. */
	zv::Val findConstructorNode(zend_string *methodName, zval *classStatements)
	{
		if (Z_TYPE_P(classStatements) != IS_ARRAY) return zv::Val::null();
		zend_class_entry *classMethodCe = pt_class(PT_CLASS_CLASS_METHOD_STMT);
		if (UNEXPECTED(classMethodCe == NULL)) return zv::Val();
		zv::Val ownedStatements = zv::Val::copyOf(zv::Ref(classStatements));
		for (zv::ArrayEntry entry : zv::ArrRef(ownedStatements.raw())) {
			zval *statement = entry.value().raw();
			ZVAL_DEREF(statement);
			if (Z_TYPE_P(statement) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(statement), classMethodCe)) continue;
			zv::Ref name = zv::ObjRef(Z_OBJ_P(statement)).prop(PT_LC("name"));
			if (name.raw() == NULL || !name.deref().isObject()) continue;
			zv::Str asString = callString(name.deref().raw(), PT_LC("tostring"));
			if (UNEXPECTED(asString.isNull())) return zv::Val();
			if (zend_string_equals(asString.get(), methodName)) return zv::Val::copyOf(zv::Ref(statement));
		}
		return zv::Val::null();
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

/* {{{ direct entries for ClassReflection.cpp (support.h): the C++ bodies for
 * the native (final) class, the methods by name for anything else */

namespace {

zv::Val pcreByName(zend_object *extension, const char *lcname, size_t len, zval *classReflection, zend_string *name)
{
	zv::Args args{classReflection, name};
	return pt_type_call(extension, lcname, len, 2, args);
}

bool pcreByNameBool(zend_object *extension, const char *lcname, size_t len, zval *classReflection, zend_string *name, bool &out)
{
	zv::Val result = pcreByName(extension, lcname, len, classReflection, name);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

} // namespace

bool pt_php_class_reflection_extension_has_property(zend_object *extension, zval *classReflection, zend_string *propertyName, bool &out)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) {
		bool ok;
		out = phpstanturbo::PhpClassReflectionExtension(extension).hasProperty(classReflection, propertyName, ok);
		return ok;
	}
	return pcreByNameBool(extension, PT_LC("hasproperty"), classReflection, propertyName, out);
}

zv::Val pt_php_class_reflection_extension_get_native_property(zend_object *extension, zval *classReflection, zend_string *propertyName)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) return phpstanturbo::PhpClassReflectionExtension(extension).getNativeProperty(classReflection, propertyName);
	return pcreByName(extension, PT_LC("getnativeproperty"), classReflection, propertyName);
}

bool pt_php_class_reflection_extension_has_method(zend_object *extension, zval *classReflection, zend_string *methodName, bool &out)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) {
		bool ok;
		out = phpstanturbo::PhpClassReflectionExtension(extension).hasMethod(classReflection, methodName, ok);
		return ok;
	}
	return pcreByNameBool(extension, PT_LC("hasmethod"), classReflection, methodName, out);
}

zv::Val pt_php_class_reflection_extension_get_method(zend_object *extension, zval *classReflection, zend_string *methodName)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) return phpstanturbo::PhpClassReflectionExtension(extension).getMethod(classReflection, methodName);
	return pcreByName(extension, PT_LC("getmethod"), classReflection, methodName);
}

bool pt_php_class_reflection_extension_has_native_method(zend_object *extension, zval *classReflection, zend_string *methodName, bool &out)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) {
		bool ok;
		out = phpstanturbo::PhpClassReflectionExtension(extension).hasNativeMethod(classReflection, methodName, ok);
		return ok;
	}
	return pcreByNameBool(extension, PT_LC("hasnativemethod"), classReflection, methodName, out);
}

zv::Val pt_php_class_reflection_extension_get_native_method(zend_object *extension, zval *classReflection, zend_string *methodName)
{
	if (EXPECTED(extension->ce == pt_ce_php_class_reflection_extension)) return phpstanturbo::PhpClassReflectionExtension(extension).getNativeMethod(classReflection, methodName);
	return pcreByName(extension, PT_LC("getnativemethod"), classReflection, methodName);
}

/* }}} */

/* {{{ registration — the engine ABI glue */

PT_MINIT_REGISTRATION(pt_register_php_class_reflection_extension)
{
	reg::Class cls("PHPStan\\Reflection\\Php\\PhpClassReflectionExtension");
	ptdecl::PhpClassReflectionExtension::declareClass(cls);
	/* the twin's properties in declaration order (the slots:: constants) */
	ptdecl::PhpClassReflectionExtension::declareProperties(cls);

	/* the DI service's constructor: the parameter class names are the
	 * twin's exactly — Nette reflects them to autowire the service and
	 * pairs the two #[AutowiredParameter]s by name (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ConstructorArgs a;
		bool inferPrivatePropertyTypeFromConstructor;
		ZEND_PARSE_PARAMETERS_START(18, 18)
			Z_PARAM_OBJECT(a.scopeFactory)
			Z_PARAM_OBJECT(a.phpDocsResolver)
			Z_PARAM_OBJECT(a.nodeScopeResolver)
			Z_PARAM_OBJECT(a.methodReflectionFactory)
			Z_PARAM_OBJECT(a.phpDocInheritanceResolver)
			Z_PARAM_OBJECT(a.deprecationProvider)
			Z_PARAM_OBJECT(a.annotationsMethodsClassReflectionExtension)
			Z_PARAM_OBJECT(a.annotationsPropertiesClassReflectionExtension)
			Z_PARAM_OBJECT(a.signatureMapProvider)
			Z_PARAM_OBJECT(a.parser)
			Z_PARAM_OBJECT(a.stubPhpDocProvider)
			Z_PARAM_OBJECT(a.reflectionProviderProvider)
			Z_PARAM_OBJECT(a.fileTypeMapper)
			Z_PARAM_OBJECT(a.attributeReflectionFactory)
			Z_PARAM_OBJECT(a.allowedConstantsMapProvider)
			Z_PARAM_BOOL(inferPrivatePropertyTypeFromConstructor)
			Z_PARAM_OBJECT(a.phpVersion)
			Z_PARAM_LONG(a.memberCacheKeysMax)
		ZEND_PARSE_PARAMETERS_END();
		a.inferPrivatePropertyTypeFromConstructor = inferPrivatePropertyTypeFromConstructor;
		if (UNEXPECTED(!phpstanturbo::PhpClassReflectionExtension::construct(Z_OBJ_P(ZEND_THIS), a))) RETURN_THROWS();
	});

	cls.method(sigs::hasProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *propertyName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, propertyName)) RETURN_THROWS();
		bool ok;
		bool result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).hasProperty(classReflection, propertyName, ok);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *scope;
		zend_string *propertyName;
		if (!zp::parse<zp::Obj, zp::Str, zp::Obj>(execute_data, classReflection, propertyName, scope)) RETURN_THROWS();
		zv::Val result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).getProperty(classReflection, propertyName, scope);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::getNativeProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *propertyName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, propertyName)) RETURN_THROWS();
		zv::Val result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).getNativeProperty(classReflection, propertyName);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *methodName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, methodName)) RETURN_THROWS();
		bool ok;
		bool result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).hasMethod(classReflection, methodName, ok);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::getMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *methodName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, methodName)) RETURN_THROWS();
		zv::Val result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).getMethod(classReflection, methodName);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::hasNativeMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *methodName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, methodName)) RETURN_THROWS();
		bool ok;
		bool result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).hasNativeMethod(classReflection, methodName, ok);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::getNativeMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		zend_string *methodName;
		if (!zp::parse<zp::Obj, zp::Str>(execute_data, classReflection, methodName)) RETURN_THROWS();
		zv::Val result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).getNativeMethod(classReflection, methodName);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::createUserlandMethodReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fileDeclaringClass, *actualDeclaringClass, *methodReflection;
		zend_string *declaringTraitName;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::StrOrNull>(execute_data, fileDeclaringClass, actualDeclaringClass, methodReflection, declaringTraitName)) RETURN_THROWS();
		zval traitNameArg;
		if (declaringTraitName != NULL) {
			ZVAL_STR(&traitNameArg, declaringTraitName);
		} else {
			ZVAL_NULL(&traitNameArg);
		}
		zv::Val result = phpstanturbo::PhpClassReflectionExtension(Z_OBJ_P(ZEND_THIS)).createUserlandMethodReflection(fileDeclaringClass, actualDeclaringClass, methodReflection, &traitNameArg);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_php_class_reflection_extension);
}

/* }}} */
