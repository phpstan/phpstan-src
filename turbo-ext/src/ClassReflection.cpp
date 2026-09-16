/*
 * PHPStanTurbo\ClassReflection — native implementation of
 * PHPStan\Reflection\ClassReflection, declared as that class itself at
 * activation (reg::Class::shadow(), final like the twin). The seven
 * getters the Type kernel calls millions of times per run
 * (getName/getCacheKey/getNativeReflection/isGeneric/hasMethod/
 * hasFinalByKeywordOverride/isEnum) are re-exported below the class as
 * pt_class_reflection_*() — a direct call into the native body, the PHP
 * method for a foreign object.
 *
 * Design
 * ------
 * Class shape. The twin is final: no PHP subclass exists, so every
 * `$this->method()` is a direct C++ call — no Z_OBJCE dispatch, no handler
 * identity checks. The DI container never instantiates this class directly:
 * the generated ClassReflectionFactory does (GenerateFactory), reflecting
 * the constructor for the eleven autowired services — the arginfo therefore
 * declares the twin's exact parameter class names (README rule 6).
 *
 * Layout. The twin's properties are declared typed property slots in the
 * twin's declaration order: the 35 class-body memo properties first (with
 * their defaults — [] / null / false, exactly the twin's), the static
 * $resolvingTypeAliasImports in its place (no instance slot), then the 19
 * promoted constructor properties in parameter order, uninitialized until
 * the constructor writes them. The std object handlers do GC/clone/free.
 * The names are load-bearing: the differential harness reads every slot by
 * reflection to compare the memo state of both sides.
 *
 * Collaborators. The eleven injected services (ClassReflectionFactory,
 * ReflectionProvider, InitializerExprTypeResolver, FileTypeMapper,
 * StubPhpDocProvider, PhpDocInheritanceResolver, PhpVersion,
 * SignatureMapProvider, DeprecationProvider, AttributeReflectionFactory,
 * ClassReflectionExtensionRegistryProvider) and the BetterReflection
 * adapter ($reflection) are PHP objects held in their slots and called by
 * name (pt_type_call). The reflection provider goes through the slot
 * readers of ReflectionAccess.cpp (a memoizing provider answers from its
 * cache), a ClassMemberAccessAnswerer scope through
 * pt_scope_get_class_reflection() of ScopeContext.cpp (a MutatingScope's
 * context slot). Other
 * ClassReflection instances (parents, interfaces, the provider's answers)
 * are called directly when they are exactly this class and by name
 * otherwise (crCall()) — under the prefixed harness they are PHP twins.
 * The Type kernel is reached natively: ObjectType::getClassReflection(),
 * TemplateTypeHelper::resolveTemplateTypes()/resolveToDefaults(),
 * TypeProjectionHelper::describe(), TypehintHelper::decideTypeFromReflection(),
 * `new ErrorType()`, `new ObjectType()` / `new GenericObjectType()`, `new
 * TemplateTypeMap()` / `new TemplateTypeVarianceMap()`; the VerbosityLevel
 * and TemplateTypeVariance singletons — and the two statics that classify
 * a Type by its class, TemplateTypeScope::createWithClass() and
 * TemplateTypeFactory::fromTemplateTag() — are reached through the
 * classes' real names (kernelSingleton() / kernelStatic(): the native
 * class in production, the PHP twin's in the prefixed harness, where PHP
 * types would refuse a native singleton and where the native factory would
 * widen a PHP bound it cannot recognise to TemplateMixedType).
 * `instanceof` against a shadowed Type class
 * (GenericObjectType, ObjectType, ErrorType, MixedType) accepts the PHP
 * twin declared next to the native class in the differential tests
 * (instanceOfShadowed()): the delegate twin's tags hand PHP types to the
 * native bodies there; in a production run the native class carries the
 * real name and the second lookup never runs. Class-map classes
 * (OutOfClassScope, the Missing*FromReflectionException classes,
 * ShouldNotHappenException, CircularTypeAliasDefinitionException,
 * UniversalObjectCratesClassReflectionExtension, ReflectionEnum,
 * ReflectionEnumBackedCase, InitializerExprContext, EnumCaseReflection,
 * RealClassClassConstantReflection, TypeAlias, ArgumentsNormalizer and the
 * parser nodes it needs (Arg, Identifier, FullyQualified, StaticCall), the
 * Extended*Reflection interfaces and their Wrapped* implementations,
 * TemplateType) go through pt_type_new / pt_type_call_static /
 * pt_type_instanceof. The three that receive `$this` —
 * InitializerExprContext::fromClassReflection(), `new EnumCaseReflection`,
 * `new RealClassClassConstantReflection` — are remapped to stand-ins for
 * the duration of the differential test, as is the crate check. is_file() is the internal function, called through
 * its cached zend_function; a ReflectionException the adapter throws is
 * caught by class name (the reflection extension's header is not part of
 * every PHP install).
 *
 * TemplateTypeMap::map() with the twin's closures is expanded in place
 * (map() rebuilds the map from getTypes() through the callback, nothing
 * else): the `static fn (): Type => new ErrorType()` of getParentClass() /
 * getImmediateInterfaces() only feeds withTypes() a list of fresh
 * ErrorTypes, and the ancestor-resolution closure becomes the loop in
 * getActiveTemplateTypeMapForAncestorResolution(); getActiveTemplateTypeMap()
 * expands its own. The one closure that must stay a callable is
 * typeMapFromList()'s TypeTraverser::map() callback, a
 * pt_type_native_callback() holder over the `use ($map, $className)`
 * snapshot.
 */

#include "TypeTraits.h"
#include "generated/ClassReflection.h"

namespace sigs = ptdecl::ClassReflection::sig;
#include "TypeOps.h"

#include "zend_closures.h"

#include <cstring>
#include <string>
#include <vector>

zend_class_entry *pt_ce_class_reflection = nullptr;

/* OBJ_PROP_NUM slots, in the twin's declaration order: the class-body
 * properties first (the static $resolvingTypeAliasImports between
 * typeAliases and hasMethodCache takes no instance slot), the promoted
 * constructor properties after them */
enum : uint32_t
{
	PT_CR_PROP_METHODS = 0,
	PT_CR_PROP_PROPERTIES,
	PT_CR_PROP_INSTANCE_PROPERTIES,
	PT_CR_PROP_STATIC_PROPERTIES,
	PT_CR_PROP_CONSTANTS,
	PT_CR_PROP_ENUM_CASES,
	PT_CR_PROP_CLASS_HIERARCHY_DISTANCES,
	PT_CR_PROP_DEPRECATED_DESCRIPTION,
	PT_CR_PROP_IS_DEPRECATED,
	PT_CR_PROP_ALLOWED_SUB_TYPES,
	PT_CR_PROP_ALLOWED_SUB_TYPES_RESOLVED,
	PT_CR_PROP_IS_GENERIC,
	PT_CR_PROP_IS_INTERNAL,
	PT_CR_PROP_IS_FINAL,
	PT_CR_PROP_IS_IMMUTABLE,
	PT_CR_PROP_HAS_CONSISTENT_CONSTRUCTOR,
	PT_CR_PROP_ACCEPTS_NAMED_ARGUMENTS,
	PT_CR_PROP_TEMPLATE_TYPE_MAP,
	PT_CR_PROP_ACTIVE_TEMPLATE_TYPE_MAP,
	PT_CR_PROP_DEFAULT_CALL_SITE_VARIANCE_MAP,
	PT_CR_PROP_CALL_SITE_VARIANCE_MAP,
	PT_CR_PROP_ANCESTORS,
	PT_CR_PROP_CACHE_KEY,
	PT_CR_PROP_SUBCLASSES,
	PT_CR_PROP_FILENAME,
	PT_CR_PROP_REFLECTION_DOC_COMMENT,
	PT_CR_PROP_STUB_PHP_DOC_BLOCK,
	PT_CR_PROP_RESOLVED_PHP_DOC_BLOCK,
	PT_CR_PROP_TRAIT_CONTEXT_RESOLVED_PHP_DOC_BLOCK,
	PT_CR_PROP_CACHED_INTERFACES,
	PT_CR_PROP_CACHED_PARENT_CLASS,
	PT_CR_PROP_CIRCULAR_PARENT_CLASS_NAME,
	PT_CR_PROP_TYPE_ALIASES,
	PT_CR_PROP_HAS_METHOD_CACHE,
	PT_CR_PROP_HAS_PROPERTY_CACHE,
	PT_CR_PROP_HAS_INSTANCE_PROPERTY_CACHE,
	PT_CR_PROP_HAS_STATIC_PROPERTY_CACHE,
	PT_CR_PROP_NAME,
	PT_CR_PROP_CLASS_REFLECTION_FACTORY,
	PT_CR_PROP_REFLECTION_PROVIDER,
	PT_CR_PROP_INITIALIZER_EXPR_TYPE_RESOLVER,
	PT_CR_PROP_FILE_TYPE_MAPPER,
	PT_CR_PROP_STUB_PHP_DOC_PROVIDER,
	PT_CR_PROP_PHP_DOC_INHERITANCE_RESOLVER,
	PT_CR_PROP_PHP_VERSION,
	PT_CR_PROP_SIGNATURE_MAP_PROVIDER,
	PT_CR_PROP_DEPRECATION_PROVIDER,
	PT_CR_PROP_ATTRIBUTE_REFLECTION_FACTORY,
	PT_CR_PROP_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER,
	PT_CR_PROP_DISPLAY_NAME,
	PT_CR_PROP_REFLECTION,
	PT_CR_PROP_ANONYMOUS_FILENAME,
	PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP,
	PT_CR_PROP_STUB_PHP_DOC_BLOCK_CALLBACK,
	PT_CR_PROP_EXTRA_CACHE_KEY,
	PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP,
	PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE,
	PT_CR_PROP_COUNT,
};

namespace phpstanturbo {

/* the real names of the shadowed Type classes instanceOfShadowed() falls
 * back to under the prefixed activation */
#define PT_CR_GENERIC_OBJECT_TYPE_NAME "PHPStan\\Type\\Generic\\GenericObjectType"
#define PT_CR_OBJECT_TYPE_NAME "PHPStan\\Type\\ObjectType"
#define PT_CR_ERROR_TYPE_NAME "PHPStan\\Type\\ErrorType"
#define PT_CR_MIXED_TYPE_NAME "PHPStan\\Type\\MixedType"
#define PT_CR_CONSTANT_INTEGER_TYPE_NAME "PHPStan\\Type\\Constant\\ConstantIntegerType"

/* Mirrors PHPStan\Reflection\ClassReflection. State lives in the PHP
 * object's property slots. Methods returning zv::Val use UNDEF to signal a
 * pending exception, a legitimate PHP null is zv::Val::null(); methods
 * returning bool with an `out` parameter return false on a pending
 * exception. */
class ClassReflection
{
public:
	explicit ClassReflection(zend_object *self) : self(self) {}

	/* {{{ the slots */

	zv::Ref slot(uint32_t index) const { return zv::ObjRef(self).propAt(index); }

	/* a slot write that also clears IS_PROP_UNINIT (the promoted typed
	 * properties start uninitialized) */
	void writeSlot(uint32_t index, zv::Val value)
	{
		zval *p = OBJ_PROP_NUM(self, index);
		zv::ObjRef(self).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(p) = 0;
	}

	zv::Val copyOfSlot(uint32_t index) const { return zv::Val::copyOf(slot(index)); }

	zval *thisZval()
	{
		ZVAL_OBJ(&selfZval, self);
		return &selfZval;
	}

	/* the Error the twin's typed-property read raises when the constructor
	 * never ran (the declaring class names it, as the engine does) */
	zv::Val uninitializedProperty(const char *name) const
	{
		zend_class_entry *declaring = pt_ce_class_reflection != NULL ? pt_ce_class_reflection : self->ce;
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(declaring->name), name);
		return zv::Val();
	}

	/* a promoted slot that must hold an object by now (the services, the
	 * adapter); NULL with the Error pending otherwise */
	zend_object *service(uint32_t index, const char *name) const
	{
		zv::Ref value = slot(index);
		if (UNEXPECTED(!value.isObject())) {
			if (value.isUndef()) {
				(void) uninitializedProperty(name);
			} else {
				zend_throw_error(NULL, "Call to a member function on %s", zend_zval_value_name(value.raw()));
			}
			return NULL;
		}
		return value.asObject();
	}

	/* $this->reflection->method(...$args) / $this->phpVersion->method(...) / ... */
	zv::Val callService(uint32_t index, const char *name, const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zend_object *object = service(index, name);
		if (UNEXPECTED(object == NULL)) return zv::Val();
		return pt_type_call(object, lcname, len, argc, argv);
	}

	zv::Val reflectionCall(const char *lcname, size_t len, uint32_t argc, zval *argv) const { return callService(PT_CR_PROP_REFLECTION, "reflection", lcname, len, argc, argv); }

