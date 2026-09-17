/*
 * Native readers of the BetterReflection adapters' answers.
 *
 * PHPStan hands its reflection classes the vendored adapters
 * (PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass /
 * ReflectionEnum / ReflectionMethod / ReflectionProperty /
 * ReflectionNamedType / ReflectionParameter), each a final wrapper whose
 * getters delegate to the wrapped BetterReflection object — two or more
 * userland frames around a property read. The classes stay PHP; these
 * readers answer from the wrapped object's immutable properties and filled
 * memos exactly as the adapter method would:
 *
 * - only an adapter of exactly its (final) class entry, wrapping an object
 *   of exactly the BetterReflection class these readers know (ReflectionClass
 *   or ReflectionEnum, which overrides none of the getters read here;
 *   ReflectionMethod; ReflectionProperty; ReflectionNamedType;
 *   ReflectionParameter), takes a fast path;
 * - a value is read only where the adapter's answer is a property (or a
 *   trivial expression over properties, spelled next to it) and the property
 *   is initialized; a memo only once filled;
 * - anything else calls the adapter's method, which computes, memoizes and
 *   raises every error the twin raises. Where the adapter's body is exactly a
 *   delegation, a fast-path object whose memo is still empty calls the
 *   wrapped object's method instead (the same call, one frame fewer).
 *
 * The class entries are resolved through the class map without autoloading
 * (an object of an undeclared class cannot exist; a class declared later in
 * the request is retried once the class table grew) and the property offsets
 * once per class entry per request.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

#include <vector>

namespace {

/* {{{ the class entries and property offsets */

/* core reflection modifier bits the BetterReflection getters test */
constexpr zend_long PT_BRA_IS_PUBLIC = 1;
constexpr zend_long PT_BRA_IS_PROTECTED = 2;
constexpr zend_long PT_BRA_IS_PRIVATE = 4;
constexpr zend_long PT_BRA_IS_STATIC = 16;
constexpr zend_long PT_BRA_IS_FINAL = 32;
/* ReflectionMethod::IS_ABSTRACT, ReflectionClass::IS_EXPLICIT_ABSTRACT and
 * Adapter\ReflectionProperty::IS_ABSTRACT_COMPATIBILITY */
constexpr zend_long PT_BRA_IS_ABSTRACT = 64;
/* Adapter\ReflectionProperty::IS_READONLY_COMPATIBILITY */
constexpr zend_long PT_BRA_PROPERTY_IS_READONLY = 128;
/* Adapter\ReflectionProperty::IS_PROTECTED_SET_COMPATIBILITY / IS_PRIVATE_SET_COMPATIBILITY */
constexpr zend_long PT_BRA_IS_PROTECTED_SET = 2048;
constexpr zend_long PT_BRA_IS_PRIVATE_SET = 4096;
/* Adapter\ReflectionClass::IS_READONLY_COMPATIBILITY */
constexpr zend_long PT_BRA_CLASS_IS_READONLY = 65536;

constexpr uint32_t PT_BRA_MAX_OFFSETS = 20;

/* one class the readers know: its entry (NULL while undeclared or when it
 * lacks a property the readers read) and its property offsets, resolved once
 * per request */
struct KnownClass
{
	int classIdx;
	const char *const *names;
	uint32_t count;
	uint32_t generation;
	uint32_t classCount; /* EG(class_table) size when an undeclared class was looked up */
	bool unusable;       /* declared without a property the readers read */
	zend_class_entry *ce;
	uint32_t offsets[PT_BRA_MAX_OFFSETS];
};

zend_never_inline bool resolveKnownClass(KnownClass &known, zend_class_entry *candidate)
{
	uint32_t classCount = zend_hash_num_elements(EG(class_table));
	if (known.generation == pt_engine_generation) {
		/* resolved this request: only an undeclared class is retried, and only
		 * once more classes exist */
		if (known.ce != NULL || known.unusable || known.classCount == classCount) return known.ce == candidate && known.ce != NULL;
	}
	known.generation = pt_engine_generation;
	known.classCount = classCount;
	known.unusable = false;
	known.ce = NULL;
	zend_class_entry *ce = pt_class_loaded(known.classIdx);
	if (ce == NULL) {
		if (UNEXPECTED(EG(exception) != NULL)) {
			/* an unconfigured class map: every call takes the methods (which
			 * meet the pending exception) */
			known.unusable = true;
		}
		return false;
	}
	for (uint32_t i = 0; i < known.count; i++) {
		int32_t offset = pt_instance_prop_offset(ce, known.names[i], strlen(known.names[i]));
		if (UNEXPECTED(offset < 0)) {
			known.unusable = true;
			return false;
		}
		known.offsets[i] = (uint32_t) offset;
	}
	known.ce = ce;
	return ce == candidate;
}

/* whether the class entry is exactly the known class */
inline bool isKnown(KnownClass &known, zend_class_entry *ce)
{
	if (EXPECTED(known.ce == ce && known.generation == pt_engine_generation && ce != NULL)) return true;
	return resolveKnownClass(known, ce);
}

/* the initialized property at the known offset (dereferenced), NULL when
 * never written */
inline zval *propertyAt(zend_object *object, const KnownClass &known, uint32_t index)
{
	zval *value = OBJ_PROP(object, known.offsets[index]);
	ZVAL_DEREF(value);
	return EXPECTED(Z_TYPE_P(value) != IS_UNDEF) ? value : NULL;
}

/* Adapter\ReflectionClass: $betterReflectionClass */
const char *const pt_bra_class_adapter_names[] = { "betterReflectionClass" };
KnownClass pt_bra_class_adapter = { PT_CLASS_ADAPTER_REFLECTION_CLASS, pt_bra_class_adapter_names, 1, 0, 0, false, NULL, {} };

/* Adapter\ReflectionEnum: $betterReflectionEnum */
const char *const pt_bra_enum_adapter_names[] = { "betterReflectionEnum" };
KnownClass pt_bra_enum_adapter = { PT_CLASS_REFLECTION_ENUM, pt_bra_enum_adapter_names, 1, 0, 0, false, NULL, {} };

/* ReflectionClass (the offsets serve ReflectionEnum, which inherits them) */
enum : uint32_t
{
	PT_BRA_CLASS_NAME = 0,
	PT_BRA_CLASS_CACHED_NAME,
	PT_BRA_CLASS_NAMESPACE,
	PT_BRA_CLASS_SHORT_NAME,
	PT_BRA_CLASS_IS_INTERFACE,
	PT_BRA_CLASS_IS_TRAIT,
	PT_BRA_CLASS_IS_ENUM,
	PT_BRA_CLASS_MODIFIERS,
	PT_BRA_CLASS_START_LINE,
	PT_BRA_CLASS_DOC_COMMENT,
	PT_BRA_CLASS_LOCATED_SOURCE,
	PT_BRA_CLASS_CACHED_INTERFACE_NAMES,
	PT_BRA_CLASS_CACHED_TRAITS,
	PT_BRA_CLASS_CACHED_CONSTRUCTOR,
	PT_BRA_CLASS_CACHED_METHODS,
	PT_BRA_CLASS_CACHED_PROPERTIES,
	PT_BRA_CLASS_CACHED_INTERFACES,
	PT_BRA_CLASS_CACHED_CONSTANTS,
	PT_BRA_CLASS_SLOT_COUNT,
};
const char *const pt_bra_class_names[PT_BRA_CLASS_SLOT_COUNT] = {
	"name", "cachedName", "namespace", "shortName", "isInterface", "isTrait", "isEnum", "modifiers", "startLine",
	"docComment", "locatedSource", "cachedInterfaceNames", "cachedTraits", "cachedConstructor", "cachedMethods", "cachedProperties",
	"cachedInterfaces", "cachedConstants",
};
KnownClass pt_bra_class = { PT_CLASS_BETTER_REFLECTION_CLASS, pt_bra_class_names, PT_BRA_CLASS_SLOT_COUNT, 0, 0, false, NULL, {} };
const char *const pt_bra_no_names[] = { NULL };
KnownClass pt_bra_enum = { PT_CLASS_BETTER_REFLECTION_ENUM, pt_bra_no_names, 0, 0, 0, false, NULL, {} };

