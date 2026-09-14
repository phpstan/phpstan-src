/*
 * Native readers of PHPStan\Reflection\ClassReflection's memo slots and of
 * a MutatingScope's ScopeContext.
 *
 * ClassReflection stays a PHP class (final, userland); the Type kernel
 * calls a handful of its trivial memoized getters millions of times per run
 * (getName() 1.3M, isGeneric() 1.6M, hasMethod() 0.8M, getCacheKey() 0.6M
 * in a self-analysis of src/Analyser, src/Rules and src/Type). Each getter
 * reads a private property and returns it once it is computed, so the
 * readers here answer straight from that property slot when it holds the
 * answer and call the PHP method otherwise — lazy computation, memoization
 * and the Error on an uninitialized slot stay the twin's by construction.
 * Only an object of exactly the ClassReflection class entry (resolved once
 * through the class map, its slot offsets cached per class entry) takes the
 * fast path; anything else goes through the method as before.
 *
 * The same for MutatingScope::isInClass() / getClassReflection(), which
 * read $this->context->getClassReflection(): when the scope is exactly a
 * MutatingScope (a PHP subclass may override them) holding a native
 * ScopeContext (ScopeContext.cpp), the class reflection comes out of the
 * context's slot.
 *
 * The class entries are looked up without autoloading: an object of a class
 * that is not declared cannot exist, so an undeclared ClassReflection or
 * MutatingScope simply means "not this class" — no fast path, no loading
 * the PHP code would not have loaded either.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"

namespace {

/* the property slot offsets of a class entry, resolved once; a user
 * class's entry is per request without opcache, so rinit forgets them */
struct ClassReflectionSlots
{
	zend_class_entry *ce;
	uint32_t name;
	uint32_t isGeneric;
	uint32_t cacheKey;
	uint32_t hasMethodCache;
	uint32_t finalByKeywordOverride;
	uint32_t reflection;
};

struct ScopeSlots
{
	zend_class_entry *ce;
	uint32_t context;
};

ClassReflectionSlots pt_cr_slots = { NULL, 0, 0, 0, 0, 0, 0 };
ScopeSlots pt_ms_slots = { NULL, 0 };

/* the slots of an object that is exactly a ClassReflection; NULL when it is
 * of some other class (the caller then calls the method) — or, with
 * `error` set and an exception pending, when the class map cannot resolve
 * the class at all */
const ClassReflectionSlots *classReflectionSlots(zend_object *object, bool &error)
{
	error = false;
	if (EXPECTED(object->ce == pt_cr_slots.ce)) return &pt_cr_slots;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_CLASS_REFLECTION);
	if (ce == NULL) {
		error = EG(exception) != NULL;
		return NULL;
	}
	if (object->ce != ce) return NULL;
	int32_t name = pt_instance_prop_offset(ce, PT_LC("name"));
	int32_t isGeneric = pt_instance_prop_offset(ce, PT_LC("isGeneric"));
	int32_t cacheKey = pt_instance_prop_offset(ce, PT_LC("cacheKey"));
	int32_t hasMethodCache = pt_instance_prop_offset(ce, PT_LC("hasMethodCache"));
	int32_t finalByKeywordOverride = pt_instance_prop_offset(ce, PT_LC("finalByKeywordOverride"));
	int32_t reflection = pt_instance_prop_offset(ce, PT_LC("reflection"));
	if (UNEXPECTED(name < 0 || isGeneric < 0 || cacheKey < 0 || hasMethodCache < 0 || finalByKeywordOverride < 0 || reflection < 0)) {
		/* not the twin these readers know: every call goes through the method */
		return NULL;
	}
	pt_cr_slots = { ce, (uint32_t) name, (uint32_t) isGeneric, (uint32_t) cacheKey, (uint32_t) hasMethodCache, (uint32_t) finalByKeywordOverride, (uint32_t) reflection };
	return &pt_cr_slots;
}

/* $object->method(...$args) through the object's own class entry, coerced
 * to bool; false = pending exception */
[[nodiscard]] bool callBool(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv, bool &out)
{
	zv::Val result = pt_type_call(object, lcname, len, argc, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* the $classReflection slot of the scope's context when the fast path
 * applies (the scope exactly a MutatingScope, its context a native
 * ScopeContext); NULL otherwise, with `error` set when the class map
 * failed */
zval *scopeClassReflectionSlot(zend_object *scope, bool &error)
{
	error = false;
	if (scope->ce != pt_ms_slots.ce) {
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_MUTATING_SCOPE);
		if (ce == NULL) {
			error = EG(exception) != NULL;
			return NULL;
		}
		if (scope->ce != ce) return NULL;
		int32_t context = pt_instance_prop_offset(ce, PT_LC("context"));
		if (UNEXPECTED(context < 0)) return NULL;
		pt_ms_slots = { ce, (uint32_t) context };
	}
	zval *context = OBJ_PROP(scope, pt_ms_slots.context);
	if (Z_TYPE_P(context) != IS_OBJECT || Z_OBJCE_P(context) != pt_ce_scope_context) return NULL;
	return pt_scope_context_class_reflection(Z_OBJ_P(context));
}

} // namespace

void pt_class_reflection_access_rinit()
{
	pt_cr_slots.ce = NULL;
	pt_ms_slots.ce = NULL;
}

/* {{{ ClassReflection */

/* return $this->name ??= $this->reflection->getName(); — the memo once it
 * is a string, the method until then */