	bool reflectionCallBool(const char *lcname, size_t len, bool &out) const
	{
		zv::Val result = reflectionCall(lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* the memo arrays keyed by member / class names: PHP array keys, so a
	 * numeric string is an integer key (symtable) */
	zval *memoFind(uint32_t index, zend_string *key) const
	{
		zv::Ref table = slot(index);
		if (UNEXPECTED(!table.isArray())) return NULL;
		return zend_symtable_find(table.asArrayTable(), key);
	}

	/* array_key_exists($key, $this->memo) */
	bool memoExists(uint32_t index, zend_string *key) const { return memoFind(index, key) != NULL; }

	/* isset($this->memo[$key]) */
	bool memoIsset(uint32_t index, zend_string *key) const
	{
		zval *found = memoFind(index, key);
		return found != NULL && Z_TYPE_P(found) != IS_NULL;
	}

	/* $this->memo[$key] = $value; the value is returned as the twin's
	 * assignment expression yields it */
	zv::Ref memoSet(uint32_t index, zend_string *key, zv::Val value)
	{
		zval *table = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zval fresh;
			array_init(&fresh);
			writeSlot(index, zv::Val::adopt(fresh));
		}
		SEPARATE_ARRAY(table);
		zval v = value.take();
		return zv::Ref(zend_symtable_update(Z_ARRVAL_P(table), key, &v));
	}

	bool memoSetBool(uint32_t index, zend_string *key, bool value)
	{
		memoSet(index, key, zv::Val::boolean(value));
		return value;
	}

	/* }}} */

	/* {{{ calls on other objects */

	/* $object->method(...$args); the engine's Error on a non-object */
	static zv::Val callOn(zv::Ref object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Ref value = object.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		return pt_type_call(value.asObject(), lcname, len, argc, argv);
	}

	static bool callBool(zv::Ref object, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
	{
		zv::Val result = callOn(object, lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* the registry the eleventh service hands out, and its getters — fetched
	 * afresh at every use, as the twin does, but out of the provider's memo
	 * and the registry's own slots where those two final classes are what
	 * holds them (ReflectionAccess.cpp); every member lookup below starts
	 * with one of these */
	zv::Val registryGet(pt_registry_member member) const
	{
		zend_object *provider = service(PT_CR_PROP_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER, "classReflectionExtensionRegistryProvider");
		if (UNEXPECTED(provider == NULL)) return zv::Val();
		return pt_class_reflection_extension_registry_member(provider, member);
	}

	zv::Val phpClassReflectionExtension() const { return registryGet(PT_REGISTRY_PHP_CLASS_REFLECTION_EXTENSION); }
	zv::Val methodsClassReflectionExtensions() const { return registryGet(PT_REGISTRY_METHODS_EXTENSIONS); }
	zv::Val propertiesClassReflectionExtensions() const { return registryGet(PT_REGISTRY_PROPERTIES_EXTENSIONS); }
	zv::Val requireExtendsMethodsClassReflectionExtension() const { return registryGet(PT_REGISTRY_REQUIRE_EXTENDS_METHODS_EXTENSION); }
	zv::Val requireExtendsPropertyClassReflectionExtension() const { return registryGet(PT_REGISTRY_REQUIRE_EXTENDS_PROPERTIES_EXTENSION); }

	/* $extension->method($this, $memberName) as bool / as value */
	bool extensionBool(zv::Ref extension, const char *lcname, size_t len, zend_string *memberName, bool &out)
	{
		zv::Args args{self, memberName};
		return callBool(extension, lcname, len, 2, args, out);
	}

	zv::Val extensionCall(zv::Ref extension, const char *lcname, size_t len, zend_string *memberName)
	{
		zv::Args args{self, memberName};
		return callOn(extension, lcname, len, 2, args);
	}

	/* the reflection provider through the slot readers of ReflectionAccess.cpp */
	bool providerHasClass(zend_string *className, bool &out) const
	{
		zend_object *provider = service(PT_CR_PROP_REFLECTION_PROVIDER, "reflectionProvider");
		if (UNEXPECTED(provider == NULL)) return false;
		zval name;
		ZVAL_STR(&name, className);
		return pt_reflection_provider_has_class(provider, &name, out);
	}

	zv::Val providerGetClass(zend_string *className) const
	{
		zend_object *provider = service(PT_CR_PROP_REFLECTION_PROVIDER, "reflectionProvider");
		if (UNEXPECTED(provider == NULL)) return zv::Val();
		zval name;
		ZVAL_STR(&name, className);
		return pt_reflection_provider_get_class(provider, &name);
	}

	/* }}} */

	/* {{{ other ClassReflection instances: direct when exactly this class,
	 * by name otherwise (the PHP twins of the differential harness) */

	static bool isNative(zend_object *object) { return object->ce == pt_ce_class_reflection; }

	template <typename Direct>
	static zv::Val crCall(zv::Ref object, const char *lcname, size_t len, Direct direct)
	{
		zv::Ref value = object.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		if (EXPECTED(isNative(value.asObject()))) return direct(ClassReflection(value.asObject()));
		return pt_type_call(value.asObject(), lcname, len, 0, NULL);
	}

	template <typename Direct>
	static bool crCallBool(zv::Ref object, const char *lcname, size_t len, bool &out, Direct direct)
	{
		zv::Ref value = object.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(value.raw()));
			return false;
		}
		if (EXPECTED(isNative(value.asObject()))) return direct(ClassReflection(value.asObject()), out);
		zv::Val result = pt_type_call(value.asObject(), lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	static zv::Val crGetName(zv::Ref cr) { return crCall(cr, PT_LC("getname"), [](ClassReflection other) { return other.getName(); }); }
	static zv::Val crGetCacheKey(zv::Ref cr) { return crCall(cr, PT_LC("getcachekey"), [](ClassReflection other) { return other.getCacheKey(); }); }
	static zv::Val crGetParentClass(zv::Ref cr) { return crCall(cr, PT_LC("getparentclass"), [](ClassReflection other) { return other.getParentClass(); }); }
	static zv::Val crGetNativeReflection(zv::Ref cr) { return crCall(cr, PT_LC("getnativereflection"), [](ClassReflection other) { return other.getNativeReflection(); }); }
	static zv::Val crGetImmediateInterfaces(zv::Ref cr) { return crCall(cr, PT_LC("getimmediateinterfaces"), [](ClassReflection other) { return other.getImmediateInterfaces(); }); }
	static bool crIsGeneric(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("isgeneric"), out, [](ClassReflection other, bool &o) { return other.isGeneric(o); }); }
	static bool crIsFinalByKeyword(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("isfinalbykeyword"), out, [](ClassReflection other, bool &o) { return other.isFinalByKeyword(o); }); }
	static bool crIsAnonymous(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("isanonymous"), out, [](ClassReflection other, bool &o) { return other.isAnonymous(o); }); }
	static bool crAllowsDynamicProperties(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("allowsdynamicproperties"), out, [](ClassReflection other, bool &o) { return other.allowsDynamicProperties(o); }); }
	static zv::Val crGetFileName(zv::Ref cr) { return crCall(cr, PT_LC("getfilename"), [](ClassReflection other) { return other.getFileName(); }); }
	static zv::Val crGetAncestors(zv::Ref cr) { return crCall(cr, PT_LC("getancestors"), [](ClassReflection other) { return other.getAncestors(); }); }
	static zv::Val crGetTypeAliases(zv::Ref cr) { return crCall(cr, PT_LC("gettypealiases"), [](ClassReflection other) { return other.getTypeAliases(); }); }
	static zv::Val crGetTemplateTypeMap(zv::Ref cr) { return crCall(cr, PT_LC("gettemplatetypemap"), [](ClassReflection other) { return other.getTemplateTypeMap(); }); }
	static zv::Val crGetActiveTemplateTypeMap(zv::Ref cr) { return crCall(cr, PT_LC("getactivetemplatetypemap"), [](ClassReflection other) { return other.getActiveTemplateTypeMap(); }); }
	static zv::Val crGetCallSiteVarianceMap(zv::Ref cr) { return crCall(cr, PT_LC("getcallsitevariancemap"), [](ClassReflection other) { return other.getCallSiteVarianceMap(); }); }
	static zv::Val crGetConstructor(zv::Ref cr) { return crCall(cr, PT_LC("getconstructor"), [](ClassReflection other) { return other.getConstructor(); }); }
	static bool crIsImmutable(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("isimmutable"), out, [](ClassReflection other, bool &o) { return other.isImmutable(o); }); }
	static bool crIsTrait(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("istrait"), out, [](ClassReflection other, bool &o) { return other.isTrait(o); }); }
	static bool crHasConstructor(zv::Ref cr, bool &out) { return crCallBool(cr, PT_LC("hasconstructor"), out, [](ClassReflection other, bool &o) { return other.hasConstructor(o); }); }

	/* $classReflection->getTraits($recursive) */
	static zv::Val crGetTraits(zv::Ref cr, bool recursive)
	{
		zv::Ref value = cr.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function getTraits() on %s", zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		if (EXPECTED(isNative(value.asObject()))) return ClassReflection(value.asObject()).getTraits(recursive);
		zval arg;
		ZVAL_BOOL(&arg, recursive);
		return pt_type_call(value.asObject(), PT_LC("gettraits"), 1, &arg);
	}

	/* $classReflection->withTypes($types) */
	static zv::Val crWithTypes(zv::Ref cr, zv::Ref types)
	{
		zv::Ref value = cr.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function withTypes() on %s", zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		if (EXPECTED(isNative(value.asObject()))) return ClassReflection(value.asObject()).withTypes(types);
		zval arg;
		ZVAL_COPY_VALUE(&arg, types.raw());
		return pt_type_call(value.asObject(), PT_LC("withtypes"), 1, &arg);
	}

	/* $class->reflection — the private slot of another instance, read as
	 * the twin reads it from inside the class: the slot of a native
	 * instance, the twin's property by name otherwise */
	static zv::Val crReflection(zv::Ref cr)
	{
		zv::Ref value = cr.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Attempt to read property \"reflection\" on %s", zend_zval_value_name(value.raw()));
			return zv::Val();
		}
		if (EXPECTED(isNative(value.asObject()))) return ClassReflection(value.asObject()).getNativeReflection();
		zv::Ref reflection = zv::ObjRef(value.asObject()).prop(PT_LC("reflection"));
		if (UNEXPECTED(reflection.raw() == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s has no property $reflection", ZSTR_VAL(value.asObject()->ce->name));
			return zv::Val();
		}
		if (UNEXPECTED(reflection.isUndef())) {
			zend_throw_error(NULL, "Typed property %s::$reflection must not be accessed before initialization", ZSTR_VAL(value.asObject()->ce->name));
			return zv::Val();
		}
		return zv::Val::copyOf(reflection);
	}

	/* $cr->withTypes(array_values($cr->getTemplateTypeMap()->map(static fn
	 * (): Type => new ErrorType())->getTypes())) — the mapped map only
	 * feeds withTypes() one fresh ErrorType per template type */
	static zv::Val crWithErrorTypes(zv::Ref cr)
	{
		zv::Val map = crGetTemplateTypeMap(cr);
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		zv::Val types = callOn(map.ref(), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		uint32_t count = types.ref().isArray() ? zend_hash_num_elements(types.ref().asArrayTable()) : 0;
		zv::Arr list = zv::Arr::create(count);
		for (uint32_t i = 0; i < count; i++) {
			zv::Val errorType = pt_type_new_error_type();
			if (UNEXPECTED(errorType.isUndef())) return zv::Val();
			list.push(std::move(errorType));
		}
		return crWithTypes(cr, list.ref());
	}

	/* }}} */

	/* {{{ the Type kernel */

	/* $value instanceof <shadowed Type class>: the native class — or, under
	 * the prefixed activation of the differential tests, where the PHP
	 * twins are declared next to the native classes, the twin of the same
	 * real name looked up without autoloading (an undeclared class is "no
	 * instance"); in a production run the native class carries the real
	 * name and the lookup never happens */
	static bool instanceOfShadowed(zv::Ref value, zend_class_entry *ce, const char *realName, size_t len)
	{
		zv::Ref v = value.deref();
		if (!v.isObject()) return false;
		if (ce != NULL && instanceof_function(v.asObject()->ce, ce)) return true;
		if (ce != NULL && zend_string_equals_cstr(ce->name, realName, len)) return false;
		zend_string *name = zend_string_init(realName, len, 0);
		zend_class_entry *twin = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
		zend_string_release(name);
		return twin != NULL && instanceof_function(v.asObject()->ce, twin);
	}

	/* TemplateTypeVariance::createStatic() / VerbosityLevel::cache() /
	 * VerbosityLevel::typeOnly() — the singleton of the class under its
	 * real name: the native class in a production run; under the prefixed
	 * activation the PHP twin's, which both the PHP types the delegate twin
	 * hands over and the native kernel (its twin-aware value readers)
	 * accept — a native singleton would be refused by the PHP types' typed
	 * parameters there. UNDEF = pending exception */
	static zv::Val kernelSingleton(const char *className, size_t classLen, const char *lcmethod, size_t methodLen)
	{
		return kernelStatic(className, classLen, lcmethod, methodLen, 0, NULL);
	}

	/* Class::method(...$args) by the class's REAL name, for the same reason:
	 * the native class in a production run, the PHP twin's body under the
	 * prefixed activation — TemplateTypeFactory picks the Template*Type
	 * class by testing the bound against the Type class entries, and the
	 * native one would widen a PHP bound (what the delegate twin's tags hand
	 * over there) to TemplateMixedType. UNDEF = pending exception */
	static zv::Val kernelStatic(const char *className, size_t classLen, const char *lcmethod, size_t methodLen, uint32_t argc, zval *argv)
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

	/* TemplateTypeScope::createWithClass($className) / TemplateTypeFactory::fromTemplateTag($scope, $tag) */
	static zv::Val templateTypeScopeWithClass(zval *className) { return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeScope"), PT_LC("createwithclass"), 1, className); }

	static zv::Val templateTypeFactoryFromTemplateTag(zval *scope, zval *tag)
	{
		zv::Args args{scope, tag};
		return kernelStatic(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeFactory"), PT_LC("fromtemplatetag"), 2, args);
	}

	static zv::Val verbosityLevelTypeOnly() { return kernelSingleton(PT_LC("PHPStan\\Type\\VerbosityLevel"), PT_LC("typeonly")); }
	static zv::Val verbosityLevelCache() { return kernelSingleton(PT_LC("PHPStan\\Type\\VerbosityLevel"), PT_LC("cache")); }
	static zv::Val templateTypeVarianceStatic() { return kernelSingleton(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeVariance"), PT_LC("createstatic")); }

	/* $type->getClassReflection() of an object type: natively for a native
	 * ObjectType (GenericObjectType included), by name otherwise */
	/* $type->getClassReflection() — the op entry of the RECEIVER's own class
	 * (GenericObjectType and StaticType override ObjectType's body, so a
	 * pt_object_type_get_class_reflection() shortcut would silently run the
	 * parent's), the PHP method for anything else */
	static zv::Val typeGetClassReflection(zv::Ref type)
	{
		zv::Ref v = type.deref();
		if (UNEXPECTED(!v.isObject())) {
			zend_throw_error(NULL, "Call to a member function getClassReflection() on %s", zend_zval_value_name(v.raw()));
			return zv::Val();
		}
		const pt_type_ops *ops = pt_type_ops_of(v.asObject()->ce);
		if (EXPECTED(ops != NULL)) {
			const pt_type_op_entry &entry = ops->entries[PT_OP_GET_CLASS_REFLECTION];
			if (EXPECTED(entry.fn != NULL)) return entry.fn(v.asObject(), entry.scope, 0, NULL);
		}
		return pt_type_call(v.asObject(), PT_LC("getclassreflection"), 0, NULL);
	}

	/* TemplateTypeHelper::resolveTemplateTypes($type,
	 * $this->getActiveTemplateTypeMapForAncestorResolution(),
	 * $this->getCallSiteVarianceMap(), TemplateTypeVariance::createStatic(),
	 * $keepErrorTypes) */
	zv::Val resolveAncestorTemplateTypes(zv::Ref type, bool keepErrorTypes)
	{
		zv::Val standins = getActiveTemplateTypeMapForAncestorResolution();
		if (UNEXPECTED(standins.isUndef())) return zv::Val();
		zv::Val callSiteVariances = getCallSiteVarianceMap();
		if (UNEXPECTED(callSiteVariances.isUndef())) return zv::Val();
		zv::Val staticVariance = templateTypeVarianceStatic();
		if (UNEXPECTED(staticVariance.isUndef())) return zv::Val();
		return pt_type_template_type_helper_resolve_template_types(type.raw(), standins.raw(), callSiteVariances.raw(), staticVariance.raw(), keepErrorTypes);
	}

	/* }}} */

	/* {{{ small helpers */

	/* is_file($fileName) — the internal function through its zend_function
	 * (persistent: resolved once); false = pending exception */
	[[nodiscard]] static bool isFile(zval *fileName, bool &out)
	{
		static zend_function *fn = nullptr;
		if (UNEXPECTED(fn == nullptr)) {
			fn = (zend_function *) zend_hash_str_find_ptr(EG(function_table), PT_LC("is_file"));
			if (UNEXPECTED(fn == nullptr)) {
				zend_throw_error(NULL, "phpstan_turbo: is_file() is not available");
				return false;
			}
		}
		zval result;
		zend_call_known_function(fn, NULL, NULL, &result, 1, fileName, NULL);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&result);
			return false;
		}
		out = zend_is_true(&result);
		zval_ptr_dtor(&result);
		return true;
	}

	/* catch (ReflectionException) — clears the pending exception when it
	 * is one (the class looked up by name: the reflection extension's
	 * header is not part of every PHP install) */
	static bool caughtReflectionException()
	{
		if (EG(exception) == NULL) return false;
		zend_string *name = zend_string_init(PT_LC("ReflectionException"), 0);
		zend_class_entry *ce = zend_lookup_class_ex(name, NULL, ZEND_FETCH_CLASS_NO_AUTOLOAD);
		zend_string_release(name);
		if (ce == NULL || !instanceof_function(EG(exception)->ce, ce)) return false;
		zend_clear_exception();
		return true;
	}

	/* throw new <class-map exception>(...$args) */
	static void throwNew(int classIdx, uint32_t argc, zval *argv)
	{
		zv::Val exception = pt_type_new(classIdx, argc, argv);
		if (UNEXPECTED(exception.isUndef())) return;
		zval z = exception.take();
		zend_throw_exception_object(&z);
	}

	/* throw new MissingMethodFromReflectionException($this->getName(), $methodName) */
	void throwMissingMethod(zend_string *methodName)
	{
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return;
		zv::Args args{name.raw(), methodName};
		throwNew(PT_CLASS_MISSING_METHOD_FROM_REFLECTION_EXCEPTION, 2, args);
	}

	/* throw new MissingPropertyFromReflectionException($this->getName(), $propertyName) */
	void throwMissingProperty(zend_string *propertyName)
	{
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return;
		zv::Args args{name.raw(), propertyName};
		throwNew(PT_CLASS_MISSING_PROPERTY_FROM_REFLECTION_EXCEPTION, 2, args);
	}

	/* implode(',', $strings) */
	static zv::Val implodeComma(zv::Ref list)
	{
		std::string out;
		bool first = true;
		for (auto entry : zv::ArrRef(list.raw())) {
			if (!first) {
				out += ',';
			}
			first = false;
			zend_string *s = zval_get_string(entry.value().raw());
			out.append(ZSTR_VAL(s), ZSTR_LEN(s));
			zend_string_release(s);
		}
		return zv::Val::string(out.data(), out.size());
	}

	static uint32_t countOf(zv::Ref array) { return array.isArray() ? zend_hash_num_elements(array.asArrayTable()) : 0; }

	/* the interned literals of the class */
	static zend_string *literal(zend_string *&memo, const char *value, size_t len)
	{
		if (UNEXPECTED(memo == nullptr)) {
			memo = zend_string_init_interned(value, len, 1);
		}
		return memo;
	}

	/* $this as a value (the twin's `return $this`) */
	zv::Val thisValue() const
	{
		zval self_;
		ZVAL_OBJ(&self_, self);
		return zv::Val::copyOf(zv::Ref(&self_));
	}

	/* ($callable)(...$args) — the stub PHPDoc callback; UNDEF = pending exception */
	static zv::Val callCallable(zv::Ref callable, uint32_t argc, zval *argv) { return pt_type_call_callable(callable.raw(), argc, argv); }

	/* $target[<the entry's key>] = $value — array_map()/array_filter() key preservation */
	static void setAtKey(zv::Arr &target, const zv::ArrayEntry &entry, zv::Val value)
	{
		if (entry.stringKeyOrNull() != NULL) {
			target.set(entry.stringKeyOrNull(), std::move(value));
		} else {
			target.arrRef().setIndex(entry.indexKey(), value.ref());
		}
	}

	/* the entry's key as the `string $name` an array-map callback receives */
	static zv::Val entryKeyAsValue(const zv::ArrayEntry &entry)
	{
		return entry.stringKeyOrNull() != NULL
			? zv::Val::string(entry.stringKeyOrNull())
			: zv::Val::adoptString(zend_long_to_str((zend_long) entry.indexKey()));
	}

	/* array_merge($a, $b): string keys overwrite, integer keys renumber */
	static zv::Val arrayMerge(zv::Ref a, zv::Ref b)
	{
		zv::Arr merged = zv::Arr::create(countOf(a) + countOf(b));
		zval *sources[2] = { a.raw(), b.raw() };
		for (int i = 0; i < 2; i++) {
			if (Z_TYPE_P(sources[i]) != IS_ARRAY) continue;
			for (auto entry : zv::ArrRef(sources[i])) {
				if (entry.stringKeyOrNull() != NULL) {
					merged.set(entry.stringKeyOrNull(), zv::Val::copyOf(entry.value()));
				} else {
					merged.push(entry.value());
				}
			}
		}

		return zv::Val(std::move(merged));
	}

	/* array_values(array_unique($list)) over a list of strings */
	static zv::Val arrayUniqueValues(zv::Ref list)
	{
		zv::Arr unique = zv::Arr::create(countOf(list));
		if (list.isArray()) {
			zv::ScratchTable seen(countOf(list));
			for (auto entry : zv::ArrRef(list.raw())) {
				zend_string *key = zval_get_string(entry.value().raw());
				if (zend_hash_find(seen.table(), key) == NULL) {
					zval marker;
					ZVAL_TRUE(&marker);
					zend_hash_add_new(seen.table(), key, &marker);
					unique.push(entry.value());
				}
				zend_string_release(key);
			}
		}

		return zv::Val(std::move(unique));
	}

	/* the twin's `private static array $resolvingTypeAliasImports` slot
	 * (borrowed; resolved once per activated class) — always the declaring
	 * class's, as `self::` names it */
	static zval *resolvingTypeAliasImports()
	{
		static zend_class_entry *memoCe = nullptr;
		static zval *memoSlot = nullptr;
		zend_class_entry *ce = pt_ce_class_reflection;
		if (UNEXPECTED(ce == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: ClassReflection is not registered");
			return NULL;
		}
		if (UNEXPECTED(memoCe != ce)) {
			if (CE_STATIC_MEMBERS(ce) == NULL) {
				zend_class_init_statics(ce);
			}
			zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("resolvingTypeAliasImports"));
			if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) == 0)) {
				zend_throw_error(NULL, "phpstan_turbo: ClassReflection has no static $resolvingTypeAliasImports");
				return NULL;
			}
			memoSlot = CE_STATIC_MEMBERS(ce) + info->offset;
			memoCe = ce;
		}
		return memoSlot;
	}

	/* catch (CircularTypeAliasDefinitionException) */
	static bool caughtCircularTypeAliasDefinitionException()
	{
		if (EG(exception) == NULL) return false;
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_CIRCULAR_TYPE_ALIAS_DEFINITION_EXCEPTION);
		if (ce == NULL || !instanceof_function(EG(exception)->ce, ce)) return false;
		zend_clear_exception();
		return true;
	}

	static zv::Val templateTypeVarianceInvariant() { return kernelSingleton(PT_LC("PHPStan\\Type\\Generic\\TemplateTypeVariance"), PT_LC("createinvariant")); }

	/* }}} */

	/* {{{ __construct */

	/* the 19 constructor arguments as zpp delivers them (NULL = a defaulted
	 * optional parameter) */
	struct ConstructArgs
	{
		zval *classReflectionFactory, *reflectionProvider, *initializerExprTypeResolver, *fileTypeMapper, *stubPhpDocProvider, *phpDocInheritanceResolver, *phpVersion, *signatureMapProvider, *deprecationProvider, *attributeReflectionFactory, *classReflectionExtensionRegistryProvider;
		zend_string *displayName;
		zval *reflection;
		zend_string *anonymousFilename;
		zval *resolvedTemplateTypeMap;
		zval *stubPhpDocBlockCallback;
		zend_string *extraCacheKey;
		zval *resolvedCallSiteVarianceMap;
		bool finalByKeywordOverride;
		bool finalByKeywordOverrideIsNull;
	};

	void construct(const ConstructArgs &a)
	{
		writeSlot(PT_CR_PROP_CLASS_REFLECTION_FACTORY, zv::Val::copyOf(zv::Ref(a.classReflectionFactory)));
		writeSlot(PT_CR_PROP_REFLECTION_PROVIDER, zv::Val::copyOf(zv::Ref(a.reflectionProvider)));
		writeSlot(PT_CR_PROP_INITIALIZER_EXPR_TYPE_RESOLVER, zv::Val::copyOf(zv::Ref(a.initializerExprTypeResolver)));
		writeSlot(PT_CR_PROP_FILE_TYPE_MAPPER, zv::Val::copyOf(zv::Ref(a.fileTypeMapper)));
		writeSlot(PT_CR_PROP_STUB_PHP_DOC_PROVIDER, zv::Val::copyOf(zv::Ref(a.stubPhpDocProvider)));
		writeSlot(PT_CR_PROP_PHP_DOC_INHERITANCE_RESOLVER, zv::Val::copyOf(zv::Ref(a.phpDocInheritanceResolver)));
		writeSlot(PT_CR_PROP_PHP_VERSION, zv::Val::copyOf(zv::Ref(a.phpVersion)));
		writeSlot(PT_CR_PROP_SIGNATURE_MAP_PROVIDER, zv::Val::copyOf(zv::Ref(a.signatureMapProvider)));
		writeSlot(PT_CR_PROP_DEPRECATION_PROVIDER, zv::Val::copyOf(zv::Ref(a.deprecationProvider)));
		writeSlot(PT_CR_PROP_ATTRIBUTE_REFLECTION_FACTORY, zv::Val::copyOf(zv::Ref(a.attributeReflectionFactory)));
		writeSlot(PT_CR_PROP_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER, zv::Val::copyOf(zv::Ref(a.classReflectionExtensionRegistryProvider)));
		writeSlot(PT_CR_PROP_DISPLAY_NAME, zv::Val::string(a.displayName));
		writeSlot(PT_CR_PROP_REFLECTION, zv::Val::copyOf(zv::Ref(a.reflection)));
		writeSlot(PT_CR_PROP_ANONYMOUS_FILENAME, a.anonymousFilename == NULL ? zv::Val::null() : zv::Val::string(a.anonymousFilename));
		writeSlot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP, optional(a.resolvedTemplateTypeMap));
		writeSlot(PT_CR_PROP_STUB_PHP_DOC_BLOCK_CALLBACK, optional(a.stubPhpDocBlockCallback));
		writeSlot(PT_CR_PROP_EXTRA_CACHE_KEY, a.extraCacheKey == NULL ? zv::Val::null() : zv::Val::string(a.extraCacheKey));
		writeSlot(PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP, optional(a.resolvedCallSiteVarianceMap));
		writeSlot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE, a.finalByKeywordOverrideIsNull ? zv::Val::null() : zv::Val::boolean(a.finalByKeywordOverride));
	}

	static zv::Val optional(zval *value) { return value == NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(value)); }

	/* }}} */

	zv::Val getNativeReflection() const
	{
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) return uninitializedProperty("reflection");
		return zv::Val::copyOf(reflection);
	}

	zv::Val getFileName()
	{
		zv::Ref filename = slot(PT_CR_PROP_FILENAME);
		if (!filename.isBool()) return zv::Val::copyOf(filename);

		zv::Ref anonymousFilename = slot(PT_CR_PROP_ANONYMOUS_FILENAME);
		if (UNEXPECTED(anonymousFilename.isUndef())) return uninitializedProperty("anonymousFilename");
		if (!anonymousFilename.isNull()) {
			zv::Val value = zv::Val::copyOf(anonymousFilename);
			writeSlot(PT_CR_PROP_FILENAME, zv::Val::copyOf(value.ref()));
			return value;
		}
		zv::Val fileName = reflectionCall(PT_LC("getfilename"), 0, NULL);
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		if (fileName.ref().isFalse()) {
			writeSlot(PT_CR_PROP_FILENAME, zv::Val::null());
			return zv::Val::null();
		}

		bool exists;
		if (UNEXPECTED(!isFile(fileName.raw(), exists))) return zv::Val();
		if (!exists) {
			writeSlot(PT_CR_PROP_FILENAME, zv::Val::null());
			return zv::Val::null();
		}

		writeSlot(PT_CR_PROP_FILENAME, zv::Val::copyOf(fileName.ref()));
		return fileName;
	}

	zv::Val getParentClass()
	{
		zv::Ref cached = slot(PT_CR_PROP_CACHED_PARENT_CLASS);
		if (!cached.isBool()) return zv::Val::copyOf(cached);

		zv::Val parentClass = reflectionCall(PT_LC("getparentclass"), 0, NULL);
		if (UNEXPECTED(parentClass.isUndef())) return zv::Val();

		if (parentClass.ref().isFalse()) {
			writeSlot(PT_CR_PROP_CACHED_PARENT_CLASS, zv::Val::null());
			return zv::Val::null();
		}

		/* $parentClass->getName() — the adapter's getter, pure: read once
		 * for the uses the twin makes of it */
		zv::Val parentName = callOn(parentClass.ref(), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(parentName.isUndef())) return zv::Val();
		zend_string *parentNameStr = zval_get_string(parentName.raw());
		zv::Str parentNameOwned = zv::Str::adopt(parentNameStr);

		zv::Val circularParentClassName = findCircularParentClassName(parentNameStr);
		if (UNEXPECTED(circularParentClassName.isUndef())) return zv::Val();
		if (!circularParentClassName.isNull()) {
			throwCircularReference(circularParentClassName.ref());
			return zv::Val();
		}

		zv::Val extendsTag = getFirstExtendsTag();
		if (UNEXPECTED(extendsTag.isUndef())) return zv::Val();

		if (!extendsTag.isNull()) {
			zv::Val extendedType = callOn(extendsTag.ref(), PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(extendedType.isUndef())) return zv::Val();
			bool valid;
			if (UNEXPECTED(!isValidAncestorType(extendedType.ref(), parentNameStr, valid))) return zv::Val();
			if (valid) {
				bool generic;
				if (UNEXPECTED(!isGeneric(generic))) return zv::Val();
				if (generic) {
					extendedType = resolveAncestorTemplateTypes(extendedType.ref(), false);
					if (UNEXPECTED(extendedType.isUndef())) return zv::Val();
				}

				if (!instanceOfShadowed(extendedType.ref(), pt_ce_generic_object_type, PT_LC(PT_CR_GENERIC_OBJECT_TYPE_NAME))) {
					return providerGetClass(parentNameStr);
				}

				zv::Val reflection = typeGetClassReflection(extendedType.ref());
				if (UNEXPECTED(reflection.isUndef())) return zv::Val();
				if (!reflection.isNull()) return reflection;
				return providerGetClass(parentNameStr);
			}
		}

		zv::Val parentReflection = providerGetClass(parentNameStr);
		if (UNEXPECTED(parentReflection.isUndef())) return zv::Val();
		bool parentGeneric;
		if (UNEXPECTED(!crIsGeneric(parentReflection.ref(), parentGeneric))) return zv::Val();
		if (parentGeneric) return crWithErrorTypes(parentReflection.ref());

		writeSlot(PT_CR_PROP_CACHED_PARENT_CLASS, zv::Val::copyOf(parentReflection.ref()));

		return parentReflection;
	}

	/* private: the class the parent class chain loops back to, null when
	 * the chain ends. BetterReflection only rejects a class extending
	 * itself directly; a cycle spanning several classes would make every
	 * walk over the hierarchy run forever */
	zv::Val findCircularParentClassName(zend_string *parentClassName)
	{
		zv::Ref memo = slot(PT_CR_PROP_CIRCULAR_PARENT_CLASS_NAME);
		if (!memo.isBool()) return zv::Val::copyOf(memo);

		writeSlot(PT_CR_PROP_CIRCULAR_PARENT_CLASS_NAME, zv::Val::null());

		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Arr visitedClassNames = zv::Arr::create(4);
		{
			zv::Str nameStr = zv::Str::adopt(zval_get_string(name.raw()));
			zv::Str lowercased = zv::Str::adopt(zend_string_tolower(nameStr.get()));
			visitedClassNames.set(lowercased.get(), zv::Val::boolean(true));
		}

		zv::Str currentClassName = zv::Str::copyOf(parentClassName);
		while (true) {
			zv::Str lowercased = zv::Str::adopt(zend_string_tolower(currentClassName.get()));
			if (visitedClassNames.arrRef().exists(lowercased.get())) {
				writeSlot(PT_CR_PROP_CIRCULAR_PARENT_CLASS_NAME, zv::Val::string(currentClassName.get()));
				return zv::Val::string(currentClassName.get());
			}

			visitedClassNames.set(lowercased.get(), zv::Val::boolean(true));

			bool has;
			if (UNEXPECTED(!providerHasClass(currentClassName.get(), has))) return zv::Val();
			if (!has) return zv::Val::null();

			/* $this->reflectionProvider->getClass($currentClassName)->reflection->getParentClass() */
			zv::Val classReflection = providerGetClass(currentClassName.get());
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			zv::Val reflection = crGetNativeReflection(classReflection.ref());
			if (UNEXPECTED(reflection.isUndef())) return zv::Val();
			zv::Val parentClass = callOn(reflection.ref(), PT_LC("getparentclass"), 0, NULL);
			if (UNEXPECTED(parentClass.isUndef())) return zv::Val();
			if (parentClass.ref().isFalse()) return zv::Val::null();

			zv::Val parentName = callOn(parentClass.ref(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(parentName.isUndef())) return zv::Val();
			currentClassName = zv::Str::adopt(zval_get_string(parentName.raw()));
		}
	}

	/* throw CircularReference::fromClassName($className) */
	static void throwCircularReference(zv::Ref className)
	{
		static const char circularReference[] = "PHPStan\\BetterReflection\\Reflection\\Exception\\CircularReference";
		zv::Str name = zv::Str::adopt(zend_string_init(circularReference, sizeof(circularReference) - 1, 0));
		zend_class_entry *ce = zend_lookup_class(name.get());
		if (UNEXPECTED(ce == NULL)) {
			if (!EG(exception)) zend_throw_error(NULL, "Class \"%s\" not found", circularReference);
			return;
		}
		zv::Val exception = pt_type_call_static_ce(ce, PT_LC("fromclassname"), 1, className.raw());
		if (UNEXPECTED(exception.isUndef())) return;
		zval thrown = exception.take();
		zend_throw_exception_object(&thrown);
	}

	zv::Val getName()
	{
		zv::Ref name = slot(PT_CR_PROP_NAME);
		if (EXPECTED(!name.isNull())) return zv::Val::copyOf(name);
		zv::Val computed = reflectionCall(PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(computed.isUndef())) return zv::Val();
		writeSlot(PT_CR_PROP_NAME, zv::Val::copyOf(computed.ref()));
		return computed;
	}

	zv::Val displayName() const
	{
		zv::Ref displayName = slot(PT_CR_PROP_DISPLAY_NAME);
		if (UNEXPECTED(displayName.isUndef())) return uninitializedProperty("displayName");
		return zv::Val::copyOf(displayName);
	}

	/* the `$templateTypes` list both getDisplayName() and getCacheKey()
	 * build: the active template types projected through the call-site
	 * variances (an entry with no variance skipped), described at the
	 * given verbosity */
	zv::Val describeTemplateTypes(bool forCacheKey)
	{
		zv::Val varianceMap = getCallSiteVarianceMap();
		if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
		zv::Val variances = callOn(varianceMap.ref(), PT_LC("getvariances"), 0, NULL);
		if (UNEXPECTED(variances.isUndef())) return zv::Val();
		zv::Val activeMap = getActiveTemplateTypeMap();
		if (UNEXPECTED(activeMap.isUndef())) return zv::Val();
		zv::Val types = callOn(activeMap.ref(), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val level = forCacheKey ? verbosityLevelCache() : verbosityLevelTypeOnly();
		if (UNEXPECTED(level.isUndef())) return zv::Val();
		zv::Arr templateTypes = zv::Arr::create(countOf(types.ref()));
		if (!types.ref().isArray()) return zv::Val(std::move(templateTypes));
		HashTable *variancesTable = variances.ref().isArray() ? variances.ref().asArrayTable() : NULL;
		for (auto entry : zv::ArrRef(types.raw())) {
			/* $variances[$name] ?? null — the same PHP array key on both sides */
			zval *variance = NULL;
			if (variancesTable != NULL) {
				zend_string *key = entry.stringKeyOrNull();
				variance = key != NULL ? zend_hash_find(variancesTable, key) : zend_hash_index_find(variancesTable, entry.indexKey());
			}
			if (variance == NULL || Z_TYPE_P(variance) == IS_NULL) continue;
			zv::Val described = pt_type_projection_helper_describe(entry.value().raw(), variance, level.raw());
			if (UNEXPECTED(described.isUndef())) return zv::Val();
			templateTypes.push(std::move(described));
		}
		return zv::Val(std::move(templateTypes));
	}

	zv::Val getDisplayName(bool withTemplateTypes)
	{
		if (!withTemplateTypes) return displayName();
		zv::Ref resolved = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolved.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		if (resolved.isNull()) return displayName();
		zv::Val resolvedTypes = callOn(resolved, PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(resolvedTypes.isUndef())) return zv::Val();
		if (countOf(resolvedTypes.ref()) == 0) return displayName();

		zv::Val templateTypes = describeTemplateTypes(false);
		if (UNEXPECTED(templateTypes.isUndef())) return zv::Val();
		zv::Val name = displayName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val joined = implodeComma(templateTypes.ref());
		return zv::Val::adoptString(zend_strpprintf(0, "%s<%s>", Z_STRVAL_P(name.raw()), Z_STRVAL_P(joined.raw())));
	}

	zv::Val getCacheKey()
	{
		zv::Ref cacheKey = slot(PT_CR_PROP_CACHE_KEY);
		if (!cacheKey.isNull()) return zv::Val::copyOf(cacheKey);

		zv::Val name = displayName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		std::string key(Z_STRVAL_P(name.raw()), Z_STRLEN_P(name.raw()));

		zv::Ref resolved = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolved.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		if (!resolved.isNull()) {
			zv::Val templateTypes = describeTemplateTypes(true);
			if (UNEXPECTED(templateTypes.isUndef())) return zv::Val();
			zv::Val joined = implodeComma(templateTypes.ref());
			key += '<';
			key.append(Z_STRVAL_P(joined.raw()), Z_STRLEN_P(joined.raw()));
			key += '>';
		}

		bool hasOverride = false;
		if (UNEXPECTED(!hasFinalByKeywordOverride(hasOverride))) return zv::Val();
		if (hasOverride) {
			bool finalByKeyword = false;
			if (UNEXPECTED(!isFinalByKeyword(finalByKeyword))) return zv::Val();
			key += finalByKeyword ? "-f=t" : "-f=f";
		}

		zv::Ref extraCacheKey = slot(PT_CR_PROP_EXTRA_CACHE_KEY);
		if (UNEXPECTED(extraCacheKey.isUndef())) return uninitializedProperty("extraCacheKey");
		if (!extraCacheKey.isNull()) {
			zend_string *extra = zval_get_string(extraCacheKey.raw());
			key += '-';
			key.append(ZSTR_VAL(extra), ZSTR_LEN(extra));
			zend_string_release(extra);
		}

		zv::Val result = zv::Val::string(key.data(), key.size());
		writeSlot(PT_CR_PROP_CACHE_KEY, zv::Val::copyOf(result.ref()));

		return result;
	}

	zv::Val getClassHierarchyDistances()
	{
		zv::Ref memo = slot(PT_CR_PROP_CLASS_HIERARCHY_DISTANCES);
		if (memo.isNull()) {
			zend_long distance = 0;
			zv::Arr distances = zv::Arr::create(8);
			zv::Val name = getName();
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			distances.set(Z_STR_P(name.raw()), zv::Val::integer(distance));
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			zv::Val current = zv::Val::copyOf(zv::Ref(&selfZv));
			zv::Val ownReflection = getNativeReflection();
			if (UNEXPECTED(ownReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!addTraitDistances(ownReflection.ref(), distance, distances))) return zv::Val();

			/* while (($currentClassReflection = $currentClassReflection->getParentClass()) !== null):
			 * walking the parents through getParentClass() and not through
			 * the native reflection makes a cyclic class hierarchy end in a
			 * CircularReference exception instead of looping forever */
			while (true) {
				zv::Val parent = crGetParentClass(current.ref());
				if (UNEXPECTED(parent.isUndef())) return zv::Val();
				if (parent.isNull()) break;
				current = std::move(parent);
				distance++;
				zv::Val parentName = crGetName(current.ref());
				if (UNEXPECTED(parentName.isUndef())) return zv::Val();
				zend_string *parentNameStr = zval_get_string(parentName.raw());
				if (!distances.arrRef().exists(parentNameStr)) {
					distances.set(parentNameStr, zv::Val::integer(distance));
				}
				zend_string_release(parentNameStr);
				zv::Val parentReflection = crGetNativeReflection(current.ref());
				if (UNEXPECTED(parentReflection.isUndef())) return zv::Val();
				if (UNEXPECTED(!addTraitDistances(parentReflection.ref(), distance, distances))) return zv::Val();
			}

			zv::Val nativeReflection = getNativeReflection();
			if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
			zv::Val interfaces = callOn(nativeReflection.ref(), PT_LC("getinterfaces"), 0, NULL);
			if (UNEXPECTED(interfaces.isUndef())) return zv::Val();
			if (interfaces.ref().isArray()) {
				for (auto entry : zv::ArrRef(interfaces.raw())) {
					distance++;
					zv::Val interfaceName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
					if (UNEXPECTED(interfaceName.isUndef())) return zv::Val();
					zend_string *interfaceNameStr = zval_get_string(interfaceName.raw());
					if (!distances.arrRef().exists(interfaceNameStr)) {
						distances.set(interfaceNameStr, zv::Val::integer(distance));
					}
					zend_string_release(interfaceNameStr);
				}
			}

			writeSlot(PT_CR_PROP_CLASS_HIERARCHY_DISTANCES, zv::Val::copyOf(distances.ref()));
			return zv::Val(std::move(distances));
		}

		return zv::Val::copyOf(memo);
	}

	/* the `foreach ($this->collectTraits($class) as $trait)` blocks of
	 * getClassHierarchyDistances() */
	bool addTraitDistances(zv::Ref classReflection, zend_long &distance, zv::Arr &distances)
	{
		zv::Val traits = collectTraits(classReflection);
		if (UNEXPECTED(traits.isUndef())) return false;
		for (auto entry : zv::ArrRef(traits.raw())) {
			distance++;
			zv::Val traitName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(traitName.isUndef())) return false;
			zend_string *traitNameStr = zval_get_string(traitName.raw());
			bool exists = distances.arrRef().exists(traitNameStr);
			if (!exists) {
				distances.set(traitNameStr, zv::Val::integer(distance));
			}
			zend_string_release(traitNameStr);
		}
		return true;
	}

	/* private; a list of the class's traits, breadth-first through the
	 * traits' own traits (the twin's array_shift() queue), each trait name
	 * once - traits can use each other in a cycle and the reflection
	 * objects are not guaranteed to be identical; UNDEF = pending
	 * exception */
	zv::Val collectTraits(zv::Ref classReflection)
	{
		zv::Arr traits = zv::Arr::create(4);
		zv::Val initial = callOn(classReflection, PT_LC("gettraits"), 0, NULL);
		if (UNEXPECTED(initial.isUndef())) return zv::Val();
		std::vector<zv::Val> queue;
		if (initial.ref().isArray()) {
			for (auto entry : zv::ArrRef(initial.raw())) {
				queue.push_back(zv::Val::copyOf(entry.value()));
			}
		}

		for (size_t head = 0; head < queue.size(); head++) {
			zv::Val trait = zv::Val::copyOf(queue[head].ref());
			/* $trait->getName() — the adapter's getter, pure: read once for
			 * both uses */
			zv::Val traitName = callOn(trait.ref(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(traitName.isUndef())) return zv::Val();
			zv::Str traitNameStr = zv::Str::adopt(zval_get_string(traitName.raw()));
			if (traits.arrRef().exists(traitNameStr.get())) continue;

			traits.set(traitNameStr.get(), zv::Val::copyOf(trait.ref()));

			zv::Val subTraits = callOn(trait.ref(), PT_LC("gettraits"), 0, NULL);
			if (UNEXPECTED(subTraits.isUndef())) return zv::Val();
			if (!subTraits.ref().isArray()) continue;
			for (auto entry : zv::ArrRef(subTraits.raw())) {
				queue.push_back(zv::Val::copyOf(entry.value()));
			}
		}

		/* array_values($traits) */
		zv::Arr list = zv::Arr::create(zend_hash_num_elements(traits.table()));
		for (auto entry : traits.arrRef()) {
			list.push(entry.value());
		}
		return zv::Val(std::move(list));
	}

	bool allowsDynamicProperties(bool &out)
	{
		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return false;
		if (isEnum_) {
			out = false;
			return true;
		}

		zv::Val deprecates = callService(PT_CR_PROP_PHP_VERSION, "phpVersion", PT_LC("deprecatesdynamicproperties"), 0, NULL);
		if (UNEXPECTED(deprecates.isUndef())) return false;
		if (!zend_is_true(deprecates.raw())) {
			out = true;
			return true;
		}

		static zend_string *magicGet = nullptr, *magicSet = nullptr, *magicIsset = nullptr;
		bool hasMagicMethod;
		if (UNEXPECTED(!hasNativeMethod(literal(magicGet, PT_LC("__get")), hasMagicMethod))) return false;
		if (!hasMagicMethod && UNEXPECTED(!hasNativeMethod(literal(magicSet, PT_LC("__set")), hasMagicMethod))) return false;
		if (!hasMagicMethod && UNEXPECTED(!hasNativeMethod(literal(magicIsset, PT_LC("__isset")), hasMagicMethod))) return false;
		if (hasMagicMethod) {
			out = true;
			return true;
		}

		zv::Val requireExtendsTags = getRequireExtendsTags();
		if (UNEXPECTED(requireExtendsTags.isUndef())) return false;
		if (requireExtendsTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(requireExtendsTags.raw())) {
				zv::Val type = callOn(entry.value(), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(type.isUndef())) return false;
				if (!instanceOfShadowed(type.ref(), pt_ce_object_type, PT_LC(PT_CR_OBJECT_TYPE_NAME))) continue;

				zv::Val reflection = typeGetClassReflection(type.ref());
				if (UNEXPECTED(reflection.isUndef())) return false;
				if (reflection.isNull()) continue;
				bool allows;
				if (UNEXPECTED(!crAllowsDynamicProperties(reflection.ref(), allows))) return false;
				if (!allows) continue;

				out = true;
				return true;
			}
		}

		bool readOnly;
		if (UNEXPECTED(!isReadOnly(readOnly))) return false;
		if (readOnly) {
			out = false;
			return true;
		}

		zend_object *provider = service(PT_CR_PROP_REFLECTION_PROVIDER, "reflectionProvider");
		if (UNEXPECTED(provider == NULL)) return false;
		zv::Args crateArgs{provider, self};
		zv::Val isCrate = pt_type_call_static(PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION, PT_LC("isuniversalobjectcrate"), 2, crateArgs);
		if (UNEXPECTED(isCrate.isUndef())) return false;
		if (zend_is_true(isCrate.raw())) {
			out = true;
			return true;
		}

		static zend_string *allowDynamicProperties = nullptr;
		zval attributeName;
		ZVAL_STR(&attributeName, literal(allowDynamicProperties, PT_LC("AllowDynamicProperties")));
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val cls = zv::Val::copyOf(zv::Ref(&selfZv));
		zv::Val attributes;
		do {
			zv::Val reflection = crReflection(cls.ref());
			if (UNEXPECTED(reflection.isUndef())) return false;
			attributes = callOn(reflection.ref(), PT_LC("getattributes"), 1, &attributeName);
			if (UNEXPECTED(attributes.isUndef())) return false;
			cls = crGetParentClass(cls.ref());
			if (UNEXPECTED(cls.isUndef())) return false;
		} while (attributes.ref().isArray() && countOf(attributes.ref()) == 0 && !cls.isNull());

		out = !(attributes.ref().isArray() && countOf(attributes.ref()) == 0);
		return true;
	}

	/** @deprecated Use hasInstanceProperty or hasStaticProperty instead */
	bool hasProperty(zend_string *propertyName, bool &out)
	{
		zval *cached = memoFind(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName);
		if (cached != NULL) {
			out = zend_is_true(cached);
			return true;
		}

		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return false;
		if (isEnum_) {
			bool hasNative;
			if (UNEXPECTED(!hasNativeProperty(propertyName, hasNative))) return false;
			out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, hasNative);
			return true;
		}

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, true);
			return true;
		}

		bool allowsDynamic;
		if (UNEXPECTED(!allowsDynamicProperties(allowsDynamic))) return false;
		if (allowsDynamic) {
			zv::Val extensions = propertiesClassReflectionExtensions();
			if (UNEXPECTED(extensions.isUndef())) return false;
			if (extensions.ref().isArray()) {
				for (auto entry : zv::ArrRef(extensions.raw())) {
					if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasproperty"), propertyName, has))) return false;
					if (has) {
						out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, true);
						return true;
					}
				}
			}
		}

		zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
		if (UNEXPECTED(requireExtends.isUndef())) return false;
		if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasproperty"), propertyName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, true);
			return true;
		}

		out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, false);
		return true;
	}

	bool hasInstanceProperty(zend_string *propertyName, bool &out)
	{
		zval *cached = memoFind(PT_CR_PROP_HAS_INSTANCE_PROPERTY_CACHE, propertyName);
		if (cached != NULL) {
			out = zend_is_true(cached);
			return true;
		}

		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return false;
		if (isEnum_) {
			bool hasNative;
			if (UNEXPECTED(!hasNativeProperty(propertyName, hasNative))) return false;
			out = memoSetBool(PT_CR_PROP_HAS_INSTANCE_PROPERTY_CACHE, propertyName, hasNative);
			return true;
		}

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return false;
		if (has) {
			zv::Val property = extensionCall(phpExtension.ref(), PT_LC("getnativeproperty"), propertyName);
			if (UNEXPECTED(property.isUndef())) return false;
			bool isStatic;
			if (UNEXPECTED(!callBool(property.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return false;
			if (!isStatic) {
				out = memoSetBool(PT_CR_PROP_HAS_INSTANCE_PROPERTY_CACHE, propertyName, true);
				return true;
			}
		}

		bool allowsDynamic;
		if (UNEXPECTED(!allowsDynamicProperties(allowsDynamic))) return false;
		if (allowsDynamic) {
			zv::Val extensions = propertiesClassReflectionExtensions();
			if (UNEXPECTED(extensions.isUndef())) return false;
			if (extensions.ref().isArray()) {
				for (auto entry : zv::ArrRef(extensions.raw())) {
					if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasproperty"), propertyName, has))) return false;
					if (!has) continue;
					zv::Val property = extensionCall(entry.value(), PT_LC("getproperty"), propertyName);
					if (UNEXPECTED(property.isUndef())) return false;
					bool isStatic;
					if (UNEXPECTED(!callBool(property.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return false;
					if (isStatic) continue;
					out = memoSetBool(PT_CR_PROP_HAS_INSTANCE_PROPERTY_CACHE, propertyName, true);
					return true;
				}
			}
		}

		/* the twin writes the last two answers into $this->hasPropertyCache
		 * (not the instance cache) — kept as it is */
		zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
		if (UNEXPECTED(requireExtends.isUndef())) return false;
		if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasinstanceproperty"), propertyName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, true);
			return true;
		}

		out = memoSetBool(PT_CR_PROP_HAS_PROPERTY_CACHE, propertyName, false);
		return true;
	}

	bool hasStaticProperty(zend_string *propertyName, bool &out)
	{
		zval *cached = memoFind(PT_CR_PROP_HAS_STATIC_PROPERTY_CACHE, propertyName);
		if (cached != NULL) {
			out = zend_is_true(cached);
			return true;
		}

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return false;
		if (has) {
			zv::Val property = extensionCall(phpExtension.ref(), PT_LC("getnativeproperty"), propertyName);
			if (UNEXPECTED(property.isUndef())) return false;
			bool isStatic;
			if (UNEXPECTED(!callBool(property.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return false;
			if (isStatic) {
				out = memoSetBool(PT_CR_PROP_HAS_STATIC_PROPERTY_CACHE, propertyName, true);
				return true;
			}
		}

		zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
		if (UNEXPECTED(requireExtends.isUndef())) return false;
		if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasstaticproperty"), propertyName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_STATIC_PROPERTY_CACHE, propertyName, true);
			return true;
		}

		out = memoSetBool(PT_CR_PROP_HAS_STATIC_PROPERTY_CACHE, propertyName, false);
		return true;
	}

	bool hasMethod(zend_string *methodName, bool &out)
	{
		zval *cached = memoFind(PT_CR_PROP_HAS_METHOD_CACHE, methodName);
		if (cached != NULL) {
			out = zend_is_true(cached);
			return true;
		}

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasmethod"), methodName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_METHOD_CACHE, methodName, true);
			return true;
		}

		zv::Val extensions = methodsClassReflectionExtensions();
		if (UNEXPECTED(extensions.isUndef())) return false;
		if (extensions.ref().isArray()) {
			for (auto entry : zv::ArrRef(extensions.raw())) {
				if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasmethod"), methodName, has))) return false;
				if (has) {
					out = memoSetBool(PT_CR_PROP_HAS_METHOD_CACHE, methodName, true);
					return true;
				}
			}
		}

		zv::Val requireExtends = requireExtendsMethodsClassReflectionExtension();
		if (UNEXPECTED(requireExtends.isUndef())) return false;
		if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasmethod"), methodName, has))) return false;
		if (has) {
			out = memoSetBool(PT_CR_PROP_HAS_METHOD_CACHE, methodName, true);
			return true;
		}

		out = memoSetBool(PT_CR_PROP_HAS_METHOD_CACHE, methodName, false);
		return true;
	}

	/* the `$key` of getMethod() / getProperty() / getInstanceProperty():
	 * the member name, suffixed by the scope's class cache key inside a
	 * class; UNDEF = pending exception */
	static zv::Val memberKey(zend_string *memberName, zval *scope)
	{
		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		if (!inClass) return zv::Val::string(memberName);
		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val cacheKey = crGetCacheKey(classReflection.ref());
		if (UNEXPECTED(cacheKey.isUndef())) return zv::Val();
		zend_string *cacheKeyStr = zval_get_string(cacheKey.raw());
		zv::Val key = zv::Val::adoptString(zend_strpprintf(0, "%s-%s", ZSTR_VAL(memberName), ZSTR_VAL(cacheKeyStr)));
		zend_string_release(cacheKeyStr);
		return key;
	}

	zv::Val getMethod(zend_string *methodName, zval *scope)
	{
		zv::Val keyVal = memberKey(methodName, scope);
		if (UNEXPECTED(keyVal.isUndef())) return zv::Val();
		zend_string *key = Z_STR_P(keyVal.raw());

		zval *cached = memoFind(PT_CR_PROP_METHODS, key);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasmethod"), methodName, has))) return zv::Val();
		if (has) {
			zv::Val method = extensionCall(phpExtension.ref(), PT_LC("getmethod"), methodName);
			if (UNEXPECTED(method.isUndef())) return zv::Val();
			bool canCall;
			if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("cancallmethod"), 1, method.raw(), canCall))) return zv::Val();
			memoSet(PT_CR_PROP_METHODS, key, zv::Val::copyOf(method.ref()));
			if (canCall) return method;
		}

		zv::Val extensions = methodsClassReflectionExtensions();
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (extensions.ref().isArray()) {
			for (auto entry : zv::ArrRef(extensions.raw())) {
				if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasmethod"), methodName, has))) return zv::Val();
				if (!has) continue;

				zv::Val naked = extensionCall(entry.value(), PT_LC("getmethod"), methodName);
				if (UNEXPECTED(naked.isUndef())) return zv::Val();
				zv::Val method = wrapExtendedMethod(std::move(naked));
				if (UNEXPECTED(method.isUndef())) return zv::Val();
				bool canCall;
				if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("cancallmethod"), 1, method.raw(), canCall))) return zv::Val();
				memoSet(PT_CR_PROP_METHODS, key, zv::Val::copyOf(method.ref()));
				if (canCall) return method;
			}
		}

		if (!memoIsset(PT_CR_PROP_METHODS, key)) {
			zv::Val requireExtends = requireExtendsMethodsClassReflectionExtension();
			if (UNEXPECTED(requireExtends.isUndef())) return zv::Val();
			if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasmethod"), methodName, has))) return zv::Val();
			if (has) {
				zv::Val method = extensionCall(requireExtends.ref(), PT_LC("getmethod"), methodName);
				if (UNEXPECTED(method.isUndef())) return zv::Val();
				memoSet(PT_CR_PROP_METHODS, key, std::move(method));
			}
		}

		if (!memoIsset(PT_CR_PROP_METHODS, key)) {
			throwMissingMethod(methodName);
			return zv::Val();
		}

		return zv::Val::copyOf(zv::Ref(memoFind(PT_CR_PROP_METHODS, key)));
	}

	/* private */
	static zv::Val wrapExtendedMethod(zv::Val method)
	{
		bool isExtended;
		if (UNEXPECTED(!pt_type_instanceof(method.raw(), PT_CLASS_EXTENDED_METHOD_REFLECTION, isExtended))) return zv::Val();
		if (isExtended) return method;

		return pt_type_new(PT_CLASS_WRAPPED_EXTENDED_METHOD_REFLECTION, 1, method.raw());
	}

	/* private */
	static zv::Val wrapExtendedProperty(zend_string *propertyName, zv::Val property)
	{
		bool isExtended;
		if (UNEXPECTED(!pt_type_instanceof(property.raw(), PT_CLASS_EXTENDED_PROPERTY_REFLECTION, isExtended))) return zv::Val();
		if (isExtended) return property;

		zv::Args args{propertyName, property.raw()};
		return pt_type_new(PT_CLASS_WRAPPED_EXTENDED_PROPERTY_REFLECTION, 2, args);
	}

	bool hasNativeMethod(zend_string *methodName, bool &out)
	{
		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		return extensionBool(phpExtension.ref(), PT_LC("hasnativemethod"), methodName, out);
	}

	zv::Val getNativeMethod(zend_string *methodName)
	{
		bool has;
		if (UNEXPECTED(!hasNativeMethod(methodName, has))) return zv::Val();
		if (!has) {
			throwMissingMethod(methodName);
			return zv::Val();
		}
		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
		return extensionCall(phpExtension.ref(), PT_LC("getnativemethod"), methodName);
	}

	bool hasConstructor(bool &out)
	{
		zv::Val constructor = findConstructor();
		if (UNEXPECTED(constructor.isUndef())) return false;
		out = !constructor.isNull();
		return true;
	}

	zv::Val getConstructor()
	{
		zv::Val constructor = findConstructor();
		if (UNEXPECTED(constructor.isUndef())) return zv::Val();
		if (constructor.isNull()) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val name = callOn(constructor.ref(), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *nameStr = zval_get_string(name.raw());
		zv::Val method = getNativeMethod(nameStr);
		zend_string_release(nameStr);
		return method;
	}

	/* private; the adapter's ReflectionMethod or null */
	zv::Val findConstructor()
	{
		zv::Val constructor = reflectionCall(PT_LC("getconstructor"), 0, NULL);
		if (UNEXPECTED(constructor.isUndef())) return zv::Val();
		if (constructor.isNull()) return zv::Val::null();

		zv::Val legacy = callService(PT_CR_PROP_PHP_VERSION, "phpVersion", PT_LC("supportslegacyconstructor"), 0, NULL);
		if (UNEXPECTED(legacy.isUndef())) return zv::Val();
		if (zend_is_true(legacy.raw())) return constructor;

		zv::Val name = callOn(constructor.ref(), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *nameStr = zval_get_string(name.raw());
		zend_string *lower = zend_string_tolower(nameStr);
		bool isConstruct = zend_string_equals_literal(lower, "__construct");
		zend_string_release(lower);
		zend_string_release(nameStr);
		if (!isConstruct) return zv::Val::null();

		return constructor;
	}

	/** @internal */
	bool evictPrivateSymbols()
	{
		static const uint32_t tables[] = { PT_CR_PROP_CONSTANTS, PT_CR_PROP_PROPERTIES, PT_CR_PROP_INSTANCE_PROPERTIES, PT_CR_PROP_STATIC_PROPERTIES, PT_CR_PROP_METHODS };
		for (uint32_t index : tables) {
			zval *table = OBJ_PROP_NUM(self, index);
			if (Z_TYPE_P(table) != IS_ARRAY) continue;
			/* foreach iterates the array as it was; the unset()s separate
			 * the slot's table from that snapshot */
			zv::Val snapshot = zv::Val::copyOf(zv::Ref(table));
			for (auto entry : zv::ArrRef(snapshot.raw())) {
				bool isPrivate;
				if (UNEXPECTED(!callBool(entry.value(), PT_LC("isprivate"), 0, NULL, isPrivate))) return false;
				if (!isPrivate) continue;
				SEPARATE_ARRAY(table);
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL) {
					zend_hash_del(Z_ARRVAL_P(table), key);
				} else {
					zend_hash_index_del(Z_ARRVAL_P(table), entry.indexKey());
				}
			}
		}
		/* PhpClassReflectionExtension's member caches are governed by their
		 * own LRU instead of per-class private-symbol eviction */
		return true;
	}

	/** @deprecated Use getInstanceProperty or getStaticProperty */
	zv::Val getProperty(zend_string *propertyName, zval *scope)
	{
		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return zv::Val();
		if (isEnum_) return getNativeProperty(propertyName);

		zv::Val keyVal = memberKey(propertyName, scope);
		if (UNEXPECTED(keyVal.isUndef())) return zv::Val();
		zend_string *key = Z_STR_P(keyVal.raw());

		zval *cached = memoFind(PT_CR_PROP_PROPERTIES, key);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
		if (has) {
			/* $this->classReflectionExtensionRegistryProvider->getRegistry()->getPhpClassReflectionExtension()->getProperty($this, $propertyName, $scope) */
			zv::Val freshExtension = phpClassReflectionExtension();
			if (UNEXPECTED(freshExtension.isUndef())) return zv::Val();
			zv::Val property = extensionGetProperty(freshExtension.ref(), propertyName, scope);
			if (UNEXPECTED(property.isUndef())) return zv::Val();
			bool canRead;
			if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("canreadproperty"), 1, property.raw(), canRead))) return zv::Val();
			memoSet(PT_CR_PROP_PROPERTIES, key, zv::Val::copyOf(property.ref()));
			if (canRead) return property;
		}

		bool allowsDynamic;
		if (UNEXPECTED(!allowsDynamicProperties(allowsDynamic))) return zv::Val();
		if (allowsDynamic) {
			zv::Val extensions = propertiesClassReflectionExtensions();
			if (UNEXPECTED(extensions.isUndef())) return zv::Val();
			if (extensions.ref().isArray()) {
				for (auto entry : zv::ArrRef(extensions.raw())) {
					if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
					if (!has) continue;

					zv::Val naked = extensionCall(entry.value(), PT_LC("getproperty"), propertyName);
					if (UNEXPECTED(naked.isUndef())) return zv::Val();
					zv::Val property = wrapExtendedProperty(propertyName, std::move(naked));
					if (UNEXPECTED(property.isUndef())) return zv::Val();
					bool canRead;
					if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("canreadproperty"), 1, property.raw(), canRead))) return zv::Val();
					memoSet(PT_CR_PROP_PROPERTIES, key, zv::Val::copyOf(property.ref()));
					if (canRead) return property;
				}
			}
		}

		/* For BC purpose */
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
		if (has) {
			zv::Val property = extensionGetProperty(phpExtension.ref(), propertyName, scope);
			if (UNEXPECTED(property.isUndef())) return zv::Val();
			memoSet(PT_CR_PROP_PROPERTIES, key, zv::Val::copyOf(property.ref()));
			return property;
		}

		if (!memoIsset(PT_CR_PROP_PROPERTIES, key)) {
			zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
			if (UNEXPECTED(requireExtends.isUndef())) return zv::Val();
			if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
			if (has) {
				zv::Val property = extensionCall(requireExtends.ref(), PT_LC("getproperty"), propertyName);
				if (UNEXPECTED(property.isUndef())) return zv::Val();
				memoSet(PT_CR_PROP_PROPERTIES, key, std::move(property));
			}
		}

		if (!memoIsset(PT_CR_PROP_PROPERTIES, key)) {
			throwMissingProperty(propertyName);
			return zv::Val();
		}

		return zv::Val::copyOf(zv::Ref(memoFind(PT_CR_PROP_PROPERTIES, key)));
	}

	/* $phpClassReflectionExtension->getProperty($this, $propertyName, $scope) */
	zv::Val extensionGetProperty(zv::Ref extension, zend_string *propertyName, zval *scope)
	{
		zv::Args args{self, propertyName, scope};
		return callOn(extension, PT_LC("getproperty"), 3, args);
	}

	zv::Val getInstanceProperty(zend_string *propertyName, zval *scope)
	{
		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return zv::Val();
		if (isEnum_) return getNativeProperty(propertyName);

		zv::Val keyVal = memberKey(propertyName, scope);
		if (UNEXPECTED(keyVal.isUndef())) return zv::Val();
		zend_string *key = Z_STR_P(keyVal.raw());

		if (!memoIsset(PT_CR_PROP_INSTANCE_PROPERTIES, key)) {
			zv::Val phpExtension = phpClassReflectionExtension();
			if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
			bool has;
			if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
			if (has) {
				zv::Val property = extensionGetProperty(phpExtension.ref(), propertyName, scope);
				if (UNEXPECTED(property.isUndef())) return zv::Val();
				bool isStatic;
				if (UNEXPECTED(!callBool(property.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return zv::Val();
				if (!isStatic) {
					bool canRead;
					if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("canreadproperty"), 1, property.raw(), canRead))) return zv::Val();
					memoSet(PT_CR_PROP_INSTANCE_PROPERTIES, key, zv::Val::copyOf(property.ref()));
					if (canRead) return property;
				}
			}

			bool allowsDynamic;
			if (UNEXPECTED(!allowsDynamicProperties(allowsDynamic))) return zv::Val();
			if (allowsDynamic) {
				zv::Val extensions = propertiesClassReflectionExtensions();
				if (UNEXPECTED(extensions.isUndef())) return zv::Val();
				if (extensions.ref().isArray()) {
					for (auto entry : zv::ArrRef(extensions.raw())) {
						if (UNEXPECTED(!extensionBool(entry.value(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
						if (!has) continue;

						zv::Val naked = extensionCall(entry.value(), PT_LC("getproperty"), propertyName);
						if (UNEXPECTED(naked.isUndef())) return zv::Val();
						bool isStatic;
						if (UNEXPECTED(!callBool(naked.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return zv::Val();
						if (isStatic) continue;

						zv::Val property = wrapExtendedProperty(propertyName, std::move(naked));
						if (UNEXPECTED(property.isUndef())) return zv::Val();
						bool canRead;
						if (UNEXPECTED(!callBool(zv::Ref(scope), PT_LC("canreadproperty"), 1, property.raw(), canRead))) return zv::Val();
						memoSet(PT_CR_PROP_INSTANCE_PROPERTIES, key, zv::Val::copyOf(property.ref()));
						if (canRead) return property;
					}
				}
			}
		}

		if (!memoIsset(PT_CR_PROP_INSTANCE_PROPERTIES, key)) {
			zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
			if (UNEXPECTED(requireExtends.isUndef())) return zv::Val();
			bool has;
			if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasinstanceproperty"), propertyName, has))) return zv::Val();
			if (has) {
				zv::Val property = extensionCall(requireExtends.ref(), PT_LC("getinstanceproperty"), propertyName);
				if (UNEXPECTED(property.isUndef())) return zv::Val();
				memoSet(PT_CR_PROP_INSTANCE_PROPERTIES, key, std::move(property));
			}
		}

		if (!memoIsset(PT_CR_PROP_INSTANCE_PROPERTIES, key)) {
			throwMissingProperty(propertyName);
			return zv::Val();
		}

		return zv::Val::copyOf(zv::Ref(memoFind(PT_CR_PROP_INSTANCE_PROPERTIES, key)));
	}

	zv::Val getStaticProperty(zend_string *propertyName)
	{
		zend_string *key = propertyName;
		if (memoIsset(PT_CR_PROP_STATIC_PROPERTIES, key)) return zv::Val::copyOf(zv::Ref(memoFind(PT_CR_PROP_STATIC_PROPERTIES, key)));

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
		bool has;
		if (UNEXPECTED(!extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, has))) return zv::Val();
		if (has) {
			zv::Val outOfClassScope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
			if (UNEXPECTED(outOfClassScope.isUndef())) return zv::Val();
			zv::Val naked = extensionGetProperty(phpExtension.ref(), propertyName, outOfClassScope.raw());
			if (UNEXPECTED(naked.isUndef())) return zv::Val();
			bool isStatic;
			if (UNEXPECTED(!callBool(naked.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return zv::Val();
			if (isStatic) {
				zv::Val property = wrapExtendedProperty(propertyName, std::move(naked));
				if (UNEXPECTED(property.isUndef())) return zv::Val();
				if (UNEXPECTED(!callBool(property.ref(), PT_LC("isstatic"), 0, NULL, isStatic))) return zv::Val();
				if (isStatic) {
					memoSet(PT_CR_PROP_STATIC_PROPERTIES, key, zv::Val::copyOf(property.ref()));
					return property;
				}
			}
		}

		zv::Val requireExtends = requireExtendsPropertyClassReflectionExtension();
		if (UNEXPECTED(requireExtends.isUndef())) return zv::Val();
		if (UNEXPECTED(!extensionBool(requireExtends.ref(), PT_LC("hasstaticproperty"), propertyName, has))) return zv::Val();
		if (has) {
			zv::Val property = extensionCall(requireExtends.ref(), PT_LC("getstaticproperty"), propertyName);
			if (UNEXPECTED(property.isUndef())) return zv::Val();
			memoSet(PT_CR_PROP_STATIC_PROPERTIES, key, zv::Val::copyOf(property.ref()));
			return property;
		}

		throwMissingProperty(propertyName);
		return zv::Val();
	}

	bool hasNativeProperty(zend_string *propertyName, bool &out)
	{
		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return false;
		return extensionBool(phpExtension.ref(), PT_LC("hasproperty"), propertyName, out);
	}

	zv::Val getNativeProperty(zend_string *propertyName)
	{
		bool has;
		if (UNEXPECTED(!hasNativeProperty(propertyName, has))) return zv::Val();
		if (!has) {
			throwMissingProperty(propertyName);
			return zv::Val();
		}

		zv::Val phpExtension = phpClassReflectionExtension();
		if (UNEXPECTED(phpExtension.isUndef())) return zv::Val();
		return extensionCall(phpExtension.ref(), PT_LC("getnativeproperty"), propertyName);
	}

	bool isAbstract(bool &out) const { return reflectionCallBool(PT_LC("isabstract"), out); }
	bool isInterface(bool &out) const { return reflectionCallBool(PT_LC("isinterface"), out); }
	bool isTrait(bool &out) const { return reflectionCallBool(PT_LC("istrait"), out); }

	/* $this->reflection instanceof ReflectionEnum && $this->reflection->isEnum()
	 * — the adapter class looked up without autoloading: an undeclared
	 * class is "no instance of it" */
	bool isEnum(bool &out) const
	{
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) {
			(void) uninitializedProperty("reflection");
			return false;
		}
		zend_class_entry *enumCe = pt_class_loaded(PT_CLASS_REFLECTION_ENUM);
		if (enumCe == NULL) {
			if (UNEXPECTED(EG(exception))) return false;
			out = false;
			return true;
		}
		if (!reflection.isObject() || !instanceof_function(reflection.asObject()->ce, enumCe)) {
			out = false;
			return true;
		}
		return reflectionCallBool(PT_LC("isenum"), out);
	}

	/* 'Interface'|'Trait'|'Enum'|'Class' */
	zv::Val getClassTypeDescription()
	{
		bool is;
		if (UNEXPECTED(!isInterface(is))) return zv::Val();
		if (is) return zv::Val::string(PT_LC("Interface"));
		if (UNEXPECTED(!isTrait(is))) return zv::Val();
		if (is) return zv::Val::string(PT_LC("Trait"));
		if (UNEXPECTED(!isEnum(is))) return zv::Val();
		if (is) return zv::Val::string(PT_LC("Enum"));

		return zv::Val::string(PT_LC("Class"));
	}

	bool isReadOnly(bool &out) const { return reflectionCallBool(PT_LC("isreadonly"), out); }

	bool isBackedEnum(bool &out) const
	{
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) {
			(void) uninitializedProperty("reflection");
			return false;
		}
		zend_class_entry *enumCe = pt_class_loaded(PT_CLASS_REFLECTION_ENUM);
		if (enumCe == NULL) {
			if (UNEXPECTED(EG(exception))) return false;
			out = false;
			return true;
		}
		if (!reflection.isObject() || !instanceof_function(reflection.asObject()->ce, enumCe)) {
			out = false;
			return true;
		}

		return reflectionCallBool(PT_LC("isbacked"), out);
	}

	/* ?Type — the backing type of a backed enum */
	zv::Val getBackedEnumType()
	{
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) return uninitializedProperty("reflection");
		zend_class_entry *enumCe = pt_class_loaded(PT_CLASS_REFLECTION_ENUM);
		if (enumCe == NULL) return UNEXPECTED(EG(exception) != NULL) ? zv::Val() : zv::Val::null();
		if (!reflection.isObject() || !instanceof_function(reflection.asObject()->ce, enumCe)) return zv::Val::null();

		bool backed;
		if (UNEXPECTED(!reflectionCallBool(PT_LC("isbacked"), backed))) return zv::Val();
		if (!backed) return zv::Val::null();

		zv::Val backingType = reflectionCall(PT_LC("getbackingtype"), 0, NULL);
		if (UNEXPECTED(backingType.isUndef())) return zv::Val();

		return pt_typehint_helper_decide_type_from_reflection(backingType.raw());
	}

	bool hasEnumCase(zend_string *name, bool &out)
	{
		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return false;
		if (!isEnum_) {
			out = false;
			return true;
		}

		zval arg;
		ZVAL_STR(&arg, name);
		zv::Val result = reflectionCall(PT_LC("hascase"), 1, &arg);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* array<string, EnumCaseReflection> */
	zv::Val getEnumCases()
	{
		bool isEnum_ = false;
		if (UNEXPECTED(!isEnum(isEnum_))) return zv::Val();
		if (!isEnum_) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}

		zv::Ref memo = slot(PT_CR_PROP_ENUM_CASES);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Val initializerExprContext = initializerExprContextFromClassReflection();
		if (UNEXPECTED(initializerExprContext.isUndef())) return zv::Val();
		zv::Val reflectionCases = reflectionCall(PT_LC("getcases"), 0, NULL);
		if (UNEXPECTED(reflectionCases.isUndef())) return zv::Val();

		zv::Arr cases = zv::Arr::create(countOf(reflectionCases.ref()));
		if (reflectionCases.ref().isArray()) {
			for (auto entry : zv::ArrRef(reflectionCases.raw())) {
				zv::Val valueType = enumCaseValueType(entry.value(), initializerExprContext.raw());
				if (UNEXPECTED(valueType.isUndef())) return zv::Val();
				zv::Val caseName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(caseName.isUndef())) return zv::Val();
				zv::Val attributes = enumCaseAttributes(entry.value());
				if (UNEXPECTED(attributes.isUndef())) return zv::Val();
				zv::Val caseReflection = newEnumCaseReflection(entry.value(), valueType.ref(), attributes.ref());
				if (UNEXPECTED(caseReflection.isUndef())) return zv::Val();
				zend_string *key = zval_get_string(caseName.raw());
				cases.set(key, std::move(caseReflection));
				zend_string_release(key);
			}
		}

		writeSlot(PT_CR_PROP_ENUM_CASES, zv::Val::copyOf(cases.ref()));

		return zv::Val(std::move(cases));
	}

	zv::Val getEnumCase(zend_string *name)
	{
		bool has;
		if (UNEXPECTED(!hasEnumCase(name, has))) return zv::Val();
		if (!has) {
			zv::Val displayName_ = getDisplayName(true);
			if (UNEXPECTED(displayName_.isUndef())) return zv::Val();
			zend_string *displayNameStr = zval_get_string(displayName_.raw());
			zval message;
			ZVAL_STR(&message, zend_strpprintf(0, "Enum case %s::%s does not exist.", ZSTR_VAL(displayNameStr), ZSTR_VAL(name)));
			zend_string_release(displayNameStr);
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &message);
			zval_ptr_dtor(&message);
			return zv::Val();
		}

		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		zend_class_entry *enumCe = pt_class_loaded(PT_CLASS_REFLECTION_ENUM);
		if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
		if (enumCe == NULL || !reflection.isObject() || !instanceof_function(reflection.asObject()->ce, enumCe)) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}

		zv::Ref enumCases = slot(PT_CR_PROP_ENUM_CASES);
		if (!enumCases.isNull() && enumCases.isArray()) {
			zval *found = zend_symtable_find(enumCases.asArrayTable(), name);
			if (found != NULL) return zv::Val::copyOf(zv::Ref(found));
		}

		zval arg;
		ZVAL_STR(&arg, name);
		zv::Val case_ = reflectionCall(PT_LC("getcase"), 1, &arg);
		if (UNEXPECTED(case_.isUndef())) return zv::Val();
		zv::Val valueType = enumCaseValueType(case_.ref(), NULL);
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zv::Val attributes = enumCaseAttributes(case_.ref());
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();

		return newEnumCaseReflection(case_.ref(), valueType.ref(), attributes.ref());
	}

	/* $case instanceof ReflectionEnumBackedCase && $case->hasBackingValue()
	 * ? $this->initializerExprTypeResolver->getType($case->getValueExpression(),
	 * $context) : null — $context NULL where the twin builds it inside the
	 * branch (getEnumCase()), the loop's shared one otherwise */
	zv::Val enumCaseValueType(zv::Ref case_, zval *context)
	{
		bool isBackedCase;
		if (UNEXPECTED(!pt_type_instanceof(case_.raw(), PT_CLASS_REFLECTION_ENUM_BACKED_CASE, isBackedCase))) return zv::Val();
		if (!isBackedCase) return zv::Val::null();
		bool hasBackingValue;
		if (UNEXPECTED(!callBool(case_, PT_LC("hasbackingvalue"), 0, NULL, hasBackingValue))) return zv::Val();
		if (!hasBackingValue) return zv::Val::null();

		zv::Val valueExpression = callOn(case_, PT_LC("getvalueexpression"), 0, NULL);
		if (UNEXPECTED(valueExpression.isUndef())) return zv::Val();
		zv::Val ownContext;
		if (context == NULL) {
			ownContext = initializerExprContextFromClassReflection();
			if (UNEXPECTED(ownContext.isUndef())) return zv::Val();
			context = ownContext.raw();
		}

		zv::Args args{valueExpression.raw(), context};
		return callService(PT_CR_PROP_INITIALIZER_EXPR_TYPE_RESOLVER, "initializerExprTypeResolver", PT_LC("gettype"), 2, args);
	}

	/* $this->attributeReflectionFactory->fromNativeReflection($case->getAttributes(),
	 * InitializerExprContext::fromClass($this->getName(), $this->getFileName())) */
	zv::Val enumCaseAttributes(zv::Ref case_)
	{
		zv::Val attributes = callOn(case_, PT_LC("getattributes"), 0, NULL);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Val context = initializerExprContextFromClass();
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Args args{attributes.raw(), context.raw()};
		return callService(PT_CR_PROP_ATTRIBUTE_REFLECTION_FACTORY, "attributeReflectionFactory", PT_LC("fromnativereflection"), 2, args);
	}

	/* new EnumCaseReflection($this, $case, $valueType, $attributes, $this->deprecationProvider) */
	zv::Val newEnumCaseReflection(zv::Ref case_, zv::Ref valueType, zv::Ref attributes)
	{
		zv::Ref deprecationProvider = slot(PT_CR_PROP_DEPRECATION_PROVIDER);
		if (UNEXPECTED(deprecationProvider.isUndef())) return uninitializedProperty("deprecationProvider");
		zv::Args args{self, case_.raw(), valueType.raw(), attributes.raw(), deprecationProvider.raw()};
		return pt_type_new(PT_CLASS_ENUM_CASE_REFLECTION, 5, args);
	}

	/* InitializerExprContext::fromClassReflection($this) */
	zv::Val initializerExprContextFromClassReflection()
	{
		return pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromclassreflection"), 1, thisZval());
	}

	/* InitializerExprContext::fromClass($this->getName(), $this->getFileName()) */
	zv::Val initializerExprContextFromClass()
	{
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val fileName = getFileName();
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		zv::Args args{name.raw(), fileName.raw()};
		return pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromclass"), 2, args);
	}

	bool isClass(bool &out)
	{
		bool is;
		if (UNEXPECTED(!isInterface(is))) return false;
		if (is) {
			out = false;
			return true;
		}
		if (UNEXPECTED(!isTrait(is))) return false;
		if (is) {
			out = false;
			return true;
		}
		if (UNEXPECTED(!isEnum(is))) return false;
		out = !is;
		return true;
	}

	bool isAnonymous(bool &out) const
	{
		zv::Ref anonymousFilename = slot(PT_CR_PROP_ANONYMOUS_FILENAME);
		if (UNEXPECTED(anonymousFilename.isUndef())) {
			(void) uninitializedProperty("anonymousFilename");
			return false;
		}
		out = !anonymousFilename.isNull();
		return true;
	}

	bool is(zend_string *className, bool &out)
	{
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return false;
		if (Z_TYPE_P(name.raw()) == IS_STRING && zend_string_equals(Z_STR_P(name.raw()), className)) {
			out = true;
			return true;
		}

		bool has;
		if (UNEXPECTED(!providerHasClass(className, has))) return false;
		if (!has) {
			out = false;
			return true;
		}

		zv::Val classReflection = providerGetClass(className);
		if (UNEXPECTED(classReflection.isUndef())) return false;
		return isSubclassOfClass(classReflection.ref(), out);
	}

	/** @deprecated Use isSubclassOfClass instead. */
	bool isSubclassOf(zend_string *className, bool &out)
	{
		bool has;
		if (UNEXPECTED(!providerHasClass(className, has))) return false;
		if (!has) {
			out = false;
			return true;
		}

		zv::Val classReflection = providerGetClass(className);
		if (UNEXPECTED(classReflection.isUndef())) return false;
		return isSubclassOfClass(classReflection.ref(), out);
	}

	bool isSubclassOfClass(zv::Ref classReflection, bool &out)
	{
		zv::Val cacheKey = crGetCacheKey(classReflection);
		if (UNEXPECTED(cacheKey.isUndef())) return false;
		zend_string *cacheKeyStr = zval_get_string(cacheKey.raw());
		zv::Str cacheKeyOwned = zv::Str::adopt(cacheKeyStr);
		if (memoIsset(PT_CR_PROP_SUBCLASSES, cacheKeyStr)) {
			out = zend_is_true(memoFind(PT_CR_PROP_SUBCLASSES, cacheKeyStr));
			return true;
		}

		bool finalOrAnonymous = false;
		if (UNEXPECTED(!crIsFinalByKeyword(classReflection, finalOrAnonymous))) return false;
		if (!finalOrAnonymous && UNEXPECTED(!crIsAnonymous(classReflection, finalOrAnonymous))) return false;
		if (finalOrAnonymous) {
			out = memoSetBool(PT_CR_PROP_SUBCLASSES, cacheKeyStr, false);
			return true;
		}

		zv::Val name = crGetName(classReflection);
		if (UNEXPECTED(name.isUndef())) return false;
		zv::Val result = reflectionCall(PT_LC("issubclassof"), 1, name.raw());
		if (UNEXPECTED(result.isUndef())) {
			if (caughtReflectionException()) {
				out = memoSetBool(PT_CR_PROP_SUBCLASSES, cacheKeyStr, false);
				return true;
			}
			return false;
		}
		out = memoSetBool(PT_CR_PROP_SUBCLASSES, cacheKeyStr, zend_is_true(result.raw()));
		return true;
	}

	bool implementsInterface(zend_string *className, bool &out)
	{
		zval name;
		ZVAL_STR(&name, className);
		zv::Val result = reflectionCall(PT_LC("implementsinterface"), 1, &name);
		if (UNEXPECTED(result.isUndef())) {
			if (caughtReflectionException()) {
				out = false;
				return true;
			}
			return false;
		}
		out = zend_is_true(result.raw());
		return true;
	}

	/* list<ClassReflection> */
	zv::Val getParents()
	{
		zv::Arr parents = zv::Arr::create(2);
		zv::Val parent = getParentClass();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		while (!parent.isNull()) {
			parents.push(parent.ref());
			zv::Val next = crGetParentClass(parent.ref());
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			parent = std::move(next);
		}

		return zv::Val(std::move(parents));
	}

	/* array<string, ClassReflection> */
	zv::Val getInterfaces()
	{
		zv::Ref cached = slot(PT_CR_PROP_CACHED_INTERFACES);
		if (!cached.isNull()) return zv::Val::copyOf(cached);

		zv::Val immediateInterfaces = getImmediateInterfaces();
		if (UNEXPECTED(immediateInterfaces.isUndef())) return zv::Val();
		zv::Arr interfaces = zv::Arr::adoptVal(zv::Val::copyOf(immediateInterfaces.ref()));
		zv::Val parent = getParentClass();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		while (!parent.isNull()) {
			zv::Val parentInterfaces = crGetImmediateInterfaces(parent.ref());
			if (UNEXPECTED(parentInterfaces.isUndef())) return zv::Val();
			if (parentInterfaces.ref().isArray()) {
				for (auto entry : zv::ArrRef(parentInterfaces.raw())) {
					if (UNEXPECTED(!addInterface(interfaces, entry.value()))) return zv::Val();
					if (UNEXPECTED(!addCollectedInterfaces(interfaces, entry.value()))) return zv::Val();
				}
			}

			zv::Val next = crGetParentClass(parent.ref());
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			parent = std::move(next);
		}

		if (immediateInterfaces.ref().isArray()) {
			for (auto entry : zv::ArrRef(immediateInterfaces.raw())) {
				if (UNEXPECTED(!addCollectedInterfaces(interfaces, entry.value()))) return zv::Val();
			}
		}

		writeSlot(PT_CR_PROP_CACHED_INTERFACES, zv::Val::copyOf(interfaces.ref()));

		return zv::Val(std::move(interfaces));
	}

	/* $interfaces[$interface->getName()] = $interface; */
	static bool addInterface(zv::Arr &interfaces, zv::Ref interface)
	{
		zv::Val name = crGetName(interface);
		if (UNEXPECTED(name.isUndef())) return false;
		zend_string *nameStr = zval_get_string(name.raw());
		interfaces.set(nameStr, zv::Val::copyOf(interface));
		zend_string_release(nameStr);
		return true;
	}

	/* foreach ($this->collectInterfaces($interface) as $i) { $interfaces[$i->getName()] = $i; } */
	bool addCollectedInterfaces(zv::Arr &interfaces, zv::Ref interface)
	{
		zv::Val collected = collectInterfaces(interface);
		if (UNEXPECTED(collected.isUndef())) return false;
		for (auto entry : zv::ArrRef(collected.raw())) {
			if (UNEXPECTED(!addInterface(interfaces, entry.value()))) return false;
		}
		return true;
	}

	/* private; array<string, ClassReflection> — the interfaces an interface
	 * extends, transitively (the twin's array_pop() stack) */
	zv::Val collectInterfaces(zv::Ref interface)
	{
		zv::Arr interfaces = zv::Arr::create(4);
		std::vector<zv::Val> queue;
		queue.push_back(zv::Val::copyOf(interface));
		while (!queue.empty()) {
			zv::Val current = std::move(queue.back());
			queue.pop_back();
			zv::Val immediate = crGetImmediateInterfaces(current.ref());
			if (UNEXPECTED(immediate.isUndef())) return zv::Val();
			if (!immediate.ref().isArray()) continue;
			for (auto entry : zv::ArrRef(immediate.raw())) {
				zv::Val name = crGetName(entry.value());
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				zend_string *nameStr = zval_get_string(name.raw());
				bool exists = interfaces.arrRef().exists(nameStr);
				if (!exists) {
					interfaces.set(nameStr, zv::Val::copyOf(entry.value()));
					queue.push_back(zv::Val::copyOf(entry.value()));
				}
				zend_string_release(nameStr);
			}
		}

		return zv::Val(std::move(interfaces));
	}

	/* array<string, ClassReflection> */
	zv::Val getImmediateInterfaces()
	{
		/* $indirectInterfaceNames: the interfaces of the parents, and of
		 * the interfaces' interfaces */
		std::vector<zv::Str> indirectInterfaceNames;
		zv::Val parent = getParentClass();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		while (!parent.isNull()) {
			zv::Val parentReflection = crGetNativeReflection(parent.ref());
			if (UNEXPECTED(parentReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!collectInterfaceNames(parentReflection.ref(), indirectInterfaceNames))) return zv::Val();

			zv::Val next = crGetParentClass(parent.ref());
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			parent = std::move(next);
		}

		zv::Val nativeReflection = getNativeReflection();
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		zv::Val interfaceInterfaces = callOn(nativeReflection.ref(), PT_LC("getinterfaces"), 0, NULL);
		if (UNEXPECTED(interfaceInterfaces.isUndef())) return zv::Val();
		if (interfaceInterfaces.ref().isArray()) {
			for (auto entry : zv::ArrRef(interfaceInterfaces.raw())) {
				if (UNEXPECTED(!collectInterfaceNames(entry.value(), indirectInterfaceNames))) return zv::Val();
			}
		}

		bool isInterface_;
		if (UNEXPECTED(!isInterface(isInterface_))) return zv::Val();
		zv::Val implementsTags = isInterface_ ? getExtendsTags() : getImplementsTags();
		if (UNEXPECTED(implementsTags.isUndef())) return zv::Val();

		/* array_diff($this->getNativeReflection()->getInterfaceNames(), $indirectInterfaceNames) */
		zv::Val nativeReflectionAgain = getNativeReflection();
		if (UNEXPECTED(nativeReflectionAgain.isUndef())) return zv::Val();
		zv::Val interfaceNames = callOn(nativeReflectionAgain.ref(), PT_LC("getinterfacenames"), 0, NULL);
		if (UNEXPECTED(interfaceNames.isUndef())) return zv::Val();
		zv::Arr immediateInterfaces = zv::Arr::create(4);
		if (!interfaceNames.ref().isArray()) return zv::Val(std::move(immediateInterfaces));
		for (auto entry : zv::ArrRef(interfaceNames.raw())) {
			zend_string *interfaceName = zval_get_string(entry.value().raw());
			zv::Str interfaceNameOwned = zv::Str::adopt(interfaceName);
			bool indirect = false;
			for (const zv::Str &indirectName : indirectInterfaceNames) {
				if (zend_string_equals(indirectName.get(), interfaceName)) {
					indirect = true;
					break;
				}
			}
			if (indirect) continue;

			bool has;
			if (UNEXPECTED(!providerHasClass(interfaceName, has))) return zv::Val();
			if (!has) continue;

			zv::Val immediateInterface = providerGetClass(interfaceName);
			if (UNEXPECTED(immediateInterface.isUndef())) return zv::Val();
			zv::Val immediateName = crGetName(immediateInterface.ref());
			if (UNEXPECTED(immediateName.isUndef())) return zv::Val();
			zend_string *immediateNameStr = zval_get_string(immediateName.raw());
			zv::Str immediateNameOwned = zv::Str::adopt(immediateNameStr);

			zval *implementsTag = implementsTags.ref().isArray() ? zend_symtable_find(implementsTags.ref().asArrayTable(), immediateNameStr) : NULL;
			if (implementsTag != NULL) {
				zv::Val implementedType = callOn(zv::Ref(implementsTag), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(implementedType.isUndef())) return zv::Val();
				bool generic;
				if (UNEXPECTED(!isGeneric(generic))) return zv::Val();
				if (generic) {
					implementedType = resolveAncestorTemplateTypes(implementedType.ref(), true);
					if (UNEXPECTED(implementedType.isUndef())) return zv::Val();
				}

				if (instanceOfShadowed(implementedType.ref(), pt_ce_generic_object_type, PT_LC(PT_CR_GENERIC_OBJECT_TYPE_NAME))) {
					zv::Val reflection = typeGetClassReflection(implementedType.ref());
					if (UNEXPECTED(reflection.isUndef())) return zv::Val();
					if (!reflection.isNull()) {
						immediateInterfaces.set(immediateNameStr, std::move(reflection));
						continue;
					}
				}
			}

			bool immediateGeneric;
			if (UNEXPECTED(!crIsGeneric(immediateInterface.ref(), immediateGeneric))) return zv::Val();
			if (immediateGeneric) {
				zv::Val withErrorTypes = crWithErrorTypes(immediateInterface.ref());
				if (UNEXPECTED(withErrorTypes.isUndef())) return zv::Val();
				immediateInterfaces.set(immediateNameStr, std::move(withErrorTypes));
				continue;
			}

			immediateInterfaces.set(immediateNameStr, std::move(immediateInterface));
		}

		return zv::Val(std::move(immediateInterfaces));
	}

	/* foreach ($reflection->getInterfaceNames() as $name) { $names[] = $name; } */
	static bool collectInterfaceNames(zv::Ref reflection, std::vector<zv::Str> &names)
	{
		zv::Val interfaceNames = callOn(reflection, PT_LC("getinterfacenames"), 0, NULL);
		if (UNEXPECTED(interfaceNames.isUndef())) return false;
		if (!interfaceNames.ref().isArray()) return true;
		for (auto entry : zv::ArrRef(interfaceNames.raw())) {
			names.push_back(zv::Str::adopt(zval_get_string(entry.value().raw())));
		}
		return true;
	}

	/* {{{ traits, constants, the PHPDoc machinery, generics */

	/* array<string, ClassReflection> */
	zv::Val getTraits(bool recursive)
	{
		zv::Val nativeReflection = getNativeReflection();
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();

		zv::Val source;
		if (recursive) {
			zv::Val collected = collectTraits(nativeReflection.ref());
			if (UNEXPECTED(collected.isUndef())) return zv::Val();
			zv::Arr keyed = zv::Arr::create(countOf(collected.ref()));
			if (collected.ref().isArray()) {
				for (auto entry : zv::ArrRef(collected.raw())) {
					zv::Val name = callOn(entry.value(), PT_LC("getname"), 0, NULL);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
					zend_string *nameStr = zval_get_string(name.raw());
					keyed.set(nameStr, zv::Val::copyOf(entry.value()));
					zend_string_release(nameStr);
				}
			}
			source = zv::Val(std::move(keyed));
		} else {
			source = callOn(nativeReflection.ref(), PT_LC("gettraits"), 0, NULL);
			if (UNEXPECTED(source.isUndef())) return zv::Val();
		}

		/* array_map(fn (ReflectionClass $trait) => $this->reflectionProvider->getClass($trait->getName()), $traits) */
		zv::Arr traits = zv::Arr::create(countOf(source.ref()));
		if (source.ref().isArray()) {
			for (auto entry : zv::ArrRef(source.raw())) {
				zv::Val name = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				zend_string *nameStr = zval_get_string(name.raw());
				zv::Val classReflection = providerGetClass(nameStr);
				zend_string_release(nameStr);
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				if (entry.stringKeyOrNull() != NULL) {
					traits.set(entry.stringKeyOrNull(), std::move(classReflection));
				} else {
					traits.arrRef().setIndex(entry.indexKey(), classReflection.ref());
				}
			}
		}

		if (!recursive) return zv::Val(std::move(traits));

		zv::Val parent = getParentClass();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		if (parent.isNull()) return zv::Val(std::move(traits));

		zv::Val parentTraits = crGetTraits(parent.ref(), true);
		if (UNEXPECTED(parentTraits.isUndef())) return zv::Val();

		return arrayMerge(traits.ref(), parentTraits.ref());
	}

	/* list<class-string> */
	zv::Val getParentClassesNames()
	{
		zv::Arr parentNames = zv::Arr::create(2);
		zv::Val parentClass = getParentClass();
		if (UNEXPECTED(parentClass.isUndef())) return zv::Val();
		while (!parentClass.isNull()) {
			zv::Val name = crGetName(parentClass.ref());
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			parentNames.push(std::move(name));
			zv::Val next = crGetParentClass(parentClass.ref());
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			parentClass = std::move(next);
		}

		return zv::Val(std::move(parentNames));
	}

	bool hasConstant(zend_string *name, bool &out)
	{
		zv::Val nativeReflection = getNativeReflection();
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		zval arg;
		ZVAL_STR(&arg, name);
		bool has;
		if (UNEXPECTED(!callBool(nativeReflection.ref(), PT_LC("hasconstant"), 1, &arg, has))) return false;
		if (!has) {
			out = false;
			return true;
		}

		zv::Val reflectionConstant = callOn(nativeReflection.ref(), PT_LC("getreflectionconstant"), 1, &arg);
		if (UNEXPECTED(reflectionConstant.isUndef())) return false;
		if (reflectionConstant.ref().isFalse()) {
			out = false;
			return true;
		}

		zv::Val declaringClassName = constantDeclaringClassName(reflectionConstant.ref());
		if (UNEXPECTED(declaringClassName.isUndef())) return false;
		zend_string *nameStr = zval_get_string(declaringClassName.raw());
		bool ok = providerHasClass(nameStr, out);
		zend_string_release(nameStr);
		return ok;
	}

	/* $reflectionConstant->getDeclaringClass()->getName() */
	static zv::Val constantDeclaringClassName(zv::Ref reflectionConstant)
	{
		zv::Val declaringClass = callOn(reflectionConstant, PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		return callOn(declaringClass.ref(), PT_LC("getname"), 0, NULL);
	}

	zv::Val getConstant(zend_string *name)
	{
		if (!memoIsset(PT_CR_PROP_CONSTANTS, name)) {
			zv::Val constant = createConstant(name);
			if (UNEXPECTED(constant.isUndef())) return zv::Val();
			memoSet(PT_CR_PROP_CONSTANTS, name, std::move(constant));
		}

		zval *found = memoFind(PT_CR_PROP_CONSTANTS, name);
		if (UNEXPECTED(found == NULL)) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(found));
	}

	/* the twin's getConstant() body up to the memo write */
	zv::Val createConstant(zend_string *name)
	{
		zv::Val nativeReflection = getNativeReflection();
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		zval nameArg;
		ZVAL_STR(&nameArg, name);
		zv::Val reflectionConstant = callOn(nativeReflection.ref(), PT_LC("getreflectionconstant"), 1, &nameArg);
		if (UNEXPECTED(reflectionConstant.isUndef())) return zv::Val();
		if (reflectionConstant.ref().isFalse()) {
			zv::Val className = getName();
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Args args{className.raw(), name};
			throwNew(PT_CLASS_MISSING_CONSTANT_FROM_REFLECTION_EXCEPTION, 2, args);
			return zv::Val();
		}

		/* $deprecation = $this->deprecationProvider->getClassConstantDeprecation($reflectionConstant) */
		zval constantArg;
		ZVAL_COPY_VALUE(&constantArg, reflectionConstant.raw());
		zv::Val deprecation = callService(PT_CR_PROP_DEPRECATION_PROVIDER, "deprecationProvider", PT_LC("getclassconstantdeprecation"), 1, &constantArg);
		if (UNEXPECTED(deprecation.isUndef())) return zv::Val();
		zv::Val deprecatedDescription = zv::Val::null();
		bool isDeprecated_ = !deprecation.isNull();
		if (isDeprecated_) {
			deprecatedDescription = callOn(deprecation.ref(), PT_LC("getdescription"), 0, NULL);
			if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
		}

		zv::Val declaringClassName = constantDeclaringClassName(reflectionConstant.ref());
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zend_string *declaringClassNameStr = zval_get_string(declaringClassName.raw());
		zv::Val declaringClass = getAncestorWithClassName(declaringClassNameStr);
		zend_string_release(declaringClassNameStr);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (declaringClass.isNull()) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}

		zv::Val fileName = crGetFileName(declaringClass.ref());
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		zv::Val phpDocType = zv::Val::null();
		zv::Val currentResolvedPhpDoc = findConstantResolvedPhpDoc(reflectionConstant.ref());
		if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return zv::Val();

		zv::Val nativeType = constantNativeType(reflectionConstant.ref(), declaringClass.ref(), name);
		if (UNEXPECTED(nativeType.isUndef())) return zv::Val();

		/* $this->phpDocInheritanceResolver->resolvePhpDocForConstant($declaringClass, $name, $currentResolvedPhpDoc) */
		zv::Args resolveArgs{declaringClass.raw(), name, currentResolvedPhpDoc.raw()};
		zv::Val resolvedPhpDoc = callService(PT_CR_PROP_PHP_DOC_INHERITANCE_RESOLVER, "phpDocInheritanceResolver", PT_LC("resolvephpdocforconstant"), 3, resolveArgs);
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();

		bool isInternal_ = false;
		bool isFinal_ = false;
		if (!resolvedPhpDoc.isNull()) {
			if (!isDeprecated_) {
				zv::Val deprecatedTag = callOn(resolvedPhpDoc.ref(), PT_LC("getdeprecatedtag"), 0, NULL);
				if (UNEXPECTED(deprecatedTag.isUndef())) return zv::Val();
				if (!deprecatedTag.isNull()) {
					deprecatedDescription = callOn(deprecatedTag.ref(), PT_LC("getmessage"), 0, NULL);
					if (UNEXPECTED(deprecatedDescription.isUndef())) return zv::Val();
				} else {
					deprecatedDescription = zv::Val::null();
				}
				if (UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isdeprecated"), 0, NULL, isDeprecated_))) return zv::Val();
			}
			if (UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isinternal"), 0, NULL, isInternal_))) return zv::Val();
			if (UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isfinal"), 0, NULL, isFinal_))) return zv::Val();
			phpDocType = resolveConstantVarPhpDocType(resolvedPhpDoc.ref(), nativeType.ref(), declaringClass.ref());
			if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
		}

		/* $this->attributeReflectionFactory->fromNativeReflection($reflectionConstant->getAttributes(),
		 * InitializerExprContext::fromClass($declaringClass->getName(), $fileName)) */
		zv::Val attributes = callOn(reflectionConstant.ref(), PT_LC("getattributes"), 0, NULL);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Val declaringName = crGetName(declaringClass.ref());
		if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
		zv::Args contextArgs{declaringName.raw(), fileName.raw()};
		zv::Val context = pt_type_call_static(PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromclass"), 2, contextArgs);
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Args attributeArgs{attributes.raw(), context.raw()};
		zv::Val attributeReflections = callService(PT_CR_PROP_ATTRIBUTE_REFLECTION_FACTORY, "attributeReflectionFactory", PT_LC("fromnativereflection"), 2, attributeArgs);
		if (UNEXPECTED(attributeReflections.isUndef())) return zv::Val();

		zv::Ref initializerExprTypeResolver = slot(PT_CR_PROP_INITIALIZER_EXPR_TYPE_RESOLVER);
		if (UNEXPECTED(initializerExprTypeResolver.isUndef())) return uninitializedProperty("initializerExprTypeResolver");

		zval args[11];
		ZVAL_COPY_VALUE(&args[0], initializerExprTypeResolver.raw());
		ZVAL_COPY_VALUE(&args[1], declaringClass.raw());
		ZVAL_COPY_VALUE(&args[2], reflectionConstant.raw());
		ZVAL_COPY_VALUE(&args[3], nativeType.raw());
		ZVAL_COPY_VALUE(&args[4], phpDocType.raw());
		ZVAL_COPY_VALUE(&args[5], resolvedPhpDoc.raw());
		ZVAL_COPY_VALUE(&args[6], deprecatedDescription.raw());
		ZVAL_BOOL(&args[7], isDeprecated_);
		ZVAL_BOOL(&args[8], isInternal_);
		ZVAL_BOOL(&args[9], isFinal_);
		ZVAL_COPY_VALUE(&args[10], attributeReflections.raw());

		return pt_type_new(PT_CLASS_REAL_CLASS_CLASS_CONSTANT_REFLECTION, 11, args);
	}

	/* $reflectionConstant->getType() !== null
	 *   ? TypehintHelper::decideTypeFromReflection($reflectionConstant->getType(), selfClass: $declaringClass)
	 *   : ($this->signatureMapProvider->hasClassConstantMetadata(...) ? ...['nativeType'] : null) */
	zv::Val constantNativeType(zv::Ref reflectionConstant, zv::Ref declaringClass, zend_string *name)
	{
		zv::Val type = callOn(reflectionConstant, PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (!type.isNull()) return pt_typehint_helper_decide_type_from_reflection(type.raw(), NULL, declaringClass.raw());

		zv::Val declaringName = crGetName(declaringClass);
		if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
		zv::Args args{declaringName.raw(), name};
		bool hasMetadata;
		zend_object *signatureMapProvider = service(PT_CR_PROP_SIGNATURE_MAP_PROVIDER, "signatureMapProvider");
		if (UNEXPECTED(signatureMapProvider == NULL)) return zv::Val();
		zv::Val has = pt_type_call(signatureMapProvider, PT_LC("hasclassconstantmetadata"), 2, args);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		hasMetadata = zend_is_true(has.raw());
		if (!hasMetadata) return zv::Val::null();

		zv::Val metadata = pt_type_call(signatureMapProvider, PT_LC("getclassconstantmetadata"), 2, args);
		if (UNEXPECTED(metadata.isUndef())) return zv::Val();
		if (!metadata.ref().isArray()) return zv::Val::null();
		zval *nativeType = zend_hash_str_find(metadata.ref().asArrayTable(), PT_LC("nativeType"));
		if (nativeType == NULL) return zv::Val::null();
		return zv::Val::copyOf(zv::Ref(nativeType));
	}

	/* @internal; the @var PHPDoc type of a class constant, without walking ancestors */
	zv::Val getConstantPhpDocType(zend_string *name)
	{
		zv::Val nativeReflection = getNativeReflection();
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		zval nameArg;
		ZVAL_STR(&nameArg, name);
		zv::Val reflectionConstant = callOn(nativeReflection.ref(), PT_LC("getreflectionconstant"), 1, &nameArg);
		if (UNEXPECTED(reflectionConstant.isUndef())) return zv::Val();
		if (reflectionConstant.ref().isFalse()) return zv::Val::null();

		zv::Val resolvedPhpDoc = findConstantResolvedPhpDoc(reflectionConstant.ref());
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) return zv::Val::null();

		zv::Val nativeType = zv::Val::null();
		zv::Val type = callOn(reflectionConstant.ref(), PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (!type.isNull()) {
			nativeType = pt_typehint_helper_decide_type_from_reflection(type.raw());
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
		}

		zv::Val declaringClassName = constantDeclaringClassName(reflectionConstant.ref());
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zv::Val ownName = getName();
		if (UNEXPECTED(ownName.isUndef())) return zv::Val();
		zend_string *declaringClassNameStr = zval_get_string(declaringClassName.raw());
		zv::Val declaringClass;
		if (Z_TYPE_P(ownName.raw()) == IS_STRING && zend_string_equals(Z_STR_P(ownName.raw()), declaringClassNameStr)) {
			zval self_;
			ZVAL_OBJ(&self_, self);
			declaringClass = zv::Val::copyOf(zv::Ref(&self_));
		} else {
			declaringClass = getAncestorWithClassName(declaringClassNameStr);
		}
		zend_string_release(declaringClassNameStr);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (declaringClass.isNull()) return zv::Val::null();

		return resolveConstantVarPhpDocType(resolvedPhpDoc.ref(), nativeType.ref(), declaringClass.ref());
	}

	/* private; ?ResolvedPhpDocBlock */
	zv::Val findConstantResolvedPhpDoc(zv::Ref reflectionConstant)
	{
		zv::Val declaringClass = callOn(reflectionConstant, PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val declaringClassName = callOn(declaringClass.ref(), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zv::Val constantName = callOn(reflectionConstant, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(constantName.isUndef())) return zv::Val();

		zv::Args stubArgs{declaringClassName.raw(), constantName.raw()};
		zv::Val resolvedPhpDoc = callService(PT_CR_PROP_STUB_PHP_DOC_PROVIDER, "stubPhpDocProvider", PT_LC("findclassconstantphpdoc"), 2, stubArgs);
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (!resolvedPhpDoc.isNull()) return resolvedPhpDoc;

		zv::Val docComment = callOn(reflectionConstant, PT_LC("getdoccomment"), 0, NULL);
		if (UNEXPECTED(docComment.isUndef())) return zv::Val();
		if (docComment.ref().isFalse()) return zv::Val::null();

		/* $this->fileTypeMapper->getResolvedPhpDoc($reflectionConstant->getDeclaringClass()->getFileName() ?: null,
		 * $declaringClassName, null, null, $docComment) */
		zv::Val fileName = callOn(declaringClass.ref(), PT_LC("getfilename"), 0, NULL);
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		zval args[5];
		if (zend_is_true(fileName.raw())) {
			ZVAL_COPY_VALUE(&args[0], fileName.raw());
		} else {
			ZVAL_NULL(&args[0]);
		}
		ZVAL_COPY_VALUE(&args[1], declaringClassName.raw());
		ZVAL_NULL(&args[2]);
		ZVAL_NULL(&args[3]);
		ZVAL_COPY_VALUE(&args[4], docComment.raw());
		return callService(PT_CR_PROP_FILE_TYPE_MAPPER, "fileTypeMapper", PT_LC("getresolvedphpdoc"), 5, args);
	}

	/* private static; the single explicit-or-compatible @var tag's type,
	 * resolved against the declaring class's template types */
	static zv::Val resolveConstantVarPhpDocType(zv::Ref resolvedPhpDoc, zv::Ref nativeType, zv::Ref declaringClass)
	{
		zv::Val varTags = callOn(resolvedPhpDoc, PT_LC("getvartags"), 0, NULL);
		if (UNEXPECTED(varTags.isUndef())) return zv::Val();
		if (!varTags.ref().isArray()) return zv::Val::null();
		zval *varTag = zend_hash_index_find(varTags.ref().asArrayTable(), 0);
		if (varTag == NULL || Z_TYPE_P(varTag) == IS_NULL || zend_hash_num_elements(varTags.ref().asArrayTable()) != 1) return zv::Val::null();

		zv::Val varType = callOn(zv::Ref(varTag), PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		bool isExplicit;
		if (UNEXPECTED(!callBool(zv::Ref(varTag), PT_LC("isexplicit"), 0, NULL, isExplicit))) return zv::Val();
		if (!isExplicit && !nativeType.isNull()) {
			zval arg;
			ZVAL_COPY_VALUE(&arg, varType.raw());
			zv::Val isSuperType = callOn(nativeType, PT_LC("issupertypeof"), 1, &arg);
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			bool yes;
			if (UNEXPECTED(!callBool(isSuperType.ref(), PT_LC("yes"), 0, NULL, yes))) return zv::Val();
			if (!yes) return zv::Val::null();
		}

		zv::Val activeTemplateTypeMap = crGetActiveTemplateTypeMap(declaringClass);
		if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val callSiteVarianceMap = crGetCallSiteVarianceMap(declaringClass);
		if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
		zv::Val invariant = templateTypeVarianceInvariant();
		if (UNEXPECTED(invariant.isUndef())) return zv::Val();

		return pt_type_template_type_helper_resolve_template_types(varType.raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), invariant.raw(), false);
	}

	bool hasTraitUse(zend_string *traitName, bool &out)
	{
		zv::Val traitNames = getTraitNames();
		if (UNEXPECTED(traitNames.isUndef())) return false;
		out = false;
		if (traitNames.ref().isArray()) {
			for (auto entry : zv::ArrRef(traitNames.raw())) {
				if (entry.value().isString() && zend_string_equals(entry.value().asString(), traitName)) {
					out = true;
					break;
				}
			}
		}
		return true;
	}

	/* private; list<string> */
	zv::Val getTraitNames()
	{
		zv::Val class_ = getNativeReflection();
		if (UNEXPECTED(class_.isUndef())) return zv::Val();
		zv::Val traits = collectTraits(class_.ref());
		if (UNEXPECTED(traits.isUndef())) return zv::Val();
		zv::Arr traitNames = zv::Arr::create(countOf(traits.ref()));
		if (traits.ref().isArray()) {
			for (auto entry : zv::ArrRef(traits.raw())) {
				zv::Val name = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(name.isUndef())) return zv::Val();
				traitNames.push(std::move(name));
			}
		}

		zv::Val names = zv::Val(std::move(traitNames));
		for (;;) {
			zv::Val parentClass = callOn(class_.ref(), PT_LC("getparentclass"), 0, NULL);
			if (UNEXPECTED(parentClass.isUndef())) return zv::Val();
			if (parentClass.ref().isFalse()) break;
			zv::Val parentTraitNames = callOn(parentClass.ref(), PT_LC("gettraitnames"), 0, NULL);
			if (UNEXPECTED(parentTraitNames.isUndef())) return zv::Val();
			zv::Val merged = arrayMerge(names.ref(), parentTraitNames.ref());
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			names = arrayUniqueValues(merged.ref());
			class_ = std::move(parentClass);
		}

		return names;
	}

	/* array<string, TypeAlias> */
	zv::Val getTypeAliases()
	{
		if (slot(PT_CR_PROP_TYPE_ALIASES).isNull()) {
			zv::Val computed = resolveTypeAliases();
			if (UNEXPECTED(computed.isUndef())) return zv::Val();
			writeSlot(PT_CR_PROP_TYPE_ALIASES, std::move(computed));
		}

		return copyOfSlot(PT_CR_PROP_TYPE_ALIASES);
	}

	/* the twin's getTypeAliases() body; the memo write is the caller's (the
	 * twin's early returns write it too — they are zv::Val results here) */
	zv::Val resolveTypeAliases()
	{
		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) return zv::Val(zv::Arr::empty());

		zv::Val typeAliasImportTags = callOn(resolvedPhpDoc.ref(), PT_LC("gettypealiasimporttags"), 0, NULL);
		if (UNEXPECTED(typeAliasImportTags.isUndef())) return zv::Val();
		zv::Val typeAliasTags = callOn(resolvedPhpDoc.ref(), PT_LC("gettypealiastags"), 0, NULL);
		if (UNEXPECTED(typeAliasTags.isUndef())) return zv::Val();

		/* array_map(static fn (TypeAliasTag $tag): TypeAlias => $tag->getTypeAlias(), $typeAliasTags) */
		zv::Arr localAliases = zv::Arr::create(countOf(typeAliasTags.ref()));
		if (typeAliasTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(typeAliasTags.raw())) {
				zv::Val alias = callOn(entry.value(), PT_LC("gettypealias"), 0, NULL);
				if (UNEXPECTED(alias.isUndef())) return zv::Val();
				setAtKey(localAliases, entry, std::move(alias));
			}
		}

		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *nameStr = zval_get_string(name.raw());
		zval *resolving = resolvingTypeAliasImports();
		if (UNEXPECTED(resolving == NULL)) {
			zend_string_release(nameStr);
			return zv::Val();
		}
		if (Z_TYPE_P(resolving) == IS_ARRAY && zend_symtable_find(Z_ARRVAL_P(resolving), nameStr) != NULL) {
			zend_string_release(nameStr);
			if (localAliases.arrRef().size() > 0) return zv::Val(std::move(localAliases));
			throwNew(PT_CLASS_CIRCULAR_TYPE_ALIAS_DEFINITION_EXCEPTION, 0, NULL);
			return zv::Val();
		}

		if (Z_TYPE_P(resolving) != IS_ARRAY) {
			zval fresh;
			array_init(&fresh);
			zval_ptr_dtor(resolving);
			ZVAL_COPY_VALUE(resolving, &fresh);
		}
		SEPARATE_ARRAY(resolving);
		zval true_;
		ZVAL_TRUE(&true_);
		zend_symtable_update(Z_ARRVAL_P(resolving), nameStr, &true_);

		zv::Val importedAliases = resolveImportedTypeAliases(typeAliasImportTags.ref());
		if (UNEXPECTED(importedAliases.isUndef())) {
			zend_string_release(nameStr);
			return zv::Val();
		}

		resolving = resolvingTypeAliasImports();
		if (resolving != NULL && Z_TYPE_P(resolving) == IS_ARRAY) {
			SEPARATE_ARRAY(resolving);
			zend_symtable_del(Z_ARRVAL_P(resolving), nameStr);
		}
		zend_string_release(nameStr);

		/* array_filter(array_merge($importedAliases, $localAliases), fn ($a) => $a !== null) */
		zv::Val merged = arrayMerge(importedAliases.ref(), localAliases.ref());
		if (UNEXPECTED(merged.isUndef())) return zv::Val();
		zv::Arr filtered = zv::Arr::create(countOf(merged.ref()));
		if (merged.ref().isArray()) {
			for (auto entry : zv::ArrRef(merged.raw())) {
				if (entry.value().isNull()) continue;
				setAtKey(filtered, entry, zv::Val::copyOf(entry.value()));
			}
		}

		return zv::Val(std::move(filtered));
	}

	/* array_map(function (TypeAliasImportTag $tag): ?TypeAlias { ... }, $typeAliasImportTags) */
	zv::Val resolveImportedTypeAliases(zv::Ref typeAliasImportTags)
	{
		zv::Arr importedAliases = zv::Arr::create(countOf(typeAliasImportTags));
		if (!typeAliasImportTags.isArray()) return zv::Val(std::move(importedAliases));

		for (auto entry : zv::ArrRef(typeAliasImportTags.raw())) {
			zv::Val importedAlias = callOn(entry.value(), PT_LC("getimportedalias"), 0, NULL);
			if (UNEXPECTED(importedAlias.isUndef())) return zv::Val();
			zv::Val importedFrom = callOn(entry.value(), PT_LC("getimportedfrom"), 0, NULL);
			if (UNEXPECTED(importedFrom.isUndef())) return zv::Val();
			zend_string *importedFromStr = zval_get_string(importedFrom.raw());
			bool hasClass;
			if (UNEXPECTED(!providerHasClass(importedFromStr, hasClass))) {
				zend_string_release(importedFromStr);
				return zv::Val();
			}
			if (!hasClass) {
				zend_string_release(importedFromStr);
				setAtKey(importedAliases, entry, zv::Val::null());
				continue;
			}
			zv::Val importedFromReflection = providerGetClass(importedFromStr);
			zend_string_release(importedFromStr);
			if (UNEXPECTED(importedFromReflection.isUndef())) return zv::Val();

			zv::Val typeAliases = crGetTypeAliases(importedFromReflection.ref());
			if (UNEXPECTED(typeAliases.isUndef())) {
				if (!caughtCircularTypeAliasDefinitionException()) return zv::Val();
				zv::Val invalid = pt_type_call_static(PT_CLASS_TYPE_ALIAS, PT_LC("invalid"), 0, NULL);
				if (UNEXPECTED(invalid.isUndef())) return zv::Val();
				setAtKey(importedAliases, entry, std::move(invalid));
				continue;
			}

			zend_string *importedAliasStr = zval_get_string(importedAlias.raw());
			zval *found = typeAliases.ref().isArray() ? zend_symtable_find(typeAliases.ref().asArrayTable(), importedAliasStr) : NULL;
			zend_string_release(importedAliasStr);
			setAtKey(importedAliases, entry, found == NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(found)));
		}

		return zv::Val(std::move(importedAliases));
	}

	zv::Val getDeprecatedDescription()
	{
		if (slot(PT_CR_PROP_IS_DEPRECATED).isNull()) {
			if (UNEXPECTED(!resolveDeprecation())) return zv::Val();
		}

		return copyOfSlot(PT_CR_PROP_DEPRECATED_DESCRIPTION);
	}

	bool isDeprecated(bool &out)
	{
		if (slot(PT_CR_PROP_IS_DEPRECATED).isNull()) {
			if (UNEXPECTED(!resolveDeprecation())) return false;
		}

		out = slot(PT_CR_PROP_IS_DEPRECATED).isTrue();
		return true;
	}

	/* private */
	bool resolveDeprecation()
	{
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) {
			(void) uninitializedProperty("reflection");
			return false;
		}
		zval reflectionArg;
		ZVAL_COPY_VALUE(&reflectionArg, reflection.raw());
		zv::Val deprecation = callService(PT_CR_PROP_DEPRECATION_PROVIDER, "deprecationProvider", PT_LC("getclassdeprecation"), 1, &reflectionArg);
		if (UNEXPECTED(deprecation.isUndef())) return false;
		if (!deprecation.isNull()) {
			zv::Val description = callOn(deprecation.ref(), PT_LC("getdescription"), 0, NULL);
			if (UNEXPECTED(description.isUndef())) return false;
			writeSlot(PT_CR_PROP_IS_DEPRECATED, zv::Val::boolean(true));
			writeSlot(PT_CR_PROP_DEPRECATED_DESCRIPTION, std::move(description));
			return true;
		}

		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
		if (!resolvedPhpDoc.isNull()) {
			bool deprecated;
			if (UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isdeprecated"), 0, NULL, deprecated))) return false;
			if (deprecated) {
				zv::Val deprecatedTag = callOn(resolvedPhpDoc.ref(), PT_LC("getdeprecatedtag"), 0, NULL);
				if (UNEXPECTED(deprecatedTag.isUndef())) return false;
				zv::Val description = zv::Val::null();
				if (!deprecatedTag.isNull()) {
					description = callOn(deprecatedTag.ref(), PT_LC("getmessage"), 0, NULL);
					if (UNEXPECTED(description.isUndef())) return false;
				}
				writeSlot(PT_CR_PROP_IS_DEPRECATED, zv::Val::boolean(true));
				writeSlot(PT_CR_PROP_DEPRECATED_DESCRIPTION, std::move(description));
				return true;
			}
		}

		bool isTrait_;
		if (UNEXPECTED(!isTrait(isTrait_))) return false;
		if (isTrait_) {
			zv::Val nativeReflection = getNativeReflection();
			if (UNEXPECTED(nativeReflection.isUndef())) return false;
			static zend_string *deprecatedLiteral = nullptr;
			zval arg;
			ZVAL_STR(&arg, literal(deprecatedLiteral, PT_LC("Deprecated")));
			zv::Val attributes = callOn(nativeReflection.ref(), PT_LC("getattributes"), 1, &arg);
			if (UNEXPECTED(attributes.isUndef())) return false;
			if (countOf(attributes.ref()) > 0) {
				writeSlot(PT_CR_PROP_IS_DEPRECATED, zv::Val::boolean(true));
				writeSlot(PT_CR_PROP_DEPRECATED_DESCRIPTION, zv::Val::null());
				return true;
			}
		}

		writeSlot(PT_CR_PROP_IS_DEPRECATED, zv::Val::boolean(false));
		writeSlot(PT_CR_PROP_DEPRECATED_DESCRIPTION, zv::Val::null());
		return true;
	}

	bool isBuiltin(bool &out) const { return reflectionCallBool(PT_LC("isinternal"), out); }

	bool isInternal(bool &out)
	{
		if (slot(PT_CR_PROP_IS_INTERNAL).isNull()) {
			zv::Val resolvedPhpDoc = getResolvedPhpDoc();
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
			bool internal = false;
			if (!resolvedPhpDoc.isNull() && UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isinternal"), 0, NULL, internal))) return false;
			writeSlot(PT_CR_PROP_IS_INTERNAL, zv::Val::boolean(internal));
		}

		out = slot(PT_CR_PROP_IS_INTERNAL).isTrue();
		return true;
	}

	bool isImmutable(bool &out)
	{
		if (slot(PT_CR_PROP_IS_IMMUTABLE).isNull()) {
			zv::Val resolvedPhpDoc = getResolvedPhpDoc();
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
			bool immutable = false;
			if (!resolvedPhpDoc.isNull()) {
				if (UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isimmutable"), 0, NULL, immutable))) return false;
				if (!immutable && UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isreadonly"), 0, NULL, immutable))) return false;
			}
			writeSlot(PT_CR_PROP_IS_IMMUTABLE, zv::Val::boolean(immutable));

			zv::Val parentClass = getParentClass();
			if (UNEXPECTED(parentClass.isUndef())) return false;
			if (!parentClass.isNull() && !immutable) {
				bool parentImmutable;
				if (UNEXPECTED(!crIsImmutable(parentClass.ref(), parentImmutable))) return false;
				writeSlot(PT_CR_PROP_IS_IMMUTABLE, zv::Val::boolean(parentImmutable));
			}
		}

		out = slot(PT_CR_PROP_IS_IMMUTABLE).isTrue();
		return true;
	}

	bool hasConsistentConstructor(bool &out)
	{
		if (slot(PT_CR_PROP_HAS_CONSISTENT_CONSTRUCTOR).isNull()) {
			zv::Val resolvedPhpDoc = getResolvedPhpDoc();
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
			bool consistent = false;
			if (!resolvedPhpDoc.isNull() && UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("hasconsistentconstructor"), 0, NULL, consistent))) return false;
			writeSlot(PT_CR_PROP_HAS_CONSISTENT_CONSTRUCTOR, zv::Val::boolean(consistent));
		}

		out = slot(PT_CR_PROP_HAS_CONSISTENT_CONSTRUCTOR).isTrue();
		return true;
	}

	bool acceptsNamedArguments(bool &out)
	{
		if (slot(PT_CR_PROP_ACCEPTS_NAMED_ARGUMENTS).isNull()) {
			zv::Val resolvedPhpDoc = getResolvedPhpDoc();
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
			bool accepts = true;
			if (!resolvedPhpDoc.isNull() && UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("acceptsnamedarguments"), 0, NULL, accepts))) return false;
			writeSlot(PT_CR_PROP_ACCEPTS_NAMED_ARGUMENTS, zv::Val::boolean(accepts));
		}

		out = slot(PT_CR_PROP_ACCEPTS_NAMED_ARGUMENTS).isTrue();
		return true;
	}

	bool isAttributeClass(bool &out)
	{
		zv::Val flags = findAttributeFlags();
		if (UNEXPECTED(flags.isUndef())) return false;
		out = !flags.isNull();
		return true;
	}

	/* private; ?int */
	zv::Val findAttributeFlags()
	{
		bool is;
		if (UNEXPECTED(!isInterface(is))) return zv::Val();
		if (!is && UNEXPECTED(!isTrait(is))) return zv::Val();
		if (!is && UNEXPECTED(!isEnum(is))) return zv::Val();
		if (is) return zv::Val::null();

		static zend_string *attributeLiteral = nullptr;
		zend_string *attributeName = literal(attributeLiteral, PT_LC("Attribute"));
		zval attributeArg;
		ZVAL_STR(&attributeArg, attributeName);
		zv::Val nativeAttributes = reflectionCall(PT_LC("getattributes"), 1, &attributeArg);
		if (UNEXPECTED(nativeAttributes.isUndef())) return zv::Val();
		if (countOf(nativeAttributes.ref()) != 1) return zv::Val::null();

		bool hasAttributeClass;
		if (UNEXPECTED(!providerHasClass(attributeName, hasAttributeClass))) return zv::Val();
		if (!hasAttributeClass) return zv::Val::null();
		zv::Val attributeClass = providerGetClass(attributeName);
		if (UNEXPECTED(attributeClass.isUndef())) return zv::Val();

		zval *firstAttribute = zend_hash_index_find(nativeAttributes.ref().asArrayTable(), 0);
		if (UNEXPECTED(firstAttribute == NULL)) return zv::Val::null();
		zv::Val argumentsExpressions = callOn(zv::Ref(firstAttribute), PT_LC("getargumentsexpressions"), 0, NULL);
		if (UNEXPECTED(argumentsExpressions.isUndef())) return zv::Val();

		zv::Arr arguments = zv::Arr::create(countOf(argumentsExpressions.ref()));
		if (argumentsExpressions.ref().isArray()) {
			for (auto entry : zv::ArrRef(argumentsExpressions.raw())) {
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL && ZSTR_LEN(key) == 0) {
					throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
					return zv::Val();
				}
				zv::Val name = zv::Val::null();
				if (key != NULL) {
					zval identifierArg;
					ZVAL_STR(&identifierArg, key);
					name = pt_type_new(PT_CLASS_IDENTIFIER, 1, &identifierArg);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
				}
				zval argArgs[5];
				ZVAL_COPY_VALUE(&argArgs[0], entry.value().raw());
				ZVAL_FALSE(&argArgs[1]);
				ZVAL_FALSE(&argArgs[2]);
				ZVAL_EMPTY_ARRAY(&argArgs[3]);
				ZVAL_COPY_VALUE(&argArgs[4], name.raw());
				zv::Val arg = pt_type_new(PT_CLASS_ARG, 5, argArgs);
				if (UNEXPECTED(arg.isUndef())) return zv::Val();
				arguments.push(std::move(arg));
			}
		}

		bool hasConstructor_;
		if (UNEXPECTED(!crHasConstructor(attributeClass.ref(), hasConstructor_))) return zv::Val();
		if (!hasConstructor_) return zv::Val::null();
		zv::Val attributeConstructor = crGetConstructor(attributeClass.ref());
		if (UNEXPECTED(attributeConstructor.isUndef())) return zv::Val();
		zv::Val attributeConstructorVariant = callOn(attributeConstructor.ref(), PT_LC("getonlyvariant"), 0, NULL);
		if (UNEXPECTED(attributeConstructorVariant.isUndef())) return zv::Val();

		zv::Val flagType;
		if (arguments.arrRef().size() == 0) {
			zv::Val parameters = callOn(attributeConstructorVariant.ref(), PT_LC("getparameters"), 0, NULL);
			if (UNEXPECTED(parameters.isUndef())) return zv::Val();
			zval *firstParameter = parameters.ref().isArray() ? zend_hash_index_find(parameters.ref().asArrayTable(), 0) : NULL;
			if (firstParameter == NULL) return zv::Val::null();
			flagType = callOn(zv::Ref(firstParameter), PT_LC("getdefaultvalue"), 0, NULL);
			if (UNEXPECTED(flagType.isUndef())) return zv::Val();
		} else {
			zval classNameArg;
			ZVAL_STR(&classNameArg, attributeName);
			zv::Val class_ = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, &classNameArg);
			if (UNEXPECTED(class_.isUndef())) return zv::Val();
			zv::Val constructorName = callOn(attributeConstructor.ref(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(constructorName.isUndef())) return zv::Val();
			zv::Args staticCallArgs{class_.raw(), constructorName.raw(), arguments.raw()};
			zv::Val staticCallNode = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgs);
			if (UNEXPECTED(staticCallNode.isUndef())) return zv::Val();
			zv::Args reorderArgs{attributeConstructorVariant.raw(), staticCallNode.raw()};
			zv::Val staticCall = pt_type_call_static(PT_CLASS_ARGUMENTS_NORMALIZER, PT_LC("reorderstaticcallarguments"), 2, reorderArgs);
			if (UNEXPECTED(staticCall.isUndef())) return zv::Val();
			if (staticCall.isNull()) return zv::Val::null();

			zv::Val callArgs = callOn(staticCall.ref(), PT_LC("getargs"), 0, NULL);
			if (UNEXPECTED(callArgs.isUndef())) return zv::Val();
			zval *firstArg = callArgs.ref().isArray() ? zend_hash_index_find(callArgs.ref().asArrayTable(), 0) : NULL;
			if (UNEXPECTED(firstArg == NULL || Z_TYPE_P(firstArg) != IS_OBJECT)) return zv::Val::null();
			zv::Ref flagExpr = zv::ObjRef(Z_OBJ_P(firstArg)).prop(PT_LC("value"));
			if (UNEXPECTED(flagExpr.raw() == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: %s has no property $value", ZSTR_VAL(Z_OBJCE_P(firstArg)->name));
				return zv::Val();
			}
			zv::Val context = initializerExprContextFromClassReflection();
			if (UNEXPECTED(context.isUndef())) return zv::Val();
			zv::Args typeArgs{flagExpr.raw(), context.raw()};
			flagType = callService(PT_CR_PROP_INITIALIZER_EXPR_TYPE_RESOLVER, "initializerExprTypeResolver", PT_LC("gettype"), 2, typeArgs);
			if (UNEXPECTED(flagType.isUndef())) return zv::Val();
		}

		if (!instanceOfShadowed(flagType.ref(), pt_ce_constant_integer_type, PT_LC(PT_CR_CONSTANT_INTEGER_TYPE_NAME))) return zv::Val::null();

		return callOn(flagType.ref(), PT_LC("getvalue"), 0, NULL);
	}

	/* list<AttributeReflection> */
	zv::Val getAttributes()
	{
		zv::Val attributes = reflectionCall(PT_LC("getattributes"), 0, NULL);
		if (UNEXPECTED(attributes.isUndef())) return zv::Val();
		zv::Val context = initializerExprContextFromClass();
		if (UNEXPECTED(context.isUndef())) return zv::Val();
		zv::Args args{attributes.raw(), context.raw()};
		return callService(PT_CR_PROP_ATTRIBUTE_REFLECTION_FACTORY, "attributeReflectionFactory", PT_LC("fromnativereflection"), 2, args);
	}

	zv::Val getAttributeClassFlags()
	{
		zv::Val flags = findAttributeFlags();
		if (UNEXPECTED(flags.isUndef())) return zv::Val();
		if (flags.isNull()) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}

		return flags;
	}

	zv::Val getObjectType()
	{
		bool generic;
		if (UNEXPECTED(!isGeneric(generic))) return zv::Val();
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *nameStr = zval_get_string(name.raw());

		if (!generic) {
			zval out;
			bool ok = pt_object_type_new(&out, nameStr);
			zend_string_release(nameStr);
			return ok ? zv::Val::adopt(out) : zv::Val();
		}

		zv::Val activeTemplateTypeMap = getActiveTemplateTypeMap();
		if (UNEXPECTED(activeTemplateTypeMap.isUndef())) {
			zend_string_release(nameStr);
			return zv::Val();
		}
		zv::Val types = typeMapToList(activeTemplateTypeMap.ref());
		if (UNEXPECTED(types.isUndef())) {
			zend_string_release(nameStr);
			return zv::Val();
		}
		zv::Val callSiteVarianceMap = getCallSiteVarianceMap();
		if (UNEXPECTED(callSiteVarianceMap.isUndef())) {
			zend_string_release(nameStr);
			return zv::Val();
		}
		zv::Val variances = varianceMapToList(callSiteVarianceMap.ref());
		if (UNEXPECTED(variances.isUndef())) {
			zend_string_release(nameStr);
			return zv::Val();
		}

		zval out;
		bool ok = pt_generic_object_type_new(&out, nameStr, types.raw(), NULL, NULL, variances.raw());
		zend_string_release(nameStr);
		return ok ? zv::Val::adopt(out) : zv::Val();
	}

	zv::Val getTemplateTypeMap()
	{
		zv::Ref memo = slot(PT_CR_PROP_TEMPLATE_TYPE_MAP);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) {
			zval empty;
			if (UNEXPECTED(!pt_template_type_map_empty(&empty))) return zv::Val();
			writeSlot(PT_CR_PROP_TEMPLATE_TYPE_MAP, zv::Val::copyOf(zv::Ref(&empty)));
			return zv::Val::adopt(empty);
		}

		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val templateTypeScope = templateTypeScopeWithClass(name.raw());
		if (UNEXPECTED(templateTypeScope.isUndef())) return zv::Val();

		zv::Val templateTags = getTemplateTags();
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(countOf(templateTags.ref()));
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val templateType = templateTypeFactoryFromTemplateTag(templateTypeScope.raw(), entry.value().raw());
				if (UNEXPECTED(templateType.isUndef())) return zv::Val();
				setAtKey(types, entry, std::move(templateType));
			}
		}

		zval map;
		if (UNEXPECTED(!pt_template_type_map_new(&map, types.raw(), NULL))) return zv::Val();
		writeSlot(PT_CR_PROP_TEMPLATE_TYPE_MAP, zv::Val::copyOf(zv::Ref(&map)));

		return zv::Val::adopt(map);
	}

	zv::Val getActiveTemplateTypeMap()
	{
		zv::Ref memo = slot(PT_CR_PROP_ACTIVE_TEMPLATE_TYPE_MAP);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Ref resolved = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolved.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		if (resolved.isNull()) {
			zv::Val templateTypeMap = getTemplateTypeMap();
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			writeSlot(PT_CR_PROP_ACTIVE_TEMPLATE_TYPE_MAP, zv::Val::copyOf(templateTypeMap.ref()));
			return templateTypeMap;
		}

		zv::Val templateTypeMap = getTemplateTypeMap();
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();

		/* $resolved->map(fn ($name, $type) => $type instanceof ErrorType &&
		 * ($t = $templateTypeMap->getType($name)) !== null
		 *     ? TemplateTypeHelper::resolveToDefaults($t) : $type) */
		zv::Val types = callOn(resolved, PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Arr mapped = zv::Arr::create(countOf(types.ref()));
		if (types.ref().isArray()) {
			for (auto entry : zv::ArrRef(types.raw())) {
				zv::Val result;
				if (!instanceOfShadowed(entry.value(), pt_ce_error_type, PT_LC(PT_CR_ERROR_TYPE_NAME))) {
					result = zv::Val::copyOf(entry.value());
				} else {
					zv::Val name = entryKeyAsValue(entry);
					zv::Val templateType = callOn(templateTypeMap.ref(), PT_LC("gettype"), 1, name.raw());
					if (UNEXPECTED(templateType.isUndef())) return zv::Val();
					if (templateType.isNull()) {
						result = zv::Val::copyOf(entry.value());
					} else {
						result = pt_type_template_type_helper_resolve_to_defaults(templateType.raw());
						if (UNEXPECTED(result.isUndef())) return zv::Val();
					}
				}
				setAtKey(mapped, entry, std::move(result));
			}
		}

		zval out;
		if (UNEXPECTED(!pt_template_type_map_new(&out, mapped.raw(), NULL))) return zv::Val();
		writeSlot(PT_CR_PROP_ACTIVE_TEMPLATE_TYPE_MAP, zv::Val::copyOf(zv::Ref(&out)));

		return zv::Val::adopt(out);
	}

	zv::Val getPossiblyIncompleteActiveTemplateTypeMap()
	{
		zv::Ref resolved = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolved.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		if (!resolved.isNull()) return zv::Val::copyOf(resolved);

		return getTemplateTypeMap();
	}

	/* private */
	zv::Val getDefaultCallSiteVarianceMap()
	{
		zv::Ref memo = slot(PT_CR_PROP_DEFAULT_CALL_SITE_VARIANCE_MAP);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) {
			zval empty;
			if (UNEXPECTED(!pt_template_type_variance_map_empty(&empty))) return zv::Val();
			writeSlot(PT_CR_PROP_DEFAULT_CALL_SITE_VARIANCE_MAP, zv::Val::copyOf(zv::Ref(&empty)));
			return zv::Val::adopt(empty);
		}

		zv::Val templateTags = getTemplateTags();
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		zv::Arr map = zv::Arr::create(countOf(templateTags.ref()));
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val tagName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(tagName.isUndef())) return zv::Val();
				zv::Val invariant = templateTypeVarianceInvariant();
				if (UNEXPECTED(invariant.isUndef())) return zv::Val();
				zend_string *key = zval_get_string(tagName.raw());
				map.set(key, std::move(invariant));
				zend_string_release(key);
			}
		}

		zval out;
		if (UNEXPECTED(!pt_template_type_variance_map_new(&out, map.raw()))) return zv::Val();
		writeSlot(PT_CR_PROP_DEFAULT_CALL_SITE_VARIANCE_MAP, zv::Val::copyOf(zv::Ref(&out)));

		return zv::Val::adopt(out);
	}

	zv::Val getCallSiteVarianceMap()
	{
		zv::Ref memo = slot(PT_CR_PROP_CALL_SITE_VARIANCE_MAP);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Ref resolved = slot(PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP);
		if (UNEXPECTED(resolved.isUndef())) return uninitializedProperty("resolvedCallSiteVarianceMap");
		zv::Val map;
		if (!resolved.isNull()) {
			map = zv::Val::copyOf(resolved);
		} else {
			map = getDefaultCallSiteVarianceMap();
			if (UNEXPECTED(map.isUndef())) return zv::Val();
		}
		writeSlot(PT_CR_PROP_CALL_SITE_VARIANCE_MAP, zv::Val::copyOf(map.ref()));

		return map;
	}

	zv::Val typeMapFromList(zv::Ref types)
	{
		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) {
			zval empty;
			return pt_template_type_map_empty(&empty) ? zv::Val::adopt(empty) : zv::Val();
		}

		zv::Val templateTags = callOn(resolvedPhpDoc.ref(), PT_LC("gettemplatetags"), 0, NULL);
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		zv::Val className = getName();
		if (UNEXPECTED(className.isUndef())) return zv::Val();

		zv::Arr map = zv::Arr::create(countOf(templateTags.ref()));
		zend_ulong i = 0;
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val type = tagTypeAt(types, i, entry.value());
				if (UNEXPECTED(type.isUndef())) return zv::Val();

				/* TypeTraverser::map($type, static function (Type $type, callable
				 * $traverse) use ($map, $className): Type { ... }) */
				zval mapState;
				ZVAL_COPY_VALUE(&mapState, map.raw());
				zv::Val callback = pt_type_native_callback(typeMapFromListVisitor, &mapState, className.raw());
				if (UNEXPECTED(callback.isUndef())) return zv::Val();
				zv::Val mapped = pt_type_traverser_map_of(type.raw(), callback.raw());
				if (UNEXPECTED(mapped.isUndef())) return zv::Val();

				zv::Val tagName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(tagName.isUndef())) return zv::Val();
				zend_string *key = zval_get_string(tagName.raw());
				map.set(key, std::move(mapped));
				zend_string_release(key);
				i++;
			}
		}

		zval out;
		return pt_template_type_map_new(&out, map.raw(), NULL) ? zv::Val::adopt(out) : zv::Val();
	}

	/* $types[$i] ?? $tag->getDefault() ?? $tag->getBound() */
	static zv::Val tagTypeAt(zv::Ref types, zend_ulong i, zv::Ref tag)
	{
		if (types.isArray()) {
			zval *found = zend_hash_index_find(types.asArrayTable(), i);
			if (found != NULL && Z_TYPE_P(found) != IS_NULL) return zv::Val::copyOf(zv::Ref(found));
		}
		zv::Val default_ = callOn(tag, PT_LC("getdefault"), 0, NULL);
		if (UNEXPECTED(default_.isUndef())) return zv::Val();
		if (!default_.isNull()) return default_;
		return callOn(tag, PT_LC("getbound"), 0, NULL);
	}

	/* the `use ($map, $className)` closure of typeMapFromList() */
	static void typeMapFromListVisitor(zval *map, zval *className, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2 || Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_argument_count_error("Too few arguments to function ClassReflection::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *type = &argv[0];

		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return;
		if (!isTemplate) {
			zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
			if (UNEXPECTED(traversed.isUndef())) return;
			traversed.intoReturnValue(return_value);
			return;
		}

		zv::Val scope = pt_type_call(Z_OBJ_P(type), PT_LC("getscope"), 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return;
		zv::Val scopeClassName = callOn(scope.ref(), PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(scopeClassName.isUndef())) return;
		bool sameClass = Z_TYPE_P(scopeClassName.raw()) == IS_STRING
			&& Z_TYPE_P(className) == IS_STRING
			&& zend_string_equals(Z_STR_P(scopeClassName.raw()), Z_STR_P(className));
		if (!sameClass) {
			ZVAL_COPY(return_value, type);
			return;
		}

		zv::Val name = pt_type_call(Z_OBJ_P(type), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return;
		zend_string *nameStr = zval_get_string(name.raw());
		zval *resolved = Z_TYPE_P(map) == IS_ARRAY ? zend_symtable_find(Z_ARRVAL_P(map), nameStr) : NULL;
		zend_string_release(nameStr);
		if (resolved != NULL && Z_TYPE_P(resolved) != IS_NULL) {
			bool resolvedIsTemplate;
			if (UNEXPECTED(!pt_type_instanceof(resolved, PT_CLASS_TEMPLATE_TYPE, resolvedIsTemplate))) return;
			if (!resolvedIsTemplate) {
				ZVAL_COPY(return_value, resolved);
				return;
			}
		}

		ZVAL_COPY(return_value, type);
	}

	zv::Val varianceMapFromList(zv::Ref variances)
	{
		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) {
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			zval out;
			return pt_template_type_variance_map_new(&out, &empty) ? zv::Val::adopt(out) : zv::Val();
		}

		zv::Val templateTags = callOn(resolvedPhpDoc.ref(), PT_LC("gettemplatetags"), 0, NULL);
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		zv::Arr map = zv::Arr::create(countOf(templateTags.ref()));
		zend_ulong i = 0;
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val variance;
				zval *found = variances.isArray() ? zend_hash_index_find(variances.asArrayTable(), i) : NULL;
				if (found != NULL && Z_TYPE_P(found) != IS_NULL) {
					variance = zv::Val::copyOf(zv::Ref(found));
				} else {
					variance = templateTypeVarianceInvariant();
					if (UNEXPECTED(variance.isUndef())) return zv::Val();
				}
				zv::Val tagName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(tagName.isUndef())) return zv::Val();
				zend_string *key = zval_get_string(tagName.raw());
				map.set(key, std::move(variance));
				zend_string_release(key);
				i++;
			}
		}

		zval out;
		return pt_template_type_variance_map_new(&out, map.raw()) ? zv::Val::adopt(out) : zv::Val();
	}

	/* list<Type> */
	zv::Val typeMapToList(zv::Ref typeMap)
	{
		zv::Val templateTags = resolvedPhpDocTemplateTags();
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		if (templateTags.isNull()) return zv::Val(zv::Arr::empty());

		zv::Arr list = zv::Arr::create(countOf(templateTags.ref()));
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val tagName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(tagName.isUndef())) return zv::Val();
				zv::Val type = callOn(typeMap, PT_LC("gettype"), 1, tagName.raw());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (type.isNull()) {
					type = callOn(entry.value(), PT_LC("getdefault"), 0, NULL);
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				}
				if (type.isNull()) {
					type = callOn(entry.value(), PT_LC("getbound"), 0, NULL);
					if (UNEXPECTED(type.isUndef())) return zv::Val();
				}
				list.push(std::move(type));
			}
		}

		return zv::Val(std::move(list));
	}

	/* list<TemplateTypeVariance> */
	zv::Val varianceMapToList(zv::Ref varianceMap)
	{
		zv::Val templateTags = resolvedPhpDocTemplateTags();
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		if (templateTags.isNull()) return zv::Val(zv::Arr::empty());

		zv::Arr list = zv::Arr::create(countOf(templateTags.ref()));
		if (templateTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(templateTags.raw())) {
				zv::Val tagName = callOn(entry.value(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(tagName.isUndef())) return zv::Val();
				zv::Val variance = callOn(varianceMap, PT_LC("getvariance"), 1, tagName.raw());
				if (UNEXPECTED(variance.isUndef())) return zv::Val();
				if (variance.isNull()) {
					variance = templateTypeVarianceInvariant();
					if (UNEXPECTED(variance.isUndef())) return zv::Val();
				}
				list.push(std::move(variance));
			}
		}

		return zv::Val(std::move(list));
	}

	/* $this->getResolvedPhpDoc()?->getTemplateTags(); null = no PHPDoc (the
	 * twin's `return []` of the *ToList() methods) */
	zv::Val resolvedPhpDocTemplateTags()
	{
		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) return zv::Val::null();
		return callOn(resolvedPhpDoc.ref(), PT_LC("gettemplatetags"), 0, NULL);
	}

	zv::Val withTypes(zv::Ref types)
	{
		zv::Val typeMap = typeMapFromList(types);
		if (UNEXPECTED(typeMap.isUndef())) return zv::Val();
		zv::Ref resolvedCallSiteVarianceMap = slot(PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP);
		if (UNEXPECTED(resolvedCallSiteVarianceMap.isUndef())) return uninitializedProperty("resolvedCallSiteVarianceMap");
		zv::Ref finalByKeywordOverride = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(finalByKeywordOverride.isUndef())) return uninitializedProperty("finalByKeywordOverride");

		return factoryCreate(typeMap.ref(), resolvedCallSiteVarianceMap, finalByKeywordOverride);
	}

	zv::Val withVariances(zv::Ref variances)
	{
		zv::Val varianceMap = varianceMapFromList(variances);
		if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
		zv::Ref resolvedTemplateTypeMap = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		zv::Ref finalByKeywordOverride = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(finalByKeywordOverride.isUndef())) return uninitializedProperty("finalByKeywordOverride");

		return factoryCreate(resolvedTemplateTypeMap, varianceMap.ref(), finalByKeywordOverride);
	}

	zv::Val asFinal() { return withFinality(true); }

	zv::Val withoutFinalByKeywordOverride()
	{
		zv::Ref finalByKeywordOverride = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(finalByKeywordOverride.isUndef())) return uninitializedProperty("finalByKeywordOverride");
		if (finalByKeywordOverride.isNull()) return thisValue();

		zv::Ref resolvedTemplateTypeMap = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		zv::Ref resolvedCallSiteVarianceMap = slot(PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP);
		if (UNEXPECTED(resolvedCallSiteVarianceMap.isUndef())) return uninitializedProperty("resolvedCallSiteVarianceMap");
		zval null_;
		ZVAL_NULL(&null_);

		return factoryCreate(resolvedTemplateTypeMap, resolvedCallSiteVarianceMap, zv::Ref(&null_));
	}

	zv::Val removeFinalKeywordOverride() { return withFinality(false); }

	/* asFinal() / removeFinalKeywordOverride(): the same guards, the
	 * override the only difference */
	zv::Val withFinality(bool override_)
	{
		bool finalByKeyword = false;
		if (UNEXPECTED(!reflectionCallBool(PT_LC("isfinal"), finalByKeyword))) return zv::Val();
		if (finalByKeyword) return thisValue();

		zv::Ref finalByKeywordOverride = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(finalByKeywordOverride.isUndef())) return uninitializedProperty("finalByKeywordOverride");
		if (finalByKeywordOverride.isBool() && zend_is_true(finalByKeywordOverride.raw()) == override_) return thisValue();

		bool isClass_;
		if (UNEXPECTED(!isClass(isClass_))) return zv::Val();
		if (!isClass_) return thisValue();
		bool isAbstract_;
		if (UNEXPECTED(!isAbstract(isAbstract_))) return zv::Val();
		if (isAbstract_) return thisValue();

		zv::Ref resolvedTemplateTypeMap = slot(PT_CR_PROP_RESOLVED_TEMPLATE_TYPE_MAP);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return uninitializedProperty("resolvedTemplateTypeMap");
		zv::Ref resolvedCallSiteVarianceMap = slot(PT_CR_PROP_RESOLVED_CALL_SITE_VARIANCE_MAP);
		if (UNEXPECTED(resolvedCallSiteVarianceMap.isUndef())) return uninitializedProperty("resolvedCallSiteVarianceMap");
		zval flag = {};
		ZVAL_BOOL(&flag, override_);

		return factoryCreate(resolvedTemplateTypeMap, resolvedCallSiteVarianceMap, zv::Ref(&flag));
	}

	/* $this->classReflectionFactory->create($this->displayName, $this->reflection,
	 * $this->anonymousFilename, $resolvedTemplateTypeMap, $this->stubPhpDocBlockCallback,
	 * null, $resolvedCallSiteVarianceMap, $finalByKeywordOverride) */
	zv::Val factoryCreate(zv::Ref resolvedTemplateTypeMap, zv::Ref resolvedCallSiteVarianceMap, zv::Ref finalByKeywordOverride)
	{
		zv::Ref displayName_ = slot(PT_CR_PROP_DISPLAY_NAME);
		if (UNEXPECTED(displayName_.isUndef())) return uninitializedProperty("displayName");
		zv::Ref reflection = slot(PT_CR_PROP_REFLECTION);
		if (UNEXPECTED(reflection.isUndef())) return uninitializedProperty("reflection");
		zv::Ref anonymousFilename = slot(PT_CR_PROP_ANONYMOUS_FILENAME);
		if (UNEXPECTED(anonymousFilename.isUndef())) return uninitializedProperty("anonymousFilename");
		zv::Ref stubPhpDocBlockCallback = slot(PT_CR_PROP_STUB_PHP_DOC_BLOCK_CALLBACK);
		if (UNEXPECTED(stubPhpDocBlockCallback.isUndef())) return uninitializedProperty("stubPhpDocBlockCallback");

		zval args[8];
		ZVAL_COPY_VALUE(&args[0], displayName_.raw());
		ZVAL_COPY_VALUE(&args[1], reflection.raw());
		ZVAL_COPY_VALUE(&args[2], anonymousFilename.raw());
		ZVAL_COPY_VALUE(&args[3], resolvedTemplateTypeMap.raw());
		ZVAL_COPY_VALUE(&args[4], stubPhpDocBlockCallback.raw());
		ZVAL_NULL(&args[5]);
		ZVAL_COPY_VALUE(&args[6], resolvedCallSiteVarianceMap.raw());
		ZVAL_COPY_VALUE(&args[7], finalByKeywordOverride.raw());

		return callService(PT_CR_PROP_CLASS_REFLECTION_FACTORY, "classReflectionFactory", PT_LC("create"), 8, args);
	}

	/* ?ResolvedPhpDocBlock */
	zv::Val getResolvedPhpDoc()
	{
		zv::Ref stubPhpDocBlockCallback = slot(PT_CR_PROP_STUB_PHP_DOC_BLOCK_CALLBACK);
		if (UNEXPECTED(stubPhpDocBlockCallback.isUndef())) return uninitializedProperty("stubPhpDocBlockCallback");
		if (!stubPhpDocBlockCallback.isNull()) {
			if (slot(PT_CR_PROP_STUB_PHP_DOC_BLOCK).isFalse()) {
				zv::Val block = callCallable(stubPhpDocBlockCallback, 0, NULL);
				if (UNEXPECTED(block.isUndef())) return zv::Val();
				writeSlot(PT_CR_PROP_STUB_PHP_DOC_BLOCK, std::move(block));
			}
			zv::Ref stubPhpDocBlock = slot(PT_CR_PROP_STUB_PHP_DOC_BLOCK);
			if (!stubPhpDocBlock.isNull()) return zv::Val::copyOf(stubPhpDocBlock);
		}

		zv::Val fileName = getFileName();
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		if (UNEXPECTED(!resolveReflectionDocComment())) return zv::Val();
		zv::Ref reflectionDocComment = slot(PT_CR_PROP_REFLECTION_DOC_COMMENT);
		if (reflectionDocComment.isNull()) return zv::Val::null();

		zv::Ref resolvedPhpDocBlock = slot(PT_CR_PROP_RESOLVED_PHP_DOC_BLOCK);
		if (!resolvedPhpDocBlock.isFalse()) return zv::Val::copyOf(resolvedPhpDocBlock);

		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Args args{fileName.raw(), name.raw(), zv::null, zv::null, slot(PT_CR_PROP_REFLECTION_DOC_COMMENT).raw()};
		zv::Val resolved = callService(PT_CR_PROP_FILE_TYPE_MAPPER, "fileTypeMapper", PT_LC("getresolvedphpdoc"), 5, args);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		writeSlot(PT_CR_PROP_RESOLVED_PHP_DOC_BLOCK, zv::Val::copyOf(resolved.ref()));

		return resolved;
	}

	/* ?ResolvedPhpDocBlock */
	zv::Val getTraitContextResolvedPhpDoc(zv::Ref implementingClass)
	{
		bool isTrait_;
		if (UNEXPECTED(!isTrait(isTrait_))) return zv::Val();
		if (!isTrait_) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}
		bool implementingIsTrait;
		if (UNEXPECTED(!crIsTrait(implementingClass, implementingIsTrait))) return zv::Val();
		if (implementingIsTrait) {
			throwNew(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
			return zv::Val();
		}

		zv::Val fileName = getFileName();
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		if (UNEXPECTED(!resolveReflectionDocComment())) return zv::Val();
		if (slot(PT_CR_PROP_REFLECTION_DOC_COMMENT).isNull()) return zv::Val::null();

		zv::Ref traitContextResolvedPhpDocBlock = slot(PT_CR_PROP_TRAIT_CONTEXT_RESOLVED_PHP_DOC_BLOCK);
		if (!traitContextResolvedPhpDocBlock.isFalse()) return zv::Val::copyOf(traitContextResolvedPhpDocBlock);

		zv::Val implementingName = crGetName(implementingClass);
		if (UNEXPECTED(implementingName.isUndef())) return zv::Val();
		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Args args{fileName.raw(), implementingName.raw(), name.raw(), zv::null, slot(PT_CR_PROP_REFLECTION_DOC_COMMENT).raw()};
		zv::Val resolved = callService(PT_CR_PROP_FILE_TYPE_MAPPER, "fileTypeMapper", PT_LC("getresolvedphpdoc"), 5, args);
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		writeSlot(PT_CR_PROP_TRAIT_CONTEXT_RESOLVED_PHP_DOC_BLOCK, zv::Val::copyOf(resolved.ref()));

		return resolved;
	}

	/* if (is_bool($this->reflectionDocComment)) { $c = $this->reflection->getDocComment();
	 * $this->reflectionDocComment = $c !== false ? $c : null; } */
	bool resolveReflectionDocComment()
	{
		if (!slot(PT_CR_PROP_REFLECTION_DOC_COMMENT).isBool()) return true;
		zv::Val docComment = reflectionCall(PT_LC("getdoccomment"), 0, NULL);
		if (UNEXPECTED(docComment.isUndef())) return false;
		if (docComment.ref().isFalse()) {
			writeSlot(PT_CR_PROP_REFLECTION_DOC_COMMENT, zv::Val::null());
		} else {
			writeSlot(PT_CR_PROP_REFLECTION_DOC_COMMENT, std::move(docComment));
		}
		return true;
	}

	/* the tag getters: $this->getResolvedPhpDoc()?->get<X>Tags() ?? [] */
	zv::Val resolvedPhpDocTags(const char *lcname, size_t len)
	{
		zv::Val resolvedPhpDoc = getResolvedPhpDoc();
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
		if (resolvedPhpDoc.isNull()) return zv::Val(zv::Arr::empty());
		return callOn(resolvedPhpDoc.ref(), lcname, len, 0, NULL);
	}

	zv::Val getExtendsTags() { return resolvedPhpDocTags(PT_LC("getextendstags")); }
	zv::Val getImplementsTags() { return resolvedPhpDocTags(PT_LC("getimplementstags")); }
	zv::Val getTemplateTags() { return resolvedPhpDocTags(PT_LC("gettemplatetags")); }
	zv::Val getMixinTags() { return resolvedPhpDocTags(PT_LC("getmixintags")); }
	zv::Val getRequireExtendsTags() { return resolvedPhpDocTags(PT_LC("getrequireextendstags")); }
	zv::Val getRequireImplementsTags() { return resolvedPhpDocTags(PT_LC("getrequireimplementstags")); }
	zv::Val getSealedTags() { return resolvedPhpDocTags(PT_LC("getsealedtags")); }
	zv::Val getPropertyTags() { return resolvedPhpDocTags(PT_LC("getpropertytags")); }
	zv::Val getMethodTags() { return resolvedPhpDocTags(PT_LC("getmethodtags")); }

	/* array<string, ClassReflection> */
	zv::Val getAncestors()
	{
		zv::Ref memo = slot(PT_CR_PROP_ANCESTORS);
		if (!memo.isNull()) return zv::Val::copyOf(memo);

		zv::Val name = getName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Arr ancestors = zv::Arr::create(8);
		zend_string *nameStr = zval_get_string(name.raw());
		zval self_;
		ZVAL_OBJ(&self_, self);
		ancestors.set(nameStr, zv::Val::copyOf(zv::Ref(&self_)));
		zend_string_release(nameStr);

		if (UNEXPECTED(!collectAncestors(ancestors))) return zv::Val();

		writeSlot(PT_CR_PROP_ANCESTORS, zv::Val::copyOf(ancestors.ref()));

		return zv::Val(std::move(ancestors));
	}

	/* private: descends into the interfaces, traits and parent class, the
	 * collected ancestors doubling as the set of already visited classes -
	 * traits can use each other in a cycle, a fatal error in PHP that must
	 * not make this walk run forever; false = pending exception */
	[[nodiscard]] bool collectAncestors(zv::Arr &ancestors)
	{
		zv::Val interfaces = getInterfaces();
		if (UNEXPECTED(interfaces.isUndef())) return false;
		if (UNEXPECTED(!addAllToAncestors(ancestors, interfaces.ref()))) return false;

		zv::Val traits = getTraits(false);
		if (UNEXPECTED(traits.isUndef())) return false;
		if (UNEXPECTED(!addAllToAncestors(ancestors, traits.ref()))) return false;

		zv::Val parent = getParentClass();
		if (UNEXPECTED(parent.isUndef())) return false;
		if (parent.isNull()) return true;

		return addToAncestors(ancestors, parent.ref());
	}

	/* $classReflection->collectAncestors($ancestors): the native body for
	 * exactly this class, the same walk through the public methods of any
	 * other ClassReflection (the PHP twins of the differential harness) */
	static bool collectAncestorsOf(zv::Ref classReflection, zv::Arr &ancestors)
	{
		zv::Ref value = classReflection.deref();
		if (UNEXPECTED(!value.isObject())) {
			zend_throw_error(NULL, "Call to a member function collectAncestors() on %s", zend_zval_value_name(value.raw()));
			return false;
		}
		if (EXPECTED(isNative(value.asObject()))) return ClassReflection(value.asObject()).collectAncestors(ancestors);

		zv::Val interfaces = pt_type_call(value.asObject(), PT_LC("getinterfaces"), 0, NULL);
		if (UNEXPECTED(interfaces.isUndef())) return false;
		if (UNEXPECTED(!addAllToAncestors(ancestors, interfaces.ref()))) return false;

		zv::Val traits = pt_type_call(value.asObject(), PT_LC("gettraits"), 0, NULL);
		if (UNEXPECTED(traits.isUndef())) return false;
		if (UNEXPECTED(!addAllToAncestors(ancestors, traits.ref()))) return false;

		zv::Val parent = pt_type_call(value.asObject(), PT_LC("getparentclass"), 0, NULL);
		if (UNEXPECTED(parent.isUndef())) return false;
		if (parent.isNull()) return true;

		return addToAncestors(ancestors, parent.ref());
	}

	/* foreach ($classReflections as $classReflection) $addToAncestors($classReflection) */
	static bool addAllToAncestors(zv::Arr &ancestors, zv::Ref classReflections)
	{
		if (!classReflections.isArray()) return true;
		for (auto entry : zv::ArrRef(classReflections.raw())) {
			if (UNEXPECTED(!addToAncestors(ancestors, entry.value()))) return false;
		}
		return true;
	}

	/* $addToAncestors($classReflection): a class not collected yet is added
	 * and descended into */
	static bool addToAncestors(zv::Arr &ancestors, zv::Ref classReflection)
	{
		zv::Val name = crGetName(classReflection);
		if (UNEXPECTED(name.isUndef())) return false;
		zv::Str nameStr = zv::Str::adopt(zval_get_string(name.raw()));
		if (ancestors.arrRef().exists(nameStr.get())) return true;

		ancestors.set(nameStr.get(), zv::Val::copyOf(classReflection));
		return collectAncestorsOf(classReflection, ancestors);
	}

	zv::Val getAncestorWithClassName(zend_string *className)
	{
		zv::Val ancestors = getAncestors();
		if (UNEXPECTED(ancestors.isUndef())) return zv::Val();
		if (!ancestors.ref().isArray()) return zv::Val::null();
		zval *found = zend_symtable_find(ancestors.ref().asArrayTable(), className);
		if (found == NULL) return zv::Val::null();

		return zv::Val::copyOf(zv::Ref(found));
	}

	/* list<Type> */
	zv::Val getResolvedMixinTypes()
	{
		zv::Val mixinTags = getMixinTags();
		if (UNEXPECTED(mixinTags.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(countOf(mixinTags.ref()));
		if (mixinTags.ref().isArray()) {
			for (auto entry : zv::ArrRef(mixinTags.raw())) {
				zv::Val type = callOn(entry.value(), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				bool generic;
				if (UNEXPECTED(!isGeneric(generic))) return zv::Val();
				if (!generic) {
					types.push(std::move(type));
					continue;
				}

				zv::Val activeTemplateTypeMap = getActiveTemplateTypeMap();
				if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
				zv::Val callSiteVarianceMap = getCallSiteVarianceMap();
				if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
				zv::Val staticVariance = templateTypeVarianceStatic();
				if (UNEXPECTED(staticVariance.isUndef())) return zv::Val();
				zv::Val resolved = pt_type_template_type_helper_resolve_template_types(type.raw(), activeTemplateTypeMap.raw(), callSiteVarianceMap.raw(), staticVariance.raw(), false);
				if (UNEXPECTED(resolved.isUndef())) return zv::Val();
				types.push(std::move(resolved));
			}
		}

		return zv::Val(std::move(types));
	}

	/* array<Type>|null, memoized in $allowedSubTypes once resolved */
	zv::Val getAllowedSubTypes()
	{
		if (slot(PT_CR_PROP_ALLOWED_SUB_TYPES_RESOLVED).isTrue()) return zv::Val::copyOf(slot(PT_CR_PROP_ALLOWED_SUB_TYPES));

		writeSlot(PT_CR_PROP_ALLOWED_SUB_TYPES_RESOLVED, zv::Val::boolean(true));
		zv::Val extensions = registryGet(PT_REGISTRY_ALLOWED_SUB_TYPES_EXTENSIONS);
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (extensions.ref().isArray()) {
			for (auto entry : zv::ArrRef(extensions.raw())) {
				zval arg;
				ZVAL_OBJ(&arg, self);
				bool supports;
				if (UNEXPECTED(!callBool(entry.value(), PT_LC("supports"), 1, &arg, supports))) return zv::Val();
				if (supports) {
					zv::Val allowedSubTypes = callOn(entry.value(), PT_LC("getallowedsubtypes"), 1, &arg);
					if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(allowedSubTypes.raw()) != IS_ARRAY && Z_TYPE_P(allowedSubTypes.raw()) != IS_NULL)) {
						zend_type_error("Cannot assign %s to property PHPStan\\Reflection\\ClassReflection::$allowedSubTypes of type ?array", zend_zval_value_name(allowedSubTypes.raw()));
						return zv::Val();
					}
					writeSlot(PT_CR_PROP_ALLOWED_SUB_TYPES, zv::Val::copyOf(zv::Ref(allowedSubTypes.raw())));
					return allowedSubTypes;
				}
			}
		}

		return zv::Val::null();
	}

	/* }}} */

	/* {{{ out of the twin's file order: the finality and
	 * genericness queries getCacheKey() / getParentClass() /
	 * isSubclassOfClass() need, and the private ancestor-resolution
	 * helpers of getParentClass() / getImmediateInterfaces() */

	bool isFinal(bool &out)
	{
		bool finalByKeyword = false;
		if (UNEXPECTED(!isFinalByKeyword(finalByKeyword))) return false;
		if (finalByKeyword) {
			out = true;
			return true;
		}

		zv::Ref memo = slot(PT_CR_PROP_IS_FINAL);
		if (memo.isNull()) {
			zv::Val resolvedPhpDoc = getResolvedPhpDoc();
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
			bool isFinal_ = false;
			if (!resolvedPhpDoc.isNull() && UNEXPECTED(!callBool(resolvedPhpDoc.ref(), PT_LC("isfinal"), 0, NULL, isFinal_))) return false;
			writeSlot(PT_CR_PROP_IS_FINAL, zv::Val::boolean(isFinal_));
		}

		out = slot(PT_CR_PROP_IS_FINAL).isTrue();
		return true;
	}

	bool hasFinalByKeywordOverride(bool &out) const
	{
		zv::Ref override_ = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(override_.isUndef())) {
			(void) uninitializedProperty("finalByKeywordOverride");
			return false;
		}
		out = !override_.isNull();
		return true;
	}

	bool isFinalByKeyword(bool &out) const
	{
		bool anonymous = false;
		if (UNEXPECTED(!isAnonymous(anonymous))) return false;
		if (anonymous) {
			out = true;
			return true;
		}

		zv::Ref override_ = slot(PT_CR_PROP_FINAL_BY_KEYWORD_OVERRIDE);
		if (UNEXPECTED(override_.isUndef())) {
			(void) uninitializedProperty("finalByKeywordOverride");
			return false;
		}
		if (!override_.isNull()) {
			out = zend_is_true(override_.raw());
			return true;
		}

		return reflectionCallBool(PT_LC("isfinal"), out);
	}

	bool isGeneric(bool &out)
	{
		zv::Ref memo = slot(PT_CR_PROP_IS_GENERIC);
		if (memo.isNull()) {
			bool isEnum_;
			if (UNEXPECTED(!isEnum(isEnum_))) return false;
			if (isEnum_) {
				writeSlot(PT_CR_PROP_IS_GENERIC, zv::Val::boolean(false));
				out = false;
				return true;
			}

			zv::Val templateTags = getTemplateTags();
			if (UNEXPECTED(templateTags.isUndef())) return false;
			writeSlot(PT_CR_PROP_IS_GENERIC, zv::Val::boolean(countOf(templateTags.ref()) > 0));
		}

		out = slot(PT_CR_PROP_IS_GENERIC).isTrue();
		return true;
	}

	/* private; the first @extends tag or null */
	zv::Val getFirstExtendsTag()
	{
		zv::Val tags = getExtendsTags();
		if (UNEXPECTED(tags.isUndef())) return zv::Val();
		if (tags.ref().isArray()) {
			for (auto entry : zv::ArrRef(tags.raw())) {
				return zv::Val::copyOf(entry.value());
			}
		}

		return zv::Val::null();
	}

	/* private; whether $type is a generic object type of one of the
	 * ancestor classes (the twin's list holds the one parent name here) */
	bool isValidAncestorType(zv::Ref type, zend_string *ancestorClass, bool &out)
	{
		if (!instanceOfShadowed(type, pt_ce_generic_object_type, PT_LC(PT_CR_GENERIC_OBJECT_TYPE_NAME))) {
			out = false;
			return true;
		}

		zv::Val reflection = typeGetClassReflection(type);
		if (UNEXPECTED(reflection.isUndef())) return false;
		if (reflection.isNull()) {
			out = false;
			return true;
		}

		zv::Val name = crGetName(reflection.ref());
		if (UNEXPECTED(name.isUndef())) return false;
		out = Z_TYPE_P(name.raw()) == IS_STRING && zend_string_equals(Z_STR_P(name.raw()), ancestorClass);
		return true;
	}

	/* private; the possibly incomplete active map with its ErrorType
	 * entries resolved to the template types' defaults where the bound is
	 * not mixed — the twin's map() closure, expanded */
	zv::Val getActiveTemplateTypeMapForAncestorResolution()
	{
		zv::Val map = getPossiblyIncompleteActiveTemplateTypeMap();
		if (UNEXPECTED(map.isUndef())) return zv::Val();
		zv::Val templateTypeMap = getTemplateTypeMap();
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val types = callOn(map.ref(), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return zv::Val();

		zv::Arr mapped = zv::Arr::create(countOf(types.ref()));
		if (types.ref().isArray()) {
			for (auto entry : zv::ArrRef(types.raw())) {
				zv::Val result;
				if (!instanceOfShadowed(entry.value(), pt_ce_error_type, PT_LC(PT_CR_ERROR_TYPE_NAME))) {
					result = zv::Val::copyOf(entry.value());
				} else {
					zv::Val name = entry.stringKeyOrNull() != NULL ? zv::Val::string(entry.stringKeyOrNull()) : zv::Val::adoptString(zend_long_to_str((zend_long) entry.indexKey()));
					zv::Val templateType = callOn(templateTypeMap.ref(), PT_LC("gettype"), 1, name.raw());
					if (UNEXPECTED(templateType.isUndef())) return zv::Val();
					bool isTemplate;
					if (UNEXPECTED(!pt_type_instanceof(templateType.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
					if (!isTemplate) {
						result = zv::Val::copyOf(entry.value());
					} else {
						zv::Val bound = callOn(templateType.ref(), PT_LC("getbound"), 0, NULL);
						if (UNEXPECTED(bound.isUndef())) return zv::Val();
						if (instanceOfShadowed(bound.ref(), pt_ce_mixed_type, PT_LC(PT_CR_MIXED_TYPE_NAME))) {
							result = zv::Val::copyOf(entry.value());
						} else {
							result = pt_type_template_type_helper_resolve_to_defaults(templateType.raw());
							if (UNEXPECTED(result.isUndef())) return zv::Val();
						}
					}
				}
				if (entry.stringKeyOrNull() != NULL) {
					mapped.set(entry.stringKeyOrNull(), std::move(result));
				} else {
					mapped.arrRef().setIndex(entry.indexKey(), result.ref());
				}
			}
		}

		zval out;
		if (UNEXPECTED(!pt_template_type_map_new(&out, mapped.raw(), NULL))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* }}} */

private:
	zend_object *self;
	zval selfZval;
};

} // namespace phpstanturbo

using phpstanturbo::ClassReflection;

/* {{{ the getters the Type kernel calls millions of times per run
 *
 * getName() 1.3M, isGeneric() 1.6M, hasMethod() 0.8M, getCacheKey() 0.6M in
 * a self-analysis of src/Analyser, src/Rules and src/Type. The shadowing
 * class is final, so an object of exactly pt_ce_class_reflection runs the
 * native body directly — no zend_call_function, no frame. A foreign object
 * (a test double, a class reflection built by something else) still goes
 * through the PHP method, which is what the twin's callers would do. */

/* $classReflection->getName(); UNDEF = pending exception */
zv::Val pt_class_reflection_get_name(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getName();
	return pt_type_call(classReflection, PT_LC("getname"), 0, NULL);
}

/* $classReflection->getCacheKey(); UNDEF = pending exception */
zv::Val pt_class_reflection_get_cache_key(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getCacheKey();
	return pt_type_call(classReflection, PT_LC("getcachekey"), 0, NULL);
}

/* $classReflection->getNativeReflection(); UNDEF = pending exception */
zv::Val pt_class_reflection_get_native_reflection(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getNativeReflection();
	return pt_type_call(classReflection, PT_LC("getnativereflection"), 0, NULL);
}

/* $method(...) on a foreign object, coerced to bool; false = pending
 * exception */
[[nodiscard]] static bool pt_cr_foreign_bool(zend_object *classReflection, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_type_call(classReflection, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $classReflection->isGeneric(); false = pending exception */
[[nodiscard]] bool pt_class_reflection_is_generic(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).isGeneric(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("isgeneric"), 0, NULL, out);
}

/* $classReflection->hasMethod($methodName); false = pending exception */
[[nodiscard]] bool pt_class_reflection_has_method(zend_object *classReflection, zval *methodName, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection && Z_TYPE_P(methodName) == IS_STRING)) {
		return ClassReflection(classReflection).hasMethod(Z_STR_P(methodName), out);
	}
	return pt_cr_foreign_bool(classReflection, PT_LC("hasmethod"), 1, methodName, out);
}