/* Adapter\ReflectionMethod: $betterReflectionMethod */
const char *const pt_bra_method_adapter_names[] = { "betterReflectionMethod" };
KnownClass pt_bra_method_adapter = { PT_CLASS_ADAPTER_REFLECTION_METHOD, pt_bra_method_adapter_names, 1, 0, 0, false, NULL, {} };

/* ReflectionMethod (with the ReflectionFunctionAbstract trait's properties) */
enum : uint32_t
{
	PT_BRA_METHOD_NAME = 0,
	PT_BRA_METHOD_ALIAS_NAME,
	PT_BRA_METHOD_MODIFIERS,
	PT_BRA_METHOD_DECLARING_CLASS,
	PT_BRA_METHOD_DOC_COMMENT,
	PT_BRA_METHOD_LOCATED_SOURCE,
	PT_BRA_METHOD_RETURNS_REFERENCE,
	PT_BRA_METHOD_IS_VARIADIC,
	PT_BRA_METHOD_IMPLEMENTING_CLASS,
	PT_BRA_METHOD_PARAMETERS,
	PT_BRA_METHOD_SLOT_COUNT,
};
const char *const pt_bra_method_names[PT_BRA_METHOD_SLOT_COUNT] = {
	"name", "aliasName", "modifiers", "declaringClass", "docComment", "locatedSource", "returnsReference", "isVariadic",
	"implementingClass", "parameters",
};
KnownClass pt_bra_method = { PT_CLASS_BETTER_REFLECTION_METHOD, pt_bra_method_names, PT_BRA_METHOD_SLOT_COUNT, 0, 0, false, NULL, {} };

/* Adapter\ReflectionProperty: $betterReflectionProperty */
const char *const pt_bra_property_adapter_names[] = { "betterReflectionProperty" };
KnownClass pt_bra_property_adapter = { PT_CLASS_ADAPTER_REFLECTION_PROPERTY, pt_bra_property_adapter_names, 1, 0, 0, false, NULL, {} };

/* ReflectionProperty */
enum : uint32_t
{
	PT_BRA_PROPERTY_NAME = 0,
	PT_BRA_PROPERTY_MODIFIERS,
	PT_BRA_PROPERTY_IS_PROMOTED,
	PT_BRA_PROPERTY_DECLARING_CLASS,
	PT_BRA_PROPERTY_DOC_COMMENT,
	PT_BRA_PROPERTY_CACHED_VIRTUAL,
	PT_BRA_PROPERTY_IMPLEMENTING_CLASS,
	PT_BRA_PROPERTY_SLOT_COUNT,
};
const char *const pt_bra_property_names[PT_BRA_PROPERTY_SLOT_COUNT] = {
	"name", "modifiers", "isPromoted", "declaringClass", "docComment", "cachedVirtual", "implementingClass",
};
KnownClass pt_bra_property = { PT_CLASS_BETTER_REFLECTION_PROPERTY, pt_bra_property_names, PT_BRA_PROPERTY_SLOT_COUNT, 0, 0, false, NULL, {} };

/* Adapter\ReflectionNamedType: $type / $allowsNull / $nameType */
enum : uint32_t
{
	PT_BRA_NAMED_TYPE_TYPE = 0,
	PT_BRA_NAMED_TYPE_ALLOWS_NULL,
	PT_BRA_NAMED_TYPE_NAME_TYPE,
	PT_BRA_NAMED_TYPE_SLOT_COUNT,
};
const char *const pt_bra_named_type_adapter_names[PT_BRA_NAMED_TYPE_SLOT_COUNT] = { "type", "allowsNull", "nameType" };
KnownClass pt_bra_named_type_adapter = { PT_CLASS_REFLECTION_NAMED_TYPE, pt_bra_named_type_adapter_names, PT_BRA_NAMED_TYPE_SLOT_COUNT, 0, 0, false, NULL, {} };

/* ReflectionNamedType: $isIdentifier */
const char *const pt_bra_named_type_names[] = { "isIdentifier" };
KnownClass pt_bra_named_type = { PT_CLASS_BETTER_REFLECTION_NAMED_TYPE, pt_bra_named_type_names, 1, 0, 0, false, NULL, {} };

/* Adapter\ReflectionParameter: $betterReflectionParameter; ReflectionParameter: $name */
const char *const pt_bra_parameter_adapter_names[] = { "betterReflectionParameter" };
KnownClass pt_bra_parameter_adapter = { PT_CLASS_ADAPTER_REFLECTION_PARAMETER, pt_bra_parameter_adapter_names, 1, 0, 0, false, NULL, {} };
const char *const pt_bra_parameter_names[] = { "name" };
KnownClass pt_bra_parameter = { PT_CLASS_BETTER_REFLECTION_PARAMETER, pt_bra_parameter_names, 1, 0, 0, false, NULL, {} };

/* ReflectionClassConstant: $implementingClass */
const char *const pt_bra_class_constant_names[] = { "implementingClass" };
KnownClass pt_bra_class_constant = { PT_CLASS_BETTER_REFLECTION_CLASS_CONSTANT, pt_bra_class_constant_names, 1, 0, 0, false, NULL, {} };

/* LocatedSource / InternalLocatedSource: isInternal() answers `false` /
 * `true`; a source class is answered by the class declaring its
 * isInternal() */
KnownClass pt_bra_located_source = { PT_CLASS_LOCATED_SOURCE, pt_bra_no_names, 0, 0, 0, false, NULL, {} };
KnownClass pt_bra_internal_located_source = { PT_CLASS_INTERNAL_LOCATED_SOURCE, pt_bra_no_names, 0, 0, 0, false, NULL, {} };

struct LocatedSourceAnswer
{
	zend_class_entry *ce;
	uint32_t generation;
	int answer; /* 1 / 0, -1 = the method must answer */
};
LocatedSourceAnswer pt_bra_located_source_answer = { NULL, 0, -1 };

/* }}} */

/* {{{ the wrapped objects */

/* the BetterReflection class behind exactly an Adapter\ReflectionClass /
 * Adapter\ReflectionEnum, when it is exactly a ReflectionClass or
 * ReflectionEnum; NULL otherwise */
inline zend_object *classOf(zval *adapter)
{
	if (UNEXPECTED(Z_TYPE_P(adapter) != IS_OBJECT)) return NULL;
	zend_object *object = Z_OBJ_P(adapter);
	zval *inner;
	if (EXPECTED(isKnown(pt_bra_class_adapter, object->ce))) {
		inner = propertyAt(object, pt_bra_class_adapter, 0);
	} else if (isKnown(pt_bra_enum_adapter, object->ce)) {
		inner = propertyAt(object, pt_bra_enum_adapter, 0);
	} else {
		return NULL;
	}
	if (UNEXPECTED(inner == NULL || Z_TYPE_P(inner) != IS_OBJECT)) return NULL;
	zend_class_entry *innerCe = Z_OBJCE_P(inner);
	if (EXPECTED(isKnown(pt_bra_class, innerCe))) return Z_OBJ_P(inner);
	if (innerCe->parent == pt_bra_class.ce && pt_bra_class.ce != NULL && isKnown(pt_bra_enum, innerCe)) return Z_OBJ_P(inner);
	return NULL;
}

/* a BetterReflection class object (from a memo) that is exactly a
 * ReflectionClass or ReflectionEnum */
inline bool isBetterReflectionClass(zval *value)
{
	if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) return false;
	zend_class_entry *ce = Z_OBJCE_P(value);
	if (EXPECTED(isKnown(pt_bra_class, ce))) return true;
	return ce->parent == pt_bra_class.ce && pt_bra_class.ce != NULL && isKnown(pt_bra_enum, ce);
}

/* the ReflectionMethod behind exactly an Adapter\ReflectionMethod, when it is
 * exactly a ReflectionMethod; NULL otherwise */
inline zend_object *methodOf(zval *adapter)
{
	if (UNEXPECTED(Z_TYPE_P(adapter) != IS_OBJECT)) return NULL;
	zend_object *object = Z_OBJ_P(adapter);
	if (UNEXPECTED(!isKnown(pt_bra_method_adapter, object->ce))) return NULL;
	zval *inner = propertyAt(object, pt_bra_method_adapter, 0);
	if (UNEXPECTED(inner == NULL || Z_TYPE_P(inner) != IS_OBJECT) || UNEXPECTED(!isKnown(pt_bra_method, Z_OBJCE_P(inner)))) return NULL;
	return Z_OBJ_P(inner);
}

