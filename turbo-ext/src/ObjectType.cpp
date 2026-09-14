/*
 * PHPStanTurbo\ObjectType — native implementation of PHPStan\Type\ObjectType.
 *
 * Declared as PHPStan\Type\ObjectType itself at activation: not final (the
 * native GenericObjectType and EnumCaseObjectType extend it, and the PHP
 * TemplateObjectType does — their constructors call parent::__construct(),
 * so the constructor is a proper method), implementing
 * PHPStan\Type\TypeWithClassName and PHPStan\Type\SubtractableType. State
 * is the twin's private properties, declared typed property slots in the
 * twin's declaration order (the class-body properties first, the promoted
 * constructor properties after them), so the std object handlers do
 * GC/clone and a subclass's own properties follow them. The twin's static
 * caches (the per-description member caches, the ancestor cache, the enum
 * cases, the LRU governing them) are file statics reset per request;
 * resetCaches() clears them as the twin's does.
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a subclass may have overridden it (GenericObjectType's
 * getClassReflection(), EnumCaseObjectType's describe(), the template
 * types' isSuperTypeOf()) — with a direct C++ call when the object's method
 * is the native one. Private methods (describeCache(),
 * checkSubclassAcceptability(), matchAllowedSubTypes(), ...) are direct C++
 * calls, as PHP never dispatches them. The private slots of another
 * ObjectType (`$type->className`, `$type->subtractedType`) are read
 * directly, as the twin does from inside the class.
 *
 * The closures the twin hands to RecursionGuard::run() and the
 * `static fn (Type $type): Type => $type` the prototype reflections take
 * are Closures over the internal PHPStanTurbo\ObjectTypeCallback holder
 * (an internal detail with no PHP twin, like the generalize() holder in
 * TypeTraits.cpp): __invoke() replays the captured call, identity()
 * returns its argument.
 */

#include "TypeTraits.h"
#include "generated/ObjectType.h"

namespace slots = ptdecl::ObjectType::slot;
namespace sigs = ptdecl::ObjectType::sig;

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_closures.h" /* zend_create_closure */
#pragma GCC diagnostic pop

zend_class_entry *pt_ce_object_type = nullptr;

/* private const DESCRIPTION_CACHE_LIMIT */
#define PT_OT_DESCRIPTION_CACHE_LIMIT 1024

/* new IsSuperTypeOfResult($trinary, $reasons) — AcceptsResult.cpp */
bool pt_result_object_create(zval *out, zend_class_entry *ce, zval *trinary, zval *reasons, zval *lazyReasons);

/* {{{ the twin's static caches (private static array $superTypes & co.):
 * declared on the class as the twin declares them, so they reflect the
 * same, and reached through the class's static member table — resolved
 * once per activation (a new class entry each request); the description
 * keys go through zend_symtable_* as the twin's array keys do */

enum pt_ot_static_kind
{
	PT_OT_STATIC_SUPER_TYPES,
	PT_OT_STATIC_METHODS,
	PT_OT_STATIC_PROPERTIES,
	PT_OT_STATIC_INSTANCE_PROPERTIES,
	PT_OT_STATIC_STATIC_PROPERTIES,
	PT_OT_STATIC_ANCESTORS,
	PT_OT_STATIC_ENUM_CASES,
	PT_OT_STATIC_DESCRIPTION_CACHE_ORDER,
	PT_OT_STATIC_LAST_TOUCHED_DESCRIPTION,
	PT_OT_STATIC_COUNT,
};

static const char *const pt_ot_static_names[PT_OT_STATIC_COUNT] = {
	"superTypes",
	"methods",
	"properties",
	"instanceProperties",
	"staticProperties",
	"ancestors",
	"enumCases",
	"descriptionCacheOrder",
	"lastTouchedDescription",
};

static zend_class_entry *pt_ot_statics_ce = nullptr;
static zval *pt_ot_statics[PT_OT_STATIC_COUNT];

/* the slot of one of the twin's static properties (borrowed; the class is
 * activated whenever one of its methods runs) */
static zval *pt_ot_static(pt_ot_static_kind kind)
{
	zend_class_entry *ce = pt_ce_object_type;
	if (UNEXPECTED(pt_ot_statics_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		for (int i = 0; i < PT_OT_STATIC_COUNT; i++) {
			zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, pt_ot_static_names[i], strlen(pt_ot_static_names[i]));
			ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
			pt_ot_statics[i] = CE_STATIC_MEMBERS(ce) + info->offset;
		}
		pt_ot_statics_ce = ce;
	}
	return pt_ot_statics[kind];
}

static zval *pt_ot_super_types() { return pt_ot_static(PT_OT_STATIC_SUPER_TYPES); }
static zval *pt_ot_methods() { return pt_ot_static(PT_OT_STATIC_METHODS); }
static zval *pt_ot_properties() { return pt_ot_static(PT_OT_STATIC_PROPERTIES); }
static zval *pt_ot_instance_properties() { return pt_ot_static(PT_OT_STATIC_INSTANCE_PROPERTIES); }
static zval *pt_ot_static_properties() { return pt_ot_static(PT_OT_STATIC_STATIC_PROPERTIES); }
static zval *pt_ot_ancestors() { return pt_ot_static(PT_OT_STATIC_ANCESTORS); }
static zval *pt_ot_enum_cases() { return pt_ot_static(PT_OT_STATIC_ENUM_CASES); }
static zval *pt_ot_description_cache_order() { return pt_ot_static(PT_OT_STATIC_DESCRIPTION_CACHE_ORDER); }
static zval *pt_ot_last_touched_description() { return pt_ot_static(PT_OT_STATIC_LAST_TOUCHED_DESCRIPTION); }

/* resetCaches(): every cache back to [], the LRU and its memo to null */
static void pt_ot_statics_reset()
{
	for (int i = PT_OT_STATIC_SUPER_TYPES; i <= PT_OT_STATIC_ENUM_CASES; i++) {
		zv::Ref(pt_ot_static((pt_ot_static_kind) i)).assign(zv::Val(zv::Arr::empty()));
	}
	zv::Ref(pt_ot_description_cache_order()).assign(zv::Val::null());
	zv::Ref(pt_ot_last_touched_description()).assign(zv::Val::null());
}

void pt_object_type_rinit()
{
	pt_ot_statics_ce = nullptr;
}

void pt_object_type_rshutdown()
{
	pt_ot_statics_ce = nullptr;
}

/* $cache[$key] — the nested array, created when asked for; NULL when absent */
static zval *pt_ot_cache_level(zval *cache, zend_string *key, bool create)
{
	if (create) {
		SEPARATE_ARRAY(cache);
	}
	zval *level = zend_symtable_find(Z_ARRVAL_P(cache), key);
	if (level == NULL) {
		if (!create) return NULL;
		zval created;
		array_init(&created);
		level = zend_symtable_update(Z_ARRVAL_P(cache), key, &created);
	} else if (create) {
		SEPARATE_ARRAY(level);
	}
	return level;
}

/* $cache[$k1][$k2] (borrowed; NULL when absent) */
static zval *pt_ot_cache_get2(zval *cache, zend_string *k1, zend_string *k2)
{
	zval *level = pt_ot_cache_level(cache, k1, false);
	if (level == NULL) return NULL;
	return zend_symtable_find(Z_ARRVAL_P(level), k2);
}

/* $cache[$k1][$k2][$k3] (borrowed; NULL when absent) */
static zval *pt_ot_cache_get3(zval *cache, zend_string *k1, zend_string *k2, zend_string *k3)
{
	zval *level = pt_ot_cache_level(cache, k1, false);
	if (level == NULL) return NULL;
	level = zend_symtable_find(Z_ARRVAL_P(level), k2);
	if (level == NULL || Z_TYPE_P(level) != IS_ARRAY) return NULL;
	return zend_symtable_find(Z_ARRVAL_P(level), k3);
}

/* $cache[$k1][$k2] = $value (borrowed, addref'd) */
static void pt_ot_cache_put2(zval *cache, zend_string *k1, zend_string *k2, zval *value)
{
	zval *level = pt_ot_cache_level(cache, k1, true);
	Z_TRY_ADDREF_P(value);
	zend_symtable_update(Z_ARRVAL_P(level), k2, value);
}

/* $cache[$k1][$k2][$k3] = $value (borrowed, addref'd) */
static void pt_ot_cache_put3(zval *cache, zend_string *k1, zend_string *k2, zend_string *k3, zval *value)
{
	zval *level = pt_ot_cache_level(cache, k1, true);
	level = pt_ot_cache_level(level, k2, true);
	Z_TRY_ADDREF_P(value);
	zend_symtable_update(Z_ARRVAL_P(level), k3, value);
}

/* unset($cache[$key]) */
static void pt_ot_cache_unset(zval *cache, zend_string *key)
{
	if (zend_symtable_find(Z_ARRVAL_P(cache), key) == NULL) return;
	SEPARATE_ARRAY(cache);
	zend_symtable_del(Z_ARRVAL_P(cache), key);
}

/* }}} */

/* {{{ the callback holder: PHPStanTurbo\ObjectTypeCallback — the closures
 * RecursionGuard::run() / runOnObjectIdentity() / TemplateTypeMap::map()
 * receive, replaying the captured call from __invoke(); identity() is the
 * `static fn (Type $type): Type => $type` of the prototype reflections */

static zend_class_entry *pt_ce_object_type_callback = nullptr;
static zend_function *pt_object_type_callback_invoke = nullptr;
static zend_function *pt_object_type_callback_identity = nullptr;

enum pt_ot_callback_kind
{
	/* $classReflection->hasProperty($propertyName) & co. (object = the reflection) */
	PT_OTC_HAS_PROPERTY,
	PT_OTC_HAS_INSTANCE_PROPERTY,
	PT_OTC_HAS_STATIC_PROPERTY,
	/* $nakedClassReflection->getProperty($propertyName, $scope) & co. */
	PT_OTC_GET_PROPERTY,
	PT_OTC_GET_INSTANCE_PROPERTY,
	PT_OTC_GET_STATIC_PROPERTY,
	/* $this->getMethod($arg, new OutOfClassScope())->getOnlyVariant()->getReturnType() (object = the type) */
	PT_OTC_METHOD_RETURN_TYPE,
	/* ... ->getIterableKeyType() / ->getIterableValueType() on top */
	PT_OTC_METHOD_RETURN_ITERABLE_KEY_TYPE,
	PT_OTC_METHOD_RETURN_ITERABLE_VALUE_TYPE,
	/* the offsetSet() parameter types: the first one returned, the second
	 * one into ->out when asked for */
	PT_OTC_OFFSET_SET_PARAMETER_TYPE,
	PT_OTC_OFFSET_SET_PARAMETER_TYPES,
	/* $this->findCallableParametersAcceptors() */
	PT_OTC_FIND_CALLABLE_PARAMETERS_ACCEPTORS,
	/* $type->getReferencedClasses() */
	PT_OTC_REFERENCED_CLASSES,
	/* static fn (): Type => new ErrorType() */
	PT_OTC_ERROR_TYPE,
};

