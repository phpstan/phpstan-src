/*
 * Native readers of the reflection provider's memo slots the Type kernel
 * consults on its hottest paths — the classes stay PHP (userland), only
 * their already-computed answers are read from their property slots:
 *
 * - ReflectionProviderStaticAccessor::getInstance(): the registered
 *   provider from the twin's `private static ?ReflectionProvider
 *   $instance` (the method, which throws, while it is null);
 * - MemoizingReflectionProvider::hasClass() / getClass(): the memoized
 *   answer from $knownClasses / $unknownClasses / $classes (the method,
 *   which asks the decorated provider and memoizes, on a miss).
 *
 * Same contract as the scope readers of ScopeContext.cpp: only an object of exactly
 * the twin's class entry (resolved once through the class map, without
 * autoloading — an object of an undeclared class cannot exist) takes the
 * fast path, its slot offsets cached per class entry and forgotten at
 * rinit; anything the slot cannot answer calls the PHP method, so lazy
 * computation, memoization and every error stay the twin's by
 * construction.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"

namespace {

/* the instance-property slot offsets of the provider's class entry,
 * resolved once */
struct MemoizingProviderSlots
{
	zend_class_entry *ce;
	uint32_t knownClasses;
	uint32_t unknownClasses;
	uint32_t classes;
};

/* a class entry whose static slot is read: the slot, NULL until resolved */
struct StaticSlot
{
	zend_class_entry *ce;
	zval *slot;
};

MemoizingProviderSlots pt_mrp_slots = { NULL, 0, 0, 0 };
StaticSlot pt_rpsa_slot = { NULL, NULL };

/* the static-property slot of a declared user class once the engine has
 * initialized its statics (the constants updated, the table allocated —
 * what the first static access through the engine does); NULL until
 * then, so the caller takes the method, which performs that first access */
zval *staticSlot(zend_class_entry *ce, const char *name, size_t len)
{
	if (UNEXPECTED(!(ce->ce_flags & ZEND_ACC_CONSTANTS_UPDATED)) || UNEXPECTED(CE_STATIC_MEMBERS(ce) == NULL)) return NULL;
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, name, len);
	if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) == 0)) return NULL;
	return CE_STATIC_MEMBERS(ce) + info->offset;
}

/* the slots of an object that is exactly a MemoizingReflectionProvider;
 * NULL when it is of some other class (the caller then calls the method)
 * — or, with `error` set and an exception pending, when the class map
 * cannot resolve the class at all */
const MemoizingProviderSlots *memoizingProviderSlots(zend_object *provider, bool &error)
{
	error = false;
	if (EXPECTED(provider->ce == pt_mrp_slots.ce)) return &pt_mrp_slots;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_MEMOIZING_REFLECTION_PROVIDER);
	if (ce == NULL) {
		error = EG(exception) != NULL;
		return NULL;
	}
	if (provider->ce != ce) return NULL;
	int32_t knownClasses = pt_instance_prop_offset(ce, PT_LC("knownClasses"));
	int32_t unknownClasses = pt_instance_prop_offset(ce, PT_LC("unknownClasses"));
	int32_t classes = pt_instance_prop_offset(ce, PT_LC("classes"));
	if (UNEXPECTED(knownClasses < 0 || unknownClasses < 0 || classes < 0)) {
		/* not the twin these readers know: every call goes through the method */
		return NULL;
	}
	pt_mrp_slots = { ce, (uint32_t) knownClasses, (uint32_t) unknownClasses, (uint32_t) classes };
	return &pt_mrp_slots;
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

/* isset($array[$key]) for a string offset: the symtable lookup (a numeric
 * string is an integer key), an entry holding null is not set */
bool issetStringOffset(zval *array, zend_string *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) return false;
	zval *found = zend_symtable_find(Z_ARRVAL_P(array), key);
	if (found == NULL) return false;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) != IS_NULL;
}

} // namespace

void pt_reflection_access_rinit()
{
	pt_mrp_slots.ce = NULL;
	pt_rpsa_slot = { NULL, NULL };
}

/* {{{ ReflectionProviderStaticAccessor */

/* if (self::$instance === null) throw ...; return self::$instance; — the
 * registered provider out of the static slot; the method (which throws
 * the MissingStaticAccessorInstanceException) until one is registered */
zv::Val pt_reflection_provider_instance()
{
	if (UNEXPECTED(pt_rpsa_slot.slot == NULL)) {
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR);
		if (ce == NULL) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			zval *slot = staticSlot(ce, PT_LC("instance"));
			if (slot != NULL) {
				pt_rpsa_slot = { ce, slot };
			}
		}
	}
	if (EXPECTED(pt_rpsa_slot.slot != NULL)) {
		zval *instance = pt_rpsa_slot.slot;
		ZVAL_DEREF(instance);
		if (EXPECTED(Z_TYPE_P(instance) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(instance));
	}
	return pt_type_call_static(PT_CLASS_REFLECTION_PROVIDER_STATIC_ACCESSOR, PT_LC("getinstance"), 0, NULL);
}

/* }}} */

/* {{{ MemoizingReflectionProvider */

/* $lower = strtolower($className); if (isset($this->knownClasses[$lower]))
 * return true; if (isset($this->unknownClasses[$className])) return false;
 * — the memoized answer; the method asks the decorated provider otherwise.
 * strtolower() is ASCII-only since PHP 8.2, as zend_string_tolower() is. */
bool pt_reflection_provider_has_class(zend_object *provider, zval *className, bool &out)
{
	bool error;
	const MemoizingProviderSlots *slots = memoizingProviderSlots(provider, error);
	if (slots != NULL && Z_TYPE_P(className) == IS_STRING) {
		zend_string *lower = zend_string_tolower(Z_STR_P(className));
		bool known = issetStringOffset(OBJ_PROP(provider, slots->knownClasses), lower);
		zend_string_release(lower);
		if (known) {
			out = true;
			return true;
		}
		if (issetStringOffset(OBJ_PROP(provider, slots->unknownClasses), Z_STR_P(className))) {
			out = false;
			return true;
		}
	} else if (UNEXPECTED(error)) {
		return false;
	}
	return callBool(provider, PT_LC("hasclass"), 1, className, out);
}

/* the same as a boolean value; UNDEF = pending exception */
zv::Val pt_reflection_provider_has_class_zv(zend_object *provider, zval *className)
{
	bool out;
	if (UNEXPECTED(!pt_reflection_provider_has_class(provider, className, out))) return zv::Val();
	return zv::Val::boolean(out);
}

/* return $this->classes[strtolower($className)] ??= $this->provider->getClass($className);
 * — the memoized reflection; the method resolves and memoizes otherwise */
zv::Val pt_reflection_provider_get_class(zend_object *provider, zval *className)
{
	bool error;
	const MemoizingProviderSlots *slots = memoizingProviderSlots(provider, error);
	if (slots != NULL && Z_TYPE_P(className) == IS_STRING) {
		zval *classes = OBJ_PROP(provider, slots->classes);
		if (EXPECTED(Z_TYPE_P(classes) == IS_ARRAY)) {
			zend_string *lower = zend_string_tolower(Z_STR_P(className));
			zval *found = zend_symtable_find(Z_ARRVAL_P(classes), lower);
			zend_string_release(lower);
			if (found != NULL) {
				ZVAL_DEREF(found);
				if (EXPECTED(Z_TYPE_P(found) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(found));
			}
		}
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	return pt_type_call(provider, PT_LC("getclass"), 1, className);
}

/* }}} */