/* the ReflectionProperty behind exactly an Adapter\ReflectionProperty */
inline zend_object *propertyOf(zval *adapter)
{
	if (UNEXPECTED(Z_TYPE_P(adapter) != IS_OBJECT)) return NULL;
	zend_object *object = Z_OBJ_P(adapter);
	if (UNEXPECTED(!isKnown(pt_bra_property_adapter, object->ce))) return NULL;
	zval *inner = propertyAt(object, pt_bra_property_adapter, 0);
	if (UNEXPECTED(inner == NULL || Z_TYPE_P(inner) != IS_OBJECT) || UNEXPECTED(!isKnown(pt_bra_property, Z_OBJCE_P(inner)))) return NULL;
	return Z_OBJ_P(inner);
}

/* }}} */

/* {{{ calls */

/* $object->method(...$argv); UNDEF = pending exception */
zend_never_inline zv::Val callOn(zval *object, const char *lcname, size_t len, const char *name, uint32_t argc = 0, zval *argv = NULL)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
}

zend_never_inline bool callBoolOn(zval *object, const char *lcname, size_t len, const char *name, bool &out)
{
	zv::Val result = callOn(object, lcname, len, name);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ ReflectionClass values */

inline bool boolAt(zend_object *object, const KnownClass &known, uint32_t index, bool &out)
{
	zval *value = propertyAt(object, known, index);
	if (UNEXPECTED(value == NULL || (Z_TYPE_P(value) != IS_TRUE && Z_TYPE_P(value) != IS_FALSE))) return false;
	out = Z_TYPE_P(value) == IS_TRUE;
	return true;
}

inline bool longAt(zend_object *object, const KnownClass &known, uint32_t index, zend_long &out)
{
	zval *value = propertyAt(object, known, index);
	if (UNEXPECTED(value == NULL || Z_TYPE_P(value) != IS_LONG)) return false;
	out = Z_LVAL_P(value);
	return true;
}

/* ReflectionClass::getName() of a known class object: the $cachedName memo,
 * the method (which fills it) otherwise; NULL = pending exception, the
 * string borrowed from the memo or owned by hold */
zend_string *className(zend_object *betterReflection, zv::Val &hold)
{
	zval *cached = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_NAME);
	if (EXPECTED(cached != NULL && Z_TYPE_P(cached) == IS_STRING)) return Z_STR_P(cached);
	hold = pt_type_call(betterReflection, PT_LC("getname"), 0, NULL);
	if (UNEXPECTED(hold.isUndef())) return NULL;
	if (UNEXPECTED(Z_TYPE_P(hold.raw()) != IS_STRING)) {
		zend_type_error("%s::getName(): Return value must be of type string, %s returned", ZSTR_VAL(betterReflection->ce->name), zend_zval_value_name(hold.raw()));
		return NULL;
	}
	return Z_STR_P(hold.raw());
}

/* ReflectionClass::getTraits() of a known class object: the $cachedTraits
 * memo, the method (which fills it) otherwise; NULL = pending exception */
zval *classTraits(zend_object *betterReflection, zv::Val &hold)
{
	zval *cached = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_TRAITS);
	if (EXPECTED(cached != NULL && Z_TYPE_P(cached) == IS_ARRAY)) return cached;
	hold = pt_type_call(betterReflection, PT_LC("gettraits"), 0, NULL);
	return hold.isUndef() ? NULL : hold.raw();
}

/* $locatedSource->isInternal() of LocatedSource (false) or
 * InternalLocatedSource (true) and their subclasses that do not override it;
 * -1 for any other source (the caller calls the method) */
int locatedSourceIsInternal(zval *locatedSource)
{
	if (UNEXPECTED(Z_TYPE_P(locatedSource) != IS_OBJECT)) return -1;
	zend_class_entry *ce = Z_OBJCE_P(locatedSource);
	LocatedSourceAnswer &cache = pt_bra_located_source_answer;
	if (EXPECTED(cache.ce == ce && cache.generation == pt_engine_generation)) return cache.answer;
	zend_function *fn = pt_find_method(ce, PT_LC("isinternal"));
	if (UNEXPECTED(fn == NULL)) {
		zend_clear_exception();
		return -1;
	}
	int answer = -1;
	if (fn->common.scope != NULL) {
		if (isKnown(pt_bra_located_source, fn->common.scope)) {
			answer = 0;
		} else if (isKnown(pt_bra_internal_located_source, fn->common.scope)) {
			answer = 1;
		}
	}
	cache = { ce, pt_engine_generation, answer };
	return answer;
}

/* ReflectionMethod::getName() of a known method object: `$aliasName ??
 * $name` (getNamespaceName() is null, getShortName() the alias or the name);
 * NULL when a property is not initialized */
inline zend_string *methodName(zend_object *method)
{
	zval *alias = propertyAt(method, pt_bra_method, PT_BRA_METHOD_ALIAS_NAME);
	if (UNEXPECTED(alias == NULL)) return NULL;
	if (Z_TYPE_P(alias) == IS_STRING) return Z_STR_P(alias);
	if (UNEXPECTED(Z_TYPE_P(alias) != IS_NULL)) return NULL;
	zval *name = propertyAt(method, pt_bra_method, PT_BRA_METHOD_NAME);
	return EXPECTED(name != NULL && Z_TYPE_P(name) == IS_STRING) ? Z_STR_P(name) : NULL;
}

/* ReflectionMethod::isConstructor() of a known method object over its
 * properties: 1 / 0, -1 when a property it needs is not there yet */
int methodIsConstructor(zend_object *method)
{
	zend_string *name = methodName(method);
	if (UNEXPECTED(name == NULL)) return -1;
	if (zend_string_equals_literal_ci(name, "__construct")) return 1;
	/* $declaringClass = $this->getDeclaringClass(); if ($declaringClass->inNamespace()) return false; */
	zval *declaringClass = propertyAt(method, pt_bra_method, PT_BRA_METHOD_DECLARING_CLASS);
	if (declaringClass == NULL || !isBetterReflectionClass(declaringClass)) return -1;
	zval *ns = propertyAt(Z_OBJ_P(declaringClass), pt_bra_class, PT_BRA_CLASS_NAMESPACE);
	if (UNEXPECTED(ns == NULL)) return -1;
	if (Z_TYPE_P(ns) != IS_NULL) return 0;
	/* strtolower($this->getName()) === strtolower($declaringClass->getShortName()) */
	zval *shortName = propertyAt(Z_OBJ_P(declaringClass), pt_bra_class, PT_BRA_CLASS_SHORT_NAME);
	if (shortName == NULL || Z_TYPE_P(shortName) != IS_STRING) return -1;
	return zend_string_equals_ci(name, Z_STR_P(shortName)) ? 1 : 0;
}

/* }}} */

} // namespace

/* {{{ Adapter\ReflectionClass / Adapter\ReflectionEnum */

zend_object *pt_better_reflection_class_of_adapter(zval *adapter)
{
	return classOf(adapter);
}

zval *pt_better_reflection_class_cached_methods(zend_object *betterReflection)
{
	zval *memo = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_METHODS);
	return memo != NULL && Z_TYPE_P(memo) == IS_ARRAY ? memo : NULL;
}

zval *pt_better_reflection_class_cached_properties(zend_object *betterReflection)
{
	zval *memo = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_PROPERTIES);
	return memo != NULL && Z_TYPE_P(memo) == IS_ARRAY ? memo : NULL;
}

zv::Val pt_class_adapter_get_name(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* return $this->betterReflectionClass->getName(); */
		zv::Val hold;
		zend_string *name = className(betterReflection, hold);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return hold.isUndef() ? zv::Val::string(name) : std::move(hold);
	}
	return callOn(adapter, PT_LC("getname"), "getName");
}