static void ZEND_FASTCALL objectTypeCallbackInvoke(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL objectTypeCallbackIdentity(INTERNAL_FUNCTION_PARAMETERS);

/* the Closure over __invoke() of a holder capturing the call; *holder
 * receives the holder object (owned) when the caller reads ->out back;
 * UNDEF = pending exception */
static zv::Val pt_ot_callback(pt_ot_callback_kind kind, zval *object, zval *arg, zval *scope, zval *holderOut = NULL)
{
	zval holder;
	object_init_ex(&holder, pt_ce_object_type_callback);
	zv::ObjRef ref(&holder);
	ref.propAtWrite(slots::subtractedType, zv::Val::integer(kind));
	ref.propAtWrite(slots::cachedParent, object != NULL ? zv::Val::copyOf(zv::Ref(object)) : zv::Val::null());
	ref.propAtWrite(slots::cachedInterfaces, arg != NULL ? zv::Val::copyOf(zv::Ref(arg)) : zv::Val::null());
	ref.propAtWrite(slots::currentAncestors, scope != NULL ? zv::Val::copyOf(zv::Ref(scope)) : zv::Val::null());
	zval closure;
#if PHP_VERSION_ID >= 80600
	/* php-src fbb2e1f23d6: $this is passed as zend_object* from 8.6 on */
	zend_create_closure(&closure, pt_object_type_callback_invoke, pt_ce_object_type_callback, pt_ce_object_type_callback, Z_OBJ(holder));
#else
	zend_create_closure(&closure, pt_object_type_callback_invoke, pt_ce_object_type_callback, pt_ce_object_type_callback, &holder);
#endif
	if (holderOut != NULL) {
		ZVAL_COPY_VALUE(holderOut, &holder); /* the closure holds its own reference */
	} else {
		zval_ptr_dtor(&holder);
	}
	return zv::Val::adopt(closure);
}

/* static fn (Type $type): Type => $type */
static zv::Val pt_ot_identity_callback()
{
	zval closure;
	zend_create_closure(&closure, pt_object_type_callback_identity, pt_ce_object_type_callback, pt_ce_object_type_callback, NULL);
	return zv::Val::adopt(closure);
}

/* }}} */

/* the handlers the $this-dispatch fast paths identify (a subclass may
 * override any of these) */
static void ZEND_FASTCALL otGetClassName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetClassReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetNakedClassReflection(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetAncestorWithClassName(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetEnumCases(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otDescribeAdditionalCacheKey(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetClassStringType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otToNumber(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otToString(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetMethod(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otHasMethod(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetTemplateType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otIsInstanceOf(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otIsOffsetAccessible(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otIsSuperTypeOf(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otSubtract(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otHasProperty(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otHasInstanceProperty(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otHasStaticProperty(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetUnresolvedPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetUnresolvedStaticPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS);
static void ZEND_FASTCALL otGetUnresolvedMethodPrototype(INTERNAL_FUNCTION_PARAMETERS);

namespace phpstanturbo {

/* the ObjectType::getUnresolved*PropertyPrototype() family: one body,
 * parametrized by the member kind */
enum ObjectTypePropertyKind
{
	PT_OT_PROPERTY,
	PT_OT_INSTANCE_PROPERTY,
	PT_OT_STATIC_PROPERTY,
};

/* Mirrors PHPStan\Type\ObjectType. State lives in the PHP object's
 * property slots. */
class ObjectType
{
public:
	explicit ObjectType(zend_object *self) : self(self) {}

	/* __construct(private string $className, ?Type $subtractedType = null,
	 * private ?ClassReflection $classReflection = null): a NeverType
	 * subtracted type is dropped; $subtractedType / $classReflection
	 * borrowed, NULL (or IS_NULL) for null */
	void construct(zend_string *className, zval *subtractedType, zval *classReflection)
	{
		if (subtractedType != NULL && (Z_TYPE_P(subtractedType) != IS_OBJECT || instanceof_function(Z_OBJCE_P(subtractedType), pt_ce_never_type))) {
			subtractedType = NULL;
		}
		if (classReflection != NULL && Z_TYPE_P(classReflection) != IS_OBJECT) {
			classReflection = NULL;
		}
		/* the slots are overwritten in place: a repeated parent::__construct()
		 * call from a subclass would otherwise leak the first values */
		writeSlot(slots::className, zv::Val::string(className));
		writeSlot(slots::subtractedType, subtractedType == NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(subtractedType)));
		writeSlot(slots::classReflection, classReflection == NULL ? zv::Val::null() : zv::Val::copyOf(zv::Ref(classReflection)));
	}

	/* new self($className, $subtractedType, $classReflection) — exactly the
	 * class, as the twin's `new self` / `new ObjectType` sites spell it;
	 * UNDEF = pending exception */
	static zv::Val create(zend_string *className, zval *subtractedType = NULL, zval *classReflection = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_object_type) != SUCCESS)) return zv::Val();
		ObjectType(Z_OBJ(object)).construct(className, subtractedType, classReflection);
		return zv::Val::adopt(object);
	}

	/* {{{ the slots */

	/* $this->className (borrowed); NULL with an Error pending when the
	 * constructor never ran (ReflectionClass::newInstanceWithoutConstructor())
	 * — the twin's typed-property read raises the same */
	[[nodiscard]] zend_string *className() const { return classNameOf(self); }

	static zend_string *classNameOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::className);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_STRING)) {
			zend_throw_error(NULL, "Typed property %s::$className must not be accessed before initialization", ZSTR_VAL(pt_ce_object_type->name));
			return NULL;
		}
		return Z_STR_P(slot);
	}

	/* $this->subtractedType (borrowed, IS_NULL or IS_OBJECT); NULL with an
	 * Error pending when uninitialized */
	zval *subtractedType() const { return subtractedTypeOf(self); }

	static zval *subtractedTypeOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::subtractedType);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$subtractedType must not be accessed before initialization", ZSTR_VAL(pt_ce_object_type->name));
			return NULL;
		}
		return slot;
	}

	/* $this->classReflection — the constructor's (borrowed, IS_NULL or
	 * IS_OBJECT); NULL with an Error pending when uninitialized (a promoted
	 * parameter's default never reaches the property without the
	 * constructor) */
	[[nodiscard]] zval *classReflection() const { return classReflectionOf(self); }

	static zval *classReflectionOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::classReflection);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$classReflection must not be accessed before initialization", ZSTR_VAL(pt_ce_object_type->name));
			return NULL;
		}
		return slot;
	}

	/* $this->lazyClassReflection (borrowed, IS_NULL or IS_OBJECT) */
	zval *lazyClassReflection() const { return OBJ_PROP_NUM(self, slots::lazyClassReflection); }

	/* }}} */

	/* {{{ resetCaches() / touchDescriptionCacheKey() */

	static void resetCaches() { pt_ot_statics_reset(); }

	/* the shared LRU over the description keys of the static caches; false
	 * = pending exception */
	[[nodiscard]] static bool touchDescriptionCacheKey(zend_string *description)
	{
		zval *lastTouched = pt_ot_last_touched_description();
		if (Z_TYPE_P(lastTouched) == IS_STRING && zend_string_equals(Z_STR_P(lastTouched), description)) return true;
		zv::Ref(lastTouched).assign(zv::Val::string(description));

		zval *cacheOrder = pt_ot_description_cache_order();
		if (Z_TYPE_P(cacheOrder) != IS_OBJECT) {
			zval limit;
			ZVAL_LONG(&limit, PT_OT_DESCRIPTION_CACHE_LIMIT);
			zv::Val order = pt_type_new(PT_CLASS_LRU_CACHE, 1, &limit);
			if (UNEXPECTED(order.isUndef())) return false;
			zv::Ref(cacheOrder).assign(std::move(order));
		}
		zend_object *order = Z_OBJ_P(cacheOrder);
		zval descriptionZv;
		ZVAL_STR(&descriptionZv, description);
		zv::Val present = pt_type_call(order, PT_LC("get"), 1, &descriptionZv);
		if (UNEXPECTED(present.isUndef())) return false;
		if (!present.isNull()) return true;

		zval args[3];
		ZVAL_STR(&args[0], description);
		ZVAL_TRUE(&args[1]);
		ZVAL_LONG(&args[2], 0);
		zv::Val evicted = pt_type_call(order, PT_LC("set"), 3, args);
		if (UNEXPECTED(evicted.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(evicted.raw()).isArray())) {
			zend_type_error("phpstan_turbo: LruCache::set() must return array");
			return false;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(evicted.raw())) {
			zv::Ref value = entry.value().deref();
			zend_string *evictKey = value.isString() ? zend_string_copy(value.asString()) : zval_get_string(value.raw());
			pt_ot_cache_unset(pt_ot_super_types(), evictKey);
			pt_ot_cache_unset(pt_ot_methods(), evictKey);
			pt_ot_cache_unset(pt_ot_properties(), evictKey);
			pt_ot_cache_unset(pt_ot_instance_properties(), evictKey);
			pt_ot_cache_unset(pt_ot_static_properties(), evictKey);
			pt_ot_cache_unset(pt_ot_ancestors(), evictKey);
			pt_ot_cache_unset(pt_ot_enum_cases(), evictKey);
			zend_string_release(evictKey);
		}
		return true;
	}

	/* }}} */

	/* {{{ the member lookups */

	/* hasProperty() / hasInstanceProperty() / hasStaticProperty(): -1 =
	 * pending exception */
	zend_long hasPropertyOfKind(ObjectTypePropertyKind kind, zval *propertyName) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return -1;
		if (classReflection.isNull()) return PT_TRI_MAYBE;
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		/* RecursionGuard::run($this, static fn (): bool => $classReflection->hasProperty($propertyName)) */
		pt_ot_callback_kind callbackKind = kind == PT_OT_PROPERTY ? PT_OTC_HAS_PROPERTY : (kind == PT_OT_INSTANCE_PROPERTY ? PT_OTC_HAS_INSTANCE_PROPERTY : PT_OTC_HAS_STATIC_PROPERTY);
		zv::Val classHasProperty = guarded(callbackKind, classReflection.raw(), propertyName, NULL);
		if (UNEXPECTED(classHasProperty.isUndef())) return -1;
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof(classHasProperty.raw(), PT_CLASS_ERROR_TYPE, isError))) return -1;
		if (zv::Ref(classHasProperty.raw()).isTrue() || isError) return PT_TRI_YES;

		if (kind != PT_OT_STATIC_PROPERTY) {
			bool allowsDynamic;
			if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("allowsdynamicproperties"), 0, NULL, allowsDynamic))) return -1;
			if (allowsDynamic) return PT_TRI_MAYBE;
		}

		bool isFinal;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isfinal"), 0, NULL, isFinal))) return -1;
		if (!isFinal) return PT_TRI_MAYBE;

		return PT_TRI_NO;
	}

	zend_long hasProperty(zval *propertyName) const { return hasPropertyOfKind(PT_OT_PROPERTY, propertyName); }
	zend_long hasInstanceProperty(zval *propertyName) const { return hasPropertyOfKind(PT_OT_INSTANCE_PROPERTY, propertyName); }
	zend_long hasStaticProperty(zval *propertyName) const { return hasPropertyOfKind(PT_OT_STATIC_PROPERTY, propertyName); }

	/* $this->getUnresolved*Prototype($name, $scope)->getTransformedProperty()
	 * / ->getTransformedMethod() — the prototype through the object's
	 * class; UNDEF = pending exception */
	zv::Val transformedMember(const char *prototypeLcname, size_t prototypeLen, zif_handler prototypeHandler, bool isMethod, zval *name, zval *scope) const
	{
		zv::Args args{name, scope};
		zv::Val prototype = thisCall(prototypeLcname, prototypeLen, prototypeHandler, 2, args, [&]() {
			if (isMethod) return getUnresolvedMethodPrototype(name, scope);
			return getUnresolvedPropertyPrototype(prototypeHandler == otGetUnresolvedPropertyPrototype ? PT_OT_PROPERTY : (prototypeHandler == otGetUnresolvedInstancePropertyPrototype ? PT_OT_INSTANCE_PROPERTY : PT_OT_STATIC_PROPERTY), name, scope);
		});
		if (UNEXPECTED(prototype.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(prototype.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s() must return an object", prototypeLcname);
			return zv::Val();
		}
		if (isMethod) return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedmethod"), 0, NULL);
		return pt_type_call(Z_OBJ_P(prototype.raw()), PT_LC("gettransformedproperty"), 0, NULL);
	}

	zv::Val getProperty(zval *propertyName, zval *scope) const { return transformedMember(PT_LC("getunresolvedpropertyprototype"), otGetUnresolvedPropertyPrototype, false, propertyName, scope); }
	zv::Val getInstanceProperty(zval *propertyName, zval *scope) const { return transformedMember(PT_LC("getunresolvedinstancepropertyprototype"), otGetUnresolvedInstancePropertyPrototype, false, propertyName, scope); }
	zv::Val getStaticProperty(zval *propertyName, zval *scope) const { return transformedMember(PT_LC("getunresolvedstaticpropertyprototype"), otGetUnresolvedStaticPropertyPrototype, false, propertyName, scope); }

	/* getUnresolvedPropertyPrototype() / getUnresolvedInstancePropertyPrototype()
	 * / getUnresolvedStaticPropertyPrototype(); UNDEF = pending exception */
	zv::Val getUnresolvedPropertyPrototype(ObjectTypePropertyKind kind, zval *propertyName, zval *scope) const
	{
		zval *cache = kind == PT_OT_PROPERTY ? pt_ot_properties() : (kind == PT_OT_INSTANCE_PROPERTY ? pt_ot_instance_properties() : pt_ot_static_properties());
		zv::Val canAccessProperty = memberAccessKey(scope);
		if (UNEXPECTED(canAccessProperty.isUndef())) return zv::Val();
		zv::Val description = describeCache(self);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		zend_string *descriptionStr = zv::Ref(description.raw()).asString();
		if (UNEXPECTED(!touchDescriptionCacheKey(descriptionStr))) return zv::Val();
		zend_string *propertyNameStr = Z_STR_P(propertyName);
		zend_string *canAccessStr = zv::Ref(canAccessProperty.raw()).asString();
		zval *cached = pt_ot_cache_get3(cache, descriptionStr, propertyNameStr, canAccessStr);
		if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val nakedClassReflection = thisGetNakedClassReflection();
		if (UNEXPECTED(nakedClassReflection.isUndef())) return zv::Val();
		if (nakedClassReflection.isNull()) return throwClassNotFound();

		if (kind != PT_OT_STATIC_PROPERTY) {
			bool isEnum;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nakedClassReflection.raw()), PT_LC("isenum"), 0, NULL, isEnum))) return zv::Val();
			if (isEnum) {
				bool nameOrValue = zend_string_equals_literal(propertyNameStr, "name");
				if (!nameOrValue && zend_string_equals_literal(propertyNameStr, "value")) {
					if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nakedClassReflection.raw()), PT_LC("isbackedenum"), 0, NULL, nameOrValue))) return zv::Val();
				}
				if (nameOrValue) {
					zv::Val enumCases = thisGetEnumCases();
					if (UNEXPECTED(enumCases.isUndef())) return zv::Val();
					if (UNEXPECTED(!zv::Ref(enumCases.raw()).isArray())) {
						zend_type_error("phpstan_turbo: getEnumCases() must return array");
						return zv::Val();
					}
					zv::Arr properties = zv::Arr::create(zv::ArrRef(enumCases.raw()).size());
					for (zv::ArrayEntry entry : zv::ArrRef(enumCases.raw())) {
						zv::Ref enumCase = entry.value().deref();
						if (UNEXPECTED(!enumCase.isObject())) {
							zend_type_error("phpstan_turbo: getEnumCases() must return a list of objects");
							return zv::Val();
						}
						zv::Args args{propertyName, scope};
						zv::Val prototype = pt_type_call(enumCase.asObject(), kind == PT_OT_PROPERTY ? "getunresolvedpropertyprototype" : "getunresolvedinstancepropertyprototype", kind == PT_OT_PROPERTY ? sizeof("getunresolvedpropertyprototype") - 1 : sizeof("getunresolvedinstancepropertyprototype") - 1, 2, args);
						if (UNEXPECTED(prototype.isUndef())) return zv::Val();
						properties.push(std::move(prototype));
					}
					uint32_t count = properties.arrRef().size();
					if (count > 0) {
						if (count == 1) return zv::Val::copyOf(properties.arrRef().findIndex(0));
						return pt_type_new(PT_CLASS_UNION_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 1, properties.raw());
					}
				}
			}
		}

		bool hasNativeProperty;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nakedClassReflection.raw()), PT_LC("hasnativeproperty"), 1, propertyName, hasNativeProperty))) return zv::Val();
		if (!hasNativeProperty) {
			nakedClassReflection = thisGetClassReflection();
			if (UNEXPECTED(nakedClassReflection.isUndef())) return zv::Val();
		}

		if (nakedClassReflection.isNull()) return throwClassNotFound();

		/* RecursionGuard::run($this, static fn () => $nakedClassReflection->getProperty($propertyName, $scope)) */
		pt_ot_callback_kind callbackKind = kind == PT_OT_PROPERTY ? PT_OTC_GET_PROPERTY : (kind == PT_OT_INSTANCE_PROPERTY ? PT_OTC_GET_INSTANCE_PROPERTY : PT_OTC_GET_STATIC_PROPERTY);
		zv::Val property = guarded(callbackKind, nakedClassReflection.raw(), propertyName, scope);
		if (UNEXPECTED(property.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof(property.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
		if (isError) {
			property = pt_type_new(PT_CLASS_DUMMY_PROPERTY_REFLECTION, 1, propertyName);
			if (UNEXPECTED(property.isUndef())) return zv::Val();
			zv::Val declaringClass = pt_type_call(Z_OBJ_P(property.raw()), PT_LC("getdeclaringclass"), 0, NULL);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			zv::Val callback = pt_ot_identity_callback();
			zv::Args args{property.raw(), declaringClass.raw(), false, callback.raw()};
			return pt_type_new(PT_CLASS_CALLBACK_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args);
		}
		if (UNEXPECTED(!zv::Ref(property.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getProperty() must return an object");
			return zv::Val();
		}

		zv::Val declaringClassName = declaringClassNameOf(property.raw());
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zv::Val ancestor = thisGetAncestorWithClassName(declaringClassName.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		zv::Val resolvedClassReflection;
		if (!ancestor.isNull()) {
			zend_object *ancestorObject = Z_OBJ_P(ancestor.raw());
			zend_long ancestorHas;
			if (kind == PT_OT_PROPERTY) {
				ancestorHas = callOnTrinary(ancestorObject, PT_LC("hasproperty"), otHasProperty, 1, propertyName, [&]() { return ObjectType(ancestorObject).hasProperty(propertyName); });
			} else if (kind == PT_OT_INSTANCE_PROPERTY) {
				ancestorHas = callOnTrinary(ancestorObject, PT_LC("hasinstanceproperty"), otHasInstanceProperty, 1, propertyName, [&]() { return ObjectType(ancestorObject).hasInstanceProperty(propertyName); });
			} else {
				ancestorHas = callOnTrinary(ancestorObject, PT_LC("hasstaticproperty"), otHasStaticProperty, 1, propertyName, [&]() { return ObjectType(ancestorObject).hasStaticProperty(propertyName); });
			}
			if (UNEXPECTED(ancestorHas < 0)) return zv::Val();
			if (ancestorHas == PT_TRI_YES) {
				resolvedClassReflection = callOn(ancestorObject, PT_LC("getclassreflection"), otGetClassReflection, 0, NULL, [&]() { return ObjectType(ancestorObject).getClassReflection(); });
				if (UNEXPECTED(resolvedClassReflection.isUndef())) return zv::Val();
				if (ancestorObject != self) {
					zv::Args args{propertyName, scope};
					zv::Val ancestorPrototype;
					if (kind == PT_OT_PROPERTY) {
						ancestorPrototype = callOn(ancestorObject, PT_LC("getunresolvedpropertyprototype"), otGetUnresolvedPropertyPrototype, 2, args, [&]() { return ObjectType(ancestorObject).getUnresolvedPropertyPrototype(PT_OT_PROPERTY, propertyName, scope); });
					} else if (kind == PT_OT_INSTANCE_PROPERTY) {
						ancestorPrototype = callOn(ancestorObject, PT_LC("getunresolvedinstancepropertyprototype"), otGetUnresolvedInstancePropertyPrototype, 2, args, [&]() { return ObjectType(ancestorObject).getUnresolvedPropertyPrototype(PT_OT_INSTANCE_PROPERTY, propertyName, scope); });
					} else {
						ancestorPrototype = callOn(ancestorObject, PT_LC("getunresolvedstaticpropertyprototype"), otGetUnresolvedStaticPropertyPrototype, 2, args, [&]() { return ObjectType(ancestorObject).getUnresolvedPropertyPrototype(PT_OT_STATIC_PROPERTY, propertyName, scope); });
					}
					if (UNEXPECTED(ancestorPrototype.isUndef())) return zv::Val();
					if (UNEXPECTED(!zv::Ref(ancestorPrototype.raw()).isObject())) {
						zend_type_error("phpstan_turbo: getUnresolvedPropertyPrototype() must return an object");
						return zv::Val();
					}
					property = pt_type_call(Z_OBJ_P(ancestorPrototype.raw()), PT_LC("getnakedproperty"), 0, NULL);
					if (UNEXPECTED(property.isUndef())) return zv::Val();
				}
			}
		}
		if (resolvedClassReflection.isNull()) {
			if (UNEXPECTED(!zv::Ref(property.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getNakedProperty() must return an object");
				return zv::Val();
			}
			resolvedClassReflection = pt_type_call(Z_OBJ_P(property.raw()), PT_LC("getdeclaringclass"), 0, NULL);
			if (UNEXPECTED(resolvedClassReflection.isUndef())) return zv::Val();
		}

		zv::Args args{property.raw(), resolvedClassReflection.raw(), true, self};
		zv::Val result = pt_type_new(PT_CLASS_CALLED_ON_TYPE_UNRESOLVED_PROPERTY_PROTOTYPE_REFLECTION, 4, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		pt_ot_cache_put3(cache, descriptionStr, propertyNameStr, canAccessStr, result.raw());
		return result;
	}

	/* $this->methodCache[$key] ??= $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	 * UNDEF = pending exception */
	zv::Val getMethod(zval *methodName, zval *scope) const
	{
		zv::Str key = zv::Str::copyOf(Z_STR_P(methodName));
		bool inClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(scope), PT_LC("isinclass"), 0, NULL, inClass))) return zv::Val();
		if (inClass) {
			zv::Val scopeClass = pt_type_call(Z_OBJ_P(scope), PT_LC("getclassreflection"), 0, NULL);
			if (UNEXPECTED(scopeClass.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(scopeClass.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClassReflection() must return an object");
				return zv::Val();
			}
			zv::Val cacheKey = pt_type_call(Z_OBJ_P(scopeClass.raw()), PT_LC("getcachekey"), 0, NULL);
			if (UNEXPECTED(cacheKey.isUndef())) return zv::Val();
			zv::Str cacheKeyStr = zv::Str::adopt(zval_get_string(cacheKey.raw()));
			/* sprintf('%s-%s', $key, $cacheKey) */
			smart_str joined = {NULL, 0};
			smart_str_append(&joined, key.get());
			smart_str_appendc(&joined, '-');
			smart_str_append(&joined, cacheKeyStr.get());
			smart_str_0(&joined);
			key = zv::Str::adopt(joined.s);
		}
		zval *cache = OBJ_PROP_NUM(self, slots::methodCache);
		if (EXPECTED(Z_TYPE_P(cache) == IS_ARRAY)) {
			zval *cached = zend_symtable_find(Z_ARRVAL_P(cache), key.get());
			if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));
		}
		zv::Val method = transformedMember(PT_LC("getunresolvedmethodprototype"), otGetUnresolvedMethodPrototype, true, methodName, scope);
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(cache) != IS_ARRAY)) {
			zend_throw_error(NULL, "Typed property %s::$methodCache must not be accessed before initialization", ZSTR_VAL(pt_ce_object_type->name));
			return zv::Val();
		}
		SEPARATE_ARRAY(cache);
		Z_TRY_ADDREF_P(method.raw());
		zend_symtable_update(Z_ARRVAL_P(cache), key.get(), method.raw());
		return method;
	}

	/* UNDEF = pending exception */
	zv::Val getUnresolvedMethodPrototype(zval *methodName, zval *scope) const
	{
		zv::Val canCallMethod = memberAccessKey(scope);
		if (UNEXPECTED(canCallMethod.isUndef())) return zv::Val();
		zv::Val description = describeCache(self);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		zend_string *descriptionStr = zv::Ref(description.raw()).asString();
		if (UNEXPECTED(!touchDescriptionCacheKey(descriptionStr))) return zv::Val();
		zend_string *methodNameStr = Z_STR_P(methodName);
		zend_string *canCallStr = zv::Ref(canCallMethod.raw()).asString();
		zval *cached = pt_ot_cache_get3(pt_ot_methods(), descriptionStr, methodNameStr, canCallStr);
		if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val nakedClassReflection = thisGetNakedClassReflection();
		if (UNEXPECTED(nakedClassReflection.isUndef())) return zv::Val();
		if (nakedClassReflection.isNull()) return throwClassNotFound();

		bool hasNativeMethod;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nakedClassReflection.raw()), PT_LC("hasnativemethod"), 1, methodName, hasNativeMethod))) return zv::Val();
		if (!hasNativeMethod) {
			nakedClassReflection = thisGetClassReflection();
			if (UNEXPECTED(nakedClassReflection.isUndef())) return zv::Val();
		}

		if (nakedClassReflection.isNull()) return throwClassNotFound();

		zv::Args args{methodName, scope};
		zv::Val method = pt_type_call(Z_OBJ_P(nakedClassReflection.raw()), PT_LC("getmethod"), 2, args);
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getMethod() must return an object");
			return zv::Val();
		}

		zv::Val declaringClassName = declaringClassNameOf(method.raw());
		if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
		zv::Val ancestor = thisGetAncestorWithClassName(declaringClassName.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		zv::Val resolvedClassReflection;
		if (!ancestor.isNull()) {
			zend_object *ancestorObject = Z_OBJ_P(ancestor.raw());
			resolvedClassReflection = callOn(ancestorObject, PT_LC("getclassreflection"), otGetClassReflection, 0, NULL, [&]() { return ObjectType(ancestorObject).getClassReflection(); });
			if (UNEXPECTED(resolvedClassReflection.isUndef())) return zv::Val();
			if (ancestorObject != self) {
				zv::Val ancestorPrototype = callOn(ancestorObject, PT_LC("getunresolvedmethodprototype"), otGetUnresolvedMethodPrototype, 2, args, [&]() { return ObjectType(ancestorObject).getUnresolvedMethodPrototype(methodName, scope); });
				if (UNEXPECTED(ancestorPrototype.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(ancestorPrototype.raw()).isObject())) {
					zend_type_error("phpstan_turbo: getUnresolvedMethodPrototype() must return an object");
					return zv::Val();
				}
				method = pt_type_call(Z_OBJ_P(ancestorPrototype.raw()), PT_LC("getnakedmethod"), 0, NULL);
				if (UNEXPECTED(method.isUndef())) return zv::Val();
			}
		}
		if (resolvedClassReflection.isNull()) {
			if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getNakedMethod() must return an object");
				return zv::Val();
			}
			resolvedClassReflection = pt_type_call(Z_OBJ_P(method.raw()), PT_LC("getdeclaringclass"), 0, NULL);
			if (UNEXPECTED(resolvedClassReflection.isUndef())) return zv::Val();
		}

		zv::Args ctorArgs{method.raw(), resolvedClassReflection.raw(), true, self};
		zv::Val result = pt_type_new(PT_CLASS_CALLED_ON_TYPE_UNRESOLVED_METHOD_PROTOTYPE_REFLECTION, 4, ctorArgs);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		pt_ot_cache_put3(pt_ot_methods(), descriptionStr, methodNameStr, canCallStr, result.raw());
		return result;
	}

	/* }}} */

	/* {{{ the class names, accepts() / isSuperTypeOf() / equals() */

	/* [$this->className] unless empty; UNDEF = pending exception */
	zv::Val getReferencedClasses() const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (ZSTR_LEN(name) == 0) return zv::Val(zv::Arr::empty());
		zv::Arr classes = zv::Arr::create(1);
		classes.push(zv::Val::string(name));
		return zv::Val(std::move(classes));
	}

	zv::Val getObjectClassNames() const { return getReferencedClasses(); }

	/* [$this->getClassReflection()] unless null; UNDEF = pending exception */
	zv::Val getObjectClassReflections() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return zv::Val(zv::Arr::empty());
		zv::Arr reflections = zv::Arr::create(1);
		reflections.push(std::move(classReflection));
		return zv::Val(std::move(reflections));
	}

	/* UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool is;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_static_type, is))) return zv::Val();
		if (is) {
			zv::Val thatClassName = pt_type_call(Z_OBJ_P(type), PT_LC("getclassname"), 0, NULL);
			if (UNEXPECTED(thatClassName.isUndef())) return zv::Val();
			return checkSubclassAcceptability(thatClassName.raw());
		}

		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, is))) return zv::Val();
		if (is) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_closure_type, is))) return zv::Val();
		if (is) {
			/* new AcceptsResult($this->isInstanceOf(Closure::class), []) */
			zend_long isClosure = thisIsInstanceOfLiteral(PT_LC("Closure"));
			if (UNEXPECTED(isClosure < 0)) return zv::Val();
			zval reasons;
			ZVAL_EMPTY_ARRAY(&reasons);
			zval result;
			if (UNEXPECTED(!pt_accepts_result_create(&result, pt_trinary_singleton(isClosure), &reasons))) return zv::Val();
			return zv::Val::adopt(result);
		}

		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_object_without_class_type, is))) return zv::Val();
		if (is) return pt_type_accepts_result(PT_TRI_MAYBE);

		zv::Val thatClassNames = pt_type_call(Z_OBJ_P(type), PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatClassNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassNames() must return array");
			return zv::Val();
		}
		uint32_t count = zv::ArrRef(thatClassNames.raw()).size();
		if (count > 1) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		if (count == 0) return pt_type_accepts_result(PT_TRI_NO);
		zv::Val first = firstClassName(thatClassNames.raw());
		if (UNEXPECTED(first.isUndef())) return zv::Val();
		return checkSubclassAcceptability(first.raw());
	}

	/* UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		zv::Val thatClassNames = pt_type_call(Z_OBJ_P(type), PT_LC("getobjectclassnames"), 0, NULL);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatClassNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassNames() must return array");
			return zv::Val();
		}
		uint32_t thatCount = zv::ArrRef(thatClassNames.raw()).size();
		bool isCompound, isObjectWithoutClass;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, isCompound) || !pt_type_instanceof_ce(type, pt_ce_object_without_class_type, isObjectWithoutClass))) {
			return zv::Val();
		}
		if (!isCompound && thatCount == 0 && !isObjectWithoutClass) return pt_type_is_super_type_of_result(PT_TRI_NO);

		zv::Val thisDescription = describeCache(self);
		if (UNEXPECTED(thisDescription.isUndef())) return zv::Val();
		zv::Val description;
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_object_type)) {
			description = describeCache(Z_OBJ_P(type));
		} else {
			zv::Val cacheLevel = pt_type_call_static(PT_CLASS_VERBOSITY_LEVEL, PT_LC("cache"), 0, NULL);
			if (UNEXPECTED(cacheLevel.isUndef())) return zv::Val();
			description = pt_type_call(Z_OBJ_P(type), PT_LC("describe"), 1, cacheLevel.raw());
		}
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		zend_string *thisDescriptionStr = zv::Ref(thisDescription.raw()).asString();
		zend_string *descriptionStr = zv::Ref(description.raw()).asString();

		if (UNEXPECTED(!touchDescriptionCacheKey(thisDescriptionStr))) return zv::Val();

		zval *cached = pt_ot_cache_get2(pt_ot_super_types(), thisDescriptionStr, descriptionStr);
		if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));

		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		if (isCompound) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv));

		bool isClosure;
		if (UNEXPECTED(!pt_type_instanceof_ce(type, pt_ce_closure_type, isClosure))) return zv::Val();
		if (isClosure) {
			/* new IsSuperTypeOfResult($this->isInstanceOf(Closure::class), []) */
			zend_long isInstance = thisIsInstanceOfLiteral(PT_LC("Closure"));
			if (UNEXPECTED(isInstance < 0)) return zv::Val();
			zval reasons, lazyReasons;
			ZVAL_EMPTY_ARRAY(&reasons);
			ZVAL_EMPTY_ARRAY(&lazyReasons); /* the constructor's default */
			zval result;
			if (UNEXPECTED(!pt_result_object_create(&result, pt_ce_is_super_type_of_result, pt_trinary_singleton(isInstance), &reasons, &lazyReasons))) {
				return zv::Val();
			}
			return storeSuperType(thisDescriptionStr, descriptionStr, zv::Val::adopt(result));
		}

		if (isObjectWithoutClass) {
			zv::Val thatSubtracted = pt_type_call(Z_OBJ_P(type), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(thatSubtracted.isUndef())) return zv::Val();
			if (!thatSubtracted.isNull()) {
				zend_long isSuperType = isSuperTypeOfTrinary(thatSubtracted.raw(), &selfZv);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType == PT_TRI_YES) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_NO));
			}
			return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_MAYBE));
		}

		bool andMaybe = false; /* the $transformResult callback: identity, or ->and(maybe) */
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zend_long isSuperType = isSuperTypeOfTrinary(subtracted, type);
			if (UNEXPECTED(isSuperType < 0)) return zv::Val();
			if (isSuperType == PT_TRI_YES) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_NO));
			if (isSuperType == PT_TRI_MAYBE) {
				andMaybe = true;
			}
		}

		bool isSubtractable;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_SUBTRACTABLE_TYPE, isSubtractable))) return zv::Val();
		if (isSubtractable) {
			zv::Val thatSubtracted = pt_type_call(Z_OBJ_P(type), PT_LC("getsubtractedtype"), 0, NULL);
			if (UNEXPECTED(thatSubtracted.isUndef())) return zv::Val();
			if (!thatSubtracted.isNull()) {
				zend_long isSuperType = isSuperTypeOfTrinary(thatSubtracted.raw(), &selfZv);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType == PT_TRI_YES) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_NO));
			}
		}

		zend_string *thisClassName = className();
		if (UNEXPECTED(thisClassName == NULL)) return zv::Val();
		if (thatCount > 1) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val thisClassReflection = thisGetClassReflection();
		if (UNEXPECTED(thisClassReflection.isUndef())) return zv::Val();
		zv::Val thatClassReflections = pt_type_call(Z_OBJ_P(type), PT_LC("getobjectclassreflections"), 0, NULL);
		if (UNEXPECTED(thatClassReflections.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatClassReflections.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getObjectClassReflections() must return array");
			return zv::Val();
		}
		zv::Val thatClassReflection;
		if (zv::ArrRef(thatClassReflections.raw()).size() == 1) {
			thatClassReflection = firstClassName(thatClassReflections.raw());
			if (UNEXPECTED(thatClassReflection.isUndef())) return zv::Val();
		} else {
			thatClassReflection = zv::Val::null();
		}

		zv::Val thatClassName = firstClassName(thatClassNames.raw());
		if (UNEXPECTED(thatClassName.isUndef())) return zv::Val();
		bool sameName = zend_is_identical(thatClassName.raw(), OBJ_PROP_NUM(self, slots::className));

		if (thisClassReflection.isNull() || thatClassReflection.isNull()) {
			if (sameName) return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_YES));
			return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_MAYBE));
		}
		if (UNEXPECTED(!zv::Ref(thisClassReflection.raw()).isObject() || !zv::Ref(thatClassReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: a class reflection must be an object");
			return zv::Val();
		}
		zend_object *thisReflection = Z_OBJ_P(thisClassReflection.raw());
		zend_object *thatReflection = Z_OBJ_P(thatClassReflection.raw());

		if (sameName) {
			zv::Val nativeReflection = pt_type_call(thisReflection, PT_LC("getnativereflection"), 0, NULL);
			if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
			bool nativeFinal;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nativeReflection.raw()), PT_LC("isfinal"), 0, NULL, nativeFinal))) return zv::Val();
			if (nativeFinal) return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_YES));

			bool thisOverride;
			if (UNEXPECTED(!pt_type_call_bool(thisReflection, PT_LC("hasfinalbykeywordoverride"), 0, NULL, thisOverride))) return zv::Val();
			if (thisOverride) {
				bool thatOverride;
				if (UNEXPECTED(!pt_type_call_bool(thatReflection, PT_LC("hasfinalbykeywordoverride"), 0, NULL, thatOverride))) return zv::Val();
				if (!thatOverride) return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_MAYBE));
			}

			return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_YES));
		}

		bool thisTrait, thatTrait;
		if (UNEXPECTED(!pt_type_call_bool(thisReflection, PT_LC("istrait"), 0, NULL, thisTrait))) return zv::Val();
		if (!thisTrait) {
			if (UNEXPECTED(!pt_type_call_bool(thatReflection, PT_LC("istrait"), 0, NULL, thatTrait))) return zv::Val();
		}
		if (thisTrait || thatTrait) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_NO));

		zv::Val thisName = pt_type_call(thisReflection, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(thisName.isUndef())) return zv::Val();
		zv::Val thatName = pt_type_call(thatReflection, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(thatName.isUndef())) return zv::Val();
		if (zend_is_identical(thisName.raw(), thatName.raw())) return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_YES));

		bool subclass;
		if (UNEXPECTED(!pt_type_call_bool(thatReflection, PT_LC("issubclassofclass"), 1, thisClassReflection.raw(), subclass))) return zv::Val();
		if (subclass) return storeSuperType(thisDescriptionStr, descriptionStr, transformed(andMaybe, PT_TRI_YES));

		if (UNEXPECTED(!pt_type_call_bool(thisReflection, PT_LC("issubclassofclass"), 1, thatClassReflection.raw(), subclass))) return zv::Val();
		if (subclass) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_MAYBE));

		bool thisInterface;
		if (UNEXPECTED(!pt_type_call_bool(thisReflection, PT_LC("isinterface"), 0, NULL, thisInterface))) return zv::Val();
		if (thisInterface) {
			bool thatFinalByKeyword;
			if (UNEXPECTED(!pt_type_call_bool(thatReflection, PT_LC("isfinalbykeyword"), 0, NULL, thatFinalByKeyword))) return zv::Val();
			if (!thatFinalByKeyword) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_MAYBE));
		}

		bool thatInterface;
		if (UNEXPECTED(!pt_type_call_bool(thatReflection, PT_LC("isinterface"), 0, NULL, thatInterface))) return zv::Val();
		if (thatInterface) {
			bool thisFinalByKeyword;
			if (UNEXPECTED(!pt_type_call_bool(thisReflection, PT_LC("isfinalbykeyword"), 0, NULL, thisFinalByKeyword))) return zv::Val();
			if (!thisFinalByKeyword) return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_MAYBE));
		}

		return storeSuperType(thisDescriptionStr, descriptionStr, pt_type_is_super_type_of_result(PT_TRI_NO));
	}

	/* the same class (get_class($type) === static::class) with the same
	 * class name and equal subtracted types; false with an exception
	 * pending on an uninitialized slot */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (Z_OBJCE_P(type) != self->ce) {
			out = false;
			return true;
		}
		zend_string *thisName = className();
		if (UNEXPECTED(thisName == NULL)) return false;
		zend_string *thatName = classNameOf(Z_OBJ_P(type));
		if (UNEXPECTED(thatName == NULL)) return false;
		if (!zend_string_equals(thisName, thatName)) {
			out = false;
			return true;
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		zval *typeSubtracted = subtractedTypeOf(Z_OBJ_P(type));
		if (UNEXPECTED(typeSubtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = Z_TYPE_P(typeSubtracted) == IS_NULL;
			return true;
		}
		if (Z_TYPE_P(typeSubtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(subtracted), PT_LC("equals"), 1, typeSubtracted, out);
	}

	/* UNDEF = pending exception */
	zv::Val checkSubclassAcceptability(zval *thatClass) const
	{
		zend_string *thisName = className();
		if (UNEXPECTED(thisName == NULL)) return zv::Val();
		if (zend_is_identical(OBJ_PROP_NUM(self, slots::className), thatClass)) return pt_type_accepts_result(PT_TRI_YES);

		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return zv::Val();

		zv::Val thisReflection = thisGetClassReflection();
		if (UNEXPECTED(thisReflection.isUndef())) return zv::Val();
		bool hasClass = false;
		if (!thisReflection.isNull()) {
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, thatClass, hasClass))) return zv::Val();
		}
		if (thisReflection.isNull() || !hasClass) return pt_type_accepts_result(PT_TRI_NO);

		thisReflection = thisGetClassReflection();
		if (UNEXPECTED(thisReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thisReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zv::Val thatReflection = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, thatClass);
		if (UNEXPECTED(thatReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClass() must return an object");
			return zv::Val();
		}

		zv::Val thisName2 = pt_type_call(Z_OBJ_P(thisReflection.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(thisName2.isUndef())) return zv::Val();
		zv::Val thatName = pt_type_call(Z_OBJ_P(thatReflection.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(thatName.isUndef())) return zv::Val();
		if (zend_is_identical(thisName2.raw(), thatName.raw())) {
			/* class alias */
			return pt_type_accepts_result(PT_TRI_YES);
		}

		bool thisInterface;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thisReflection.raw()), PT_LC("isinterface"), 0, NULL, thisInterface))) return zv::Val();
		if (thisInterface) {
			bool thatInterface;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thatReflection.raw()), PT_LC("isinterface"), 0, NULL, thatInterface))) return zv::Val();
			if (thatInterface) {
				bool implements;
				if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thatReflection.raw()), PT_LC("implementsinterface"), 1, thisName2.raw(), implements))) return zv::Val();
				return pt_type_accepts_result(implements ? PT_TRI_YES : PT_TRI_NO);
			}
		}

		bool subclass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thatReflection.raw()), PT_LC("issubclassofclass"), 1, thisReflection.raw(), subclass))) return zv::Val();
		return pt_type_accepts_result(subclass ? PT_TRI_YES : PT_TRI_NO);
	}

	/* }}} */

	/* {{{ describe() */

	/* UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		zval *cachedPreciseName = OBJ_PROP_NUM(self, slots::cachedPreciseName);
		if (Z_TYPE_P(cachedPreciseName) == IS_STRING && (which == PT_VERBOSITY_VALUE || which == PT_VERBOSITY_TYPE_ONLY)) {
			return zv::Val::copyOf(zv::Ref(cachedPreciseName));
		}

		switch (which) {
			case PT_VERBOSITY_TYPE_ONLY:
			case PT_VERBOSITY_VALUE:
				return preciseName();
			case PT_VERBOSITY_PRECISE:
				return preciseWithSubtracted(level);
			case PT_VERBOSITY_CACHE:
			default: {
				zv::Val precise = preciseWithSubtracted(level);
				if (UNEXPECTED(precise.isUndef())) return zv::Val();
				smart_str description = {NULL, 0};
				smart_str_append(&description, zv::Ref(precise.raw()).asString());
				smart_str_appendc(&description, '-');
				smart_str_append(&description, self->ce->name); /* static::class */
				smart_str_appendc(&description, '-');
				zval *reflection = classReflection();
				if (UNEXPECTED(reflection == NULL)) {
					smart_str_free(&description);
					return zv::Val();
				}
				if (Z_TYPE_P(reflection) == IS_OBJECT) {
					smart_str_appendc(&description, '-');
					if (UNEXPECTED(!appendStartLine(&description, Z_OBJ_P(reflection)))) {
						smart_str_free(&description);
						return zv::Val();
					}
					smart_str_appendc(&description, '-');
				}
				zv::Val additional = thisCall(PT_LC("describeadditionalcachekey"), otDescribeAdditionalCacheKey, 0, NULL, [&]() { return describeAdditionalCacheKey(); });
				if (UNEXPECTED(additional.isUndef())) {
					smart_str_free(&description);
					return zv::Val();
				}
				zv::Str additionalStr = zv::Str::adopt(zval_get_string(additional.raw()));
				smart_str_append(&description, additionalStr.get());
				smart_str_0(&description);
				return zv::Val::adoptString(description.s);
			}
		}
	}

	static zv::Val describeAdditionalCacheKey() { return zv::Val::string("", 0); }

	/* the memoized cache-level description ($this->cachedDescription):
	 * $this->describe(VerbosityLevel::cache()) for a subclass, the class
	 * name with the subtracted type and the constructor reflection's line
	 * and finality for the class itself; an owned string, UNDEF = pending
	 * exception */
	static zv::Val describeCache(zend_object *object)
	{
		zval *cached = OBJ_PROP_NUM(object, slots::cachedDescription);
		if (Z_TYPE_P(cached) == IS_STRING) return zv::Val::copyOf(zv::Ref(cached));

		if (object->ce != pt_ce_object_type) {
			zv::Val cacheLevel = pt_type_call_static(PT_CLASS_VERBOSITY_LEVEL, PT_LC("cache"), 0, NULL);
			if (UNEXPECTED(cacheLevel.isUndef())) return zv::Val();
			zv::Val description = pt_type_call(object, PT_LC("describe"), 1, cacheLevel.raw());
			if (UNEXPECTED(description.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
				zend_type_error("phpstan_turbo: describe() must return string");
				return zv::Val();
			}
			zv::ObjRef(object).propAtWrite(slots::cachedDescription, zv::Val::copyOf(zv::Ref(description.raw())));
			return description;
		}

		zend_string *name = classNameOf(object);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		/* the `$this instanceof GenericObjectType` branch of the twin is
		 * unreachable here: a subclass returned above */
		zval *subtracted = subtractedTypeOf(object);
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val cacheLevel = pt_type_call_static(PT_CLASS_VERBOSITY_LEVEL, PT_LC("cache"), 0, NULL);
		if (UNEXPECTED(cacheLevel.isUndef())) return zv::Val();
		/* exactly the class: describeSubtractedType() is the trait's */
		zv::Val subtractedDescription = pt_type_describe_subtracted_type(subtracted, cacheLevel.raw());
		if (UNEXPECTED(subtractedDescription.isUndef())) return zv::Val();
		smart_str description = {NULL, 0};
		smart_str_append(&description, name);
		smart_str_append(&description, zv::Ref(subtractedDescription.raw()).asString());

		zval *reflection = classReflectionOf(object);
		if (UNEXPECTED(reflection == NULL)) {
			smart_str_free(&description);
			return zv::Val();
		}
		if (Z_TYPE_P(reflection) == IS_OBJECT) {
			smart_str_appendc(&description, '-');
			if (UNEXPECTED(!appendStartLine(&description, Z_OBJ_P(reflection)))) {
				smart_str_free(&description);
				return zv::Val();
			}
			smart_str_appendc(&description, '-');

			bool override;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflection), PT_LC("hasfinalbykeywordoverride"), 0, NULL, override))) {
				smart_str_free(&description);
				return zv::Val();
			}
			if (override) {
				bool finalByKeyword;
				if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflection), PT_LC("isfinalbykeyword"), 0, NULL, finalByKeyword))) {
					smart_str_free(&description);
					return zv::Val();
				}
				smart_str_appendl(&description, finalByKeyword ? "f=t" : "f=f", 3);
			}
		}
		smart_str_0(&description);
		zv::Val result = zv::Val::adoptString(description.s);
		zv::ObjRef(object).propAtWrite(slots::cachedDescription, zv::Val::copyOf(zv::Ref(result.raw())));
		return result;
	}

	/* }}} */

	/* {{{ the conversions */

	/* float|int for SimpleXMLElement / GMP, ErrorType otherwise; UNDEF =
	 * pending exception */
	zv::Val toNumber() const
	{
		zend_long numeric = isInstanceOfAny({ "SimpleXMLElement", "GMP" });
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) {
			zv::Arr types = zv::Arr::create(2);
			if (UNEXPECTED(!pushNew(types, pt_float_type_new) || !pushNew(types, pt_integer_type_new))) return zv::Val();
			return pt_type_new_union(std::move(types));
		}
		return pt_type_new_error_type();
	}

	/* $this->getClassStringType(); UNDEF = pending exception */
	zv::Val toGetClassResultType() const { return thisGetClassStringType(); }

	/* the literal class name for a final class, class-string<X>&literal-string
	 * otherwise; UNDEF = pending exception */
	zv::Val toClassConstantType(zval *reflectionProvider) const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflectionProvider), PT_LC("hasclass"), 1, OBJ_PROP_NUM(self, slots::className), hasClass))) return zv::Val();
		if (hasClass) {
			zv::Val reflection = pt_type_call(Z_OBJ_P(reflectionProvider), PT_LC("getclass"), 1, OBJ_PROP_NUM(self, slots::className));
			if (UNEXPECTED(reflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(reflection.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClass() must return an object");
				return zv::Val();
			}
			bool finalByKeyword;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflection.raw()), PT_LC("isfinalbykeyword"), 0, NULL, finalByKeyword))) return zv::Val();
			if (finalByKeyword) {
				zv::Val reflectionName = pt_type_call(Z_OBJ_P(reflection.raw()), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(reflectionName.isUndef())) return zv::Val();
				zv::Str reflectionNameStr = zv::Str::adopt(zval_get_string(reflectionName.raw()));
				zval result;
				if (UNEXPECTED(!pt_constant_string_type_new(&result, reflectionNameStr.get(), true))) return zv::Val();
				return zv::Val::adopt(result);
			}
		}

		return classStringAndLiteral();
	}

	/* new IntersectionType([$this->getClassStringType(), new AccessoryLiteralStringType()]) */
	zv::Val classStringAndLiteral() const
	{
		zv::Arr types = zv::Arr::create(2);
		zv::Val classString = thisGetClassStringType();
		if (UNEXPECTED(classString.isUndef())) return zv::Val();
		types.push(std::move(classString));
		if (UNEXPECTED(!pushNew(types, pt_accessory_literal_string_type_new))) return zv::Val();
		return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
	}

	/* new ClassNameToObjectTypeResult($this, true) */
	zv::Val toObjectTypeForInstanceofCheck() const
	{
		zv::Args args{self, true};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* object|class-string / object, never certain; UNDEF = pending exception */
	static zv::Val toObjectTypeForIsACheck(bool allowString)
	{
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
		zv::Val type;
		if (allowString) {
			zval classString;
			if (UNEXPECTED(!pt_class_string_type_new(&classString))) return zv::Val();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(objectWithoutClass));
			types.push(zv::Val::adopt(classString));
			type = pt_type_new_union(std::move(types));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		} else {
			type = std::move(objectWithoutClass);
		}
		zv::Args args{type.raw(), false};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = thisCall(PT_LC("tonumber"), otToNumber, 0, NULL, [&]() { return toNumber(); });
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toNumber() must return %s", ptcls::type);
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(number.raw()), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* int for SimpleXMLElement / GMP and the curl handles, ErrorType
	 * otherwise; UNDEF = pending exception */
	zv::Val toInteger() const
	{
		zend_long numeric = isInstanceOfAny({ "SimpleXMLElement", "GMP" });
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) return integerType();
		zv::Val name = thisGetClassName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		if (zv::Ref(name.raw()).stringEquals("CurlHandle") || zv::Ref(name.raw()).stringEquals("CurlMultiHandle")) return integerType();
		return pt_type_new_error_type();
	}

	/* float for SimpleXMLElement / GMP, ErrorType otherwise */
	zv::Val toFloat() const
	{
		zend_long numeric = isInstanceOfAny({ "SimpleXMLElement", "GMP" });
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) {
			return pt_val_of<pt_float_type_new>();
		}
		return pt_type_new_error_type();
	}

	/* numeric-string&non-empty-string for BcMath\Number / GMP, the
	 * __toString() return type when declared, ErrorType otherwise; UNDEF =
	 * pending exception */
	zv::Val toString() const
	{
		zend_long numeric = isInstanceOfAny({ "BcMath\\Number", "GMP" });
		if (UNEXPECTED(numeric < 0)) return zv::Val();
		if (numeric == PT_TRI_YES) {
			zv::Arr types = zv::Arr::create(3);
			if (UNEXPECTED(!pushNew(types, pt_string_type_new) || !pushNew(types, pt_accessory_numeric_string_type_new) || !pushNew(types, pt_accessory_non_empty_string_type_new))) {
				return zv::Val();
			}
			return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
		}

		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return pt_type_new_error_type();

		bool hasToString;
		zv::Val toStringName = zv::Val::string(PT_LC("__toString"));
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(classReflection.raw()), PT_LC("hasnativemethod"), 1, toStringName.raw(), hasToString))) return zv::Val();
		if (hasToString) return methodReturnType(toStringName.raw());

		return pt_type_new_error_type();
	}

	/* the array shape of the declared properties (an intersection of
	 * hasOffsetValue types for a non-final class), array<mixed, mixed> for
	 * internal classes, ArrayObject and universal object crates; UNDEF =
	 * pending exception */
	zv::Val toArray() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return mixedArray();
		if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}

		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return zv::Val();

		zv::Val nativeReflection = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getnativereflection"), 0, NULL);
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		bool userDefined;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(nativeReflection.raw()), PT_LC("isuserdefined"), 0, NULL, userDefined))) return zv::Val();
		bool crate = !userDefined;
		if (!crate) {
			zv::Val arrayObject = zv::Val::string(PT_LC("ArrayObject"));
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(classReflection.raw()), PT_LC("is"), 1, arrayObject.raw(), crate))) return zv::Val();
		}
		if (!crate) {
			zv::Args args{provider.raw(), classReflection.raw()};
			zv::Val universal = pt_type_call_static(PT_CLASS_UNIVERSAL_OBJECT_CRATES_CLASS_REFLECTION_EXTENSION, PT_LC("isuniversalobjectcrate"), 2, args);
			if (UNEXPECTED(universal.isUndef())) return zv::Val();
			crate = zend_is_true(universal.raw());
		}
		if (crate) return mixedArray();

		zv::Arr arrayKeys = zv::Arr::create(8);
		zv::Arr arrayValues = zv::Arr::create(8);

		bool isFinal;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(classReflection.raw()), PT_LC("isfinal"), 0, NULL, isFinal))) return zv::Val();

		zv::Val current = std::move(classReflection);
		do {
			zv::Val native = pt_type_call(Z_OBJ_P(current.raw()), PT_LC("getnativereflection"), 0, NULL);
			if (UNEXPECTED(native.isUndef())) return zv::Val();
			zv::Val properties = pt_type_call(Z_OBJ_P(native.raw()), PT_LC("getproperties"), 0, NULL);
			if (UNEXPECTED(properties.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(properties.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getProperties() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry entry : zv::ArrRef(properties.raw())) {
				zv::Ref nativeProperty = entry.value().deref();
				if (UNEXPECTED(!nativeProperty.isObject())) {
					zend_type_error("phpstan_turbo: getProperties() must return a list of objects");
					return zv::Val();
				}
				bool isStatic;
				if (UNEXPECTED(!pt_type_call_bool(nativeProperty.asObject(), PT_LC("isstatic"), 0, NULL, isStatic))) return zv::Val();
				if (isStatic) continue;

				zv::Val nativeDeclaringClass = pt_type_call(nativeProperty.asObject(), PT_LC("getdeclaringclass"), 0, NULL);
				if (UNEXPECTED(nativeDeclaringClass.isUndef())) return zv::Val();
				zv::Val nativeDeclaringName = pt_type_call(Z_OBJ_P(nativeDeclaringClass.raw()), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(nativeDeclaringName.isUndef())) return zv::Val();
				zv::Val declaringClass = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, nativeDeclaringName.raw());
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				zv::Val nativeName = pt_type_call(nativeProperty.asObject(), PT_LC("getname"), 0, NULL);
				if (UNEXPECTED(nativeName.isUndef())) return zv::Val();
				zv::Val property = pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getnativeproperty"), 1, nativeName.raw());
				if (UNEXPECTED(property.isUndef())) return zv::Val();

				zv::Str keyName = zv::Str::adopt(zval_get_string(nativeName.raw()));
				bool isPrivate, isProtected = false;
				if (UNEXPECTED(!pt_type_call_bool(nativeProperty.asObject(), PT_LC("isprivate"), 0, NULL, isPrivate))) return zv::Val();
				if (!isPrivate) {
					if (UNEXPECTED(!pt_type_call_bool(nativeProperty.asObject(), PT_LC("isprotected"), 0, NULL, isProtected))) return zv::Val();
				}
				if (isPrivate) {
					/* sprintf("\0%s\0%s", $declaringClass->getName(), $keyName) */
					zv::Val declaringName = pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getname"), 0, NULL);
					if (UNEXPECTED(declaringName.isUndef())) return zv::Val();
					zv::Str declaringNameStr = zv::Str::adopt(zval_get_string(declaringName.raw()));
					smart_str mangled = {NULL, 0};
					smart_str_appendc(&mangled, '\0');
					smart_str_append(&mangled, declaringNameStr.get());
					smart_str_appendc(&mangled, '\0');
					smart_str_append(&mangled, keyName.get());
					smart_str_0(&mangled);
					keyName = zv::Str::adopt(mangled.s);
				} else if (isProtected) {
					/* sprintf("\0*\0%s", $keyName) */
					smart_str mangled = {NULL, 0};
					smart_str_appendl(&mangled, "\0*\0", 3);
					smart_str_append(&mangled, keyName.get());
					smart_str_0(&mangled);
					keyName = zv::Str::adopt(mangled.s);
				}

				zval key;
				if (UNEXPECTED(!pt_constant_string_type_new(&key, keyName.get()))) return zv::Val();
				arrayKeys.push(zv::Val::adopt(key));
				zv::Val readableType = pt_type_call(Z_OBJ_P(property.raw()), PT_LC("getreadabletype"), 0, NULL);
				if (UNEXPECTED(readableType.isUndef())) return zv::Val();
				arrayValues.push(std::move(readableType));
			}

			current = pt_type_call(Z_OBJ_P(current.raw()), PT_LC("getparentclass"), 0, NULL);
			if (UNEXPECTED(current.isUndef())) return zv::Val();
		} while (!current.isNull());

		if (!isFinal) {
			uint32_t count = arrayKeys.arrRef().size();
			if (count == 0 || count > 16) return mixedArray();

			zv::Arr types = zv::Arr::create(count + 1);
			zv::Val array = mixedArray();
			if (UNEXPECTED(array.isUndef())) return zv::Val();
			types.push(std::move(array));
			for (uint32_t i = 0; i < count; i++) {
				zval hasOffsetValueRaw;
				zv::Val hasOffsetValue = pt_has_offset_value_type_new(&hasOffsetValueRaw, arrayKeys.arrRef().findIndex(i).raw(), arrayValues.arrRef().findIndex(i).raw()) ? zv::Val::adopt(hasOffsetValueRaw) : zv::Val();
				if (UNEXPECTED(hasOffsetValue.isUndef())) return zv::Val();
				types.push(std::move(hasOffsetValue));
			}

			return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
		}

		zval args[2];
		ZVAL_COPY_VALUE(&args[0], arrayKeys.raw());
		ZVAL_COPY_VALUE(&args[1], arrayValues.raw());
		return pt_type_new(PT_CLASS_CONSTANT_ARRAY_TYPE, 2, args);
	}

	/* $this, or $this|$this->toString() when coercion may call
	 * __toString(); UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (!strictTypes) {
			zv::Val classReflection = thisGetClassReflection();
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			bool hasToString = false;
			if (!classReflection.isNull()) {
				zv::Val toStringName = zv::Val::string(PT_LC("__toString"));
				if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(classReflection.raw()), PT_LC("hasnativemethod"), 1, toStringName.raw(), hasToString))) return zv::Val();
			}
			if (classReflection.isNull() || !hasToString) return thisValue();

			zv::Val string = thisCall(PT_LC("tostring"), otToString, 0, NULL, [&]() { return toString(); });
			if (UNEXPECTED(string.isUndef())) return zv::Val();
			zv::Args args{self, string.raw()};
			return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
		}

		return thisValue();
	}

	/* bool for SimpleXMLElement / BcMath\Number / GMP, true otherwise;
	 * UNDEF = pending exception */
	zv::Val toBoolean() const
	{
		zend_long undecided = isInstanceOfAny({ "SimpleXMLElement", "BcMath\\Number", "GMP" });
		if (UNEXPECTED(undecided < 0)) return zv::Val();
		zval result;
		if (undecided == PT_TRI_YES) {
			if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
			return zv::Val::adopt(result);
		}
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, true))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new GenericClassStringType($this) */
	zv::Val getClassStringType() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_type_new_ce(pt_ce_generic_class_string_type, 1, &selfZv);
	}

	/* yes for an enum (or UnitEnum), maybe for an interface an enum could
	 * implement, no otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isEnum() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return -1;
		if (classReflection.isNull()) return PT_TRI_MAYBE;
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		bool isEnum;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isenum"), 0, NULL, isEnum))) return -1;
		if (!isEnum) {
			if (UNEXPECTED(!reflectionIs(reflection, PT_LC("UnitEnum"), isEnum))) return -1;
		}
		if (isEnum) return PT_TRI_YES;

		bool isInterface;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isinterface"), 0, NULL, isInterface))) return -1;
		if (isInterface) {
			/* enums cannot have __toString, extend Exception/Error, or
			 * extend DateTimeInterface */
			bool excluded;
			if (UNEXPECTED(!reflectionIs(reflection, PT_LC("Stringable"), excluded))) return -1;
			if (!excluded) {
				if (UNEXPECTED(!reflectionIs(reflection, PT_LC("Throwable"), excluded))) return -1;
			}
			if (!excluded) {
				if (UNEXPECTED(!reflectionIs(reflection, PT_LC("DateTimeInterface"), excluded))) return -1;
			}
			if (!excluded) return PT_TRI_MAYBE;
		}

		return PT_TRI_NO;
	}

	/* no for stdClass, yes otherwise; -1 = pending exception */
	[[nodiscard]] zend_long canCallMethods() const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return -1;
		if (zend_string_equals_literal_ci(name, "stdclass")) return PT_TRI_NO;
		return PT_TRI_YES;
	}

	/* hasMethod() / hasConstant(): yes when the class declares it, no for a
	 * final class, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long hasMember(const char *lcname, size_t len, zval *name) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return -1;
		if (classReflection.isNull()) return PT_TRI_MAYBE;
		zend_object *reflection = Z_OBJ_P(classReflection.raw());
		bool has;
		if (UNEXPECTED(!pt_type_call_bool(reflection, lcname, len, 1, name, has))) return -1;
		if (has) return PT_TRI_YES;
		bool isFinal;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isfinal"), 0, NULL, isFinal))) return -1;
		if (isFinal) return PT_TRI_NO;
		return PT_TRI_MAYBE;
	}

	zend_long hasMethod(zval *methodName) const { return hasMember(PT_LC("hasmethod"), methodName); }
	zend_long hasConstant(zval *constantName) const { return hasMember(PT_LC("hasconstant"), constantName); }

	/* $this->getClassReflection()->getConstant($constantName); UNDEF =
	 * pending exception */
	zv::Val getConstant(zval *constantName) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return throwClassNotFound();
		return pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getconstant"), 1, constantName);
	}

	/* the ancestor's template argument, resolved to its bound or default
	 * when unresolved; ErrorType when unknown; UNDEF = pending exception */
	zv::Val getTemplateType(zval *ancestorClassName, zval *templateTypeName) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return pt_type_new_error_type();

		zv::Val ancestorClassReflection = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getancestorwithclassname"), 1, ancestorClassName);
		if (UNEXPECTED(ancestorClassReflection.isUndef())) return zv::Val();
		if (ancestorClassReflection.isNull()) return pt_type_new_error_type();
		zend_object *ancestor = Z_OBJ_P(ancestorClassReflection.raw());

		zv::Val activeTemplateTypeMap = pt_type_call(ancestor, PT_LC("getpossiblyincompleteactivetemplatetypemap"), 0, NULL);
		if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val type = pt_type_call(Z_OBJ_P(activeTemplateTypeMap.raw()), PT_LC("gettype"), 1, templateTypeName);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) return pt_type_new_error_type();
		bool is;
		if (UNEXPECTED(!pt_type_instanceof(type.raw(), PT_CLASS_UNRESOLVED_TEMPLATE_ARGUMENT_TYPE, is))) return zv::Val();
		if (is) {
			/* read out of the object as a derived value - see TemplateTypeHelper::resolveTemplateTypes() */
			return pt_type_call(Z_OBJ_P(type.raw()), PT_LC("getdelegate"), 0, NULL);
		}
		if (UNEXPECTED(!pt_type_instanceof(type.raw(), PT_CLASS_ERROR_TYPE, is))) return zv::Val();
		if (is) {
			zv::Val templateTypeMap = pt_type_call(ancestor, PT_LC("gettemplatetypemap"), 0, NULL);
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			zv::Val templateType = pt_type_call(Z_OBJ_P(templateTypeMap.raw()), PT_LC("gettype"), 1, templateTypeName);
			if (UNEXPECTED(templateType.isUndef())) return zv::Val();
			if (templateType.isNull()) return type;

			zv::Val bound = pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_HELPER, PT_LC("resolvetobounds"), 1, templateType.raw());
			if (UNEXPECTED(bound.isUndef())) return zv::Val();
			if (zv::Ref(bound.raw()).instanceOf(pt_ce_mixed_type)) {
				bool explicitMixed;
				if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(bound.raw()), PT_LC("isexplicitmixed"), 0, NULL, explicitMixed))) return zv::Val();
				if (explicitMixed) {
					zval mixed;
					if (UNEXPECTED(!pt_mixed_type_new(&mixed, false))) return zv::Val();
					return zv::Val::adopt(mixed);
				}
			}

			return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_HELPER, PT_LC("resolvetodefaults"), 1, templateType.raw());
		}

		return type;
	}

	/* }}} */

	/* {{{ the iterable family */

	/* $this->isInstanceOf(Traversable::class); -1 = pending exception */
	[[nodiscard]] zend_long isIterable() const { return thisIsInstanceOfLiteral(PT_LC("Traversable")); }

	/* ... ->and(maybe); -1 = pending exception */
	[[nodiscard]] zend_long isIterableAtLeastOnce() const
	{
		zend_long traversable = isIterable();
		if (UNEXPECTED(traversable < 0)) return -1;
		return traversable < PT_TRI_MAYBE ? traversable : PT_TRI_MAYBE;
	}

	/* the count() return type of a Countable (int<0, max> without a known
	 * count()), ErrorType for a non-Countable; UNDEF = pending exception */
	zv::Val getArraySize() const
	{
		zend_long countable = thisIsInstanceOfLiteral(PT_LC("Countable"));
		if (UNEXPECTED(countable < 0)) return zv::Val();
		if (countable == PT_TRI_NO) return pt_type_new_error_type();

		zv::Val countName = zv::Val::string(PT_LC("count"));
		zend_long hasCount = thisCallTrinary(PT_LC("hasmethod"), otHasMethod, 1, countName.raw(), [&]() { return hasMethod(countName.raw()); });
		if (UNEXPECTED(hasCount < 0)) return zv::Val();
		if (hasCount != PT_TRI_YES) return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0);

		return guardedMethodReturnType(PT_OTC_METHOD_RETURN_TYPE, countName.raw());
	}

	/* getIterableKeyType() / getIterableValueType(): the IteratorAggregate
	 * or Traversable template argument, the Iterator method's return type,
	 * mixed for the extra offset-accessible classes and other Traversables,
	 * ErrorType otherwise; UNDEF = pending exception */
	zv::Val iterableType(bool key) const
	{
		bool isTraversable = false;
		zend_long aggregate = thisIsInstanceOfLiteral(PT_LC("IteratorAggregate"));
		if (UNEXPECTED(aggregate < 0)) return zv::Val();
		if (aggregate == PT_TRI_YES) {
			zv::Val getIterator = zv::Val::string(PT_LC("getIterator"));
			zv::Val type = guardedMethodReturnType(key ? PT_OTC_METHOD_RETURN_ITERABLE_KEY_TYPE : PT_OTC_METHOD_RETURN_ITERABLE_VALUE_TYPE, getIterator.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			isTraversable = true;
			bool implicitMixed;
			if (UNEXPECTED(!isImplicitMixed(type.raw(), implicitMixed))) return zv::Val();
			if (!implicitMixed) return type;
		}

		zend_long extraOffsetAccessible = isExtraOffsetAccessibleClass();
		if (UNEXPECTED(extraOffsetAccessible < 0)) return zv::Val();
		if (extraOffsetAccessible != PT_TRI_YES) {
			zend_long traversable = thisIsInstanceOfLiteral(PT_LC("Traversable"));
			if (UNEXPECTED(traversable < 0)) return zv::Val();
			if (traversable == PT_TRI_YES) {
				isTraversable = true;
				zv::Val traversableName = zv::Val::string(PT_LC("Traversable"));
				zv::Val argumentName = key ? zv::Val::string(PT_LC("TKey")) : zv::Val::string(PT_LC("TValue"));
				zv::Args args{traversableName.raw(), argumentName.raw()};
				zv::Val argument = thisCall(PT_LC("gettemplatetype"), otGetTemplateType, 2, args, [&]() { return getTemplateType(traversableName.raw(), argumentName.raw()); });
				if (UNEXPECTED(argument.isUndef())) return zv::Val();
				bool isError;
				if (UNEXPECTED(!pt_type_instanceof(argument.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
				if (!isError) {
					bool implicitMixed;
					if (UNEXPECTED(!isImplicitMixed(argument.raw(), implicitMixed))) return zv::Val();
					if (!implicitMixed) return argument;
				}
			}
		}

		zend_long iterator = thisIsInstanceOfLiteral(PT_LC("Iterator"));
		if (UNEXPECTED(iterator < 0)) return zv::Val();
		if (iterator == PT_TRI_YES) {
			zv::Val methodName = key ? zv::Val::string(PT_LC("key")) : zv::Val::string(PT_LC("current"));
			return guardedMethodReturnType(PT_OTC_METHOD_RETURN_TYPE, methodName.raw());
		}

		if (extraOffsetAccessible == PT_TRI_YES) {
			zval mixed;
			if (UNEXPECTED(!pt_mixed_type_new(&mixed, true))) return zv::Val();
			return zv::Val::adopt(mixed);
		}

		if (isTraversable) return pt_type_new_mixed_type();

		return pt_type_new_error_type();
	}

	zv::Val getIterableKeyType() const { return iterableType(true); }
	zv::Val getIterableValueType() const { return iterableType(false); }

	/* $this->getIterableKeyType() / $this->getIterableValueType() through
	 * the object's class — the first/last variants; UNDEF = pending exception */
	zv::Val thisGetIterableKeyType() const { return thisCall(PT_LC("getiterablekeytype"), otGetIterableKeyType, 0, NULL, [&]() { return getIterableKeyType(); }); }
	zv::Val thisGetIterableValueType() const { return thisCall(PT_LC("getiterablevaluetype"), otGetIterableValueType, 0, NULL, [&]() { return getIterableValueType(); }); }

	/* }}} */

	/* {{{ the offset family */

	/* true for the ConstantBooleanType(true) of $type->isTrue(), false of
	 * ->isFalse(), bool otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		zval result;
		if (isTrue == PT_TRI_YES) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, true))) return zv::Val();
			return zv::Val::adopt(result);
		}
		zend_long isFalse = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(isFalse < 0)) return zv::Val();
		if (isFalse == PT_TRI_YES) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
			return zv::Val::adopt(result);
		}
		if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* yes for one of the EXTRA_OFFSET_CLASSES, no for a final class, maybe
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isExtraOffsetAccessibleClass() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return -1;
		if (classReflection.isNull()) return PT_TRI_MAYBE;
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		static const char *const extraOffsetClasses[] = {
			"DOMNamedNodeMap", // Only read and existence
			"Dom\\NamedNodeMap", // Only read and existence
			"DOMNodeList", // Only read and existence
			"Dom\\NodeList", // Only read and existence
			"Dom\\HTMLCollection", // Only read and existence
			"Dom\\DtdNamedNodeMap", // Only read and existence
			"PDORow", // Only read and existence
			"ResourceBundle", // Only read
			"FFI\\CData", // Very funky and weird
			"SimpleXMLElement",
			"Threaded",
		};
		for (const char *extraOffsetClass : extraOffsetClasses) {
			bool is;
			if (UNEXPECTED(!reflectionIs(reflection, extraOffsetClass, strlen(extraOffsetClass), is))) return -1;
			if (is) return PT_TRI_YES;
		}

		bool isInterface;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isinterface"), 0, NULL, isInterface))) return -1;
		if (isInterface) return PT_TRI_MAYBE;

		bool isFinal;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isfinal"), 0, NULL, isFinal))) return -1;
		if (isFinal) return PT_TRI_NO;

		return PT_TRI_MAYBE;
	}

	/* $this->isInstanceOf(ArrayAccess::class)->or($this->isExtraOffsetAccessibleClass());
	 * -1 = pending exception */
	[[nodiscard]] zend_long isOffsetAccessible() const
	{
		zend_long arrayAccess = thisIsInstanceOfLiteral(PT_LC("ArrayAccess"));
		if (UNEXPECTED(arrayAccess < 0)) return -1;
		zend_long extra = isExtraOffsetAccessibleClass();
		if (UNEXPECTED(extra < 0)) return -1;
		return arrayAccess > extra ? arrayAccess : extra;
	}

	/* $this->isOffsetAccessible(); -1 = pending exception */
	[[nodiscard]] zend_long isOffsetAccessLegal() const { return thisIsOffsetAccessible(); }

	/* no when ArrayAccess::offsetSet() rejects the offset, maybe otherwise;
	 * the extra classes' verdict and maybe for the rest; -1 = pending
	 * exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetType) const
	{
		zend_long arrayAccess = thisIsInstanceOfLiteral(PT_LC("ArrayAccess"));
		if (UNEXPECTED(arrayAccess < 0)) return -1;
		if (arrayAccess == PT_TRI_YES) {
			zv::Val acceptedOffsetType = guarded(PT_OTC_OFFSET_SET_PARAMETER_TYPE, NULL, NULL, NULL);
			if (UNEXPECTED(acceptedOffsetType.isUndef())) return -1;
			if (UNEXPECTED(!zv::Ref(acceptedOffsetType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: the accepted offset type must be %s", ptcls::type);
				return -1;
			}
			zend_long isSuperType = isSuperTypeOfTrinary(acceptedOffsetType.raw(), offsetType);
			if (UNEXPECTED(isSuperType < 0)) return -1;
			if (isSuperType == PT_TRI_NO) return PT_TRI_NO;
			return PT_TRI_MAYBE;
		}

		zend_long extra = isExtraOffsetAccessibleClass();
		if (UNEXPECTED(extra < 0)) return -1;
		return extra < PT_TRI_MAYBE ? extra : PT_TRI_MAYBE;
	}

	/* ArrayAccess::offsetGet()'s return type, mixed for a possibly extra
	 * offset-accessible class, ErrorType otherwise; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long arrayAccess = thisIsInstanceOfLiteral(PT_LC("ArrayAccess"));
		if (UNEXPECTED(arrayAccess < 0)) return zv::Val();
		if (arrayAccess == PT_TRI_YES) {
			zv::Val offsetGet = zv::Val::string(PT_LC("offsetGet"));
			return guardedMethodReturnType(PT_OTC_METHOD_RETURN_TYPE, offsetGet.raw());
		}

		zend_long extra = isExtraOffsetAccessibleClass();
		if (UNEXPECTED(extra < 0)) return zv::Val();
		if (extra != PT_TRI_NO) return pt_type_new_mixed_type();

		return pt_type_new_error_type();
	}

	/* $this when the offset and value are accepted by ArrayAccess::offsetSet()
	 * (or the class is not one), ErrorType otherwise; $offsetType NULL for
	 * null; UNDEF = pending exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zend_long accessible = thisIsOffsetAccessible();
		if (UNEXPECTED(accessible < 0)) return zv::Val();
		if (accessible == PT_TRI_NO) return pt_type_new_error_type();

		zend_long arrayAccess = thisIsInstanceOfLiteral(PT_LC("ArrayAccess"));
		if (UNEXPECTED(arrayAccess < 0)) return zv::Val();
		if (arrayAccess == PT_TRI_YES) {
			/* $acceptedValueType = new NeverType(), overwritten by the closure */
			zv::Val acceptedValueType = pt_type_new_never_type();
			if (UNEXPECTED(acceptedValueType.isUndef())) return zv::Val();
			zval holder;
			zv::Val acceptedOffsetType = guarded(PT_OTC_OFFSET_SET_PARAMETER_TYPES, NULL, NULL, NULL, &holder);
			zv::Val holderVal = zv::Val::adopt(holder);
			if (UNEXPECTED(acceptedOffsetType.isUndef())) return zv::Val();
			zval *out = OBJ_PROP_NUM(Z_OBJ(holder), slots::cachedDescription);
			if (Z_TYPE_P(out) == IS_OBJECT) {
				acceptedValueType = zv::Val::copyOf(zv::Ref(out));
			}
			if (UNEXPECTED(!zv::Ref(acceptedOffsetType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: the accepted offset type must be %s", ptcls::type);
				return zv::Val();
			}

			zv::Val nullOffset;
			if (offsetType == NULL) {
				zval nullType;
				if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
				nullOffset = zv::Val::adopt(nullType);
				offsetType = nullOffset.raw();
			}

			bool rejected = false;
			if (!zv::Ref(offsetType).instanceOf(pt_ce_mixed_type)) {
				zend_long isSuperType = isSuperTypeOfTrinary(acceptedOffsetType.raw(), offsetType);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				rejected = isSuperType != PT_TRI_YES;
			}
			if (!rejected && !zv::Ref(valueType).instanceOf(pt_ce_mixed_type)) {
				zend_long isSuperType = isSuperTypeOfTrinary(acceptedValueType.raw(), valueType);
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				rejected = isSuperType != PT_TRI_YES;
			}
			if (rejected) return pt_type_new_error_type();
		}

		// in the future we may return intersection of $this and OffsetAccessibleType()
		return thisValue();
	}

	/* $this unless $this->isOffsetAccessible() is no — setExistingOffsetValueType()
	 * and unsetOffset(); UNDEF = pending exception */
	zv::Val thisUnlessNotOffsetAccessible() const
	{
		zend_long accessible = thisIsOffsetAccessible();
		if (UNEXPECTED(accessible < 0)) return zv::Val();
		if (accessible == PT_TRI_NO) return pt_type_new_error_type();
		return thisValue();
	}

	/* }}} */

	/* {{{ enum cases, callables, isInstanceOf() */

	/* the enum's cases minus the subtracted ones, memoized per
	 * description; [] for a non-enum; UNDEF = pending exception */
	zv::Val getEnumCases() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return zv::Val(zv::Arr::empty());
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		bool isEnum;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isenum"), 0, NULL, isEnum))) return zv::Val();
		if (!isEnum) return zv::Val(zv::Arr::empty());

		zv::Val cacheKey = describeCache(self);
		if (UNEXPECTED(cacheKey.isUndef())) return zv::Val();
		zend_string *cacheKeyStr = zv::Ref(cacheKey.raw()).asString();
		if (UNEXPECTED(!touchDescriptionCacheKey(cacheKeyStr))) return zv::Val();
		zval *cached = zend_symtable_find(Z_ARRVAL_P(pt_ot_enum_cases()), cacheKeyStr);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val reflectionName = pt_type_call(reflection, PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(reflectionName.isUndef())) return zv::Val();
		zv::Str enumClassName = zv::Str::adopt(zval_get_string(reflectionName.raw()));

		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();

		/* the names of the subtracted cases, when there is a subtracted type */
		zv::ScratchTable subtractedEnumCaseNames(0);
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zv::Val subtractedCases = pt_type_call(Z_OBJ_P(subtracted), PT_LC("getenumcases"), 0, NULL);
			if (UNEXPECTED(subtractedCases.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(subtractedCases.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getEnumCases() must return array");
				return zv::Val();
			}
			for (zv::ArrayEntry entry : zv::ArrRef(subtractedCases.raw())) {
				zv::Ref subtractedCase = entry.value().deref();
				if (UNEXPECTED(!subtractedCase.isObject())) {
					zend_type_error("phpstan_turbo: getEnumCases() must return a list of objects");
					return zv::Val();
				}
				zv::Val caseName = pt_type_call(subtractedCase.asObject(), PT_LC("getenumcasename"), 0, NULL);
				if (UNEXPECTED(caseName.isUndef())) return zv::Val();
				zv::Str caseNameStr = zv::Str::adopt(zval_get_string(caseName.raw()));
				zval marker;
				ZVAL_TRUE(&marker);
				zend_symtable_update(subtractedEnumCaseNames.table(), caseNameStr.get(), &marker);
			}
		}

		zv::Val enumCases = pt_type_call(reflection, PT_LC("getenumcases"), 0, NULL);
		if (UNEXPECTED(enumCases.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(enumCases.raw()).isArray())) {
			zend_type_error("phpstan_turbo: ClassReflection::getEnumCases() must return array");
			return zv::Val();
		}
		zv::Arr cases = zv::Arr::create(zv::ArrRef(enumCases.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(enumCases.raw())) {
			zv::Ref enumCase = entry.value().deref();
			if (UNEXPECTED(!enumCase.isObject())) {
				zend_type_error("phpstan_turbo: ClassReflection::getEnumCases() must return a list of objects");
				return zv::Val();
			}
			zv::Val caseName = pt_type_call(enumCase.asObject(), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(caseName.isUndef())) return zv::Val();
			zv::Str caseNameStr = zv::Str::adopt(zval_get_string(caseName.raw()));
			if (Z_TYPE_P(subtracted) != IS_NULL && zend_symtable_find(subtractedEnumCaseNames.table(), caseNameStr.get()) != NULL) continue;
			zval enumCaseType;
			if (UNEXPECTED(!pt_enum_case_object_type_new(&enumCaseType, enumClassName.get(), caseNameStr.get(), classReflection.raw()))) return zv::Val();
			cases.push(zv::Val::adopt(enumCaseType));
		}

		SEPARATE_ARRAY(pt_ot_enum_cases());
		Z_TRY_ADDREF_P(cases.raw());
		zend_symtable_update(Z_ARRVAL_P(pt_ot_enum_cases()), cacheKeyStr, cases.raw());
		return zv::Val(std::move(cases));
	}

	/* the single case, null otherwise; UNDEF = pending exception */
	zv::Val getEnumCaseObject() const
	{
		zv::Val cases = thisGetEnumCases();
		if (UNEXPECTED(cases.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(cases.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getEnumCases() must return array");
			return zv::Val();
		}
		if (zv::ArrRef(cases.raw()).size() == 1) return firstClassName(cases.raw());
		return zv::Val::null();
	}

	/* $this->getEnumCases() */
	zv::Val getFiniteTypes() const { return thisGetEnumCases(); }

	/* yes with a known __invoke(), maybe for a trivial acceptor, no
	 * otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val parametersAcceptors = guarded(PT_OTC_FIND_CALLABLE_PARAMETERS_ACCEPTORS, &selfZv, NULL, NULL);
		if (UNEXPECTED(parametersAcceptors.isUndef())) return -1;
		if (parametersAcceptors.isNull()) return PT_TRI_NO;
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof(parametersAcceptors.raw(), PT_CLASS_ERROR_TYPE, isError))) return -1;
		if (isError) return PT_TRI_NO;
		if (UNEXPECTED(!zv::Ref(parametersAcceptors.raw()).isArray())) {
			zend_type_error("phpstan_turbo: findCallableParametersAcceptors() must return array");
			return -1;
		}

		if (zv::ArrRef(parametersAcceptors.raw()).size() == 1) {
			zv::Val first = firstClassName(parametersAcceptors.raw());
			if (UNEXPECTED(first.isUndef())) return -1;
			bool trivial;
			if (UNEXPECTED(!pt_type_instanceof(first.raw(), PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, trivial))) return -1;
			if (trivial) return PT_TRI_MAYBE;
		}

		return PT_TRI_YES;
	}

	/* UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors() const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (zend_string_equals_literal(name, "Closure")) {
			zv::Val closureName = zv::Val::string(PT_LC("Closure"));
			zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 1, closureName.raw());
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
			zv::Arr acceptors = zv::Arr::create(1);
			acceptors.push(std::move(acceptor));
			return zv::Val(std::move(acceptors));
		}
		zv::Val parametersAcceptors = findCallableParametersAcceptors();
		if (UNEXPECTED(parametersAcceptors.isUndef())) return zv::Val();
		if (parametersAcceptors.isNull()) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		return parametersAcceptors;
	}

	/* the __invoke() variants, a trivial acceptor for an unknown or
	 * non-final class, null for a final class without __invoke(); UNDEF =
	 * pending exception */
	zv::Val findCallableParametersAcceptors() const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (classReflection.isNull()) return trivialAcceptors();
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		zv::Val invokeName = zv::Val::string(PT_LC("__invoke"));
		bool hasInvoke;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("hasnativemethod"), 1, invokeName.raw(), hasInvoke))) return zv::Val();
		if (hasInvoke) {
			zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			zv::Val method = thisGetMethod(invokeName.raw(), scope.raw());
			if (UNEXPECTED(method.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getMethod() must return an object");
				return zv::Val();
			}
			zv::Val variants = pt_type_call(Z_OBJ_P(method.raw()), PT_LC("getvariants"), 0, NULL);
			if (UNEXPECTED(variants.isUndef())) return zv::Val();
			zv::Args args{method.raw(), variants.raw()};
			return pt_type_call_static(PT_CLASS_FUNCTION_CALLABLE_VARIANT, PT_LC("createfromvariants"), 2, args);
		}

		bool finalByKeyword;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isfinalbykeyword"), 0, NULL, finalByKeyword))) return zv::Val();
		if (!finalByKeyword) return trivialAcceptors();

		return zv::Val::null();
	}

	/* yes when the class is $className, no when $className is final or the
	 * class is not an interface, maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isInstanceOf(zval *className) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return -1;
		if (classReflection.isNull()) return PT_TRI_MAYBE;
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		bool is;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("is"), 1, className, is))) return -1;
		if (is) return PT_TRI_YES;

		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return -1;
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, className, hasClass))) return -1;
		if (hasClass) {
			zv::Val thatClassReflection = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, className);
			if (UNEXPECTED(thatClassReflection.isUndef())) return -1;
			bool isFinal;
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(thatClassReflection.raw()), PT_LC("isfinal"), 0, NULL, isFinal))) return -1;
			if (isFinal) return PT_TRI_NO;
		}

		bool isInterface;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isinterface"), 0, NULL, isInterface))) return -1;
		if (isInterface) return PT_TRI_MAYBE;

		return PT_TRI_NO;
	}

	/* }}} */

	/* {{{ subtraction */

	/* UNDEF = pending exception */
	zv::Val subtract(zval *type) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisChangeSubtractedType(type);

		// A sealed hierarchy rebuilds its subtraction from the flattened parts
		// below, so normalising the union of the old and the new subtracted type
		// first is wasted work - and quadratic when a removal peels the subtypes
		// off one at a time, as removing a whole enum from its own type does.
		// Only the path that keeps the subtracted type as it is needs the union.
		zv::Val flattenedSubtracted = pt_type_call_static(PT_CLASS_TYPE_UTILS, PT_LC("flattentypes"), 1, subtracted);
		if (UNEXPECTED(flattenedSubtracted.isUndef())) return zv::Val();
		zv::Val flattenedType = pt_type_call_static(PT_CLASS_TYPE_UTILS, PT_LC("flattentypes"), 1, type);
		if (UNEXPECTED(flattenedType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(flattenedSubtracted.raw()).isArray() || !zv::Ref(flattenedType.raw()).isArray())) {
			zend_type_error("phpstan_turbo: TypeUtils::flattenTypes() must return array");
			return zv::Val();
		}
		/* array_merge() of two lists */
		zv::Arr merged = zv::Arr::create(zv::ArrRef(flattenedSubtracted.raw()).size() + zv::ArrRef(flattenedType.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(flattenedSubtracted.raw())) {
			merged.push(entry.value());
		}
		for (zv::ArrayEntry entry : zv::ArrRef(flattenedType.raw())) {
			merged.push(entry.value());
		}
		zv::Val matched = matchAllowedSubTypes(merged.raw());
		if (UNEXPECTED(matched.isUndef())) return zv::Val();
		if (!matched.isNull()) return matched;

		zv::Args args{subtracted, type};
		zv::Val unioned = pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
		if (UNEXPECTED(unioned.isUndef())) return zv::Val();
		return thisChangeSubtractedType(unioned.raw());
	}

	/* $this->changeSubtractedType(null); UNDEF = pending exception */
	zv::Val getTypeWithoutSubtractedType() const
	{
		zval null;
		ZVAL_NULL(&null);
		return thisChangeSubtractedType(&null);
	}

	/* $this without the ClassReflection::asFinal() flavour of the
	 * constructor's reflection; UNDEF = pending exception */
	zv::Val withoutFinalByKeywordOverride() const
	{
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		bool override = false;
		if (Z_TYPE_P(reflection) == IS_OBJECT) {
			if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(reflection), PT_LC("hasfinalbykeywordoverride"), 0, NULL, override))) return zv::Val();
		}
		if (Z_TYPE_P(reflection) != IS_OBJECT || !override) return thisValue();

		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val withoutOverride = pt_type_call(Z_OBJ_P(reflection), PT_LC("withoutfinalbykeywordoverride"), 0, NULL);
		if (UNEXPECTED(withoutOverride.isUndef())) return zv::Val();
		return create(name, subtracted, withoutOverride.raw());
	}

	/* the sealed hierarchy's remainder when the subtracted type names its
	 * members, $this when nothing changes, new self($className,
	 * $subtractedType) otherwise; $subtractedType IS_NULL for null; UNDEF =
	 * pending exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		if (Z_TYPE_P(subtractedType) != IS_NULL) {
			zv::Val flattened = pt_type_call_static(PT_CLASS_TYPE_UTILS, PT_LC("flattentypes"), 1, subtractedType);
			if (UNEXPECTED(flattened.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(flattened.raw()).isArray())) {
				zend_type_error("phpstan_turbo: TypeUtils::flattenTypes() must return array");
				return zv::Val();
			}
			zv::Val matched = matchAllowedSubTypes(flattened.raw());
			if (UNEXPECTED(matched.isUndef())) return zv::Val();
			if (!matched.isNull()) return matched;
		}

		zval *subtracted = this->subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL && Z_TYPE_P(subtractedType) == IS_NULL) return thisValue();

		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return create(name, Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType);
	}

	/* Rebuilds the type from the subtracted members of its sealed
	 * hierarchy; null when the class has no allowed subtypes, or when a
	 * subtracted type is not one of them; UNDEF = pending exception */
	zv::Val matchAllowedSubTypes(zval *subtractedTypes) const
	{
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val allowedSubTypes;
		if (!classReflection.isNull()) {
			allowedSubTypes = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getallowedsubtypes"), 0, NULL);
			if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
		} else {
			allowedSubTypes = zv::Val::null();
		}
		if (allowedSubTypes.isNull()) return zv::Val::null();
		if (UNEXPECTED(!zv::Ref(allowedSubTypes.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getAllowedSubTypes() must return ?array");
			return zv::Val();
		}
		uint32_t allowedSubTypesCount = zend_hash_num_elements(Z_ARRVAL_P(allowedSubTypes.raw()));
		zv::Arr subtractedSubTypes = zv::Arr::create(allowedSubTypesCount);

		/* Enum cases are finite values: FiniteTypeSet keys them by value
		 * identity, so a subtracted case is a lookup instead of an equals()
		 * sweep over every allowed case - quadratic in the enum size. Allowed
		 * subtypes it cannot key (a sealed class hierarchy) still take the
		 * sweep. Both working copies drop the allowed subtypes as they match. */
		zv::Arr allowedList = zv::Arr::create(allowedSubTypesCount);
		for (zv::ArrayEntry allowedEntry : zv::ArrRef(allowedSubTypes.raw())) {
			allowedList.push(allowedEntry.value());
		}
		zv::Val allowedListVal(std::move(allowedList));
		zv::Val allowedSet = pt_type_call_static(PT_CLASS_FINITE_TYPE_SET, PT_LC("create"), 1, allowedListVal.raw());
		if (UNEXPECTED(allowedSet.isUndef())) return zv::Val();
		zv::Arr keyed = zv::Arr::create(0);
		zv::Arr remaining = zv::Arr::create(0);
		if (Z_TYPE_P(allowedSet.raw()) == IS_OBJECT) {
			zv::Val members = pt_type_call(Z_OBJ_P(allowedSet.raw()), PT_LC("getmembers"), 0, NULL);
			if (UNEXPECTED(members.isUndef())) return zv::Val();
			zv::Val others = pt_type_call(Z_OBJ_P(allowedSet.raw()), PT_LC("getothers"), 0, NULL);
			if (UNEXPECTED(others.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(members.raw()) != IS_ARRAY || Z_TYPE_P(others.raw()) != IS_ARRAY)) {
				zend_type_error("phpstan_turbo: FiniteTypeSet::getMembers()/getOthers() must return arrays");
				return zv::Val();
			}
			keyed = zv::Arr::adoptTable(zend_array_dup(Z_ARRVAL_P(members.raw())));
			remaining = zv::Arr::adoptTable(zend_array_dup(Z_ARRVAL_P(others.raw())));
		} else {
			remaining = zv::Arr::adoptTable(zend_array_dup(Z_ARRVAL_P(allowedListVal.raw())));
		}

		for (zv::ArrayEntry subTypeEntry : zv::ArrRef(subtractedTypes)) {
			zv::Ref subType = subTypeEntry.value().deref();
			if (UNEXPECTED(!subType.isObject())) {
				zend_type_error("phpstan_turbo: a subtracted type must be %s", ptcls::type);
				return zv::Val();
			}
			zv::Val key = pt_type_call_static(PT_CLASS_FINITE_TYPE_SET, PT_LC("key"), 1, subType.raw());
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			if (Z_TYPE_P(key.raw()) == IS_STRING) {
				zval *keyedAllowed = zend_symtable_find(keyed.table(), Z_STR_P(key.raw()));
				if (keyedAllowed == NULL) return zv::Val::null();
				bool equal;
				if (UNEXPECTED(!pt_type_call_bool(subType.asObject(), PT_LC("equals"), 1, keyedAllowed, equal))) return zv::Val();
				if (!equal) return zv::Val::null();
				subtractedSubTypes.push(subType);
				zend_symtable_del(keyed.table(), Z_STR_P(key.raw()));
				continue;
			}
			bool matchedOne = false;
			for (zv::ArrayEntry allowedEntry : zv::TableRef(remaining.table())) {
				zv::Ref allowedSubType = allowedEntry.value().deref();
				bool equal;
				if (UNEXPECTED(!pt_type_call_bool(subType.asObject(), PT_LC("equals"), 1, allowedSubType.raw(), equal))) return zv::Val();
				if (equal) {
					// An allowed subtype is dropped as it matches, so no two matches
					// can be the same one and the matches need no keying.
					subtractedSubTypes.push(subType);
					if (allowedEntry.hasStringKey()) {
						zend_hash_del(remaining.table(), allowedEntry.stringKey());
					} else {
						zend_hash_index_del(remaining.table(), allowedEntry.indexKey());
					}
					matchedOne = true;
					break;
				}
			}
			if (!matchedOne) return zv::Val::null();
		}

		/* array_merge(array_values($keyed), array_values($others)) */
		if (keyed.arrRef().size() + remaining.arrRef().size() == 1) {
			for (zv::ArrayEntry entry : zv::TableRef(keyed.table())) {
				return zv::Val::copyOf(entry.value());
			}
			for (zv::ArrayEntry entry : zv::TableRef(remaining.table())) {
				return zv::Val::copyOf(entry.value());
			}
		}

		uint32_t subtractedSubTypesCount = subtractedSubTypes.arrRef().size();
		if (subtractedSubTypesCount == allowedSubTypesCount) return pt_type_new_never_type();

		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (subtractedSubTypesCount == 0) return create(name);

		if (subtractedSubTypesCount == 1) return create(name, subtractedSubTypes.arrRef().findIndex(0).raw());

		zv::Val unionType = pt_type_new_union(std::move(subtractedSubTypes));
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return create(name, unionType.raw());
	}

	/* $cb($this->subtractedType) — new self with the callback's result when
	 * it differs; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val newSubtracted;
		if (Z_TYPE_P(subtracted) != IS_NULL) {
			zval result;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtracted, &result))) return zv::Val();
			newSubtracted = zv::Val::adopt(result);
		} else {
			newSubtracted = zv::Val::null();
		}

		/* $subtractedType !== $this->subtractedType */
		if (!zend_is_identical(newSubtracted.raw(), subtracted)) {
			zend_string *name = className();
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (newSubtracted.isNull()) return create(name);
			bool isType;
			if (UNEXPECTED(!pt_type_instanceof(newSubtracted.raw(), PT_CLASS_TYPE, isType))) return zv::Val();
			if (UNEXPECTED(!isType)) {
				zend_type_error("%s::__construct(): Argument #2 ($subtractedType) must be of type ?%s, %s given", ZSTR_VAL(pt_ce_object_type->name), ptcls::type, zend_zval_value_name(newSubtracted.raw()));
				return zv::Val();
			}
			return create(name, newSubtracted.raw());
		}

		return thisValue();
	}

	/* $this without a subtracted type, new self($className) with one */
	zv::Val traverseSimultaneously() const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		if (Z_TYPE_P(subtracted) == IS_NULL) return thisValue();
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return create(name);
	}

	/* }}} */

	/* {{{ the reflections and ancestors */

	/* the reflection already known, or the provider's; null for an unknown
	 * class; UNDEF = pending exception */
	zv::Val getNakedClassReflection() const
	{
		zval *resolved = resolvedClassReflection();
		if (UNEXPECTED(resolved == NULL)) return zv::Val();
		if (Z_TYPE_P(resolved) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(resolved));
		return providerClass(NULL);
	}

	/* the reflection (fetched lazily and memoized), with its template
	 * types erased for a generic class; null for an unknown class; UNDEF =
	 * pending exception */
	zv::Val getClassReflection() const
	{
		zv::Val classReflection;
		zval *resolved = resolvedClassReflection();
		if (UNEXPECTED(resolved == NULL)) return zv::Val();
		if (Z_TYPE_P(resolved) == IS_OBJECT) {
			classReflection = zv::Val::copyOf(zv::Ref(resolved));
		} else {
			classReflection = providerClass(OBJ_PROP_NUM(self, slots::lazyClassReflection));
			if (UNEXPECTED(classReflection.isUndef()) || classReflection.isNull()) return classReflection;
		}
		if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: a class reflection must be an object");
			return zv::Val();
		}
		zend_object *reflection = Z_OBJ_P(classReflection.raw());

		bool isGeneric;
		if (UNEXPECTED(!pt_type_call_bool(reflection, PT_LC("isgeneric"), 0, NULL, isGeneric))) return zv::Val();
		if (isGeneric) {
			/* $classReflection->withTypes(array_values($classReflection->getTemplateTypeMap()->map(static fn (): Type => new ErrorType())->getTypes())) */
			zv::Val templateTypeMap = pt_type_call(reflection, PT_LC("gettemplatetypemap"), 0, NULL);
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			zv::Val callback = pt_ot_callback(PT_OTC_ERROR_TYPE, NULL, NULL, NULL);
			zv::Val mapped = pt_type_call(Z_OBJ_P(templateTypeMap.raw()), PT_LC("map"), 1, callback.raw());
			if (UNEXPECTED(mapped.isUndef())) return zv::Val();
			zv::Val types = pt_type_call(Z_OBJ_P(mapped.raw()), PT_LC("gettypes"), 0, NULL);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(types.raw()).isArray())) {
				zend_type_error("phpstan_turbo: TemplateTypeMap::getTypes() must return array");
				return zv::Val();
			}
			zv::Arr values = zv::Arr::create(zv::ArrRef(types.raw()).size());
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				values.push(entry.value());
			}
			return pt_type_call(reflection, PT_LC("withtypes"), 1, values.raw());
		}

		return classReflection;
	}

	/* $this->classReflection ?? $this->lazyClassReflection (borrowed;
	 * IS_NULL when neither is known; NULL with an Error pending when
	 * uninitialized) */
	[[nodiscard]] zval *resolvedClassReflection() const
	{
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return NULL;
		if (Z_TYPE_P(reflection) == IS_OBJECT) return reflection;
		return lazyClassReflection();
	}

	/* $this projected onto the ancestor $className: $this itself, the
	 * interface's or the parent's ancestor, memoized per instance and per
	 * description; null when unrelated; UNDEF = pending exception */
	zv::Val getAncestorWithClassName(zval *className) const
	{
		zend_string *thisName = this->className();
		if (UNEXPECTED(thisName == NULL)) return zv::Val();
		if (zend_is_identical(OBJ_PROP_NUM(self, slots::className), className)) return thisValue();

		zval *resolved = resolvedClassReflection();
		if (UNEXPECTED(resolved == NULL)) return zv::Val();
		if (Z_TYPE_P(resolved) == IS_OBJECT) {
			zv::Val resolvedName = pt_type_call(Z_OBJ_P(resolved), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(resolvedName.isUndef())) return zv::Val();
			if (zend_is_identical(className, resolvedName.raw())) return thisValue();
		}

		zend_string *classNameStr = Z_STR_P(className);
		zval *currentAncestors = OBJ_PROP_NUM(self, slots::currentAncestors);
		if (Z_TYPE_P(currentAncestors) == IS_ARRAY) {
			zval *current = zend_symtable_find(Z_ARRVAL_P(currentAncestors), classNameStr);
			if (current != NULL) return zv::Val::copyOf(zv::Ref(current));
		}

		zv::Val description = describeCache(self);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		zend_string *descriptionStr = zv::Ref(description.raw()).asString();
		if (UNEXPECTED(!touchDescriptionCacheKey(descriptionStr))) return zv::Val();
		zval *cached = pt_ot_cache_get2(pt_ot_ancestors(), descriptionStr, classNameStr);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return zv::Val();
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, className, hasClass))) return zv::Val();
		if (!hasClass) return storeAncestor(descriptionStr, classNameStr, zv::Val::null());
		zv::Val theirReflection = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, className);
		if (UNEXPECTED(theirReflection.isUndef())) return zv::Val();

		zv::Val thisReflection = thisGetClassReflection();
		if (UNEXPECTED(thisReflection.isUndef())) return zv::Val();
		if (thisReflection.isNull()) return storeAncestor(descriptionStr, classNameStr, zv::Val::null());
		zv::Val theirName = pt_type_call(Z_OBJ_P(theirReflection.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(theirName.isUndef())) return zv::Val();
		zv::Val thisReflectionName = pt_type_call(Z_OBJ_P(thisReflection.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(thisReflectionName.isUndef())) return zv::Val();
		if (zend_is_identical(theirName.raw(), thisReflectionName.raw())) return storeAncestor(descriptionStr, classNameStr, thisValue());

		zv::Val interfaces = getInterfaces();
		if (UNEXPECTED(interfaces.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(interfaces.raw())) {
			zv::Ref interface = entry.value().deref();
			if (UNEXPECTED(!interface.isObject())) {
				zend_type_error("phpstan_turbo: getInterfaces() must return objects");
				return zv::Val();
			}
			zend_object *interfaceObject = interface.asObject();
			zv::Val ancestor = callOn(interfaceObject, PT_LC("getancestorwithclassname"), otGetAncestorWithClassName, 1, className, [&]() { return ObjectType(interfaceObject).getAncestorWithClassName(className); });
			if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
			if (!ancestor.isNull()) return storeAncestor(descriptionStr, classNameStr, std::move(ancestor));
		}

		zv::Val parent = getParent();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		if (!parent.isNull()) {
			zend_object *parentObject = Z_OBJ_P(parent.raw());
			zv::Val ancestor = callOn(parentObject, PT_LC("getancestorwithclassname"), otGetAncestorWithClassName, 1, className, [&]() { return ObjectType(parentObject).getAncestorWithClassName(className); });
			if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
			if (!ancestor.isNull()) return storeAncestor(descriptionStr, classNameStr, std::move(ancestor));
		}

		return storeAncestor(descriptionStr, classNameStr, zv::Val::null());
	}

	/* the parent class's object type, memoized; null without one; UNDEF =
	 * pending exception */
	zv::Val getParent() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedParent);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));
		zv::Val thisReflection = thisGetClassReflection();
		if (UNEXPECTED(thisReflection.isUndef())) return zv::Val();
		if (thisReflection.isNull()) return zv::Val::null();

		zv::Val parentReflection = pt_type_call(Z_OBJ_P(thisReflection.raw()), PT_LC("getparentclass"), 0, NULL);
		if (UNEXPECTED(parentReflection.isUndef())) return zv::Val();
		if (parentReflection.isNull()) return zv::Val::null();

		zv::Val parent = pt_type_call(Z_OBJ_P(parentReflection.raw()), PT_LC("getobjecttype"), 0, NULL);
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(parent.raw()).instanceOf(pt_ce_object_type))) {
			zend_type_error("phpstan_turbo: getObjectType() must return %s", ZSTR_VAL(pt_ce_object_type->name));
			return zv::Val();
		}
		zv::ObjRef(self).propAtWrite(slots::cachedParent, zv::Val::copyOf(zv::Ref(parent.raw())));
		return parent;
	}

	/* the interfaces' object types (keyed as ClassReflection::getInterfaces()
	 * keys them), memoized; UNDEF = pending exception */
	zv::Val getInterfaces() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedInterfaces);
		if (Z_TYPE_P(cached) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(cached));
		zv::Val thisReflection = thisGetClassReflection();
		if (UNEXPECTED(thisReflection.isUndef())) return zv::Val();
		if (thisReflection.isNull()) {
			zv::ObjRef(self).propAtWrite(slots::cachedInterfaces, zv::Val(zv::Arr::empty()));
			return zv::Val(zv::Arr::empty());
		}

		zv::Val interfaceReflections = pt_type_call(Z_OBJ_P(thisReflection.raw()), PT_LC("getinterfaces"), 0, NULL);
		if (UNEXPECTED(interfaceReflections.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(interfaceReflections.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getInterfaces() must return array");
			return zv::Val();
		}
		zv::Arr interfaces = zv::Arr::create(zv::ArrRef(interfaceReflections.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(interfaceReflections.raw())) {
			zv::Ref interfaceReflection = entry.value().deref();
			if (UNEXPECTED(!interfaceReflection.isObject())) {
				zend_type_error("phpstan_turbo: getInterfaces() must return objects");
				return zv::Val();
			}
			zv::Val interface = pt_type_call(interfaceReflection.asObject(), PT_LC("getobjecttype"), 0, NULL);
			if (UNEXPECTED(interface.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(interface.raw()).instanceOf(pt_ce_object_type))) {
				zend_type_error("phpstan_turbo: getObjectType() must return %s", ZSTR_VAL(pt_ce_object_type->name));
				return zv::Val();
			}
			/* array_map() over one array keeps its keys */
			if (entry.hasStringKey()) {
				interfaces.set(entry.stringKey(), std::move(interface));
			} else {
				zval v = interface.take();
				interfaces.separate();
				zend_hash_index_update(interfaces.table(), entry.indexKey(), &v);
			}
		}
		zv::ObjRef(self).propAtWrite(slots::cachedInterfaces, zv::Val::copyOf(zv::Ref(interfaces.raw())));
		return zv::Val(std::move(interfaces));
	}

	/* }}} */

	/* {{{ tryRemove(), exponentiate(), toPhpDocNode(), hasTemplateOrLateResolvableType() */

	/* the rest of an EQUAL_UNION_CLASSES base class when one of its classes
	 * is removed, $this->subtract() for a subtype, null otherwise; UNDEF =
	 * pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (zv::Ref(typeToRemove).instanceOf(pt_ce_object_type)) {
			zend_class_entry *unionCe = pt_class(PT_CLASS_UNION_TYPE);
			if (UNEXPECTED(unionCe == NULL)) return zv::Val();
			zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&unionCe->constants_table, PT_LC("EQUAL_UNION_CLASSES"));
			if (UNEXPECTED(constant == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: %s::EQUAL_UNION_CLASSES not found", ZSTR_VAL(unionCe->name));
				return zv::Val();
			}
			if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, unionCe) != SUCCESS)) return zv::Val();
			if (UNEXPECTED(Z_TYPE(constant->value) != IS_ARRAY)) {
				zend_type_error("phpstan_turbo: %s::EQUAL_UNION_CLASSES must be an array", ZSTR_VAL(unionCe->name));
				return zv::Val();
			}
			for (zv::ArrayEntry baseEntry : zv::ArrRef(&constant->value)) {
				zv::Val thisName = thisGetClassName();
				if (UNEXPECTED(thisName.isUndef())) return zv::Val();
				zval baseClass;
				if (baseEntry.hasStringKey()) {
					ZVAL_STR(&baseClass, baseEntry.stringKey());
				} else {
					ZVAL_LONG(&baseClass, (zend_long) baseEntry.indexKey());
				}
				if (!zend_is_identical(thisName.raw(), &baseClass)) continue;
				zv::Ref classes = baseEntry.value().deref();
				if (UNEXPECTED(!classes.isArray())) {
					zend_type_error("phpstan_turbo: EQUAL_UNION_CLASSES must hold arrays");
					return zv::Val();
				}

				for (zv::ArrayEntry classEntry : zv::ArrRef(classes.raw())) {
					zv::Val removedName = pt_type_call(Z_OBJ_P(typeToRemove), PT_LC("getclassname"), 0, NULL);
					if (UNEXPECTED(removedName.isUndef())) return zv::Val();
					if (!zend_is_identical(removedName.raw(), classEntry.value().raw())) continue;
					/* unset($classes[$index]); TypeCombinator::union(...array_map(new ObjectType(...), $classes)) */
					zv::Arr objectTypes = zv::Arr::create(zv::ArrRef(classes.raw()).size());
					for (zv::ArrayEntry otherEntry : zv::ArrRef(classes.raw())) {
						if (otherEntry.value().raw() == classEntry.value().raw()) continue;
						zv::Ref objectClass = otherEntry.value().deref();
						zv::Str objectClassName = zv::Str::adopt(zval_get_string(objectClass.raw()));
						zv::Val objectType = create(objectClassName.get());
						if (UNEXPECTED(objectType.isUndef())) return zv::Val();
						objectTypes.push(std::move(objectType));
					}
					return pt_type_call_static_spread(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), objectTypes.table());
				}
			}
		}

		zv::Val isSuperType = thisCall(PT_LC("issupertypeof"), otIsSuperTypeOf, 1, typeToRemove, [&]() { return isSuperTypeOf(typeToRemove); });
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(isSuperType.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_YES) {
			return thisCall(PT_LC("subtract"), otSubtract, 1, typeToRemove, [&]() { return subtract(typeToRemove); });
		}

		/* A sealed hierarchy subtracts by set difference, so the members this
		 * type no longer holds (already subtracted) are no-ops and the rest
		 * come off in one subtraction - the same result as removing them one at
		 * a time, without rebuilding the subtracted union once per member. */
		zv::Val classReflection = thisGetClassReflection();
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zend_class_entry *unionTypeCe = pt_class(PT_CLASS_UNION_TYPE);
		if (UNEXPECTED(unionTypeCe == NULL)) return zv::Val();
		if (zv::Ref(typeToRemove).instanceOf(unionTypeCe) && !classReflection.isNull()) {
			zv::Val allowedSubTypes = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getallowedsubtypes"), 0, NULL);
			if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
			if (!allowedSubTypes.isNull()) {
				zv::Val members = pt_type_call(Z_OBJ_P(typeToRemove), PT_LC("gettypes"), 0, NULL);
				if (UNEXPECTED(members.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(members.raw()) != IS_ARRAY)) {
					zend_type_error("phpstan_turbo: UnionType::getTypes() must return an array");
					return zv::Val();
				}
				zv::Arr membersToRemove = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(members.raw())));
				for (zv::ArrayEntry memberEntry : zv::ArrRef(members.raw())) {
					zval *member = memberEntry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(member) != IS_OBJECT)) {
						zend_type_error("phpstan_turbo: a union member must be %s", ptcls::type);
						return zv::Val();
					}
					zv::Val isSuperTypeOfMember = thisCall(PT_LC("issupertypeof"), otIsSuperTypeOf, 1, member, [&]() { return isSuperTypeOf(member); });
					if (UNEXPECTED(isSuperTypeOfMember.isUndef())) return zv::Val();
					zend_long memberValue = pt_type_result_trinary(isSuperTypeOfMember.raw());
					if (UNEXPECTED(memberValue < 0)) return zv::Val();
					if (memberValue == PT_TRI_YES) {
						membersToRemove.push(zv::Ref(member));
						continue;
					}
					if (memberValue == PT_TRI_MAYBE) return zv::Val::null();
				}

				if (membersToRemove.arrRef().size() == 0) return thisValue();
				zv::Val toSubtract;
				if (membersToRemove.arrRef().size() == 1) {
					toSubtract = zv::Val::copyOf(membersToRemove.arrRef().findIndex(0));
				} else {
					toSubtract = pt_type_new_union(std::move(membersToRemove));
					if (UNEXPECTED(toSubtract.isUndef())) return zv::Val();
				}
				zval *toSubtractRaw = toSubtract.raw();
				return thisCall(PT_LC("subtract"), otSubtract, 1, toSubtractRaw, [&]() { return subtract(toSubtractRaw); });
			}
		}

		return zv::Val::null();
	}

	/* $this|$exponent when both may be objects, ErrorType otherwise; UNDEF
	 * = pending exception */
	zv::Val exponentiate(zval *exponent) const
	{
		zv::Val object = pt_type_new_object_without_class_type();
		if (UNEXPECTED(object.isUndef())) return zv::Val();
		if (!zv::Ref(exponent).instanceOf(pt_ce_never_type)) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			zend_long thisIsObject = isSuperTypeOfTrinary(object.raw(), &selfZv);
			if (UNEXPECTED(thisIsObject < 0)) return zv::Val();
			if (thisIsObject != PT_TRI_NO) {
				zend_long exponentIsObject = isSuperTypeOfTrinary(object.raw(), exponent);
				if (UNEXPECTED(exponentIsObject < 0)) return zv::Val();
				if (exponentIsObject != PT_TRI_NO) {
					zv::Args args{self, exponent};
					return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
				}
			}
		}
		return pt_type_new_error_type();
	}

	/* new IdentifierTypeNode($this->getClassName()); UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zv::Val name = thisGetClassName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	/* the subtracted type's answer, false without one; false with an
	 * exception pending on an uninitialized slot */
	bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(subtracted), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, out);
	}

	/* }}} */