/* $classReflection->hasFinalByKeywordOverride(); false = pending exception */
[[nodiscard]] bool pt_class_reflection_has_final_by_keyword_override(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).hasFinalByKeywordOverride(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("hasfinalbykeywordoverride"), 0, NULL, out);
}

/* $classReflection->isEnum(); false = pending exception */
[[nodiscard]] bool pt_class_reflection_is_enum(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).isEnum(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("isenum"), 0, NULL, out);
}

/* $classReflection->getDisplayName($withTemplateTypes) / ->isBuiltin();
 * UNDEF / false = pending exception */
zv::Val pt_class_reflection_get_display_name(zend_object *classReflection, bool withTemplateTypes)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getDisplayName(withTemplateTypes);
	zval withTemplateTypesZv;
	ZVAL_BOOL(&withTemplateTypesZv, withTemplateTypes);
	return pt_type_call(classReflection, PT_LC("getdisplayname"), 1, &withTemplateTypesZv);
}

[[nodiscard]] bool pt_class_reflection_is_builtin(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).isBuiltin(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("isbuiltin"), 0, NULL, out);
}

/* the statement handlers' reads (ClassMethodHandler.cpp, ClassLikeHandler.cpp):
 * $classReflection->hasConstructor() / ->getConstructor() / ->isReadOnly() /
 * ->getFileName() / ->evictPrivateSymbols(); UNDEF / false = pending
 * exception */