bool pt_class_adapter_is_final(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* if ($this->isEnum) return true; return (bool) ($this->modifiers & CoreReflectionClass::IS_FINAL); */
		bool isEnum;
		zend_long modifiers;
		if (EXPECTED(boolAt(betterReflection, pt_bra_class, PT_BRA_CLASS_IS_ENUM, isEnum) && longAt(betterReflection, pt_bra_class, PT_BRA_CLASS_MODIFIERS, modifiers))) {
			out = isEnum || (modifiers & PT_BRA_IS_FINAL) != 0;
			return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isfinal"), "isFinal", out);
}

bool pt_class_adapter_is_abstract(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	zend_long modifiers;
	if (EXPECTED(betterReflection != NULL) && EXPECTED(longAt(betterReflection, pt_bra_class, PT_BRA_CLASS_MODIFIERS, modifiers))) {
		/* (bool) ($this->modifiers & CoreReflectionClass::IS_EXPLICIT_ABSTRACT) */
		out = (modifiers & PT_BRA_IS_ABSTRACT) != 0;
		return true;
	}
	return callBoolOn(adapter, PT_LC("isabstract"), "isAbstract", out);
}

bool pt_class_adapter_is_read_only(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	zend_long modifiers;
	if (EXPECTED(betterReflection != NULL) && EXPECTED(longAt(betterReflection, pt_bra_class, PT_BRA_CLASS_MODIFIERS, modifiers))) {
		/* (bool) ($this->modifiers & ReflectionClassAdapter::IS_READONLY_COMPATIBILITY) */
		out = (modifiers & PT_BRA_CLASS_IS_READONLY) != 0;
		return true;
	}
	return callBoolOn(adapter, PT_LC("isreadonly"), "isReadOnly", out);
}

bool pt_class_adapter_is_interface(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL) && EXPECTED(boolAt(betterReflection, pt_bra_class, PT_BRA_CLASS_IS_INTERFACE, out))) return true;
	return callBoolOn(adapter, PT_LC("isinterface"), "isInterface", out);
}

bool pt_class_adapter_is_trait(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL) && EXPECTED(boolAt(betterReflection, pt_bra_class, PT_BRA_CLASS_IS_TRAIT, out))) return true;
	return callBoolOn(adapter, PT_LC("istrait"), "isTrait", out);
}

bool pt_class_adapter_is_internal(zval *adapter, bool &out)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* return $this->locatedSource->isInternal(); */
		zval *locatedSource = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_LOCATED_SOURCE);
		int answer = locatedSource != NULL ? locatedSourceIsInternal(locatedSource) : -1;
		if (EXPECTED(answer >= 0)) {
			out = answer == 1;
			return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isinternal"), "isInternal", out);
}

zv::Val pt_class_adapter_get_start_line(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		zval *startLine = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_START_LINE);
		if (EXPECTED(startLine != NULL && Z_TYPE_P(startLine) == IS_LONG)) return zv::Val::integer(Z_LVAL_P(startLine));
	}
	return callOn(adapter, PT_LC("getstartline"), "getStartLine");
}

zv::Val pt_class_adapter_get_doc_comment(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* return $this->betterReflectionClass->getDocComment() ?? false; */
		zval *docComment = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_DOC_COMMENT);
		if (EXPECTED(docComment != NULL)) {
			if (Z_TYPE_P(docComment) == IS_NULL) return zv::Val::boolean(false);
			if (EXPECTED(Z_TYPE_P(docComment) == IS_STRING)) return zv::Val::copyOf(zv::Ref(docComment));
		}
	}
	return callOn(adapter, PT_LC("getdoccomment"), "getDocComment");
}

zv::Val pt_class_adapter_get_interface_names(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* return $this->betterReflectionClass->getInterfaceNames(); — the
		 * $cachedInterfaceNames memo, the wrapped method (which fills it)
		 * otherwise */
		zval *cached = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_INTERFACE_NAMES);
		if (EXPECTED(cached != NULL && Z_TYPE_P(cached) == IS_ARRAY)) return zv::Val::copyOf(zv::Ref(cached));
		return pt_type_call(betterReflection, PT_LC("getinterfacenames"), 0, NULL);
	}
	return callOn(adapter, PT_LC("getinterfacenames"), "getInterfaceNames");
}

namespace {

/* ReflectionClass::getInterfaces() of a known class object: the
 * $cachedInterfaces memo when it holds only known class objects, the method
 * (which fills it) otherwise; NULL = pending exception, `known` cleared when
 * an interface is of a class the readers do not know */
zval *classInterfaces(zend_object *betterReflection, zv::Val &hold, bool &known)
{
	zval *interfaces = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_INTERFACES);
	if (interfaces == NULL || Z_TYPE_P(interfaces) != IS_ARRAY) {
		hold = pt_type_call(betterReflection, PT_LC("getinterfaces"), 0, NULL);
		if (UNEXPECTED(hold.isUndef())) return NULL;
		interfaces = hold.raw();
	}
	if (UNEXPECTED(Z_TYPE_P(interfaces) != IS_ARRAY)) {
		known = false;
		return interfaces;
	}
	for (zv::ArrayEntry entry : zv::ArrRef(interfaces)) {
		if (UNEXPECTED(!isBetterReflectionClass(entry.value().deref().raw()))) {
			known = false;
			break;
		}
	}
	return interfaces;
}

} // namespace

zv::Val pt_class_adapter_get_interfaces_names(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	zv::Arr names = zv::Arr::create(4);
	if (EXPECTED(betterReflection != NULL)) {
		/* array_map(static fn ($interface): self => new self($interface),
		 * $this->betterReflectionClass->getInterfaces()), each asked getName() */
		zv::Val interfacesHold;
		bool known = true;
		zval *interfaces = classInterfaces(betterReflection, interfacesHold, known);
		if (UNEXPECTED(interfaces == NULL)) return zv::Val();
		if (EXPECTED(known)) {
			for (zv::ArrayEntry entry : zv::ArrRef(interfaces)) {
				zv::Val nameHold;
				zend_string *name = className(Z_OBJ_P(entry.value().deref().raw()), nameHold);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				names.push(zv::Val::string(name));
			}
			return zv::Val(std::move(names));
		}
	}
	zv::Val interfaces = callOn(adapter, PT_LC("getinterfaces"), "getInterfaces");
	if (UNEXPECTED(interfaces.isUndef())) return zv::Val();
	if (Z_TYPE_P(interfaces.raw()) != IS_ARRAY) return zv::Val(std::move(names));
	for (zv::ArrayEntry entry : zv::ArrRef(interfaces.raw())) {
		zv::Val name = callOn(entry.value().deref().raw(), PT_LC("getname"), "getName");
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		names.push(std::move(name));
	}
	return zv::Val(std::move(names));
}

zv::Val pt_class_adapter_get_interfaces_interface_names(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	zv::Arr names = zv::Arr::create(4);
	if (EXPECTED(betterReflection != NULL)) {
		/* foreach ($adapter->getInterfaces() as $interface) foreach
		 * ($interface->getInterfaceNames() as $name) $names[] = $name; */
		zv::Val interfacesHold;
		bool known = true;
		zval *interfaces = classInterfaces(betterReflection, interfacesHold, known);
		if (UNEXPECTED(interfaces == NULL)) return zv::Val();
		if (EXPECTED(known)) {
			for (zv::ArrayEntry entry : zv::ArrRef(interfaces)) {
				zend_object *interface = Z_OBJ_P(entry.value().deref().raw());
				zval *interfaceNames = propertyAt(interface, pt_bra_class, PT_BRA_CLASS_CACHED_INTERFACE_NAMES);
				zv::Val interfaceNamesHold;
				if (interfaceNames == NULL || Z_TYPE_P(interfaceNames) != IS_ARRAY) {
					interfaceNamesHold = pt_type_call(interface, PT_LC("getinterfacenames"), 0, NULL);
					if (UNEXPECTED(interfaceNamesHold.isUndef())) return zv::Val();
					interfaceNames = interfaceNamesHold.raw();
				}
				if (Z_TYPE_P(interfaceNames) != IS_ARRAY) continue;
				for (zv::ArrayEntry name : zv::ArrRef(interfaceNames)) {
					names.push(name.value().deref());
				}
			}
			return zv::Val(std::move(names));
		}
	}
	zv::Val interfaces = callOn(adapter, PT_LC("getinterfaces"), "getInterfaces");
	if (UNEXPECTED(interfaces.isUndef())) return zv::Val();
	if (Z_TYPE_P(interfaces.raw()) != IS_ARRAY) return zv::Val(std::move(names));
	for (zv::ArrayEntry entry : zv::ArrRef(interfaces.raw())) {
		zv::Val interfaceNames = pt_class_adapter_get_interface_names(entry.value().deref().raw());
		if (UNEXPECTED(interfaceNames.isUndef())) return zv::Val();
		if (Z_TYPE_P(interfaceNames.raw()) != IS_ARRAY) continue;
		for (zv::ArrayEntry name : zv::ArrRef(interfaceNames.raw())) {
			names.push(name.value().deref());
		}
	}
	return zv::Val(std::move(names));
}