private:
	zend_object *self;

	/* exactly an ObjectType, none of its methods overridden: $this-calls
	 * can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_object_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t slot, zv::Val value) const
	{
		zval *p = OBJ_PROP_NUM(self, slot);
		zv::ObjRef(self).propAtWrite(slot, std::move(value));
		Z_PROP_FLAG_P(p) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* {{{ $this-dispatch: through the object's class entry, straight to the
	 * C++ body when the object's method is the native one */

	template <typename Direct>
	zv::Val thisCall(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) const
	{
		return callOn(self, lcname, len, handler, argc, argv, direct);
	}

	template <typename Direct>
	zend_long thisCallTrinary(const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) const
	{
		return callOnTrinary(self, lcname, len, handler, argc, argv, direct);
	}

	/* $object->method(...$args) on another ObjectType, the same way */
	template <typename Direct>
	static zv::Val callOn(zend_object *object, const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) { return pt_this_call(object, object->ce == pt_ce_object_type, lcname, len, handler, argc, argv, direct); }

	template <typename Direct>
	static zend_long callOnTrinary(zend_object *object, const char *lcname, size_t len, zif_handler handler, uint32_t argc, zval *argv, Direct direct) { return pt_this_call_trinary(object, object->ce == pt_ce_object_type, lcname, len, handler, argc, argv, direct); }

	zv::Val thisGetClassName() const
	{
		return thisCall(PT_LC("getclassname"), otGetClassName, 0, NULL, [&]() {
			zend_string *name = className();
			if (UNEXPECTED(name == NULL)) return zv::Val();
			return zv::Val::string(name);
		});
	}

	zv::Val thisGetClassReflection() const { return thisCall(PT_LC("getclassreflection"), otGetClassReflection, 0, NULL, [&]() { return getClassReflection(); }); }
	zv::Val thisGetNakedClassReflection() const { return thisCall(PT_LC("getnakedclassreflection"), otGetNakedClassReflection, 0, NULL, [&]() { return getNakedClassReflection(); }); }
	zv::Val thisGetAncestorWithClassName(zval *className) const { return thisCall(PT_LC("getancestorwithclassname"), otGetAncestorWithClassName, 1, className, [&]() { return getAncestorWithClassName(className); }); }
	zv::Val thisGetEnumCases() const { return thisCall(PT_LC("getenumcases"), otGetEnumCases, 0, NULL, [&]() { return getEnumCases(); }); }
	zv::Val thisGetClassStringType() const { return thisCall(PT_LC("getclassstringtype"), otGetClassStringType, 0, NULL, [&]() { return getClassStringType(); }); }
	zv::Val thisChangeSubtractedType(zval *subtractedType) const { return thisCall(PT_LC("changesubtractedtype"), otChangeSubtractedType, 1, subtractedType, [&]() { return changeSubtractedType(subtractedType); }); }
	zend_long thisIsOffsetAccessible() const { return thisCallTrinary(PT_LC("isoffsetaccessible"), otIsOffsetAccessible, 0, NULL, [&]() { return isOffsetAccessible(); }); }
	zend_long thisIsInstanceOf(zval *className) const { return thisCallTrinary(PT_LC("isinstanceof"), otIsInstanceOf, 1, className, [&]() { return isInstanceOf(className); }); }

	zv::Val thisGetMethod(zval *methodName, zval *scope) const
	{
		zv::Args args{methodName, scope};
		return thisCall(PT_LC("getmethod"), otGetMethod, 2, args, [&]() { return getMethod(methodName, scope); });
	}

	/* $this->isInstanceOf('Literal'); -1 = pending exception */
	[[nodiscard]] zend_long thisIsInstanceOfLiteral(const char *name, size_t len) const
	{
		zv::Val nameZv = zv::Val::string(name, len);
		return thisIsInstanceOf(nameZv.raw());
	}

	/* $this->isInstanceOf(A)->yes() || $this->isInstanceOf(B)->yes() ...:
	 * yes when one is, no otherwise (short-circuiting like the twin); -1 =
	 * pending exception */
	zend_long isInstanceOfAny(std::initializer_list<const char *> names) const
	{
		for (const char *name : names) {
			zend_long is = thisIsInstanceOfLiteral(name, strlen(name));
			if (UNEXPECTED(is < 0)) return -1;
			if (is == PT_TRI_YES) return PT_TRI_YES;
		}
		return PT_TRI_NO;
	}

	/* }}} */

	/* {{{ calls into the reflection layer */

	/* $classReflection->is('Name'); false = pending exception */
	[[nodiscard]] static bool reflectionIs(zend_object *reflection, const char *name, size_t len, bool &out)
	{
		zv::Val nameZv = zv::Val::string(name, len);
		return pt_type_call_bool(reflection, PT_LC("is"), 1, nameZv.raw(), out);
	}

	/* ReflectionProviderStaticAccessor::getInstance() */
	static zv::Val reflectionProvider()
	{
		return pt_type_call_static(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
	}

	/* the provider's reflection of $this->className, null when it has no
	 * such class; memoized into *memo when given; UNDEF = pending exception */
	zv::Val providerClass(zval *memo) const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return zv::Val();
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, OBJ_PROP_NUM(self, slots::className), hasClass))) return zv::Val();
		if (!hasClass) return zv::Val::null();
		zv::Val classReflection = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclass"), 1, OBJ_PROP_NUM(self, slots::className));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (memo != NULL) {
			zv::Ref(memo).assign(zv::Val::copyOf(zv::Ref(classReflection.raw())));
		}
		return classReflection;
	}

	/* $a->isSuperTypeOf($b)'s trinary; -1 = pending exception */
	[[nodiscard]] static zend_long isSuperTypeOfTrinary(zval *a, zval *b)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(a), PT_LC("issupertypeof"), 1, b);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}

	/* RecursionGuard::run($this, <closure>); UNDEF = pending exception */
	zv::Val guarded(pt_ot_callback_kind kind, zval *object, zval *arg, zval *scope, zval *holderOut = NULL) const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val callback = pt_ot_callback(kind, object != NULL ? object : &selfZv, arg, scope, holderOut);
		if (UNEXPECTED(callback.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], &selfZv);
		ZVAL_COPY_VALUE(&args[1], callback.raw());
		return pt_type_call_static(PT_CLASS_RECURSION_GUARD, PT_LC("run"), 2, args);
	}

	/* RecursionGuard::run($this, fn (): Type => $this->getMethod($name, new OutOfClassScope())->getOnlyVariant()->getReturnType()[->getIterable*Type()]);
	 * UNDEF = pending exception */
	zv::Val guardedMethodReturnType(pt_ot_callback_kind kind, zval *methodName) const
	{
		return guarded(kind, NULL, methodName, NULL);
	}

