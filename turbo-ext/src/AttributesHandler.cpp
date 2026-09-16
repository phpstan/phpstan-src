/*
 * PHPStanTurbo\AttributesHandler — native implementation of
 * PHPStan\Analyser\AttributesHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processAttributeGroups() — called by the
 * declaration handlers and ParametersProcessor — is exported as
 * pt_attributes_handler_process_attribute_groups() (Engine.h conventions).
 * Nearly every call site passes an empty attribute list: that answers
 * without touching anything.
 *
 * MutatingScope::resolveName(), the ReflectionProvider and ClassReflection
 * lookups, the method reflection's variants, ParametersAcceptorSelector,
 * ArgumentsNormalizer, ArgumentsHandler, ExpressionContext and
 * NodeScopeResolver are called through their direct entries; the attribute
 * arguments' closures recurse through ArgumentsHandler into ClosureProcessor
 * on NodeScopeResolver's entries, which keep the fresh-stack guard on the
 * path.
 */

#include "support.h"
#include "generated/AttributesHandler.h"

namespace slots = ptdecl::AttributesHandler::slot;
namespace sigs = ptdecl::AttributesHandler::sig;
#include "zv.h"
#include "Engine.h"
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_attributes_handler = nullptr;

namespace {

pt_property_site pt_ath_attrs_site;
pt_property_site pt_ath_attr_name_site;
pt_property_site pt_ath_attr_args_site;
pt_property_site pt_ath_arg_value_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\AttributesHandler; false = pending exception. */
class AttributesHandler
{
public:
	explicit AttributesHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflectionProvider, zval *argumentsHandler)
	{
		writeSlot(slots::reflectionProvider, reflectionProvider);
		writeSlot(slots::argumentsHandler, argumentsHandler);
	}

	/* Mirrors processAttributeGroups() */
	[[nodiscard]] bool processAttributeGroups(zval *nodeScopeResolver, zval *stmt, zval *attrGroups, zval *scope, zval *storage, zval *nodeCallback) const
	{
		if (EXPECTED(zend_hash_num_elements(Z_ARRVAL_P(attrGroups)) == 0)) return true;

		/* foreach iterates the array as it was when the loop started */
		zv::Val groups = zv::Val::copyOf(zv::Ref(attrGroups));
		for (zv::ArrayEntry groupEntry : zv::ArrRef(groups.raw())) {
			zval *attrGroup = groupEntry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(attrGroup) != IS_OBJECT)) {
				/* AttributeGroup[] by the parser's typed property; anything
				 * else fails at the twin's callNodeCallback($attrGroup) */
				zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::callNodeCallback(): Argument #2 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(attrGroup));
				return false;
			}
			zval *attrs = ptcall::nodeProperty(pt_ath_attrs_site, attrGroup, PT_LC("attrs"));
			if (UNEXPECTED(attrs == NULL)) return false;
			zv::Val attrsHold = zv::Val::copyOf(zv::Ref(attrs));
			if (EXPECTED(Z_TYPE_P(attrsHold.raw()) == IS_ARRAY)) {
				for (zv::ArrayEntry attrEntry : zv::ArrRef(attrsHold.raw())) {
					if (UNEXPECTED(!processAttribute(nodeScopeResolver, stmt, attrEntry.value().deref().raw(), scope, storage, nodeCallback))) return false;
				}
			}
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, attrGroup, scope, storage))) return false;
		}
		return true;
	}