zv::Val pt_class_adapter_get_trait_names(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/* foreach ($this->betterReflectionClass->getTraits() as $trait)
		 * $traitsByName[$trait->getName()] = new self($trait); — the names,
		 * keyed by themselves */
		zv::Val traitsHold;
		zval *traits = classTraits(betterReflection, traitsHold);
		if (UNEXPECTED(traits == NULL)) return zv::Val();
		if (EXPECTED(Z_TYPE_P(traits) == IS_ARRAY)) {
			bool known = true;
			for (zv::ArrayEntry entry : zv::ArrRef(traits)) {
				if (UNEXPECTED(!isBetterReflectionClass(entry.value().deref().raw()))) {
					known = false;
					break;
				}
			}
			if (EXPECTED(known)) {
				zv::Arr names = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(traits)));
				for (zv::ArrayEntry entry : zv::ArrRef(traits)) {
					zv::Val nameHold;
					zend_string *name = className(Z_OBJ_P(entry.value().deref().raw()), nameHold);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					names.set(name, zv::Val::string(name));
				}
				return zv::Val(std::move(names));
			}
		}
	}

	zv::Val traits = callOn(adapter, PT_LC("gettraits"), "getTraits");
	if (UNEXPECTED(traits.isUndef())) return zv::Val();
	zv::Arr names = zv::Arr::create(Z_TYPE_P(traits.raw()) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(traits.raw())) : 0);
	if (Z_TYPE_P(traits.raw()) != IS_ARRAY) return zv::Val(std::move(names));
	for (zv::ArrayEntry entry : zv::ArrRef(traits.raw())) {
		zv::Val name = callOn(entry.value().deref().raw(), PT_LC("getname"), "getName");
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			names.set(key, std::move(name));
		} else {
			names.arrRef().setIndex(entry.indexKey(), name.ref());
		}
	}
	return zv::Val(std::move(names));
}

zv::Val pt_class_adapter_collect_trait_names(zval *adapter)
{
	/*
	 * ClassReflection::collectTraits($class) — a breadth-first queue over
	 * getTraits() of the class and of every queued trait — mapped to the
	 * trait names. A trait whose name was already collected is skipped
	 * together with its own traits: traits can use each other in a cycle,
	 * and every getTraits() call wraps the traits in fresh adapters, so only
	 * the names identify them. Each name once, in queue order.
	 */
	zv::Arr names = zv::Arr::create(4);
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		std::vector<zv::Val> queue;
		/* the traits of one class as getTraits() keys them: the first
		 * position of each name, which the queue then holds as the class */
		auto enqueueTraits = [&](zend_object *owner, bool &known) -> bool {
			zv::Val traitsHold;
			zval *traits = classTraits(owner, traitsHold);
			if (UNEXPECTED(traits == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(traits) != IS_ARRAY)) {
				known = false;
				return true;
			}
			size_t first = queue.size();
			std::vector<zend_string *> seen;
			for (zv::ArrayEntry entry : zv::ArrRef(traits)) {
				zval *trait = entry.value().deref().raw();
				if (UNEXPECTED(!isBetterReflectionClass(trait))) {
					known = false;
					return true;
				}
				zv::Val nameHold;
				zend_string *name = className(Z_OBJ_P(trait), nameHold);
				if (UNEXPECTED(name == NULL)) return false;
				bool duplicate = false;
				for (size_t i = 0; i < seen.size(); i++) {
					if (zend_string_equals(seen[i], name)) {
						/* $traitsByName[$name] = new self($trait): the later
						 * trait of the same name replaces the earlier one in
						 * its position */
						queue[first + i] = zv::Val::copyOf(zv::Ref(trait));
						duplicate = true;
						break;
					}
				}
				if (duplicate) continue;
				seen.push_back(name);
				queue.push_back(zv::Val::copyOf(zv::Ref(trait)));
			}
			return true;
		};

		bool known = true;
		if (UNEXPECTED(!enqueueTraits(betterReflection, known))) return zv::Val();
		zv::Arr collected = zv::Arr::create(4);
		for (size_t head = 0; known && head < queue.size(); head++) {
			zend_object *trait = Z_OBJ_P(queue[head].raw());
			zv::Val nameHold;
			zend_string *name = className(trait, nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			/* array_key_exists($trait->getName(), $traits) */
			if (collected.arrRef().exists(name)) continue;
			collected.set(name, zv::Val::boolean(true));
			names.push(zv::Val::string(name));
			if (UNEXPECTED(!enqueueTraits(trait, known))) return zv::Val();
		}
		if (EXPECTED(known)) return zv::Val(std::move(names));
		names = zv::Arr::create(4);
	}

	/* the twin's walk over the adapters */
	std::vector<zv::Val> queue;
	auto enqueueAdapterTraits = [&](zval *owner) -> bool {
		zv::Val traits = callOn(owner, PT_LC("gettraits"), "getTraits");
		if (UNEXPECTED(traits.isUndef())) return false;
		if (Z_TYPE_P(traits.raw()) != IS_ARRAY) return true;
		for (zv::ArrayEntry entry : zv::ArrRef(traits.raw())) {
			queue.push_back(zv::Val::copyOf(entry.value().deref()));
		}
		return true;
	};
	if (UNEXPECTED(!enqueueAdapterTraits(adapter))) return zv::Val();
	zv::Arr collected = zv::Arr::create(4);
	for (size_t head = 0; head < queue.size(); head++) {
		zval *trait = queue[head].raw();
		zv::Val name = callOn(trait, PT_LC("getname"), "getName");
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Str nameStr = zv::Str::adopt(zval_get_string(name.raw()));
		if (collected.arrRef().exists(nameStr.get())) continue;
		collected.set(nameStr.get(), zv::Val::boolean(true));
		names.push(std::move(name));
		if (UNEXPECTED(!enqueueAdapterTraits(trait))) return zv::Val();
	}
	return zv::Val(std::move(names));
}

zv::Val pt_class_adapter_get_constructor_name(zval *adapter)
{
	zend_object *betterReflection = classOf(adapter);
	if (EXPECTED(betterReflection != NULL)) {
		/*
		 * ReflectionClass::getConstructor(): the $cachedConstructor memo, or
		 * array_values(array_filter($this->getMethods(), fn ($m) =>
		 * $m->isConstructor()))[0] ?? null over the $cachedMethods memo
		 * (getMethods() only re-keys it by the real names, which differ
		 * exactly where the lowercased keys do) — the adapter wraps the
		 * method, whose getName() is asked
		 */
		zval *cached = propertyAt(betterReflection, pt_bra_class, PT_BRA_CLASS_CACHED_CONSTRUCTOR);
		if (EXPECTED(cached != NULL)) {
			if (Z_TYPE_P(cached) == IS_OBJECT) {
				if (EXPECTED(isKnown(pt_bra_method, Z_OBJCE_P(cached)))) {
					zend_string *name = methodName(Z_OBJ_P(cached));
					if (EXPECTED(name != NULL)) return zv::Val::string(name);
				}
			} else if (Z_TYPE_P(cached) == IS_NULL) {
				zval *methods = pt_better_reflection_class_cached_methods(betterReflection);
				if (methods != NULL) {
					bool known = true;
					for (zv::ArrayEntry entry : zv::ArrRef(methods)) {
						zval *method = entry.value().deref().raw();
						if (UNEXPECTED(Z_TYPE_P(method) != IS_OBJECT || !isKnown(pt_bra_method, Z_OBJCE_P(method)))) {
							known = false;
							break;
						}
						int isConstructor = methodIsConstructor(Z_OBJ_P(method));
						if (UNEXPECTED(isConstructor < 0)) {
							known = false;
							break;
						}
						if (isConstructor == 1) {
							/* not memoized here: the twin's memo only short-cuts this scan */
							return zv::Val::string(methodName(Z_OBJ_P(method)));
						}
					}
					if (known) return zv::Val::null();
				}
			}
		}
	}

	zv::Val constructor = callOn(adapter, PT_LC("getconstructor"), "getConstructor");
	if (UNEXPECTED(constructor.isUndef()) || constructor.isNull()) return constructor;
	return pt_method_adapter_get_name(constructor.raw());
}