public:
	/* $this->getMethod($name, new OutOfClassScope())->getOnlyVariant()->getReturnType();
	 * UNDEF = pending exception */
	zv::Val methodReturnType(zval *methodName) const
	{
		zv::Val scope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
		if (UNEXPECTED(scope.isUndef())) return zv::Val();
		zv::Val method = thisGetMethod(methodName, scope.raw());
		if (UNEXPECTED(method.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getMethod() must return an object");
			return zv::Val();
		}
		zv::Val variant = pt_type_call(Z_OBJ_P(method.raw()), PT_LC("getonlyvariant"), 0, NULL);
		if (UNEXPECTED(variant.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(variant.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getOnlyVariant() must return an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(variant.raw()), PT_LC("getreturntype"), 0, NULL);
	}

private:
	/* $type instanceof MixedType && !$type->isExplicitMixed(); false =
	 * pending exception */
	static bool isImplicitMixed(zval *type, bool &out)
	{
		if (!zv::Ref(type).instanceOf(pt_ce_mixed_type)) {
			out = false;
			return true;
		}
		bool explicitMixed;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(type), PT_LC("isexplicitmixed"), 0, NULL, explicitMixed))) return false;
		out = !explicitMixed;
		return true;
	}

	/* $array[0] — the engine's warning and null for a missing key; UNDEF =
	 * pending exception */
	static zv::Val firstClassName(zval *array)
	{
		zval *first = zend_hash_index_find(Z_ARRVAL_P(array), 0);
		if (first == NULL) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		return zv::Val::copyOf(zv::Ref(first).deref());
	}

	/* the scope's class name, 'no' outside a class — the member caches'
	 * access key; an owned string, UNDEF = pending exception */
	static zv::Val memberAccessKey(zval *scope)
	{
		bool inClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(scope), PT_LC("isinclass"), 0, NULL, inClass))) return zv::Val();
		if (!inClass) return zv::Val::string(PT_LC("no"));
		zv::Val scopeClass = pt_type_call(Z_OBJ_P(scope), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(scopeClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(scopeClass.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zv::Val name = pt_type_call(Z_OBJ_P(scopeClass.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		return zv::Val::adoptString(zval_get_string(name.raw()));
	}

	/* $member->getDeclaringClass()->getName(); UNDEF = pending exception */
	static zv::Val declaringClassNameOf(zval *member)
	{
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(member), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(declaringClass.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getDeclaringClass() must return an object");
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getname"), 0, NULL);
	}

	/* throw new ClassNotFoundException($this->className); always UNDEF */
	zv::Val throwClassNotFound() const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val exception = pt_type_new(PT_CLASS_CLASS_NOT_FOUND_EXCEPTION, 1, OBJ_PROP_NUM(self, slots::className));
		if (UNEXPECTED(exception.isUndef())) return zv::Val();
		zend_throw_exception_object(exception.raw());
		(void) exception.take(); /* thrown: the engine owns it now */
		return zv::Val();
	}

	/* self::$superTypes[$thisDescription][$description] = $result */
	static zv::Val storeSuperType(zend_string *thisDescription, zend_string *description, zv::Val result)
	{
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		pt_ot_cache_put2(pt_ot_super_types(), thisDescription, description, result.raw());
		return result;
	}

	/* self::$ancestors[$description][$className] = $this->currentAncestors[$className] = $ancestor */
	zv::Val storeAncestor(zend_string *description, zend_string *className, zv::Val ancestor) const
	{
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		zval *currentAncestors = OBJ_PROP_NUM(self, slots::currentAncestors);
		if (EXPECTED(Z_TYPE_P(currentAncestors) == IS_ARRAY)) {
			SEPARATE_ARRAY(currentAncestors);
			Z_TRY_ADDREF_P(ancestor.raw());
			zend_symtable_update(Z_ARRVAL_P(currentAncestors), className, ancestor.raw());
		}
		pt_ot_cache_put2(pt_ot_ancestors(), description, className, ancestor.raw());
		return ancestor;
	}

	/* the $transformResult callback of isSuperTypeOf(): the result, or
	 * ->and(maybe) when the subtracted type may cover the type; UNDEF =
	 * pending exception */
	static zv::Val transformed(bool andMaybe, zend_long value)
	{
		zv::Val result = pt_type_is_super_type_of_result(value);
		if (UNEXPECTED(result.isUndef()) || !andMaybe) return result;
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, maybe.raw());
	}

	/* $this->className . $this->describeSubtractedType($this->subtractedType, $level);
	 * UNDEF = pending exception */
	zv::Val preciseWithSubtracted(zval *level) const
	{
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Val subtractedDescription;
		if (EXPECTED(isExact() || pt_type_method_is(self, PT_LC("describesubtractedtype"), pt_type_trait_substractable_describe_subtracted_type))) {
			subtractedDescription = pt_type_describe_subtracted_type(subtracted, level);
		} else {
			zv::Args args{subtracted, level};
			subtractedDescription = pt_type_call(self, PT_LC("describesubtractedtype"), 2, args);
		}
		if (UNEXPECTED(subtractedDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(subtractedDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: describeSubtractedType() must return string");
			return zv::Val();
		}
		smart_str description = {NULL, 0};
		smart_str_append(&description, name);
		smart_str_append(&description, zv::Ref(subtractedDescription.raw()).asString());
		smart_str_0(&description);
		return zv::Val::adoptString(description.s);
	}

	/* the memoized precise class name ($this->cachedPreciseName): the
	 * provider's spelling, the class name as given for an unknown class;
	 * UNDEF = pending exception */
	zv::Val preciseName() const
	{
		zval *cached = OBJ_PROP_NUM(self, slots::cachedPreciseName);
		if (Z_TYPE_P(cached) == IS_STRING) return zv::Val::copyOf(zv::Ref(cached));
		zend_string *name = className();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val provider = reflectionProvider();
		if (UNEXPECTED(provider.isUndef())) return zv::Val();
		bool hasClass;
		if (UNEXPECTED(!pt_type_call_bool(Z_OBJ_P(provider.raw()), PT_LC("hasclass"), 1, OBJ_PROP_NUM(self, slots::className), hasClass))) return zv::Val();
		zv::Val precise;
		if (!hasClass) {
			precise = zv::Val::string(name);
		} else {
			zv::Val providerName = pt_type_call(Z_OBJ_P(provider.raw()), PT_LC("getclassname"), 1, OBJ_PROP_NUM(self, slots::className));
			if (UNEXPECTED(providerName.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(providerName.raw()).isString())) {
				zend_type_error("phpstan_turbo: getClassName() must return string");
				return zv::Val();
			}
			precise = std::move(providerName);
		}
		zv::ObjRef(self).propAtWrite(slots::cachedPreciseName, zv::Val::copyOf(zv::Ref(precise.raw())));
		return precise;
	}

	/* (string) $reflection->getNativeReflection()->getStartLine() appended;
	 * false = pending exception */
	[[nodiscard]] static bool appendStartLine(smart_str *description, zend_object *reflection)
	{
		zv::Val nativeReflection = pt_type_call(reflection, PT_LC("getnativereflection"), 0, NULL);
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(nativeReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getNativeReflection() must return an object");
			return false;
		}
		zv::Val startLine = pt_type_call(Z_OBJ_P(nativeReflection.raw()), PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(startLine.isUndef())) return false;
		zv::Str line = zv::Str::adopt(zval_get_string(startLine.raw()));
		smart_str_append(description, line.get());
		return true;
	}

	/* }}} */

	/* {{{ type factories */

	static zv::Val integerType()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	/* new ArrayType(new MixedType(), new MixedType()) */
	static zv::Val mixedArray()
	{
		zv::Val key = pt_type_new_mixed_type();
		zv::Val value = pt_type_new_mixed_type();
		if (UNEXPECTED(key.isUndef() || value.isUndef())) return zv::Val();
		zval arrayRaw;
		if (UNEXPECTED(!pt_array_type_new(&arrayRaw, key.raw(), value.raw()))) return zv::Val();
		return zv::Val::adopt(arrayRaw);
	}

	/* [new TrivialParametersAcceptor()] */
	static zv::Val trivialAcceptors()
	{
		zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
		if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
		zv::Arr acceptors = zv::Arr::create(1);
		acceptors.push(std::move(acceptor));
		return zv::Val(std::move(acceptors));
	}

	static bool pushNew(zv::Arr &types, int classIdx)
	{
		zv::Val type = pt_type_new(classIdx, 0, NULL);
		if (UNEXPECTED(type.isUndef())) return false;
		types.push(std::move(type));
		return true;
	}

	/* the same for a shadowing class, through its exported constructor */
	static bool pushNew(zv::Arr &types, bool (*construct)(zval *))
	{
		zval raw;
		if (UNEXPECTED(!construct(&raw))) return false;
		types.push(zv::Val::adopt(raw));
		return true;
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::ObjectType;
using phpstanturbo::NullableLong;

/* {{{ the exports (support.h / TypeTraits.h) */

bool pt_object_type_new(zval *out, zend_string *className, zval *subtractedType, zval *classReflection)
{
	return pt_val_into(ObjectType::create(className, subtractedType, classReflection), out);
}

zend_string *pt_object_type_class_name(zend_object *object) { return ObjectType::classNameOf(object); }
zval *pt_object_type_subtracted_type(zend_object *object) { return ObjectType::subtractedTypeOf(object); }
zval *pt_object_type_class_reflection(zend_object *object) { return ObjectType::classReflectionOf(object); }

void pt_object_type_construct(zend_object *self, zend_string *className, zval *subtractedType, zval *classReflection)
{
	ObjectType(self).construct(className, subtractedType, classReflection);
}

zv::Val pt_object_type_describe(zend_object *self, zval *level) { return ObjectType(self).describe(level); }
zv::Val pt_object_type_get_class_reflection(zend_object *self) { return ObjectType(self).getClassReflection(); }
bool pt_object_type_equals(zend_object *self, zval *type, bool &out) { return ObjectType(self).equals(type, out); }
zv::Val pt_object_type_get_referenced_classes(zend_object *self) { return ObjectType(self).getReferencedClasses(); }
zv::Val pt_object_type_is_super_type_of(zend_object *self, zval *type) { return ObjectType(self).isSuperTypeOf(type); }
zv::Val pt_object_type_get_unresolved_property_prototype(zend_object *self, zval *propertyName, zval *scope) { return ObjectType(self).getUnresolvedPropertyPrototype(phpstanturbo::PT_OT_PROPERTY, propertyName, scope); }
zv::Val pt_object_type_get_unresolved_instance_property_prototype(zend_object *self, zval *propertyName, zval *scope) { return ObjectType(self).getUnresolvedPropertyPrototype(phpstanturbo::PT_OT_INSTANCE_PROPERTY, propertyName, scope); }
zv::Val pt_object_type_get_unresolved_static_property_prototype(zend_object *self, zval *propertyName, zval *scope) { return ObjectType(self).getUnresolvedPropertyPrototype(phpstanturbo::PT_OT_STATIC_PROPERTY, propertyName, scope); }
zv::Val pt_object_type_get_unresolved_method_prototype(zend_object *self, zval *methodName, zval *scope) { return ObjectType(self).getUnresolvedMethodPrototype(methodName, scope); }
zv::Val pt_object_type_change_subtracted_type(zend_object *self, zval *subtractedType) { return ObjectType(self).changeSubtractedType(subtractedType); }
zv::Val pt_object_type_to_php_doc_node(zend_object *self) { return ObjectType(self).toPhpDocNode(); }

zv::Val pt_object_type_referenced_classes_callback(zval *type)
{
	return pt_ot_callback(PT_OTC_REFERENCED_CLASSES, type, NULL, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ObjectType(Z_OBJ_P(ZEND_THIS))

/* ObjectTypeCallback::__invoke(...$args): replays the captured call */
static void ZEND_FASTCALL objectTypeCallbackInvoke(INTERNAL_FUNCTION_PARAMETERS)
{
	if (UNEXPECTED(Z_TYPE_P(ZEND_THIS) != IS_OBJECT)) {
		zend_throw_error(NULL, "phpstan_turbo: ObjectTypeCallback called without its holder");
		RETURN_THROWS();
	}
	zend_object *holder = Z_OBJ_P(ZEND_THIS);
	zend_long kind = Z_LVAL_P(OBJ_PROP_NUM(holder, slots::subtractedType));
	zval *object = OBJ_PROP_NUM(holder, slots::cachedParent);
	zval *arg = OBJ_PROP_NUM(holder, slots::cachedInterfaces);
	zval *scope = OBJ_PROP_NUM(holder, slots::currentAncestors);
	zv::Args args{arg, scope};
	switch (kind) {
		case PT_OTC_HAS_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("hasproperty"), 1, arg));
		case PT_OTC_HAS_INSTANCE_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("hasinstanceproperty"), 1, arg));
		case PT_OTC_HAS_STATIC_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("hasstaticproperty"), 1, arg));
		case PT_OTC_GET_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("getproperty"), 2, args));
		case PT_OTC_GET_INSTANCE_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("getinstanceproperty"), 2, args));
		case PT_OTC_GET_STATIC_PROPERTY:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("getstaticproperty"), 1, arg));
		case PT_OTC_METHOD_RETURN_TYPE:
			PT_RETURN_VAL(ObjectType(Z_OBJ_P(object)).methodReturnType(arg));
		case PT_OTC_METHOD_RETURN_ITERABLE_KEY_TYPE:
		case PT_OTC_METHOD_RETURN_ITERABLE_VALUE_TYPE: {
			zv::Val returnType = ObjectType(Z_OBJ_P(object)).methodReturnType(arg);
			if (UNEXPECTED(returnType.isUndef())) RETURN_THROWS();
			if (UNEXPECTED(!zv::Ref(returnType.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getReturnType() must return %s", ptcls::type);
				RETURN_THROWS();
			}
			if (kind == PT_OTC_METHOD_RETURN_ITERABLE_KEY_TYPE) {
				PT_RETURN_VAL(pt_type_call(Z_OBJ_P(returnType.raw()), PT_LC("getiterablekeytype"), 0, NULL));
			}
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(returnType.raw()), PT_LC("getiterablevaluetype"), 0, NULL));
		}
		case PT_OTC_OFFSET_SET_PARAMETER_TYPE:
		case PT_OTC_OFFSET_SET_PARAMETER_TYPES: {
			/* $parameters = $this->getMethod('offsetSet', new OutOfClassScope())->getOnlyVariant()->getParameters() */
			zv::Val outOfClassScope = pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL);
			if (UNEXPECTED(outOfClassScope.isUndef())) RETURN_THROWS();
			zv::Val offsetSet = zv::Val::string(PT_LC("offsetSet"));
			zv::Args methodArgs{offsetSet.raw(), outOfClassScope.raw()};
			zend_object *type = Z_OBJ_P(object);
			zv::Val method = (type->ce == pt_ce_object_type || pt_type_method_is(type, PT_LC("getmethod"), otGetMethod))
				? ObjectType(type).getMethod(offsetSet.raw(), outOfClassScope.raw())
				: pt_type_call(type, PT_LC("getmethod"), 2, methodArgs);
			if (UNEXPECTED(method.isUndef())) RETURN_THROWS();
			if (UNEXPECTED(!zv::Ref(method.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getMethod() must return an object");
				RETURN_THROWS();
			}
			zv::Val variant = pt_type_call(Z_OBJ_P(method.raw()), PT_LC("getonlyvariant"), 0, NULL);
			if (UNEXPECTED(variant.isUndef())) RETURN_THROWS();
			if (UNEXPECTED(!zv::Ref(variant.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getOnlyVariant() must return an object");
				RETURN_THROWS();
			}
			zv::Val parameters = pt_type_call(Z_OBJ_P(variant.raw()), PT_LC("getparameters"), 0, NULL);
			if (UNEXPECTED(parameters.isUndef())) RETURN_THROWS();
			if (UNEXPECTED(!zv::Ref(parameters.raw()).isArray())) {
				zend_type_error("phpstan_turbo: getParameters() must return array");
				RETURN_THROWS();
			}
			if (zv::ArrRef(parameters.raw()).size() < 2) {
				/* throw new ShouldNotHappenException(sprintf('Method %s::%s() has less than 2 parameters.', $this->className, 'offsetSet')) */
				zend_string *className = ObjectType::classNameOf(type);
				if (UNEXPECTED(className == NULL)) RETURN_THROWS();
				zv::Val message = zv::Val::adoptString(zend_strpprintf(0, "Method %s::%s() has less than 2 parameters.", ZSTR_VAL(className), "offsetSet"));
				zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, message.raw());
				if (UNEXPECTED(exception.isUndef())) RETURN_THROWS();
				zend_throw_exception_object(exception.raw());
				(void) exception.take(); /* thrown: the engine owns it now */
				RETURN_THROWS();
			}
			zval *offsetParameter = zend_hash_index_find(Z_ARRVAL_P(parameters.raw()), 0);
			zval *valueParameter = zend_hash_index_find(Z_ARRVAL_P(parameters.raw()), 1);
			if (UNEXPECTED(offsetParameter == NULL || valueParameter == NULL || Z_TYPE_P(offsetParameter) != IS_OBJECT || Z_TYPE_P(valueParameter) != IS_OBJECT)) {
				zend_type_error("phpstan_turbo: getParameters() must return a list of objects");
				RETURN_THROWS();
			}
			if (kind == PT_OTC_OFFSET_SET_PARAMETER_TYPES) {
				zv::Val acceptedValueType = pt_type_call(Z_OBJ_P(valueParameter), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(acceptedValueType.isUndef())) RETURN_THROWS();
				zv::ObjRef(holder).propAtWrite(slots::cachedDescription, std::move(acceptedValueType));
			}
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(offsetParameter), PT_LC("gettype"), 0, NULL));
		}
		case PT_OTC_FIND_CALLABLE_PARAMETERS_ACCEPTORS:
			PT_RETURN_VAL(ObjectType(Z_OBJ_P(object)).findCallableParametersAcceptors());
		case PT_OTC_REFERENCED_CLASSES:
			PT_RETURN_VAL(pt_type_call(Z_OBJ_P(object), PT_LC("getreferencedclasses"), 0, NULL));
		case PT_OTC_ERROR_TYPE:
			PT_RETURN_VAL(pt_type_new_error_type());
		default:
			zend_throw_error(NULL, "phpstan_turbo: unknown ObjectTypeCallback kind");
			RETURN_THROWS();
	}
}