private:
	zend_object *self;

	void writeSlot(uint32_t index, zval *value)
	{
		zv::ObjRef(self).propAtWrite(index, zv::Val::copyOf(zv::Ref(value)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, index)) = 0;
	}

	/* the body of the inner foreach over $attrGroup->attrs */
	[[nodiscard]] bool processAttribute(zval *nodeScopeResolver, zval *stmt, zval *attr, zval *scope, zval *storage, zval *nodeCallback) const
	{
		if (UNEXPECTED(Z_TYPE_P(attr) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"name\" on %s", zend_zval_value_name(attr));
			return false;
		}
		zval *name = ptcall::nodeProperty(pt_ath_attr_name_site, attr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::resolveName(): Argument #1 ($name) must be of type PhpParser\\Node\\Name, %s given", zend_zval_value_name(name));
			return false;
		}
		zv::Val className = pt_mutating_scope_resolve_name(Z_OBJ_P(scope), Z_OBJ_P(name));
		if (UNEXPECTED(className.isUndef())) return false;
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		bool hasClass = false;
		if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), className.raw(), hasClass))) return false;
		if (hasClass) {
			zv::Val classReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className.raw());
			if (UNEXPECTED(classReflection.isUndef())) return false;
			if (UNEXPECTED(!classReflection.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function hasConstructor() on %s", zend_zval_value_name(classReflection.raw()));
				return false;
			}
			bool hasConstructor = false;
			if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(classReflection.raw()), hasConstructor))) return false;
			if (hasConstructor) {
				return processConstructorAttribute(nodeScopeResolver, stmt, attr, name, classReflection.raw(), scope, storage, nodeCallback);
			}
		}

		zval *args = ptcall::nodeProperty(pt_ath_attr_args_site, attr, PT_LC("args"));
		if (UNEXPECTED(args == NULL)) return false;
		zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));
		if (EXPECTED(Z_TYPE_P(argsHold.raw()) == IS_ARRAY)) {
			for (zv::ArrayEntry argEntry : zv::ArrRef(argsHold.raw())) {
				zval *arg = argEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
					zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(arg));
					return false;
				}
				zval *value = ptcall::nodeProperty(pt_ath_arg_value_site, arg, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return false;
				zv::Val context = pt_expression_context_create_deep();
				if (UNEXPECTED(context.isUndef())) return false;
				zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, value, scope, storage, nodeCallback, context.raw());
				if (UNEXPECTED(result.isUndef())) return false;
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, arg, scope, storage))) return false;
			}
		}
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, attr, scope, storage);
	}

	/* the branch of an attribute whose class has a constructor: its
	 * arguments walked as the constructor call's */
	[[nodiscard]] bool processConstructorAttribute(zval *nodeScopeResolver, zval *stmt, zval *attr, zval *name, zval *classReflection, zval *scope, zval *storage, zval *nodeCallback) const
	{
		zv::Val constructorReflection = pt_class_reflection_get_constructor(Z_OBJ_P(classReflection));
		if (UNEXPECTED(constructorReflection.isUndef())) return false;
		if (UNEXPECTED(!constructorReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getVariants() on %s", zend_zval_value_name(constructorReflection.raw()));
			return false;
		}
		zval *args = ptcall::nodeProperty(pt_ath_attr_args_site, attr, PT_LC("args"));
		if (UNEXPECTED(args == NULL)) return false;
		zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));
		zv::Val variants = pt_extended_method_reflection_call(constructorReflection.raw(), PT_MR_GET_VARIANTS);
		if (UNEXPECTED(variants.isUndef())) return false;
		zv::Val namedArgumentsVariants = pt_extended_method_reflection_call(constructorReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
		if (UNEXPECTED(namedArgumentsVariants.isUndef())) return false;
		zv::Val parametersAcceptor = pt_parameters_acceptor_selector_combine_variants_for_normalization(argsHold.raw(), variants.raw(), namedArgumentsVariants.raw());
		if (UNEXPECTED(parametersAcceptor.isUndef())) return false;
		zv::Args newArgv{name, argsHold.raw()};
		zv::Val expr = pt_type_new(PT_CLASS_NEW, 2, newArgv);
		if (UNEXPECTED(expr.isUndef())) return false;
		zv::Val reordered = pt_arguments_normalizer_reorder_new_arguments(parametersAcceptor.raw(), expr.raw());
		if (UNEXPECTED(reordered.isUndef())) return false;
		if (!reordered.isNull()) {
			expr = std::move(reordered);
		}
		zv::Val processVariants = pt_extended_method_reflection_call(constructorReflection.raw(), PT_MR_GET_VARIANTS);
		if (UNEXPECTED(processVariants.isUndef())) return false;
		zv::Val processNamedArgumentsVariants = pt_extended_method_reflection_call(constructorReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
		if (UNEXPECTED(processNamedArgumentsVariants.isUndef())) return false;
		zv::Val context = pt_expression_context_create_deep();
		if (UNEXPECTED(context.isUndef())) return false;
		zval null;
		ZVAL_NULL(&null);
		zv::Val argsResult = pt_arguments_handler_process_args(OBJ_PROP_NUM(self, slots::argumentsHandler), nodeScopeResolver, stmt, constructorReflection.raw(), &null, processVariants.raw(), processNamedArgumentsVariants.raw(), expr.raw(), scope, storage, nodeCallback, context.raw());
		if (UNEXPECTED(argsResult.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, attr, scope, storage);
	}
};

} // namespace phpstanturbo

using phpstanturbo::AttributesHandler;

bool pt_attributes_handler_process_attribute_groups(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *attrGroups, zval *scope, zval *storage, zval *nodeCallback)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_attributes_handler && Z_TYPE_P(attrGroups) == IS_ARRAY)) return AttributesHandler(Z_OBJ_P(handler)).processAttributeGroups(nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback);
	zv::Args argv{nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback};
	return !pt_type_call(Z_OBJ_P(handler), PT_LC("processattributegroups"), 6, argv).isUndef();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_attributes_handler()
{
	reg::Class cls("PHPStan\\Analyser\\AttributesHandler");
	ptdecl::AttributesHandler::declareClass(cls);
	ptdecl::AttributesHandler::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider, *argumentsHandler;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, reflectionProvider, argumentsHandler)) RETURN_THROWS();
		AttributesHandler(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider, argumentsHandler);
	});

	cls.method(sigs::processAttributeGroups, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *attrGroups, *scope, *storage, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_ARRAY(attrGroups)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!AttributesHandler(Z_OBJ_P(ZEND_THIS)).processAttributeGroups(nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_attributes_handler);
}

/* }}} */