/* }}} */

/* {{{ Adapter\ReflectionMethod */

zend_object *pt_better_reflection_method_of_adapter(zval *adapter)
{
	return methodOf(adapter);
}

zv::Val pt_method_adapter_get_name(zval *adapter)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL)) {
		zend_string *name = methodName(method);
		if (EXPECTED(name != NULL)) return zv::Val::string(name);
	}
	return callOn(adapter, PT_LC("getname"), "getName");
}

namespace {

/* a modifier bit of a known method object; false when the property is not
 * initialized */
inline bool methodModifier(zval *adapter, zend_long bit, bool &out)
{
	zend_object *method = methodOf(adapter);
	zend_long modifiers;
	if (EXPECTED(method != NULL) && EXPECTED(longAt(method, pt_bra_method, PT_BRA_METHOD_MODIFIERS, modifiers))) {
		out = (modifiers & bit) != 0;
		return true;
	}
	return false;
}

} // namespace

bool pt_method_adapter_is_static(zval *adapter, bool &out)
{
	if (EXPECTED(methodModifier(adapter, PT_BRA_IS_STATIC, out))) return true;
	return callBoolOn(adapter, PT_LC("isstatic"), "isStatic", out);
}

bool pt_method_adapter_is_public(zval *adapter, bool &out)
{
	if (EXPECTED(methodModifier(adapter, PT_BRA_IS_PUBLIC, out))) return true;
	return callBoolOn(adapter, PT_LC("ispublic"), "isPublic", out);
}

bool pt_method_adapter_is_private(zval *adapter, bool &out)
{
	if (EXPECTED(methodModifier(adapter, PT_BRA_IS_PRIVATE, out))) return true;
	return callBoolOn(adapter, PT_LC("isprivate"), "isPrivate", out);
}

bool pt_method_adapter_is_final(zval *adapter, bool &out)
{
	if (EXPECTED(methodModifier(adapter, PT_BRA_IS_FINAL, out))) return true;
	return callBoolOn(adapter, PT_LC("isfinal"), "isFinal", out);
}

bool pt_method_adapter_is_abstract(zval *adapter, bool &out)
{
	zend_object *method = methodOf(adapter);
	zend_long modifiers;
	if (EXPECTED(method != NULL) && EXPECTED(longAt(method, pt_bra_method, PT_BRA_METHOD_MODIFIERS, modifiers))) {
		/* (bool) ($this->modifiers & IS_ABSTRACT) || $this->getDeclaringClass()->isInterface() */
		if ((modifiers & PT_BRA_IS_ABSTRACT) != 0) {
			out = true;
			return true;
		}
		zval *declaringClass = propertyAt(method, pt_bra_method, PT_BRA_METHOD_DECLARING_CLASS);
		if (declaringClass != NULL && isBetterReflectionClass(declaringClass) && boolAt(Z_OBJ_P(declaringClass), pt_bra_class, PT_BRA_CLASS_IS_INTERFACE, out)) return true;
	}
	return callBoolOn(adapter, PT_LC("isabstract"), "isAbstract", out);
}

bool pt_method_adapter_is_internal(zval *adapter, bool &out)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL)) {
		zval *locatedSource = propertyAt(method, pt_bra_method, PT_BRA_METHOD_LOCATED_SOURCE);
		int answer = locatedSource != NULL ? locatedSourceIsInternal(locatedSource) : -1;
		if (EXPECTED(answer >= 0)) {
			out = answer == 1;
			return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isinternal"), "isInternal", out);
}

bool pt_method_adapter_returns_reference(zval *adapter, bool &out)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL) && EXPECTED(boolAt(method, pt_bra_method, PT_BRA_METHOD_RETURNS_REFERENCE, out))) return true;
	return callBoolOn(adapter, PT_LC("returnsreference"), "returnsReference", out);
}

bool pt_method_adapter_is_variadic(zval *adapter, bool &out)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL) && EXPECTED(boolAt(method, pt_bra_method, PT_BRA_METHOD_IS_VARIADIC, out))) return true;
	return callBoolOn(adapter, PT_LC("isvariadic"), "isVariadic", out);
}

zv::Val pt_method_adapter_get_doc_comment(zval *adapter)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL)) {
		/* return $this->betterReflectionMethod->getDocComment() ?? false; */
		zval *docComment = propertyAt(method, pt_bra_method, PT_BRA_METHOD_DOC_COMMENT);
		if (EXPECTED(docComment != NULL)) {
			if (Z_TYPE_P(docComment) == IS_NULL) return zv::Val::boolean(false);
			if (EXPECTED(Z_TYPE_P(docComment) == IS_STRING)) return zv::Val::copyOf(zv::Ref(docComment));
		}
	}
	return callOn(adapter, PT_LC("getdoccomment"), "getDocComment");
}

/* }}} */

/* {{{ Adapter\ReflectionProperty */

zv::Val pt_property_adapter_get_name(zval *adapter)
{
	zend_object *property = propertyOf(adapter);
	if (EXPECTED(property != NULL)) {
		zval *name = propertyAt(property, pt_bra_property, PT_BRA_PROPERTY_NAME);
		if (EXPECTED(name != NULL && Z_TYPE_P(name) == IS_STRING)) return zv::Val::copyOf(zv::Ref(name));
	}
	return callOn(adapter, PT_LC("getname"), "getName");
}

zv::Val pt_property_adapter_get_better_reflection(zval *adapter)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_property_adapter, Z_OBJCE_P(adapter)))) {
		zval *inner = propertyAt(Z_OBJ_P(adapter), pt_bra_property_adapter, 0);
		if (EXPECTED(inner != NULL && Z_TYPE_P(inner) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(inner));
	}
	return callOn(adapter, PT_LC("getbetterreflection"), "getBetterReflection");
}

namespace {

inline bool propertyModifier(zval *adapter, zend_long bit, bool &out)
{
	zend_object *property = propertyOf(adapter);
	zend_long modifiers;
	if (EXPECTED(property != NULL) && EXPECTED(longAt(property, pt_bra_property, PT_BRA_PROPERTY_MODIFIERS, modifiers))) {
		out = (modifiers & bit) != 0;
		return true;
	}
	return false;
}

} // namespace

bool pt_property_adapter_is_static(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_STATIC, out))) return true;
	return callBoolOn(adapter, PT_LC("isstatic"), "isStatic", out);
}

bool pt_property_adapter_is_public(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_PUBLIC, out))) return true;
	return callBoolOn(adapter, PT_LC("ispublic"), "isPublic", out);
}

bool pt_property_adapter_is_private(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_PRIVATE, out))) return true;
	return callBoolOn(adapter, PT_LC("isprivate"), "isPrivate", out);
}

bool pt_property_adapter_is_protected(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_PROTECTED, out))) return true;
	return callBoolOn(adapter, PT_LC("isprotected"), "isProtected", out);
}

bool pt_property_adapter_is_final(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_FINAL, out))) return true;
	return callBoolOn(adapter, PT_LC("isfinal"), "isFinal", out);
}

bool pt_property_adapter_is_protected_set(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_PROTECTED_SET, out))) return true;
	return callBoolOn(adapter, PT_LC("isprotectedset"), "isProtectedSet", out);
}

bool pt_property_adapter_is_private_set(zval *adapter, bool &out)
{
	if (EXPECTED(propertyModifier(adapter, PT_BRA_IS_PRIVATE_SET, out))) return true;
	return callBoolOn(adapter, PT_LC("isprivateset"), "isPrivateSet", out);
}

