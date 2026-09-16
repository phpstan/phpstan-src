/*
 * PHPStanTurbo\ParametersProcessor — native implementation of
 * PHPStan\Analyser\ParametersProcessor.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processParams() — called by the function,
 * method and closure walks — is exported as
 * pt_parameters_processor_process_params() (Engine.h conventions); it reaches
 * AttributesHandler and NodeScopeResolver through their direct entries.
 */

#include "support.h"
#include "generated/ParametersProcessor.h"

namespace slots = ptdecl::ParametersProcessor::slot;
namespace sigs = ptdecl::ParametersProcessor::sig;
#include "zv.h"
#include "Engine.h"
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_parameters_processor = nullptr;

namespace {

pt_property_site pt_pp_attr_groups_site;
pt_property_site pt_pp_type_site;
pt_property_site pt_pp_default_site;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ParametersProcessor; false = pending exception. */
class ParametersProcessor
{
public:
	explicit ParametersProcessor(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *attributesHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::attributesHandler)) = 0;
	}

	/* Mirrors processParams() */
	[[nodiscard]] bool processParams(zval *nodeScopeResolver, zval *stmt, zval *params, zval *scope, zval *storage, zval *nodeCallback) const
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(params)) == 0) return true;

		/* foreach iterates the array as it was when the loop started */
		zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
		for (zv::ArrayEntry entry : zv::ArrRef(paramsHold.raw())) {
			zval *param = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT)) {
				zend_type_error("PHPStan\\Analyser\\AttributesHandler::processAttributeGroups(): Argument #3 ($attrGroups) must be of type array, null given");
				return false;
			}
			zval *attrGroups = ptcall::nodeProperty(pt_pp_attr_groups_site, param, PT_LC("attrGroups"));
			if (UNEXPECTED(attrGroups == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(attrGroups) != IS_ARRAY)) {
				zend_type_error("PHPStan\\Analyser\\AttributesHandler::processAttributeGroups(): Argument #3 ($attrGroups) must be of type array, %s given", zend_zval_value_name(attrGroups));
				return false;
			}
			if (UNEXPECTED(!pt_attributes_handler_process_attribute_groups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return false;
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, param, scope, storage))) return false;
			zval *type = ptcall::nodeProperty(pt_pp_type_site, param, PT_LC("type"));
			if (UNEXPECTED(type == NULL)) return false;
			if (Z_TYPE_P(type) != IS_NULL) {
				zv::Val typeHold = zv::Val::copyOf(zv::Ref(type));
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, typeHold.raw(), scope, storage))) return false;
			}
			zval *defaultValue = ptcall::nodeProperty(pt_pp_default_site, param, PT_LC("default"));
			if (UNEXPECTED(defaultValue == NULL)) return false;
			if (Z_TYPE_P(defaultValue) == IS_NULL) continue;

			zv::Val defaultHold = zv::Val::copyOf(zv::Ref(defaultValue));
			zv::Val context = pt_expression_context_create_deep();
			if (UNEXPECTED(context.isUndef())) return false;
			zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, defaultHold.raw(), scope, storage, nodeCallback, context.raw());
			if (UNEXPECTED(result.isUndef())) return false;
		}
		return true;
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ParametersProcessor;

bool pt_parameters_processor_process_params(zval *processor, zval *nodeScopeResolver, zval *stmt, zval *params, zval *scope, zval *storage, zval *nodeCallback)
{
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_parameters_processor && Z_TYPE_P(params) == IS_ARRAY)) return ParametersProcessor(Z_OBJ_P(processor)).processParams(nodeScopeResolver, stmt, params, scope, storage, nodeCallback);
	zv::Args argv{nodeScopeResolver, stmt, params, scope, storage, nodeCallback};
	return !pt_type_call(Z_OBJ_P(processor), PT_LC("processparams"), 6, argv).isUndef();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_parameters_processor()
{
	reg::Class cls("PHPStan\\Analyser\\ParametersProcessor");
	ptdecl::ParametersProcessor::declareClass(cls);
	ptdecl::ParametersProcessor::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter class exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *attributesHandler;
		if (!zp::parse<zp::Obj>(execute_data, attributesHandler)) RETURN_THROWS();
		ParametersProcessor(Z_OBJ_P(ZEND_THIS)).construct(attributesHandler);
	});

	cls.method(sigs::processParams, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *params, *scope, *storage, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_ARRAY(params)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ParametersProcessor(Z_OBJ_P(ZEND_THIS)).processParams(nodeScopeResolver, stmt, params, scope, storage, nodeCallback))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_parameters_processor);
}

/* }}} */