/* ObjectTypeCallback::identity(Type $type): Type */
static void ZEND_FASTCALL objectTypeCallbackIdentity(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	RETURN_COPY(type);
}

/* the trivial bodies the twin repeats */

static void ZEND_FASTCALL otEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL otNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL otYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL otThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL otError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

/* (string $name) → TrinaryLogic */
static void pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAMETERS, zend_long (ObjectType::*method)(zval *) const)
{
	zend_string *name;
	if (!zp::parse<zp::Str>(execute_data, name)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)(&nameZv));
}

/* (string $name, ClassMemberAccessAnswerer $scope) → the transformed member */
static void pt_ot_member(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ObjectType::*method)(zval *, zval *) const)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL((PT_THIS.*method)(&nameZv, scope));
}

/* (string $name, ClassMemberAccessAnswerer $scope) → the unresolved property prototype */
static void pt_ot_property_prototype(INTERNAL_FUNCTION_PARAMETERS, phpstanturbo::ObjectTypePropertyKind kind)
{
	zend_string *name;
	zval *scope;
	if (!zp::parse<zp::Str, zp::Obj>(execute_data, name, scope)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	PT_RETURN_VAL(PT_THIS.getUnresolvedPropertyPrototype(kind, &nameZv, scope));
}

/* the no-argument methods returning a TrinaryLogic */
static void pt_ot_trinary(INTERNAL_FUNCTION_PARAMETERS, zend_long (ObjectType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW((PT_THIS.*method)());
}

/* the no-argument methods returning a value */
static void pt_ot_value(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ObjectType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

/* (Type $type) → a value */
static void pt_ot_value_of_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ObjectType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

static void ZEND_FASTCALL otGetClassName(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zend_string *name = PT_THIS.className();
	if (UNEXPECTED(name == NULL)) RETURN_THROWS();
	RETURN_STR_COPY(name);
}

static void ZEND_FASTCALL otGetClassReflection(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getClassReflection); }
static void ZEND_FASTCALL otGetNakedClassReflection(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getNakedClassReflection); }

static void ZEND_FASTCALL otGetAncestorWithClassName(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *className;
	if (!zp::parse<zp::Str>(execute_data, className)) RETURN_THROWS();
	zval nameZv;
	ZVAL_STR(&nameZv, className);
	PT_RETURN_VAL(PT_THIS.getAncestorWithClassName(&nameZv));
}

static void ZEND_FASTCALL otGetEnumCases(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getEnumCases); }