bool pt_property_adapter_is_abstract(zval *adapter, bool &out)
{
	zend_object *property = propertyOf(adapter);
	zend_long modifiers;
	if (EXPECTED(property != NULL) && EXPECTED(longAt(property, pt_bra_property, PT_BRA_PROPERTY_MODIFIERS, modifiers))) {
		/* (bool) ($this->modifiers & IS_ABSTRACT_COMPATIBILITY) || $this->getDeclaringClass()->isInterface() */
		if ((modifiers & PT_BRA_IS_ABSTRACT) != 0) {
			out = true;
			return true;
		}
		zval *declaringClass = propertyAt(property, pt_bra_property, PT_BRA_PROPERTY_DECLARING_CLASS);
		if (declaringClass != NULL && isBetterReflectionClass(declaringClass) && boolAt(Z_OBJ_P(declaringClass), pt_bra_class, PT_BRA_CLASS_IS_INTERFACE, out)) return true;
	}
	return callBoolOn(adapter, PT_LC("isabstract"), "isAbstract", out);
}

bool pt_property_adapter_is_read_only(zval *adapter, bool &out)
{
	zend_object *property = propertyOf(adapter);
	zend_long modifiers;
	if (EXPECTED(property != NULL) && EXPECTED(longAt(property, pt_bra_property, PT_BRA_PROPERTY_MODIFIERS, modifiers))) {
		/* ($this->modifiers & IS_READONLY_COMPATIBILITY) || $this->getDeclaringClass()->isReadOnly() */
		if ((modifiers & PT_BRA_PROPERTY_IS_READONLY) != 0) {
			out = true;
			return true;
		}
		zval *declaringClass = propertyAt(property, pt_bra_property, PT_BRA_PROPERTY_DECLARING_CLASS);
		zend_long classModifiers;
		if (declaringClass != NULL && isBetterReflectionClass(declaringClass) && longAt(Z_OBJ_P(declaringClass), pt_bra_class, PT_BRA_CLASS_MODIFIERS, classModifiers)) {
			out = (classModifiers & PT_BRA_CLASS_IS_READONLY) != 0;
			return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isreadonly"), "isReadOnly", out);
}

bool pt_property_adapter_is_promoted(zval *adapter, bool &out)
{
	zend_object *property = propertyOf(adapter);
	if (EXPECTED(property != NULL) && EXPECTED(boolAt(property, pt_bra_property, PT_BRA_PROPERTY_IS_PROMOTED, out))) return true;
	return callBoolOn(adapter, PT_LC("ispromoted"), "isPromoted", out);
}

bool pt_property_adapter_is_virtual(zval *adapter, bool &out)
{
	zend_object *property = propertyOf(adapter);
	/* $this->cachedVirtual ??= $this->createCachedVirtual(); — the filled memo */
	if (EXPECTED(property != NULL) && EXPECTED(boolAt(property, pt_bra_property, PT_BRA_PROPERTY_CACHED_VIRTUAL, out))) return true;
	return callBoolOn(adapter, PT_LC("isvirtual"), "isVirtual", out);
}

zv::Val pt_property_adapter_get_doc_comment(zval *adapter)
{
	zend_object *property = propertyOf(adapter);
	if (EXPECTED(property != NULL)) {
		/* return $this->betterReflectionProperty->getDocComment() ?? false; */
		zval *docComment = propertyAt(property, pt_bra_property, PT_BRA_PROPERTY_DOC_COMMENT);
		if (EXPECTED(docComment != NULL)) {
			if (Z_TYPE_P(docComment) == IS_NULL) return zv::Val::boolean(false);
			if (EXPECTED(Z_TYPE_P(docComment) == IS_STRING)) return zv::Val::copyOf(zv::Ref(docComment));
		}
	}
	return callOn(adapter, PT_LC("getdoccomment"), "getDocComment");
}

/* }}} */

/* {{{ Adapter\ReflectionNamedType / Adapter\ReflectionParameter */

zv::Val pt_named_type_adapter_get_name(zval *adapter)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_named_type_adapter, Z_OBJCE_P(adapter)))) {
		zval *nameType = propertyAt(Z_OBJ_P(adapter), pt_bra_named_type_adapter, PT_BRA_NAMED_TYPE_NAME_TYPE);
		if (EXPECTED(nameType != NULL && Z_TYPE_P(nameType) == IS_STRING)) return zv::Val::copyOf(zv::Ref(nameType));
	}
	return callOn(adapter, PT_LC("getname"), "getName");
}

bool pt_named_type_adapter_allows_null(zval *adapter, bool &out)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_named_type_adapter, Z_OBJCE_P(adapter)))
		&& EXPECTED(boolAt(Z_OBJ_P(adapter), pt_bra_named_type_adapter, PT_BRA_NAMED_TYPE_ALLOWS_NULL, out))) {
		return true;
	}
	return callBoolOn(adapter, PT_LC("allowsnull"), "allowsNull", out);
}

bool pt_named_type_adapter_is_identifier(zval *adapter, bool &out)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_named_type_adapter, Z_OBJCE_P(adapter)))) {
		/* if (is_string($this->type)) return true; return $this->type->isIdentifier(); */
		zval *type = propertyAt(Z_OBJ_P(adapter), pt_bra_named_type_adapter, PT_BRA_NAMED_TYPE_TYPE);
		if (EXPECTED(type != NULL)) {
			if (Z_TYPE_P(type) == IS_STRING) {
				out = true;
				return true;
			}
			if (EXPECTED(Z_TYPE_P(type) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_named_type, Z_OBJCE_P(type))) && EXPECTED(boolAt(Z_OBJ_P(type), pt_bra_named_type, 0, out))) return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isidentifier"), "isIdentifier", out);
}

zv::Val pt_parameter_adapter_get_name(zval *adapter)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_parameter_adapter, Z_OBJCE_P(adapter)))) {
		zval *inner = propertyAt(Z_OBJ_P(adapter), pt_bra_parameter_adapter, 0);
		if (EXPECTED(inner != NULL && Z_TYPE_P(inner) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_parameter, Z_OBJCE_P(inner)))) {
			zval *name = propertyAt(Z_OBJ_P(inner), pt_bra_parameter, 0);
			if (EXPECTED(name != NULL && Z_TYPE_P(name) == IS_STRING)) return zv::Val::copyOf(zv::Ref(name));
		}
	}
	return callOn(adapter, PT_LC("getname"), "getName");
}

/* }}} */

/* {{{ BetterReflection objects the adapters hand out */

zv::Val pt_reflection_adapter_get_better_reflection(zval *adapter)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(adapter);
		zval *inner = NULL;
		if (isKnown(pt_bra_method_adapter, ce)) {
			inner = propertyAt(Z_OBJ_P(adapter), pt_bra_method_adapter, 0);
		} else if (isKnown(pt_bra_property_adapter, ce)) {
			inner = propertyAt(Z_OBJ_P(adapter), pt_bra_property_adapter, 0);
		}
		if (EXPECTED(inner != NULL && Z_TYPE_P(inner) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(inner));
	}
	return callOn(adapter, PT_LC("getbetterreflection"), "getBetterReflection");
}

