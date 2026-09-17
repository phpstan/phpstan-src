/*
 * PHPStanTurbo\DeprecatedAttributeResolver — native implementation of
 * PHPStan\Analyser\DeprecatedAttributeResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getDeprecatedAttribute() — asked by
 * ClassMethodHandler, FunctionHandler and PropertyHooksProcessor for every
 * declaration without a @deprecated tag — is exported as
 * pt_deprecated_attribute_resolver_get_deprecated_attribute(), which hands
 * the two values over without the twin's array.
 *
 * Nearly every declaration has no attribute at all: that answers without a
 * call. The twin builds the InitializerExprContext first; it is built here
 * right before the first argument type is resolved, the only use it has
 * (its factory is pure, so nothing observable moves). InitializerExprContext
 * stays PHP and is called through the cached sites below, the php-parser Name
 * / Identifier toString() too; InitializerExprTypeResolver through its direct
 * entry.
 */

#include "support.h"
#include "generated/DeprecatedAttributeResolver.h"

namespace slots = ptdecl::DeprecatedAttributeResolver::slot;
namespace sigs = ptdecl::DeprecatedAttributeResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_deprecated_attribute_resolver = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_dar_from_stub_parameter_site;
pt_method_site pt_dar_attr_name_to_string_site;
pt_method_site pt_dar_arg_name_to_string_site;

/* InitializerExprContext::fromStubParameter($className, $stubFile, $function) */
zv::Val initializerExprContextFromStubParameter(zval *className, zval *stubFile, zval *function)
{
	zv::Args argv{className, stubFile, function};
	return pt_call_static_cached(pt_dar_from_stub_parameter_site, PT_CLASS_INITIALIZER_EXPR_CONTEXT, PT_LC("fromstubparameter"), 3, argv);
}

/* $initializerExprTypeResolver->getType($expr, $context) */
zv::Val initializerExprType(zval *initializerExprTypeResolver, zval *expr, zval *context)
{
	return pt_initializer_expr_type_resolver_get_type(initializerExprTypeResolver, expr, context);
}

/* }}} */

pt_property_site pt_dar_attr_groups_site;
pt_property_site pt_dar_attrs_site;
pt_property_site pt_dar_attr_name_site;
pt_property_site pt_dar_attr_args_site;
pt_property_site pt_dar_arg_name_site;
pt_property_site pt_dar_arg_value_site;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

zend_never_inline ZEND_COLD bool propertyOnNonObject(const char *property, zval *value)
{
	zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", property, zend_zval_value_name(value));
	return !EG(exception);
}

zend_never_inline ZEND_COLD bool foreachOnNonArray(zval *value)
{
	zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(value));
	return !EG(exception);
}