zv::Val pt_class_reflection_get_name(zend_object *classReflection)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *name = OBJ_PROP(classReflection, slots->name);
		if (EXPECTED(Z_TYPE_P(name) == IS_STRING)) return zv::Val::string(Z_STR_P(name));
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(classReflection, PT_LC("getname"), 0, NULL);
}

/* $cacheKey = $this->cacheKey; if ($cacheKey !== null) return $this->cacheKey; */
zv::Val pt_class_reflection_get_cache_key(zend_object *classReflection)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *cacheKey = OBJ_PROP(classReflection, slots->cacheKey);
		if (EXPECTED(Z_TYPE_P(cacheKey) == IS_STRING)) return zv::Val::string(Z_STR_P(cacheKey));
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(classReflection, PT_LC("getcachekey"), 0, NULL);
}

/* return $this->reflection; — the constructor's argument, an object once
 * initialized (the method's Error before that) */
zv::Val pt_class_reflection_get_native_reflection(zend_object *classReflection)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *reflection = OBJ_PROP(classReflection, slots->reflection);
		if (EXPECTED(Z_TYPE_P(reflection) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(reflection));
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(classReflection, PT_LC("getnativereflection"), 0, NULL);
}

/* if ($this->isGeneric === null) { ... } return $this->isGeneric; — the
 * memo once it is a bool */
bool pt_class_reflection_is_generic(zend_object *classReflection, bool &out)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *isGeneric = OBJ_PROP(classReflection, slots->isGeneric);
		if (EXPECTED(Z_TYPE_P(isGeneric) == IS_TRUE || Z_TYPE_P(isGeneric) == IS_FALSE)) {
			out = Z_TYPE_P(isGeneric) == IS_TRUE;
			return true;
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(classReflection, PT_LC("isgeneric"), 0, NULL, out);
}

/* if (array_key_exists($methodName, $this->hasMethodCache)) return
 * $this->hasMethodCache[$methodName]; — the cached bool (a string offset,
 * so a numeric string is an integer key: the symtable lookup); the method
 * computes and caches otherwise */
bool pt_class_reflection_has_method(zend_object *classReflection, zval *methodName, bool &out)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL && Z_TYPE_P(methodName) == IS_STRING) {
		zval *cache = OBJ_PROP(classReflection, slots->hasMethodCache);
		if (EXPECTED(Z_TYPE_P(cache) == IS_ARRAY)) {
			zval *cached = zend_symtable_find(Z_ARRVAL_P(cache), Z_STR_P(methodName));
			if (cached != NULL && (Z_TYPE_P(cached) == IS_TRUE || Z_TYPE_P(cached) == IS_FALSE)) {
				out = Z_TYPE_P(cached) == IS_TRUE;
				return true;
			}
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(classReflection, PT_LC("hasmethod"), 1, methodName, out);
}

/* return $this->finalByKeywordOverride !== null; — a pure read of the
 * promoted ?bool (the method's Error while uninitialized) */
bool pt_class_reflection_has_final_by_keyword_override(zend_object *classReflection, bool &out)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *override = OBJ_PROP(classReflection, slots->finalByKeywordOverride);
		if (Z_TYPE_P(override) == IS_NULL) {
			out = false;
			return true;
		}
		if (EXPECTED(Z_TYPE_P(override) == IS_TRUE || Z_TYPE_P(override) == IS_FALSE)) {
			out = true;
			return true;
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(classReflection, PT_LC("hasfinalbykeywordoverride"), 0, NULL, out);
}

/* return $this->reflection instanceof ReflectionEnum && $this->reflection->isEnum();
 * — false without a call when the reflection is not a ReflectionEnum
 * (`instanceof` sees an undeclared class as "no instance of it", hence the
 * no-autoload lookup); the method decides otherwise */
bool pt_class_reflection_is_enum(zend_object *classReflection, bool &out)
{
	bool error;
	const ClassReflectionSlots *slots = classReflectionSlots(classReflection, error);
	if (slots != NULL) {
		zval *reflection = OBJ_PROP(classReflection, slots->reflection);
		if (EXPECTED(Z_TYPE_P(reflection) == IS_OBJECT)) {
			zend_class_entry *enumCe = pt_class_loaded(PT_CLASS_REFLECTION_ENUM);
			if (enumCe == NULL) {
				if (UNEXPECTED(EG(exception))) return false;
				out = false;
				return true;
			}
			if (!instanceof_function(Z_OBJCE_P(reflection), enumCe)) {
				out = false;
				return true;
			}
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(classReflection, PT_LC("isenum"), 0, NULL, out);
}

/* }}} */

/* {{{ MutatingScope */

/* return $this->context->getClassReflection() !== null; */
bool pt_scope_is_in_class(zend_object *scope, bool &out)
{
	bool error;
	zval *classReflection = scopeClassReflectionSlot(scope, error);
	if (classReflection != NULL) {
		if (Z_TYPE_P(classReflection) == IS_NULL) {
			out = false;
			return true;
		}
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT)) {
			out = true;
			return true;
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(scope, PT_LC("isinclass"), 0, NULL, out);
}

/* return $this->context->getClassReflection(); */
zv::Val pt_scope_get_class_reflection(zend_object *scope)
{
	bool error;
	zval *classReflection = scopeClassReflectionSlot(scope, error);
	if (classReflection != NULL) {
		if (Z_TYPE_P(classReflection) == IS_NULL) return zv::Val::null();
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(classReflection));
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(scope, PT_LC("getclassreflection"), 0, NULL);
}

/* }}} */
