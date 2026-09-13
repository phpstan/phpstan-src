/*
 * Fluent, zero-cost class registration — PHP-CPP's extension.add() look
 * without its call-time cost. The builder assembles the same
 * zend_internal_arg_info / zend_function_entry structures the PHP_METHOD +
 * ZEND_BEGIN_ARG_INFO_EX + PHP_ME macro triple produced, and hands the
 * engine the raw handler pointers directly — no trampoline, no Php::Value
 * boxing, byte-identical dispatch. Handlers are plain functions or
 * non-capturing lambdas with the (INTERNAL_FUNCTION_PARAMETERS) signature,
 * so each method is declared exactly once: name, flags, signature and body
 * together at the registration site.
 *
 * Two registration paths share the builder:
 * - register_() registers an internal class at module startup (extension-only
 *   classes such as PHPStanTurbo\Runtime).
 * - shadow() records a plan for a class that shadows a PHP implementation.
 *   Plans are materialised by Runtime::activateShadowing() (Shadow.cpp) as
 *   linked *user* classes carrying the PHP twin's real name — see README.md,
 *   "How it works".
 *
 * Everything here runs once at module startup; allocations are persistent
 * (the engine references the arginfo arrays for the process lifetime).
 */

#ifndef PHPSTANTURBO_REG_H
#define PHPSTANTURBO_REG_H

#include "support.h"

#include <cstring>
#include <initializer_list>
#include <vector>

namespace reg {

constexpr uint32_t Public = ZEND_ACC_PUBLIC;
constexpr uint32_t Private = ZEND_ACC_PRIVATE;
constexpr uint32_t Static = ZEND_ACC_STATIC;
constexpr uint32_t PublicStatic = ZEND_ACC_PUBLIC | ZEND_ACC_STATIC;

/* One parameter's metadata, mirroring what the ZEND_ARG_* macros encode. */
struct Arg
{
	const char *name;
	uint32_t typeMask;               /* MAY_BE_* mask incl. by-ref/variadic bits */
	const char *className = nullptr; /* persistent literal for object types */
	const char *defaultValue = nullptr; /* persistent literal of the default value's PHP source ("[]"), needed for named-argument skipping and reflection */
};

/* an optional parameter with its default value's PHP source (e.g. "[]") —
 * without it the engine refuses to skip the parameter via named arguments */
inline Arg withDefault(Arg arg, const char *defaultValue)
{
	arg.defaultValue = defaultValue;
	return arg;
}

namespace detail {

inline uint32_t flagBits(bool byRef, bool variadic)
{
	return _ZEND_ARG_INFO_FLAGS(byRef ? ZEND_SEND_BY_REF : ZEND_SEND_BY_VAL, variadic ? 1 : 0, 0);
}

inline uint32_t codeMask(zend_uchar code, bool nullable)
{
	uint32_t mask = code == _IS_BOOL ? MAY_BE_BOOL : (uint32_t) (1u << code);
	return mask | (nullable ? MAY_BE_NULL : 0);
}

} // namespace detail

/* an untyped parameter (ZEND_ARG_INFO) */
inline Arg any(const char *name, bool byRef = false)
{
	return { name, detail::flagBits(byRef, false), nullptr };
}

inline Arg longArg(const char *name)
{
	return { name, detail::codeMask(IS_LONG, false) | detail::flagBits(false, false), nullptr };
}

inline Arg boolArg(const char *name)
{
	return { name, detail::codeMask(_IS_BOOL, false) | detail::flagBits(false, false), nullptr };
}

inline Arg stringArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_STRING, nullable) | detail::flagBits(false, false), nullptr };
}

inline Arg arrayArg(const char *name)
{
	return { name, detail::codeMask(IS_ARRAY, false) | detail::flagBits(false, false), nullptr };
}

inline Arg callableArg(const char *name)
{
	return { name, MAY_BE_CALLABLE | detail::flagBits(false, false), nullptr };
}

inline Arg objectArg(const char *name, bool nullable = false)
{
	return { name, detail::codeMask(IS_OBJECT, nullable) | detail::flagBits(false, false), nullptr };
}

/* object of a specific class; className must be a persistent literal */
inline Arg obj(const char *name, const char *className, bool nullable = false)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | (nullable ? MAY_BE_NULL : 0) | detail::flagBits(false, false), className };
}

inline Arg variadicObj(const char *name, const char *className)
{
	return { name, _ZEND_TYPE_LITERAL_NAME_BIT | detail::flagBits(false, true), className };
}