/* $name->toString() === $literal; false = pending exception */
[[nodiscard]] bool nameEquals(pt_method_site &site, zval *name, const char *literal, size_t len, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
		memberCallOnNonObject("toString", name);
		return false;
	}
	zv::Val string = pt_call_method_cached(site, Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
	if (UNEXPECTED(string.isUndef())) return false;
	out = Z_TYPE_P(string.raw()) == IS_STRING && zend_string_equals_cstr(Z_STR_P(string.raw()), literal, len);
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\DeprecatedAttributeResolver; false = pending
 * exception. */
class DeprecatedAttributeResolver
{
public:
	explicit DeprecatedAttributeResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *initializerExprTypeResolver)
	{
		zv::ObjRef(self).propAtWrite(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
	}

	/* Mirrors getDeprecatedAttribute(): [$isDeprecated, $deprecatedDescription] */
	[[nodiscard]] bool getDeprecatedAttribute(zval *scope, zval *stmt, bool &isDeprecated, zv::Val &deprecatedDescription) const
	{
		isDeprecated = false;
		deprecatedDescription = zv::Val::null();
		zval *attrGroups = ptsh::readNodeProperty(pt_dar_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return false;
		if (EXPECTED(Z_TYPE_P(attrGroups) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(attrGroups)) == 0)) return true;

		zv::Val initializerExprContext;
		zv::Val deprecatedDescriptionType = zv::Val::null();
		if (UNEXPECTED(Z_TYPE_P(attrGroups) != IS_ARRAY)) return foreachOnNonArray(attrGroups);
		zv::Val groups = zv::Val::copyOf(zv::Ref(attrGroups));
		for (auto groupEntry : zv::ArrRef(groups.raw())) {
			zval *attrGroup = groupEntry.value().deref().raw();
			zval *attrs;
			if (UNEXPECTED(Z_TYPE_P(attrGroup) != IS_OBJECT)) {
				if (UNEXPECTED(!propertyOnNonObject("attrs", attrGroup))) return false;
				attrs = &EG(uninitialized_zval);
			} else {
				attrs = ptsh::readNodeProperty(pt_dar_attrs_site, attrGroup, PT_LC("attrs"));
				if (UNEXPECTED(attrs == NULL)) return false;
			}
			if (UNEXPECTED(Z_TYPE_P(attrs) != IS_ARRAY)) {
				if (UNEXPECTED(!foreachOnNonArray(attrs))) return false;
				continue;
			}
			zv::Val attrsHold = zv::Val::copyOf(zv::Ref(attrs));
			for (auto attrEntry : zv::ArrRef(attrsHold.raw())) {
				zval *attr = attrEntry.value().deref().raw();
				if (UNEXPECTED(!processAttribute(scope, stmt, attr, isDeprecated, initializerExprContext, deprecatedDescriptionType))) return false;
			}
		}

		if (!deprecatedDescriptionType.isNull()) {
			if (UNEXPECTED(Z_TYPE_P(deprecatedDescriptionType.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("getConstantStrings", deprecatedDescriptionType.raw());
				return false;
			}
			zv::Val constantStrings = pt_type_call(Z_OBJ_P(deprecatedDescriptionType.raw()), PT_LC("getconstantstrings"), 0, NULL);
			if (UNEXPECTED(constantStrings.isUndef())) return false;
			if (Z_TYPE_P(constantStrings.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) == 1) {
				zval *first = zend_hash_index_find(Z_ARRVAL_P(constantStrings.raw()), 0);
				if (UNEXPECTED(first == NULL)) {
					zend_error(E_WARNING, "Undefined array key 0");
					if (UNEXPECTED(EG(exception))) return false;
					memberCallOnNonObject("getValue", &EG(uninitialized_zval));
					return false;
				}
				ZVAL_DEREF(first);
				if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
					memberCallOnNonObject("getValue", first);
					return false;
				}
				deprecatedDescription = pt_type_call(Z_OBJ_P(first), PT_LC("getvalue"), 0, NULL);
				if (UNEXPECTED(deprecatedDescription.isUndef())) return false;
			}
		}
		return true;
	}

private:
	zend_object *self;

	/* the inner foreach's body over one attribute */
	[[nodiscard]] bool processAttribute(zval *scope, zval *stmt, zval *attr, bool &isDeprecated, zv::Val &initializerExprContext, zv::Val &deprecatedDescriptionType) const
	{
		zval *name;
		if (UNEXPECTED(Z_TYPE_P(attr) != IS_OBJECT)) {
			if (UNEXPECTED(!propertyOnNonObject("name", attr))) return false;
			name = &EG(uninitialized_zval);
		} else {
			name = ptsh::readNodeProperty(pt_dar_attr_name_site, attr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
		}
		bool isDeprecatedAttribute;
		if (UNEXPECTED(!nameEquals(pt_dar_attr_name_to_string_site, name, PT_LC("Deprecated"), isDeprecatedAttribute))) return false;
		if (!isDeprecatedAttribute) return true;
		isDeprecated = true;

		zval *args = ptsh::readNodeProperty(pt_dar_attr_args_site, attr, PT_LC("args"));
		if (UNEXPECTED(args == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(args) != IS_ARRAY)) return foreachOnNonArray(args);
		zv::Val arguments = zv::Val::copyOf(zv::Ref(args));
		for (auto argEntry : zv::ArrRef(arguments.raw())) {
			zval *arg = argEntry.value().deref().raw();
			zval *argName;
			if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
				if (UNEXPECTED(!propertyOnNonObject("name", arg))) return false;
				argName = &EG(uninitialized_zval);
			} else {
				argName = ptsh::readNodeProperty(pt_dar_arg_name_site, arg, PT_LC("name"));
				if (UNEXPECTED(argName == NULL)) return false;
			}
			if (Z_TYPE_P(argName) == IS_NULL) {
				/* $i !== 0 */
				if (argEntry.stringKeyOrNull() != NULL || argEntry.indexKey() != 0) continue;
				return resolveDescriptionType(scope, stmt, arg, initializerExprContext, deprecatedDescriptionType);
			}
			zv::Val argNameHold = zv::Val::copyOf(zv::Ref(argName));
			bool isMessage;
			if (UNEXPECTED(!nameEquals(pt_dar_arg_name_to_string_site, argNameHold.raw(), PT_LC("message"), isMessage))) return false;
			if (!isMessage) continue;
			return resolveDescriptionType(scope, stmt, arg, initializerExprContext, deprecatedDescriptionType);
		}
		return true;
	}

	/* $deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext) */
	[[nodiscard]] bool resolveDescriptionType(zval *scope, zval *stmt, zval *arg, zv::Val &initializerExprContext, zv::Val &deprecatedDescriptionType) const
	{
		if (initializerExprContext.isUndef()) {
			initializerExprContext = createInitializerExprContext(scope, stmt);
			if (UNEXPECTED(initializerExprContext.isUndef())) return false;
		}
		zval *value;
		if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
			if (UNEXPECTED(!propertyOnNonObject("value", arg))) return false;
			value = &EG(uninitialized_zval);
		} else {
			value = ptsh::readNodeProperty(pt_dar_arg_value_site, arg, PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return false;
		}
		zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
		deprecatedDescriptionType = initializerExprType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), valueHold.raw(), initializerExprContext.raw());
		return !deprecatedDescriptionType.isUndef();
	}

	/* InitializerExprContext::fromStubParameter($scope->isInClass() ?
	 * $scope->getClassReflection()->getName() : null, $scope->getFile(), $stmt) */
	static zv::Val createInitializerExprContext(zval *scope, zval *stmt)
	{
		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		zv::Val className = zv::Val::null();
		if (inClass) {
			zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("getName", classReflection.raw());
				return zv::Val();
			}
			className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return zv::Val();
		}
		zv::Val file = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(file.isUndef())) return zv::Val();
		return initializerExprContextFromStubParameter(className.raw(), file.raw(), stmt);
	}
};

} // namespace phpstanturbo