static void ZEND_FASTCALL otDescribeAdditionalCacheKey(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_STRING();
}

static void ZEND_FASTCALL otGetClassStringType(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getClassStringType); }
static void ZEND_FASTCALL otToNumber(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toNumber); }
static void ZEND_FASTCALL otToString(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toString); }
static void ZEND_FASTCALL otGetMethod(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getMethod); }
static void ZEND_FASTCALL otHasMethod(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::hasMethod); }

static void ZEND_FASTCALL otGetTemplateType(INTERNAL_FUNCTION_PARAMETERS)
{
	zend_string *ancestorClassName, *templateTypeName;
	if (!zp::parse<zp::Str, zp::Str>(execute_data, ancestorClassName, templateTypeName)) RETURN_THROWS();
	zval ancestorZv, templateZv;
	ZVAL_STR(&ancestorZv, ancestorClassName);
	ZVAL_STR(&templateZv, templateTypeName);
	PT_RETURN_VAL(PT_THIS.getTemplateType(&ancestorZv, &templateZv));
}

static void ZEND_FASTCALL otIsInstanceOf(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isInstanceOf); }
static void ZEND_FASTCALL otGetIterableKeyType(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getIterableKeyType); }
static void ZEND_FASTCALL otGetIterableValueType(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getIterableValueType); }
static void ZEND_FASTCALL otIsOffsetAccessible(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isOffsetAccessible); }