namespace {

/* $member->declaringClass / ->implementingClass of exactly a
 * ReflectionMethod or ReflectionProperty once resolved (their getters are
 * `$this->x ??= $this->reflector->reflectClass($this->xName)`), the getter
 * otherwise */
zv::Val memberClass(zval *member, uint32_t methodIndex, uint32_t propertyIndex, const char *lcname, size_t len, const char *name)
{
	if (EXPECTED(Z_TYPE_P(member) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(member);
		zval *value = NULL;
		if (isKnown(pt_bra_method, ce)) {
			value = propertyAt(Z_OBJ_P(member), pt_bra_method, methodIndex);
		} else if (isKnown(pt_bra_property, ce)) {
			value = propertyAt(Z_OBJ_P(member), pt_bra_property, propertyIndex);
		}
		if (value != NULL && Z_TYPE_P(value) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(value));
	}
	return callOn(member, lcname, len, name);
}

} // namespace

zv::Val pt_better_reflection_member_get_declaring_class(zval *member)
{
	return memberClass(member, PT_BRA_METHOD_DECLARING_CLASS, PT_BRA_PROPERTY_DECLARING_CLASS, PT_LC("getdeclaringclass"), "getDeclaringClass");
}

zv::Val pt_better_reflection_member_get_implementing_class(zval *member)
{
	return memberClass(member, PT_BRA_METHOD_IMPLEMENTING_CLASS, PT_BRA_PROPERTY_IMPLEMENTING_CLASS, PT_LC("getimplementingclass"), "getImplementingClass");
}

zv::Val pt_better_reflection_class_get_name(zval *betterReflectionClass)
{
	if (EXPECTED(isBetterReflectionClass(betterReflectionClass))) {
		zv::Val hold;
		zend_string *name = className(Z_OBJ_P(betterReflectionClass), hold);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return hold.isUndef() ? zv::Val::string(name) : std::move(hold);
	}
	return callOn(betterReflectionClass, PT_LC("getname"), "getName");
}

bool pt_better_reflection_class_is_trait(zval *betterReflectionClass, bool &out)
{
	if (EXPECTED(isBetterReflectionClass(betterReflectionClass)) && EXPECTED(boolAt(Z_OBJ_P(betterReflectionClass), pt_bra_class, PT_BRA_CLASS_IS_TRAIT, out))) return true;
	return callBoolOn(betterReflectionClass, PT_LC("istrait"), "isTrait", out);
}

zv::Val pt_method_adapter_get_parameter_names(zval *adapter)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL)) {
		/* array_map(fn (ReflectionParameter $p) => $p->getName(), $adapter->getParameters())
		 * — the adapter wraps array_values($this->parameters) */
		zval *parameters = propertyAt(method, pt_bra_method, PT_BRA_METHOD_PARAMETERS);
		if (EXPECTED(parameters != NULL && Z_TYPE_P(parameters) == IS_ARRAY)) {
			zv::Arr names = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(parameters)));
			bool known = true;
			for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
				zval *parameter = entry.value().deref().raw();
				zval *name = Z_TYPE_P(parameter) == IS_OBJECT && isKnown(pt_bra_parameter, Z_OBJCE_P(parameter)) ? propertyAt(Z_OBJ_P(parameter), pt_bra_parameter, 0) : NULL;
				if (UNEXPECTED(name == NULL || Z_TYPE_P(name) != IS_STRING)) {
					known = false;
					break;
				}
				names.push(zv::Ref(name));
			}
			if (EXPECTED(known)) return zv::Val(std::move(names));
		}
	}
	zv::Val parameters = callOn(adapter, PT_LC("getparameters"), "getParameters");
	if (UNEXPECTED(parameters.isUndef())) return zv::Val();
	zv::Arr names = zv::Arr::create(Z_TYPE_P(parameters.raw()) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(parameters.raw())) : 0);
	if (Z_TYPE_P(parameters.raw()) != IS_ARRAY) return zv::Val(std::move(names));
	for (zv::ArrayEntry entry : zv::ArrRef(parameters.raw())) {
		zv::Val name = pt_parameter_adapter_get_name(entry.value().deref().raw());
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zend_string *key = entry.stringKeyOrNull();
		if (key != NULL) {
			names.set(key, std::move(name));
		} else {
			names.arrRef().setIndex(entry.indexKey(), name.ref());
		}
	}
	return zv::Val(std::move(names));
}

/* }}} */

bool pt_reflection_adapter_is_member_adapter(zval *adapter)
{
	if (UNEXPECTED(Z_TYPE_P(adapter) != IS_OBJECT)) return false;
	zend_class_entry *ce = Z_OBJCE_P(adapter);
	return isKnown(pt_bra_method_adapter, ce) || isKnown(pt_bra_property_adapter, ce);
}

bool pt_method_adapter_is_constructor(zval *adapter, bool &out)
{
	zend_object *method = methodOf(adapter);
	if (EXPECTED(method != NULL)) {
		int isConstructor = methodIsConstructor(method);
		if (EXPECTED(isConstructor >= 0)) {
			out = isConstructor == 1;
			return true;
		}
	}
	return callBoolOn(adapter, PT_LC("isconstructor"), "isConstructor", out);
}

zv::Val pt_member_adapter_get_declaring_class_name(zval *adapter)
{
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(adapter);
		zval *inner = NULL;
		if (isKnown(pt_bra_method_adapter, ce)) {
			inner = propertyAt(Z_OBJ_P(adapter), pt_bra_method_adapter, 0);
		} else if (isKnown(pt_bra_property_adapter, ce)) {
			inner = propertyAt(Z_OBJ_P(adapter), pt_bra_property_adapter, 0);
		}
		if (EXPECTED(inner != NULL && Z_TYPE_P(inner) == IS_OBJECT)) {
			/* new ReflectionClass($this->betterReflection…->getImplementingClass()) asked getName() */
			zv::Val implementingClass = pt_better_reflection_member_get_implementing_class(inner);
			if (UNEXPECTED(implementingClass.isUndef())) return zv::Val();
			return pt_better_reflection_class_get_name(implementingClass.raw());
		}
	}
	zv::Val declaringClass = callOn(adapter, PT_LC("getdeclaringclass"), "getDeclaringClass");
	if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
	return callOn(declaringClass.raw(), PT_LC("getname"), "getName");
}

bool pt_class_adapter_constant_declaring_class_name(zval *adapter, zend_string *name, zv::Val &out)
{
	/*
	 * $adapter->hasConstant($name) and $adapter->getReflectionConstant($name)
	 * !== false and ->getDeclaringClass()->getName(): for exactly an
	 * Adapter\ReflectionClass over exactly a ReflectionClass (not an enum,
	 * whose cases the adapter consults first) the $cachedConstants memo
	 * getConstants() answers from, the constant's implementing class (what
	 * the constant adapter's getDeclaringClass() wraps) asked getName()
	 */
	if (EXPECTED(Z_TYPE_P(adapter) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_class_adapter, Z_OBJCE_P(adapter)))) {
		zval *inner = propertyAt(Z_OBJ_P(adapter), pt_bra_class_adapter, 0);
		if (EXPECTED(inner != NULL && Z_TYPE_P(inner) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_class, Z_OBJCE_P(inner)))) {
			if (ZSTR_LEN(name) == 0) {
				out = zv::Val::null();
				return true;
			}
			zval *constants = propertyAt(Z_OBJ_P(inner), pt_bra_class, PT_BRA_CLASS_CACHED_CONSTANTS);
			if (constants != NULL && Z_TYPE_P(constants) == IS_ARRAY) {
				zval *constant = zend_symtable_find(Z_ARRVAL_P(constants), name);
				if (constant != NULL) ZVAL_DEREF(constant);
				if (constant == NULL || Z_TYPE_P(constant) == IS_NULL) {
					out = zv::Val::null();
					return true;
				}
				if (EXPECTED(Z_TYPE_P(constant) == IS_OBJECT) && EXPECTED(isKnown(pt_bra_class_constant, Z_OBJCE_P(constant)))) {
					zval *implementingClass = propertyAt(Z_OBJ_P(constant), pt_bra_class_constant, 0);
					zv::Val implementingHold;
					if (implementingClass == NULL || Z_TYPE_P(implementingClass) != IS_OBJECT) {
						implementingHold = pt_type_call(Z_OBJ_P(constant), PT_LC("getimplementingclass"), 0, NULL);
						if (UNEXPECTED(implementingHold.isUndef())) return false;
						implementingClass = implementingHold.raw();
					}
					out = pt_better_reflection_class_get_name(implementingClass);
					return !out.isUndef();
				}
			}
		}
	}

	zval nameArg;
	ZVAL_STR(&nameArg, name);
	bool has;
	zv::Val hasConstant = callOn(adapter, PT_LC("hasconstant"), "hasConstant", 1, &nameArg);
	if (UNEXPECTED(hasConstant.isUndef())) return false;
	has = zend_is_true(hasConstant.raw());
	if (!has) {
		out = zv::Val::null();
		return true;
	}
	zv::Val reflectionConstant = callOn(adapter, PT_LC("getreflectionconstant"), "getReflectionConstant", 1, &nameArg);
	if (UNEXPECTED(reflectionConstant.isUndef())) return false;
	if (Z_TYPE_P(reflectionConstant.raw()) == IS_FALSE) {
		out = zv::Val::null();
		return true;
	}
	zv::Val declaringClass = callOn(reflectionConstant.raw(), PT_LC("getdeclaringclass"), "getDeclaringClass");
	if (UNEXPECTED(declaringClass.isUndef())) return false;
	out = callOn(declaringClass.raw(), PT_LC("getname"), "getName");
	return !out.isUndef();
}