using phpstanturbo::DeprecatedAttributeResolver;

bool pt_deprecated_attribute_resolver_get_deprecated_attribute(zval *resolver, zval *scope, zval *stmt, zv::Val &isDeprecated, zv::Val &deprecatedDescription)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_deprecated_attribute_resolver)) {
		bool deprecated;
		if (UNEXPECTED(!DeprecatedAttributeResolver(Z_OBJ_P(resolver)).getDeprecatedAttribute(scope, stmt, deprecated, deprecatedDescription))) return false;
		isDeprecated = zv::Val::boolean(deprecated);
		return true;
	}
	zv::Args argv{scope, stmt};
	zv::Val result = pt_type_call(Z_OBJ_P(resolver), PT_LC("getdeprecatedattribute"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	zval *first = ptsh::listItem(result.raw(), 0);
	if (UNEXPECTED(first == NULL)) return false;
	isDeprecated = zv::Val::copyOf(zv::Ref(first));
	zval *second = ptsh::listItem(result.raw(), 1);
	if (UNEXPECTED(second == NULL)) return false;
	deprecatedDescription = zv::Val::copyOf(zv::Ref(second));
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_deprecated_attribute_resolver()
{
	reg::Class cls("PHPStan\\Analyser\\DeprecatedAttributeResolver");
	ptdecl::DeprecatedAttributeResolver::declareClass(cls);
	ptdecl::DeprecatedAttributeResolver::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj>(execute_data, initializerExprTypeResolver)) RETURN_THROWS();
		DeprecatedAttributeResolver(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver);
	});

	cls.method(sigs::getDeprecatedAttribute, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *stmt;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, stmt)) RETURN_THROWS();
		bool isDeprecated;
		zv::Val deprecatedDescription;
		if (UNEXPECTED(!DeprecatedAttributeResolver(Z_OBJ_P(ZEND_THIS)).getDeprecatedAttribute(scope, stmt, isDeprecated, deprecatedDescription))) RETURN_THROWS();
		zv::Arr result = zv::Arr::create(2);
		result.push(zv::Val::boolean(isDeprecated));
		result.push(std::move(deprecatedDescription));
		PT_RETURN_VAL(zv::Val(std::move(result)));
	});

	cls.shadow(&pt_ce_deprecated_attribute_resolver);
}

/* }}} */