enum class PropertyKind
{
	Long,
	Null,
	Bool,
	EmptyArray,
	PublicReadonlyTyped, /* UNDEF default, defaultValue carries the MAY_BE_* type mask */
};

struct Property
{
	const char *name;
	PropertyKind kind;
	uint32_t visibility;
	zend_long defaultValue;
};

struct Constant
{
	const char *name;
	zend_long value;
};

/*
 * Everything a shadowing class needs to be declared at activation time: the
 * PHP twin's real name (the class is registered under it, or under
 * "<prefix><short name>" in the differential tests), its final flag, parent
 * and interfaces, and the members. The entries vector ends with the sentinel
 * zend_register_functions() expects. `out` receives the linked class entry.
 */
struct ShadowPlan
{
	const char *name;
	uint32_t flags;
	const char *parentName;
	std::vector<const char *> interfaces;
	std::vector<zend_function_entry> entries;
	std::vector<Property> properties;
	std::vector<Constant> constants;
	zend_class_entry **out;
	zend_class_entry *ce;
};

/* declares the builder's properties and constants on a registered or
 * initialised class entry; works for internal and user classes alike since
 * the engine picks the allocation from ce->type */
inline void declareMembers(zend_class_entry *ce, const std::vector<Property> &properties, const std::vector<Constant> &constants)
{
	for (const Property &property : properties) {
		size_t len = strlen(property.name);
		switch (property.kind) {
			case PropertyKind::Long:
				zend_declare_property_long(ce, property.name, len, property.defaultValue, property.visibility);
				break;
			case PropertyKind::Null:
				zend_declare_property_null(ce, property.name, len, property.visibility);
				break;
			case PropertyKind::Bool:
				zend_declare_property_bool(ce, property.name, len, property.defaultValue, property.visibility);
				break;
			case PropertyKind::EmptyArray: {
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				zend_declare_property(ce, property.name, len, &emptyArray, property.visibility);
				break;
			}
			case PropertyKind::PublicReadonlyTyped: {
				zend_string *nameStr = zend_string_init(property.name, len, ce->type == ZEND_INTERNAL_CLASS);
				zval undef;
				ZVAL_UNDEF(&undef);
				zend_type type = ZEND_TYPE_INIT_MASK((uint32_t) property.defaultValue);
				zend_declare_typed_property(ce, nameStr, &undef, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, NULL, type);
				zend_string_release(nameStr);
				break;
			}
		}
	}
	for (const Constant &constant : constants) {
		zend_declare_class_constant_long(ce, constant.name, strlen(constant.name), constant.value);
	}
}

} // namespace reg

/* Shadow.cpp: the plan registry and Runtime::activateShadowing() */
void pt_shadow_plan_add(reg::ShadowPlan &&plan);

namespace reg {

/*
 * Builder for one class. Usage:
 *
 *   reg::Class cls("PHPStanTurbo\\Foo");
 *   cls.privateLongProperty("value", 0);
 *   cls.method("bar", reg::Public, 1, { reg::longArg("x") },
 *       [](INTERNAL_FUNCTION_PARAMETERS) { ... });
 *   ce = cls.register_();
 */
class Class
{
public:
	explicit Class(const char *name) : name(name) {}

	/* the PHP twin is final — the declared class is too */
	Class &final()
	{
		flags |= ZEND_ACC_FINAL;
		return *this;
	}

	/* the PHP twin's parent class (real name; a shadowed parent resolves to
	 * its native class, anything else autoloads at activation) */
	Class &parent(const char *parentName)
	{
		this->parentName = parentName;
		return *this;
	}

	/* the PHP twin's own implements clause (real interface names, resolved
	 * through the autoloader at activation) */
	Class &implements(std::initializer_list<const char *> interfaceNames)
	{
		for (const char *interfaceName : interfaceNames) {
			interfaces.push_back(interfaceName);
		}
		return *this;
	}

