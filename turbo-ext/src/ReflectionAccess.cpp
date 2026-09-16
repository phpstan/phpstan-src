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
 *   which asks the decorated provider and memoizes, on a miss);
 * - LazyClassReflectionExtensionRegistryProvider::getRegistry() followed by
 *   one ClassReflectionExtensionRegistry getter: the built registry from the
 *   provider's $registry memo and the extension from the registry's own
 *   constructor-written slot (the methods while the memo is still null, or
 *   for any other provider implementation).
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

/* the $registry memo slot of the lazy registry provider's class entry */
struct RegistryProviderSlots
{
	zend_class_entry *ce;
	uint32_t registry;
};

/* the registry's constructor-written slots, in pt_registry_member order */
struct RegistrySlots
{
	zend_class_entry *ce;
	uint32_t members[PT_REGISTRY_MEMBER_COUNT];
};

RegistryProviderSlots pt_registry_provider_slots = { NULL, 0 };

/* the $extensions memo slot of LazyExtensionsCollection's class entry */
struct LazyExtensionsCollectionSlots
{
	zend_class_entry *ce;
	uint32_t extensions;
};

LazyExtensionsCollectionSlots pt_lazy_extensions_collection_slots = { NULL, 0 };

/* ExtensionClassHelper's `private static array $extensionClassNames` memo */
StaticSlot pt_extension_class_names_slot = { NULL, NULL };
RegistrySlots pt_registry_slots = { NULL, { 0, 0, 0, 0, 0, 0 } };

/* the registry property and the twin's getter behind each member */
struct RegistryMemberNames
{
	const char *property;
	const char *getter;
};

const RegistryMemberNames pt_registry_member_names[PT_REGISTRY_MEMBER_COUNT] = {
	/* PT_REGISTRY_PHP_CLASS_REFLECTION_EXTENSION */ { "phpClassReflectionExtension", "getphpclassreflectionextension" },
	/* PT_REGISTRY_METHODS_EXTENSIONS */ { "methodsClassReflectionExtensions", "getmethodsclassreflectionextensions" },
	/* PT_REGISTRY_PROPERTIES_EXTENSIONS */ { "propertiesClassReflectionExtensions", "getpropertiesclassreflectionextensions" },
	/* PT_REGISTRY_REQUIRE_EXTENDS_METHODS_EXTENSION */ { "requireExtendsMethodsClassReflectionExtension", "getrequireextendsmethodsclassreflectionextension" },
	/* PT_REGISTRY_REQUIRE_EXTENDS_PROPERTIES_EXTENSION */ { "requireExtendsPropertiesClassReflectionExtension", "getrequireextendspropertyclassreflectionextension" },
	/* PT_REGISTRY_ALLOWED_SUB_TYPES_EXTENSIONS */ { "allowedSubTypesClassReflectionExtensions", "getallowedsubtypesclassreflectionextensions" },
};

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
	pt_registry_provider_slots.ce = NULL;
	pt_registry_slots.ce = NULL;
	pt_lazy_extensions_collection_slots.ce = NULL;
	pt_extension_class_names_slot = { NULL, NULL };
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

/* {{{ ClassReflectionExtensionRegistryProvider */

namespace {

/* the memo slot of an object that is exactly a
 * LazyClassReflectionExtensionRegistryProvider; NULL when it is of some other
 * class (the caller then calls getRegistry()) — or, with `error` set and an
 * exception pending, when the class map cannot resolve the class at all */
const RegistryProviderSlots *registryProviderSlots(zend_object *provider, bool &error)
{
	error = false;
	if (EXPECTED(provider->ce == pt_registry_provider_slots.ce)) return &pt_registry_provider_slots;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_LAZY_CLASS_REFLECTION_EXTENSION_REGISTRY_PROVIDER);
	if (ce == NULL) {
		error = EG(exception) != NULL;
		return NULL;
	}
	if (provider->ce != ce) return NULL;
	int32_t registry = pt_instance_prop_offset(ce, PT_LC("registry"));
	if (UNEXPECTED(registry < 0)) {
		/* not the twin this reader knows: every call goes through the method */
		return NULL;
	}
	pt_registry_provider_slots = { ce, (uint32_t) registry };
	return &pt_registry_provider_slots;
}

/* the slots of an object that is exactly a ClassReflectionExtensionRegistry;
 * the same contract as registryProviderSlots() */
const RegistrySlots *registrySlots(zend_object *registry, bool &error)
{
	error = false;
	if (EXPECTED(registry->ce == pt_registry_slots.ce)) return &pt_registry_slots;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_CLASS_REFLECTION_EXTENSION_REGISTRY);
	if (ce == NULL) {
		error = EG(exception) != NULL;
		return NULL;
	}
	if (registry->ce != ce) return NULL;
	RegistrySlots slots;
	slots.ce = ce;
	for (int i = 0; i < PT_REGISTRY_MEMBER_COUNT; i++) {
		const char *property = pt_registry_member_names[i].property;
		int32_t offset = pt_instance_prop_offset(ce, property, strlen(property));
		if (UNEXPECTED(offset < 0)) return NULL;
		slots.members[i] = (uint32_t) offset;
	}
	pt_registry_slots = slots;
	return &pt_registry_slots;
}

} // namespace