static void ZEND_FASTCALL otChangeSubtractedType(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *subtractedType;
	if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
	zval nullZv;
	if (subtractedType == NULL) {
		ZVAL_NULL(&nullZv);
		subtractedType = &nullZv;
	}
	PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
}

static void ZEND_FASTCALL otIsSuperTypeOf(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isSuperTypeOf); }
static void ZEND_FASTCALL otSubtract(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::subtract); }
static void ZEND_FASTCALL otHasProperty(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::hasProperty); }
static void ZEND_FASTCALL otHasInstanceProperty(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::hasInstanceProperty); }
static void ZEND_FASTCALL otHasStaticProperty(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::hasStaticProperty); }
static void ZEND_FASTCALL otGetUnresolvedPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_property_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, phpstanturbo::PT_OT_PROPERTY); }
static void ZEND_FASTCALL otGetUnresolvedInstancePropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_property_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, phpstanturbo::PT_OT_INSTANCE_PROPERTY); }
static void ZEND_FASTCALL otGetUnresolvedStaticPropertyPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_property_prototype(INTERNAL_FUNCTION_PARAM_PASSTHRU, phpstanturbo::PT_OT_STATIC_PROPERTY); }
static void ZEND_FASTCALL otGetUnresolvedMethodPrototype(INTERNAL_FUNCTION_PARAMETERS) { pt_ot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getUnresolvedMethodPrototype); }