	/*
	 * requiredArgs is ZEND_BEGIN_ARG_INFO_EX's required_num_args; args carry
	 * name/type/by-ref/variadic exactly as the ZEND_ARG_* macros would.
	 */
	Class &method(const char *methodName, uint32_t flags, uint32_t requiredArgs, std::initializer_list<Arg> args, zif_handler handler, const Arg *returns = NULL)
	{
		/* arginfo array: slot 0 is the return-info slot carrying the
		 * required-args count, exactly as ZEND_BEGIN_ARG_INFO_EX emits.
		 * A declared return type goes in the same slot's type — needed only
		 * where the engine enforces it (implementing a userland interface). */
		auto *argInfo = (zend_internal_arg_info *) pemalloc(sizeof(zend_internal_arg_info) * (args.size() + 1), 1);
		argInfo[0].name = (const char *) (uintptr_t) requiredArgs;
		argInfo[0].type.ptr = returns != NULL ? (void *) returns->className : NULL;
		argInfo[0].type.type_mask = returns != NULL ? returns->typeMask : 0;
		argInfo[0].default_value = NULL;
		size_t i = 1;
		for (const Arg &arg : args) {
			argInfo[i].name = arg.name;
			argInfo[i].type.ptr = (void *) arg.className;
			argInfo[i].type.type_mask = arg.typeMask;
			argInfo[i].default_value = arg.defaultValue;
			i++;
		}

		zend_function_entry entry;
		memset(&entry, 0, sizeof(entry));
		entry.fname = methodName;
		entry.handler = handler;
		entry.arg_info = argInfo;
		entry.num_args = (uint32_t) args.size();
		entry.flags = flags;
		entries.push_back(entry);
		return *this;
	}

	/* declaration order defines the OBJ_PROP_NUM slot, as with the macros */
	Class &privateLongProperty(const char *propertyName, zend_long defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::Long, ZEND_ACC_PRIVATE, defaultValue });
		return *this;
	}

	/* a private null-initialised property (zend_declare_property_null) */
	Class &privateNullProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::Null, ZEND_ACC_PRIVATE, 0 });
		return *this;
	}

	/* a protected property defaulting to an empty array (zend_declare_property
	 * with ZVAL_EMPTY_ARRAY) */
	Class &protectedArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PROTECTED, 0 });
		return *this;
	}

	Class &privateArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PRIVATE, 0 });
		return *this;
	}

	Class &publicArrayProperty(const char *propertyName)
	{
		properties.push_back({ propertyName, PropertyKind::EmptyArray, ZEND_ACC_PUBLIC, 0 });
		return *this;
	}

	/* a protected bool property (zend_declare_property_bool) */
	Class &protectedBoolProperty(const char *propertyName, bool defaultValue)
	{
		properties.push_back({ propertyName, PropertyKind::Bool, ZEND_ACC_PROTECTED, defaultValue ? 1 : 0 });
		return *this;
	}

	/* a `public readonly` typed property with no default (IS_PROP_UNINIT),
	 * as a promoted readonly constructor parameter declares; typeMask is a
	 * MAY_BE_* mask */
	Class &publicReadonlyProperty(const char *propertyName, uint32_t typeMask)
	{
		properties.push_back({ propertyName, PropertyKind::PublicReadonlyTyped, ZEND_ACC_PUBLIC | ZEND_ACC_READONLY, (zend_long) typeMask });
		return *this;
	}

	/* a public long class constant (zend_declare_class_constant_long) */
	Class &classConstantLong(const char *constantName, zend_long value)
	{
		constants.push_back({ constantName, value });
		return *this;
	}

	/* an internal class registered at module startup (extension-only
	 * classes with no PHP twin) */
	zend_class_entry *register_()
	{
		zend_function_entry sentinel;
		memset(&sentinel, 0, sizeof(sentinel));
		entries.push_back(sentinel);

		/* the engine copies fentry contents but references arg_info forever;
		 * the entries vector lives only through this call, argInfo persists */
		zend_class_entry ce;
		INIT_CLASS_ENTRY_EX(ce, name, strlen(name), entries.data());
		zend_class_entry *registered = zend_register_internal_class(&ce);
		registered->ce_flags |= flags;
		declareMembers(registered, properties, constants);
		return registered;
	}

	/* a class shadowing a PHP implementation: recorded now, declared under
	 * the twin's name by Runtime::activateShadowing(); *out is set then */
	void shadow(zend_class_entry **out)
	{
		zend_function_entry sentinel;
		memset(&sentinel, 0, sizeof(sentinel));
		entries.push_back(sentinel);

		reg::ShadowPlan plan;
		plan.name = name;
		plan.flags = flags;
		plan.parentName = parentName;
		plan.interfaces = std::move(interfaces);
		plan.entries = std::move(entries);
		plan.properties = std::move(properties);
		plan.constants = std::move(constants);
		plan.out = out;
		plan.ce = NULL;
		pt_shadow_plan_add(std::move(plan));
	}

private:
	const char *name;
	uint32_t flags = 0;
	const char *parentName = nullptr;
	std::vector<const char *> interfaces;
	std::vector<zend_function_entry> entries;
	std::vector<Property> properties;
	std::vector<Constant> constants;
};

} // namespace reg

#endif
