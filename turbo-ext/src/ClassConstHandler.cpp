/*
 * PHPStanTurbo\ClassConstHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ClassConstHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * AttributesHandler, ExpressionResult, the contexts, MutatingScope,
 * ClassReflection, the statement results and NodeScopeResolver are called
 * through their direct entries; the php-parser Name and ClassConstFetch
 * nodes are instantiated through the class map.
 */

#include "support.h"
#include "generated/ClassConstHandler.h"

namespace slots = ptdecl::ClassConstHandler::slot;
namespace sigs = ptdecl::ClassConstHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_class_const_handler = nullptr;

namespace {

pt_property_site pt_cch_attr_groups_site;
pt_property_site pt_cch_consts_site;
pt_property_site pt_cch_const_value_site;
pt_property_site pt_cch_const_name_site;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ClassConstHandler; UNDEF = pending
 * exception. */
class ClassConstHandler
{
public:
	explicit ClassConstHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *attributesHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_CONST_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *entryScope = scope;
		zv::Val scopeHold;
		zv::Arr impurePoints = zv::Arr::empty();
		zval *attrGroups = ptsh::readNodeProperty(pt_cch_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return zv::Val();

		zval *consts = ptsh::readNodeProperty(pt_cch_consts_site, stmt, PT_LC("consts"));
		if (UNEXPECTED(consts == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(consts) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(consts));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(consts));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				zval *constNode = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(constNode) != IS_OBJECT)) {
					zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(constNode));
					if (UNEXPECTED(EG(exception))) return zv::Val();
					zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processExprNode(): Argument #2 ($expr) must be of type PhpParser\\Node\\Expr, null given");
					return zv::Val();
				}
				zval *value = ptsh::readNodeProperty(pt_cch_const_value_site, constNode, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
				bool resolveTemplateArguments;
				if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
				zv::Val constResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, valueHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(constResult.isUndef())) return zv::Val();
				// the constant's callback fires after its value was processed, so
				// rule-side asks about the value answer from the storage
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, constNode, scope, storage))) return zv::Val();
				{
					zv::Val hold;
					zval *resultImpurePoints = pt_expression_result_impure_points(constResult.raw(), hold);
					if (UNEXPECTED(resultImpurePoints == NULL || !pt_callable_array_merge_into(impurePoints, resultImpurePoints))) return zv::Val();
				}
				zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				if (classReflection.isNull()) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				zv::Val fetch = classConstFetch(scope, constNode);
				if (UNEXPECTED(fetch.isUndef())) return zv::Val();
				zv::Val type = pt_expression_result_get_type(constResult.raw());
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val nativeType = pt_expression_result_get_native_type(constResult.raw());
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(scope), Z_OBJ_P(fetch.raw()), type.raw(), nativeType.raw());
				if (UNEXPECTED(assigned.isUndef())) return zv::Val();
				scopeHold = std::move(assigned);
				scope = scopeHold.raw();
			}
		}

		// deferred from processStmtNode() - fires after the values were processed
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, storage))) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, impurePoints.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ClassConstHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* new Expr\ClassConstFetch(new Name\FullyQualified($scope->getClassReflection()->getName()), $const->name) */
	static zv::Val classConstFetch(zval *scope, zval *constNode)
	{
		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getName", classReflection.raw());
			return zv::Val();
		}
		zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val name = pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, className.raw());
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zval *constName = ptsh::readNodeProperty(pt_cch_const_name_site, constNode, PT_LC("name"));
		if (UNEXPECTED(constName == NULL)) return zv::Val();
		zv::Args fetchArgv{name.raw(), constName};
		return pt_type_new(PT_CLASS_CLASS_CONST_FETCH, 2, fetchArgv);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassConstHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_class_const_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ClassConstHandler");
	ptdecl::ClassConstHandler::declareClass(cls);
	ptdecl::ClassConstHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *attributesHandler;
		if (!zp::parse<zp::Obj>(execute_data, attributesHandler)) RETURN_THROWS();
		ClassConstHandler(Z_OBJ_P(ZEND_THIS)).construct(attributesHandler);
	});

	cls.method<&ClassConstHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClassConstHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_class_const_handler);
	pt_stmt_handler_entry_register(&pt_ce_class_const_handler, &ClassConstHandler::processStmtEntry);
}

/* }}} */