void pt_register_object_type()
{
	/* the callback holder: registered under a builder name other than
	 * `cls` on purpose — the side-by-side parity scan pairs
	 * `cls.method(...)` lines with the twin's methods, and the holder's
	 * methods have none */
	reg::Class holder("PHPStanTurbo\\ObjectTypeCallback");
	holder.privateLongProperty("kind", 0);
	holder.privateNullProperty("object");
	holder.privateNullProperty("arg");
	holder.privateNullProperty("scope");
	holder.privateNullProperty("out");
	holder.method("__invoke", reg::Public, 0, { reg::variadicObj("args", "mixed") }, objectTypeCallbackInvoke);
	holder.method("identity", reg::PublicStatic, 1, { reg::obj("type", ptcls::type) }, objectTypeCallbackIdentity, &ptret::type);
	pt_ce_object_type_callback = holder.register_();
	pt_ce_object_type_callback->ce_flags |= ZEND_ACC_FINAL;
	pt_object_type_callback_invoke = (zend_function *) zend_hash_str_find_ptr(&pt_ce_object_type_callback->function_table, PT_LC("__invoke"));
	pt_object_type_callback_identity = (zend_function *) zend_hash_str_find_ptr(&pt_ce_object_type_callback->function_table, PT_LC("identity"));
	ZEND_ASSERT(pt_object_type_callback_invoke != NULL && pt_object_type_callback_identity != NULL);

	reg::Class cls("PHPStan\\Type\\ObjectType");
	ptdecl::ObjectType::declareClass(cls);
	/* the twin's declaration order defines the PT_OT_PROP_* slots */
	cls.privateClassConstantLong("DESCRIPTION_CACHE_LIMIT", PT_OT_DESCRIPTION_CACHE_LIMIT);
	cls.privateTypedClassProperty("subtractedType", "PHPStan\\Type\\Type", true);
	/* the static caches, in the twin's declaration order too */
	cls.privateStaticTypedArrayPropertyDefaultEmpty("superTypes");
	cls.privateTypedClassPropertyDefaultNull("cachedParent", "PHPStan\\Type\\ObjectType");
	cls.privateTypedPropertyDefaultNull("cachedInterfaces", MAY_BE_ARRAY);
	cls.privateStaticTypedArrayPropertyDefaultEmpty("methods");
	cls.privateStaticTypedArrayPropertyDefaultEmpty("properties");
	cls.privateStaticTypedArrayPropertyDefaultEmpty("instanceProperties");
	cls.privateStaticTypedArrayPropertyDefaultEmpty("staticProperties");
	cls.privateStaticTypedArrayPropertyDefaultEmpty("ancestors");
	cls.privateTypedArrayPropertyDefaultEmpty("currentAncestors");
	cls.privateTypedPropertyDefaultNull("cachedDescription", MAY_BE_STRING);
	cls.privateTypedPropertyDefaultNull("cachedPreciseName", MAY_BE_STRING);
	cls.privateTypedClassPropertyDefaultNull("lazyClassReflection", "PHPStan\\Reflection\\ClassReflection");
	cls.privateStaticTypedArrayPropertyDefaultEmpty("enumCases");
	cls.privateStaticTypedClassPropertyDefaultNull("descriptionCacheOrder", "PHPStan\\Internal\\LruCache");
	cls.privateStaticTypedPropertyDefaultNull("lastTouchedDescription", MAY_BE_STRING);
	cls.privateTypedArrayPropertyDefaultEmpty("methodCache");
	cls.privateTypedProperty("className", MAY_BE_STRING);
	/* a promoted parameter's default never reaches the property without
	 * the constructor: uninitialized, like the twin's */
	cls.privateTypedClassProperty("classReflection", "PHPStan\\Reflection\\ClassReflection", true);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className;
		zval *subtractedType = NULL, *classReflection = NULL;
		if (!zp::parse<zp::Str, zp::Opt<zp::ObjOrNull>, zp::Opt<zp::ObjOrNull>>(execute_data, className, subtractedType, classReflection)) RETURN_THROWS();
		PT_THIS.construct(className, subtractedType, classReflection);
	});

	cls.method("resetCaches", reg::PublicStatic, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ObjectType::resetCaches();
	});

	cls.method(sigs::getClassName, otGetClassName);
	cls.method(sigs::hasProperty, otHasProperty);
	cls.method(sigs::getProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getProperty);
	});
	cls.method(sigs::getUnresolvedPropertyPrototype, otGetUnresolvedPropertyPrototype);
	cls.method(sigs::hasInstanceProperty, otHasInstanceProperty);
	cls.method(sigs::getInstanceProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getInstanceProperty);
	});
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, otGetUnresolvedInstancePropertyPrototype);
	cls.method(sigs::hasStaticProperty, otHasStaticProperty);
	cls.method(sigs::getStaticProperty, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_member(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getStaticProperty);
	});
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, otGetUnresolvedStaticPropertyPrototype);

	cls.method(sigs::getReferencedClasses, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getReferencedClasses);
	});
	cls.method(sigs::getObjectClassNames, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getObjectClassNames);
	});
	cls.method(sigs::getObjectClassReflections, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getObjectClassReflections);
	});

	cls.method<&ObjectType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, otIsSuperTypeOf);

	cls.method<&ObjectType::equals, zp::Obj>(sigs::equals);

	cls.method<&ObjectType::describe, zp::Obj>(sigs::describe);

	cls.method(sigs::describeAdditionalCacheKey, otDescribeAdditionalCacheKey);

	cls.method(sigs::toNumber, otToNumber);
	cls.method(sigs::toBitwiseNotType, otError0);
	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toGetClassResultType);
	});
	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toClassConstantType);
	});
	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toObjectTypeForInstanceofCheck);
	});
	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(ObjectType::toObjectTypeForIsACheck(allowString));
	});
	cls.method(sigs::toAbsoluteNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toAbsoluteNumber);
	});
	cls.method(sigs::toInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toInteger);
	});
	cls.method(sigs::toFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toFloat);
	});
	cls.method(sigs::toString, otToString);
	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toArray);
	});
	cls.method(sigs::toArrayKey, otError0);
	cls.method<&ObjectType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);
	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toBoolean);
	});

	cls.method(sigs::isObject, otYes0);
	cls.method(sigs::getClassStringType, otGetClassStringType);
	cls.method(sigs::isEnum, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isEnum);
	});
	cls.method(sigs::canAccessProperties, otYes0);
	cls.method(sigs::canCallMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::canCallMethods);
	});
	cls.method(sigs::hasMethod, otHasMethod);
	cls.method(sigs::getMethod, otGetMethod);
	cls.method(sigs::getUnresolvedMethodPrototype, otGetUnresolvedMethodPrototype);
	cls.method(sigs::canAccessConstants, otYes0);
	cls.method(sigs::hasConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary_of_name(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::hasConstant);
	});
	cls.method(sigs::getConstant, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *constantName;
		if (!zp::parse<zp::Str>(execute_data, constantName)) RETURN_THROWS();
		zval nameZv;
		ZVAL_STR(&nameZv, constantName);
		PT_RETURN_VAL(PT_THIS.getConstant(&nameZv));
	});
	cls.method(sigs::getTemplateType, otGetTemplateType);
	cls.method(sigs::getConstantStrings, otEmptyArray0);

	cls.method(sigs::isIterable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isIterable);
	});
	cls.method(sigs::isIterableAtLeastOnce, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isIterableAtLeastOnce);
	});
	cls.method(sigs::getArraySize, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getArraySize);
	});
	cls.method(sigs::getIterableKeyType, otGetIterableKeyType);
	cls.method(sigs::getFirstIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::thisGetIterableKeyType);
	});
	cls.method(sigs::getLastIterableKeyType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::thisGetIterableKeyType);
	});
	cls.method(sigs::getIterableValueType, otGetIterableValueType);
	cls.method(sigs::getFirstIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::thisGetIterableValueType);
	});
	cls.method(sigs::getLastIterableValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::thisGetIterableValueType);
	});

	cls.method(sigs::isNull, otNo0);
	cls.method(sigs::isConstantValue, otNo0);
	cls.method(sigs::isConstantScalarValue, otNo0);
	cls.method(sigs::getConstantScalarTypes, otEmptyArray0);
	cls.method(sigs::getConstantScalarValues, otEmptyArray0);
	cls.method(sigs::isTrue, otNo0);
	cls.method(sigs::isFalse, otNo0);
	cls.method(sigs::isBoolean, otNo0);
	cls.method(sigs::isFloat, otNo0);
	cls.method(sigs::isInteger, otNo0);
	cls.method(sigs::isString, otNo0);
	cls.method(sigs::isNumericString, otNo0);
	cls.method(sigs::isDecimalIntegerString, otNo0);
	cls.method(sigs::isNonEmptyString, otNo0);
	cls.method(sigs::isNonFalsyString, otNo0);
	cls.method(sigs::isLiteralString, otNo0);
	cls.method(sigs::isLowercaseString, otNo0);
	cls.method(sigs::isClassString, otNo0);
	cls.method(sigs::isUppercaseString, otNo0);
	cls.method(sigs::getClassStringObjectType, otError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, otThis0);
	cls.method(sigs::isVoid, otNo0);
	cls.method(sigs::isScalar, otNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(ObjectType::looseCompare(type));
	});

	cls.method(sigs::isOffsetAccessible, otIsOffsetAccessible);
	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isOffsetAccessLegal);
	});
	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});
	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getOffsetValueType);
	});
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType));
	});
	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.thisUnlessNotOffsetAccessible());
	});
	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.thisUnlessNotOffsetAccessible());
	});

	cls.method(sigs::getEnumCases, otGetEnumCases);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getEnumCaseObject);
	});

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_trinary(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::isCallable);
	});
	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.getCallableParametersAcceptors());
	});
	cls.method(sigs::isCloneable, otYes0);
	cls.method(sigs::isInstanceOf, otIsInstanceOf);

	cls.method(sigs::subtract, otSubtract);
	cls.method(sigs::getTypeWithoutSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getTypeWithoutSubtractedType);
	});
	cls.method(sigs::withoutFinalByKeywordOverride, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::withoutFinalByKeywordOverride);
	});
	cls.method(sigs::changeSubtractedType, otChangeSubtractedType);
	cls.method(sigs::getSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *subtracted = PT_THIS.subtractedType();
		if (UNEXPECTED(subtracted == NULL)) RETURN_THROWS();
		RETURN_COPY(subtracted);
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method(sigs::getNakedClassReflection, otGetNakedClassReflection);
	cls.method(sigs::getClassReflection, otGetClassReflection);
	cls.method(sigs::getAncestorWithClassName, otGetAncestorWithClassName);

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::tryRemove);
	});
	cls.method(sigs::getFiniteTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::getFiniteTypes);
	});
	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value_of_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::exponentiate);
	});
	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ot_value(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ObjectType::toPhpDocNode);
	});
	cls.method<&ObjectType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::ObjectType::registerTraits(cls);

	cls.shadow(&pt_ce_object_type);
}

/* }}} */