[[nodiscard]] bool pt_class_reflection_has_constructor(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).hasConstructor(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("hasconstructor"), 0, NULL, out);
}

zv::Val pt_class_reflection_get_constructor(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getConstructor();
	return pt_type_call(classReflection, PT_LC("getconstructor"), 0, NULL);
}

[[nodiscard]] bool pt_class_reflection_is_read_only(zend_object *classReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).isReadOnly(out);
	return pt_cr_foreign_bool(classReflection, PT_LC("isreadonly"), 0, NULL, out);
}

zv::Val pt_class_reflection_get_file_name(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).getFileName();
	return pt_type_call(classReflection, PT_LC("getfilename"), 0, NULL);
}

[[nodiscard]] bool pt_class_reflection_evict_private_symbols(zend_object *classReflection)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection)) return ClassReflection(classReflection).evictPrivateSymbols();
	return !pt_type_call(classReflection, PT_LC("evictprivatesymbols"), 0, NULL).isUndef();
}

/* $classReflection->is($className) / ->isSubclassOfClass($class); false =
 * pending exception */
[[nodiscard]] bool pt_class_reflection_is(zend_object *classReflection, zval *className, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection && Z_TYPE_P(className) == IS_STRING)) return ClassReflection(classReflection).is(Z_STR_P(className), out);
	return pt_cr_foreign_bool(classReflection, PT_LC("is"), 1, className, out);
}