/*
 * $this->classReflectionExtensionRegistryProvider->getRegistry()-><getter>()
 * — the two accessor hops every member lookup of the native ClassReflection
 * starts with, as property reads.
 *
 * The lazy provider builds its registry once and keeps it in $registry
 * forever (it drops its container reference right after), and the registry is
 * a final value class whose slots only its constructor writes — so a non-null
 * memo and the slot behind the getter are what the two methods would answer,
 * by construction. Anything else — the first call of a run, a provider or
 * registry of some other class — takes the methods.
 */
zv::Val pt_class_reflection_extension_registry_member(zend_object *provider, pt_registry_member member)
{
	bool error;
	zv::Val owned;
	zval *registry = NULL;
	const RegistryProviderSlots *providerSlots = registryProviderSlots(provider, error);
	if (EXPECTED(providerSlots != NULL)) {
		zval *memo = OBJ_PROP(provider, providerSlots->registry);
		if (EXPECTED(Z_TYPE_P(memo) == IS_OBJECT)) {
			registry = memo;
		}
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	if (registry == NULL) {
		owned = pt_type_call(provider, PT_LC("getregistry"), 0, NULL);
		if (UNEXPECTED(owned.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(owned.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", pt_registry_member_names[member].getter, zend_zval_value_name(owned.raw()));
			return zv::Val();
		}
		registry = owned.raw();
	}

	const RegistrySlots *slots = registrySlots(Z_OBJ_P(registry), error);
	if (EXPECTED(slots != NULL)) {
		zval *value = OBJ_PROP(Z_OBJ_P(registry), slots->members[member]);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(value));
		/* a promoted property the constructor never wrote — the getter, which
		 * raises the twin's Error */
	} else if (UNEXPECTED(error)) {
		return zv::Val();
	}
	const char *getter = pt_registry_member_names[member].getter;
	return pt_type_call(Z_OBJ_P(registry), getter, strlen(getter), 0, NULL);
}

/* }}} */

/* {{{ LazyExtensionsCollection */

/* return $this->extensions ??= array_values(...) — the memoized list out of
 * the slot once the first getAll() filled it; the method before that and for
 * any other ExtensionsCollection */
zv::Val pt_extensions_collection_get_all(zend_object *collection)
{
	if (EXPECTED(collection->ce == pt_lazy_extensions_collection_slots.ce)) {
		zval *extensions = OBJ_PROP(collection, pt_lazy_extensions_collection_slots.extensions);
		if (EXPECTED(Z_TYPE_P(extensions) == IS_ARRAY)) return zv::Val::copyOf(zv::Ref(extensions));
	} else if (pt_lazy_extensions_collection_slots.ce == NULL) {
		zend_class_entry *ce = pt_class_loaded(PT_CLASS_LAZY_EXTENSIONS_COLLECTION);
		if (ce == NULL) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			int32_t extensions = pt_instance_prop_offset(ce, PT_LC("extensions"));
			if (EXPECTED(extensions >= 0)) {
				pt_lazy_extensions_collection_slots = { ce, (uint32_t) extensions };
				if (collection->ce == ce) {
					zval *slot = OBJ_PROP(collection, (uint32_t) extensions);
					if (Z_TYPE_P(slot) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(slot));
				}
			}
		}
	}
	return pt_type_call(collection, PT_LC("getall"), 0, NULL);
}

/* }}} */

/* {{{ ExtensionClassHelper */

/* ExtensionClassHelper::getExtensionClassNames($reflectionProvider, $className):
 * the memoized list out of the static $extensionClassNames once computed for
 * the class, the method (which computes and memoizes it) otherwise */
zv::Val pt_extension_class_helper_get_extension_class_names(zval *reflectionProvider, zval *className)
{
	if (EXPECTED(Z_TYPE_P(className) == IS_STRING)) {
		if (UNEXPECTED(pt_extension_class_names_slot.slot == NULL)) {
			zend_class_entry *ce = pt_class_loaded(PT_CLASS_EXTENSION_CLASS_HELPER);
			if (ce == NULL) {
				if (UNEXPECTED(EG(exception))) return zv::Val();
			} else {
				zval *slot = staticSlot(ce, PT_LC("extensionClassNames"));
				if (slot != NULL) {
					pt_extension_class_names_slot = { ce, slot };
				}
			}
		}
		if (EXPECTED(pt_extension_class_names_slot.slot != NULL)) {
			zval *memo = pt_extension_class_names_slot.slot;
			ZVAL_DEREF(memo);
			if (EXPECTED(Z_TYPE_P(memo) == IS_ARRAY)) {
				zval *names = zend_symtable_find(Z_ARRVAL_P(memo), Z_STR_P(className));
				if (EXPECTED(names != NULL)) return zv::Val::copyOf(zv::Ref(names).deref());
			}
		}
	}
	zv::Args args{reflectionProvider, className};
	return pt_type_call_static(PT_CLASS_EXTENSION_CLASS_HELPER, PT_LC("getextensionclassnames"), 2, args);
}

/* }}} */