[[nodiscard]] bool pt_class_reflection_is_subclass_of_class(zend_object *classReflection, zval *otherClassReflection, bool &out)
{
	if (EXPECTED(classReflection->ce == pt_ce_class_reflection && Z_TYPE_P(otherClassReflection) == IS_OBJECT)) return ClassReflection(classReflection).isSubclassOfClass(zv::Ref(otherClassReflection), out);
	return pt_cr_foreign_bool(classReflection, PT_LC("issubclassofclass"), 1, otherClassReflection, out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ClassReflection(Z_OBJ_P(ZEND_THIS))

/* a `bool method(bool &out)` body into return_value */
#define PT_CR_RETURN_BOOL(expr) \
	do { \
		bool out_; \
		if (UNEXPECTED(!(expr))) { \
			RETURN_THROWS(); \
		} \
		RETURN_BOOL(out_); \
	} while (0)

namespace pt_cr {
/* the twin's parameter and return class names (persistent literals) */
inline constexpr const char *self = "PHPStan\\Reflection\\ClassReflection";
inline constexpr const char *classReflectionFactory = "PHPStan\\Reflection\\ClassReflectionFactory";
inline constexpr const char *initializerExprTypeResolver = "PHPStan\\Reflection\\InitializerExprTypeResolver";
inline constexpr const char *fileTypeMapper = "PHPStan\\Type\\FileTypeMapper";
inline constexpr const char *stubPhpDocProvider = "PHPStan\\PhpDoc\\StubPhpDocProvider";
inline constexpr const char *phpDocInheritanceResolver = "PHPStan\\PhpDoc\\PhpDocInheritanceResolver";
inline constexpr const char *signatureMapProvider = "PHPStan\\Reflection\\SignatureMap\\SignatureMapProvider";
inline constexpr const char *deprecationProvider = "PHPStan\\Reflection\\Deprecation\\DeprecationProvider";
inline constexpr const char *attributeReflectionFactory = "PHPStan\\Reflection\\AttributeReflectionFactory";
inline constexpr const char *classReflectionExtensionRegistryProvider = "PHPStan\\DependencyInjection\\Reflection\\ClassReflectionExtensionRegistryProvider";
inline constexpr const char *coreReflectionClass = "ReflectionClass";
inline constexpr const char *templateTypeMap = "PHPStan\\Type\\Generic\\TemplateTypeMap";
inline constexpr const char *templateTypeVarianceMap = "PHPStan\\Type\\Generic\\TemplateTypeVarianceMap";
inline constexpr const char *closure = "Closure";
inline constexpr const char *extendsTag = "PHPStan\\PhpDoc\\Tag\\ExtendsTag";
inline constexpr const char *resolvedPhpDocBlock = "PHPStan\\PhpDoc\\ResolvedPhpDocBlock";

} // namespace pt_cr

void pt_register_class_reflection()
{
	using namespace pt_cr;

	reg::Class cls("PHPStan\\Reflection\\ClassReflection");
	ptdecl::ClassReflection::declareClass(cls);

	/* {{{ the slots, in the twin's declaration order (the PT_CR_PROP_*
	 * enum): the class-body properties with their defaults, the static
	 * one in its place, then the promoted constructor properties,
	 * uninitialized until the constructor writes them */
	cls.property("methods", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("properties", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("instanceProperties", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("staticProperties", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("constants", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("enumCases", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.property("classHierarchyDistances", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.property("deprecatedDescription", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("isDeprecated", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("allowedSubTypes", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.property("allowedSubTypesResolved", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedBool, 0);
	cls.property("isGeneric", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("isInternal", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("isFinal", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("isImmutable", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("hasConsistentConstructor", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("acceptsNamedArguments", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_BOOL | MAY_BE_NULL);
	cls.property("templateTypeMap", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, templateTypeMap);
	cls.property("activeTemplateTypeMap", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, templateTypeMap);
	cls.property("defaultCallSiteVarianceMap", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, templateTypeVarianceMap);
	cls.property("callSiteVarianceMap", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_NULL, templateTypeVarianceMap);
	cls.property("ancestors", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.property("cacheKey", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("subclasses", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("filename", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_STRING | MAY_BE_FALSE | MAY_BE_NULL);
	cls.property("reflectionDocComment", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_STRING | MAY_BE_FALSE | MAY_BE_NULL);
	cls.property("stubPhpDocBlock", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_FALSE | MAY_BE_NULL, "PHPStan\\PhpDoc\\ResolvedPhpDocBlock");
	cls.property("resolvedPhpDocBlock", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_FALSE, "PHPStan\\PhpDoc\\ResolvedPhpDocBlock");
	cls.property("traitContextResolvedPhpDocBlock", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_FALSE, "PHPStan\\PhpDoc\\ResolvedPhpDocBlock");
	cls.property("cachedInterfaces", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.property("cachedParentClass", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_FALSE | MAY_BE_NULL, "self");
	cls.property("circularParentClassName", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedFalse, MAY_BE_STRING | MAY_BE_FALSE | MAY_BE_NULL);
	cls.property("typeAliases", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_ARRAY | MAY_BE_NULL);
	cls.privateStaticTypedArrayPropertyDefaultEmpty("resolvingTypeAliasImports");
	cls.property("hasMethodCache", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("hasPropertyCache", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("hasInstancePropertyCache", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("hasStaticPropertyCache", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedEmptyArray, MAY_BE_ARRAY);
	cls.property("name", ZEND_ACC_PRIVATE, reg::PropertyKind::TypedNull, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("classReflectionFactory", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, classReflectionFactory);
	cls.property("reflectionProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, ptcls::reflectionProvider);
	cls.property("initializerExprTypeResolver", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, initializerExprTypeResolver);
	cls.property("fileTypeMapper", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, fileTypeMapper);
	cls.property("stubPhpDocProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, stubPhpDocProvider);
	cls.property("phpDocInheritanceResolver", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, phpDocInheritanceResolver);
	cls.property("phpVersion", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, ptcls::phpVersion);
	cls.property("signatureMapProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, signatureMapProvider);
	cls.property("deprecationProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, deprecationProvider);
	cls.property("attributeReflectionFactory", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, attributeReflectionFactory);
	cls.property("classReflectionExtensionRegistryProvider", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, classReflectionExtensionRegistryProvider);
	cls.property("displayName", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_STRING);
	cls.property("reflection", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, 0, coreReflectionClass);
	cls.property("anonymousFilename", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("resolvedTemplateTypeMap", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, templateTypeMap);
	cls.property("stubPhpDocBlockCallback", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, closure);
	cls.property("extraCacheKey", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_STRING | MAY_BE_NULL);
	cls.property("resolvedCallSiteVarianceMap", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_NULL, templateTypeVarianceMap);
	cls.property("finalByKeywordOverride", ZEND_ACC_PRIVATE, reg::PropertyKind::Typed, MAY_BE_BOOL | MAY_BE_NULL);
	/* }}} */

	cls.method("__construct", reg::Public, 16, {
		reg::obj("classReflectionFactory", classReflectionFactory),
		reg::obj("reflectionProvider", ptcls::reflectionProvider),
		reg::obj("initializerExprTypeResolver", initializerExprTypeResolver),
		reg::obj("fileTypeMapper", fileTypeMapper),
		reg::obj("stubPhpDocProvider", stubPhpDocProvider),
		reg::obj("phpDocInheritanceResolver", phpDocInheritanceResolver),
		reg::obj("phpVersion", ptcls::phpVersion),
		reg::obj("signatureMapProvider", signatureMapProvider),
		reg::obj("deprecationProvider", deprecationProvider),
		reg::obj("attributeReflectionFactory", attributeReflectionFactory),
		reg::obj("classReflectionExtensionRegistryProvider", classReflectionExtensionRegistryProvider),
		reg::stringArg("displayName"),
		reg::obj("reflection", coreReflectionClass),
		reg::stringArg("anonymousFilename", true),
		reg::obj("resolvedTemplateTypeMap", templateTypeMap, true),
		reg::obj("stubPhpDocBlockCallback", closure, true),
		reg::withDefault(reg::stringArg("extraCacheKey", true), "null"),
		reg::withDefault(reg::obj("resolvedCallSiteVarianceMap", templateTypeVarianceMap, true), "null"),
		reg::withDefault({ "finalByKeywordOverride", MAY_BE_BOOL | MAY_BE_NULL | reg::detail::flagBits(false, false), nullptr }, "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ClassReflection::ConstructArgs a = {};
		a.finalByKeywordOverrideIsNull = true;
		ZEND_PARSE_PARAMETERS_START(16, 19)
			Z_PARAM_OBJECT(a.classReflectionFactory)
			Z_PARAM_OBJECT(a.reflectionProvider)
			Z_PARAM_OBJECT(a.initializerExprTypeResolver)
			Z_PARAM_OBJECT(a.fileTypeMapper)
			Z_PARAM_OBJECT(a.stubPhpDocProvider)
			Z_PARAM_OBJECT(a.phpDocInheritanceResolver)
			Z_PARAM_OBJECT(a.phpVersion)
			Z_PARAM_OBJECT(a.signatureMapProvider)
			Z_PARAM_OBJECT(a.deprecationProvider)
			Z_PARAM_OBJECT(a.attributeReflectionFactory)
			Z_PARAM_OBJECT(a.classReflectionExtensionRegistryProvider)
			Z_PARAM_STR(a.displayName)
			Z_PARAM_OBJECT(a.reflection)
			Z_PARAM_STR_OR_NULL(a.anonymousFilename)
			Z_PARAM_OBJECT_OR_NULL(a.resolvedTemplateTypeMap)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(a.stubPhpDocBlockCallback, zend_ce_closure)
			Z_PARAM_OPTIONAL
			Z_PARAM_STR_OR_NULL(a.extraCacheKey)
			Z_PARAM_OBJECT_OR_NULL(a.resolvedCallSiteVarianceMap)
			Z_PARAM_BOOL_OR_NULL(a.finalByKeywordOverride, a.finalByKeywordOverrideIsNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_THIS.construct(a);
	});

	cls.method<&ClassReflection::getNativeReflection>(sigs::getNativeReflection);

	cls.method<&ClassReflection::getFileName>(sigs::getFileName);

	cls.method<&ClassReflection::getParentClass>(sigs::getParentClass);

	cls.method<&ClassReflection::getName>(sigs::getName);

	cls.method(sigs::getDisplayName, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool withTemplateTypes = true;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, withTemplateTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.getDisplayName(withTemplateTypes));
	});

	cls.method<&ClassReflection::getCacheKey>(sigs::getCacheKey);

	cls.method<&ClassReflection::getClassHierarchyDistances>(sigs::getClassHierarchyDistances);

	cls.method(sigs::findCircularParentClassName, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *parentClassName;
		if (!zp::parse<zp::Str>(execute_data, parentClassName)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.findCircularParentClassName(parentClassName));
	});

	cls.method(sigs::collectAncestors, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *ancestorsArg;
		if (!zp::parse<zp::Zval>(execute_data, ancestorsArg)) RETURN_THROWS();
		zval *ancestorsZv = ancestorsArg;
		ZVAL_DEREF(ancestorsZv);
		if (UNEXPECTED(Z_TYPE_P(ancestorsZv) != IS_ARRAY)) {
			zend_argument_type_error(1, "must be of type array, %s given", zend_zval_value_name(ancestorsZv));
			RETURN_THROWS();
		}
		zv::Arr ancestors = zv::Arr::copyOfTable(Z_ARRVAL_P(ancestorsZv));
		bool collected = PT_THIS.collectAncestors(ancestors);
		zval written = ancestors.take();
		zval_ptr_dtor(ancestorsZv);
		ZVAL_COPY_VALUE(ancestorsZv, &written);
		if (UNEXPECTED(!collected)) RETURN_THROWS();
	});

	cls.method(sigs::collectTraits, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		if (!zp::parse<zp::Obj>(execute_data, classReflection)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.collectTraits(zv::Ref(classReflection)));
	});

	cls.method<&ClassReflection::allowsDynamicProperties>(sigs::allowsDynamicProperties);

	cls.method<&ClassReflection::hasProperty, zp::Str>(sigs::hasProperty);

	cls.method<&ClassReflection::hasInstanceProperty, zp::Str>(sigs::hasInstanceProperty);

	cls.method<&ClassReflection::hasStaticProperty, zp::Str>(sigs::hasStaticProperty);

	cls.method<&ClassReflection::hasMethod, zp::Str>(sigs::hasMethod);

	cls.method<&ClassReflection::getMethod, zp::Str, zp::Obj>(sigs::getMethod);

	cls.method(sigs::wrapExtendedMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *method;
		if (!zp::parse<zp::Obj>(execute_data, method)) RETURN_THROWS();
		PT_RETURN_VAL(ClassReflection::wrapExtendedMethod(zv::Val::copyOf(zv::Ref(method))));
	});

	cls.method(sigs::wrapExtendedProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *propertyName;
		zval *property;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, propertyName, property)) RETURN_THROWS();
		PT_RETURN_VAL(ClassReflection::wrapExtendedProperty(propertyName, zv::Val::copyOf(zv::Ref(property))));
	});

	cls.method<&ClassReflection::hasNativeMethod, zp::Str>(sigs::hasNativeMethod);

	cls.method<&ClassReflection::getNativeMethod, zp::Str>(sigs::getNativeMethod);

	cls.method<&ClassReflection::hasConstructor>(sigs::hasConstructor);

	cls.method<&ClassReflection::getConstructor>(sigs::getConstructor);

	cls.method<&ClassReflection::findConstructor>(sigs::findConstructor);

	cls.method(sigs::evictPrivateSymbols, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(!PT_THIS.evictPrivateSymbols())) RETURN_THROWS();
	});

	cls.method<&ClassReflection::getProperty, zp::Str, zp::Obj>(sigs::getProperty);

	cls.method<&ClassReflection::getInstanceProperty, zp::Str, zp::Obj>(sigs::getInstanceProperty);

	cls.method<&ClassReflection::getStaticProperty, zp::Str>(sigs::getStaticProperty);

	cls.method<&ClassReflection::hasNativeProperty, zp::Str>(sigs::hasNativeProperty);

	cls.method<&ClassReflection::getNativeProperty, zp::Str>(sigs::getNativeProperty);

	cls.method<&ClassReflection::isAbstract>(sigs::isAbstract);

	cls.method<&ClassReflection::isInterface>(sigs::isInterface);

	cls.method<&ClassReflection::isTrait>(sigs::isTrait);

	cls.method<&ClassReflection::isEnum>(sigs::isEnum);

	cls.method<&ClassReflection::getClassTypeDescription>(sigs::getClassTypeDescription);

	cls.method<&ClassReflection::isReadOnly>(sigs::isReadOnly);

	cls.method<&ClassReflection::isBackedEnum>(sigs::isBackedEnum);

	cls.method<&ClassReflection::getBackedEnumType>(sigs::getBackedEnumType);

	cls.method<&ClassReflection::hasEnumCase, zp::Str>(sigs::hasEnumCase);

	cls.method<&ClassReflection::getEnumCases>(sigs::getEnumCases);

	cls.method<&ClassReflection::getEnumCase, zp::Str>(sigs::getEnumCase);

	cls.method<&ClassReflection::isClass>(sigs::isClass);

	cls.method<&ClassReflection::isAnonymous>(sigs::isAnonymous);

	cls.method<&ClassReflection::is, zp::Str>(sigs::is);

	cls.method<&ClassReflection::isSubclassOf, zp::Str>(sigs::isSubclassOf);

	cls.method(sigs::isSubclassOfClass, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection;
		if (!zp::parse<zp::Obj>(execute_data, classReflection)) RETURN_THROWS();
		PT_CR_RETURN_BOOL(PT_THIS.isSubclassOfClass(zv::Ref(classReflection), out_));
	});

	cls.method<&ClassReflection::implementsInterface, zp::Str>(sigs::implementsInterface);

	cls.method<&ClassReflection::getParents>(sigs::getParents);

	cls.method<&ClassReflection::getInterfaces>(sigs::getInterfaces);

	cls.method(sigs::collectInterfaces, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *interface;
		if (!zp::parse<zp::Obj>(execute_data, interface)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.collectInterfaces(zv::Ref(interface)));
	});

	cls.method<&ClassReflection::getImmediateInterfaces>(sigs::getImmediateInterfaces);

	cls.method(sigs::getTraits, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool recursive = false;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, recursive)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.getTraits(recursive));
	});

	cls.method<&ClassReflection::getParentClassesNames>(sigs::getParentClassesNames);

	cls.method<&ClassReflection::hasConstant, zp::Str>(sigs::hasConstant);

	cls.method<&ClassReflection::getConstant, zp::Str>(sigs::getConstant);

	cls.method<&ClassReflection::getConstantPhpDocType, zp::Str>(sigs::getConstantPhpDocType);

	cls.method(sigs::findConstantResolvedPhpDoc, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionConstant;
		if (!zp::parse<zp::Obj>(execute_data, reflectionConstant)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.findConstantResolvedPhpDoc(zv::Ref(reflectionConstant)));
	});

	cls.method(sigs::resolveConstantVarPhpDocType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *resolvedPhpDoc, *nativeType, *declaringClass;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::Obj>(execute_data, resolvedPhpDoc, nativeType, declaringClass)) RETURN_THROWS();
		zval nullType;
		ZVAL_NULL(&nullType);
		PT_RETURN_VAL(ClassReflection::resolveConstantVarPhpDocType(zv::Ref(resolvedPhpDoc), zv::Ref(nativeType == NULL ? &nullType : nativeType), zv::Ref(declaringClass)));
	});

	cls.method<&ClassReflection::hasTraitUse, zp::Str>(sigs::hasTraitUse);

	cls.method<&ClassReflection::getTraitNames>(sigs::getTraitNames);

	cls.method<&ClassReflection::getTypeAliases>(sigs::getTypeAliases);

	cls.method<&ClassReflection::getDeprecatedDescription>(sigs::getDeprecatedDescription);

	cls.method<&ClassReflection::isDeprecated>(sigs::isDeprecated);

	cls.method(sigs::resolveDeprecation, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(!PT_THIS.resolveDeprecation())) RETURN_THROWS();
	});

	cls.method<&ClassReflection::isBuiltin>(sigs::isBuiltin);

	cls.method<&ClassReflection::isInternal>(sigs::isInternal);

	cls.method<&ClassReflection::isImmutable>(sigs::isImmutable);

	cls.method<&ClassReflection::hasConsistentConstructor>(sigs::hasConsistentConstructor);

	cls.method<&ClassReflection::acceptsNamedArguments>(sigs::acceptsNamedArguments);

	cls.method<&ClassReflection::isAttributeClass>(sigs::isAttributeClass);

	cls.method<&ClassReflection::findAttributeFlags>(sigs::findAttributeFlags);

	cls.method<&ClassReflection::getAttributes>(sigs::getAttributes);

	cls.method<&ClassReflection::getAttributeClassFlags>(sigs::getAttributeClassFlags);

	cls.method<&ClassReflection::getObjectType>(sigs::getObjectType);

	cls.method<&ClassReflection::getTemplateTypeMap>(sigs::getTemplateTypeMap);

	cls.method<&ClassReflection::getActiveTemplateTypeMap>(sigs::getActiveTemplateTypeMap);

	cls.method<&ClassReflection::getPossiblyIncompleteActiveTemplateTypeMap>(sigs::getPossiblyIncompleteActiveTemplateTypeMap);

	cls.method<&ClassReflection::getDefaultCallSiteVarianceMap>(sigs::getDefaultCallSiteVarianceMap);

	cls.method<&ClassReflection::getCallSiteVarianceMap>(sigs::getCallSiteVarianceMap);

	cls.method(sigs::typeMapFromList, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.typeMapFromList(zv::Ref(types)));
	});

	cls.method(sigs::varianceMapFromList, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *variances;
		if (!zp::parse<zp::Arr>(execute_data, variances)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.varianceMapFromList(zv::Ref(variances)));
	});

	cls.method(sigs::typeMapToList, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *typeMap;
		if (!zp::parse<zp::Obj>(execute_data, typeMap)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.typeMapToList(zv::Ref(typeMap)));
	});

	cls.method(sigs::varianceMapToList, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *varianceMap;
		if (!zp::parse<zp::Obj>(execute_data, varianceMap)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.varianceMapToList(zv::Ref(varianceMap)));
	});

	cls.method(sigs::withTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		if (!zp::parse<zp::Arr>(execute_data, types)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.withTypes(zv::Ref(types)));
	});

	cls.method(sigs::withVariances, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *variances;
		if (!zp::parse<zp::Arr>(execute_data, variances)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.withVariances(zv::Ref(variances)));
	});

	cls.method<&ClassReflection::asFinal>(sigs::asFinal);

	cls.method<&ClassReflection::withoutFinalByKeywordOverride>(sigs::withoutFinalByKeywordOverride);

	cls.method<&ClassReflection::removeFinalKeywordOverride>(sigs::removeFinalKeywordOverride);

	cls.method<&ClassReflection::getResolvedPhpDoc>(sigs::getResolvedPhpDoc);

	cls.method(sigs::getTraitContextResolvedPhpDoc, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *implementingClass;
		if (!zp::parse<zp::Obj>(execute_data, implementingClass)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.getTraitContextResolvedPhpDoc(zv::Ref(implementingClass)));
	});

	cls.method<&ClassReflection::getExtendsTags>(sigs::getExtendsTags);

	cls.method<&ClassReflection::getImplementsTags>(sigs::getImplementsTags);

	cls.method<&ClassReflection::getTemplateTags>(sigs::getTemplateTags);

	cls.method<&ClassReflection::getAncestors>(sigs::getAncestors);

	cls.method<&ClassReflection::getAncestorWithClassName, zp::Str>(sigs::getAncestorWithClassName);

	cls.method<&ClassReflection::getMixinTags>(sigs::getMixinTags);

	cls.method<&ClassReflection::getRequireExtendsTags>(sigs::getRequireExtendsTags);

	cls.method<&ClassReflection::getRequireImplementsTags>(sigs::getRequireImplementsTags);

	cls.method<&ClassReflection::getSealedTags>(sigs::getSealedTags);

	cls.method<&ClassReflection::getPropertyTags>(sigs::getPropertyTags);

	cls.method<&ClassReflection::getMethodTags>(sigs::getMethodTags);

	cls.method<&ClassReflection::getResolvedMixinTypes>(sigs::getResolvedMixinTypes);

	cls.method<&ClassReflection::getAllowedSubTypes>(sigs::getAllowedSubTypes);

	/* out of the twin's file order (see the handle class) */
	cls.method<&ClassReflection::isFinal>(sigs::isFinal);

	cls.method<&ClassReflection::hasFinalByKeywordOverride>(sigs::hasFinalByKeywordOverride);

	cls.method<&ClassReflection::isFinalByKeyword>(sigs::isFinalByKeyword);

	cls.method<&ClassReflection::isGeneric>(sigs::isGeneric);

	cls.method<&ClassReflection::getFirstExtendsTag>(sigs::getFirstExtendsTag);

	cls.method(sigs::isValidAncestorType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *ancestorClasses;
		if (!zp::parse<zp::Obj, zp::Arr>(execute_data, type, ancestorClasses)) RETURN_THROWS();
		/* in_array($reflection->getName(), $ancestorClasses, true) over the
		 * whole list: the C++ body takes the one name every twin site
		 * passes, so the private method walks the list here */
		if (!ClassReflection::instanceOfShadowed(zv::Ref(type), pt_ce_generic_object_type, PT_LC(PT_CR_GENERIC_OBJECT_TYPE_NAME))) {
			RETURN_FALSE;
		}
		zv::Val reflection = ClassReflection::typeGetClassReflection(zv::Ref(type));
		if (UNEXPECTED(reflection.isUndef())) RETURN_THROWS();
		if (reflection.isNull()) {
			RETURN_FALSE;
		}
		zv::Val name = ClassReflection::crGetName(reflection.ref());
		if (UNEXPECTED(name.isUndef())) RETURN_THROWS();
		for (auto entry : zv::ArrRef(ancestorClasses)) {
			if (Z_TYPE_P(name.raw()) == IS_STRING && entry.value().isString() && zend_string_equals(Z_STR_P(name.raw()), entry.value().asString())) {
				RETURN_TRUE;
			}
		}
		RETURN_FALSE;
	});

	cls.method<&ClassReflection::getActiveTemplateTypeMapForAncestorResolution>(sigs::getActiveTemplateTypeMapForAncestorResolution);

	cls.shadow(&pt_ce_class_reflection);
}

/* }}} */
