/*
 * PHPStanTurbo\FunctionHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\FunctionHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The gatherer frame the twin pushes around the body walk is a native
 * closure capturing what the PHP closure captures: $functionScope by value,
 * the five gathered lists by reference. MutatingScope, ImpurePoint, the
 * statement results, ExpressionResultStorage, VariableLivenessResolver and
 * NodeScopeResolver are called through their direct entries; the
 * declaration processors through the shared helpers of StmtHandlerCalls.h,
 * the PHP reflection and node classes through the sites below.
 */

#include "support.h"
#include "generated/FunctionHandler.h"

namespace slots = ptdecl::FunctionHandler::slot;
namespace sigs = ptdecl::FunctionHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_function_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_fh_get_name_site;
pt_method_site pt_fh_get_return_node_site;

/* $functionReflection->getName() */
zv::Val getFunctionName(zval *functionReflection)
{
	return pt_call_method_cached(pt_fh_get_name_site, Z_OBJ_P(functionReflection), PT_LC("getname"), 0, NULL);
}

/* $returnAfterFinallyNode->getReturnNode() */
zv::Val getReturnNode(zval *node)
{
	return pt_call_method_cached(pt_fh_get_return_node_site, Z_OBJ_P(node), PT_LC("getreturnnode"), 0, NULL);
}

/* }}} */

pt_property_site pt_fh_attr_groups_site;
pt_property_site pt_fh_params_site;
pt_property_site pt_fh_return_type_site;
pt_property_site pt_fh_stmts_site;

/* the literals, permanent interned strings (module startup) */
zend_string *pt_fh_property_assign = nullptr;
zend_string *pt_fh_property_assignment = nullptr;
zend_string *pt_fh_function_exists = nullptr;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\FunctionHandler; UNDEF = pending
 * exception. */
class FunctionHandler
{
public:
	explicit FunctionHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *deprecatedAttributeResolver, zval *phpDocsResolver, zval *attributesHandler, zval *parametersProcessor)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::deprecatedAttributeResolver, zv::Val::copyOf(zv::Ref(deprecatedAttributeResolver)));
		object.propAtWrite(slots::phpDocsResolver, zv::Val::copyOf(zv::Ref(phpDocsResolver)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
		object.propAtWrite(slots::parametersProcessor, zv::Val::copyOf(zv::Ref(parametersProcessor)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_FUNCTION_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *attrGroups = ptsh::readNodeProperty(pt_fh_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return zv::Val();
		/* [$templateTypeMap, ..., $isInternal, , $isPure, $acceptsNamedArguments, ,
		 * $phpDocComment, $asserts,, $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters] */
		static constexpr uint32_t listIndexes[] = { 0, 1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 13, 14, 16, 20 };
		uint32_t destructured = 0;
		for (uint32_t index : listIndexes) destructured |= 1u << index;
		pt_php_docs phpDocs;
		if (UNEXPECTED(!ptsh::getPhpDocs(OBJ_PROP_NUM(self, slots::phpDocsResolver), scope, stmt, destructured, phpDocs))) return zv::Val();
		zval *docs[PT_PHP_DOCS_COUNT];
		for (uint32_t index = 0; index < PT_PHP_DOCS_COUNT; index++) docs[index] = &phpDocs.items[index];
		zval *deprecatedDescription = docs[6];
		zval *isDeprecated = docs[7];

		zval *params = ptsh::readNodeProperty(pt_fh_params_site, stmt, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processParams(OBJ_PROP_NUM(self, slots::parametersProcessor), nodeScopeResolver, stmt, params, scope, storage, nodeCallback))) return zv::Val();

		zval *returnType = ptsh::readNodeProperty(pt_fh_return_type_site, stmt, PT_LC("returnType"));
		if (UNEXPECTED(returnType == NULL)) return zv::Val();
		if (Z_TYPE_P(returnType) != IS_NULL) {
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnType, scope, storage))) return zv::Val();
		}

		zv::Val attributeIsDeprecated;
		zv::Val attributeDeprecatedDescription;
		if (!zend_is_true(isDeprecated)) {
			if (UNEXPECTED(!ptsh::getDeprecatedAttribute(OBJ_PROP_NUM(self, slots::deprecatedAttributeResolver), scope, stmt, attributeIsDeprecated, attributeDeprecatedDescription))) return zv::Val();
			isDeprecated = attributeIsDeprecated.raw();
			deprecatedDescription = attributeDeprecatedDescription.raw();
		}

		zval enterArgv[16];
		ZVAL_COPY_VALUE(&enterArgv[0], stmt);
		ZVAL_COPY_VALUE(&enterArgv[1], docs[0]);
		ZVAL_COPY_VALUE(&enterArgv[2], docs[1]);
		ZVAL_COPY_VALUE(&enterArgv[3], docs[4]);
		ZVAL_COPY_VALUE(&enterArgv[4], docs[5]);
		ZVAL_COPY_VALUE(&enterArgv[5], deprecatedDescription);
		ZVAL_COPY_VALUE(&enterArgv[6], isDeprecated);
		ZVAL_COPY_VALUE(&enterArgv[7], docs[8]);
		ZVAL_COPY_VALUE(&enterArgv[8], docs[10]);
		ZVAL_COPY_VALUE(&enterArgv[9], docs[11]);
		ZVAL_COPY_VALUE(&enterArgv[10], docs[14]);
		ZVAL_COPY_VALUE(&enterArgv[11], docs[13]);
		ZVAL_COPY_VALUE(&enterArgv[12], docs[16]);
		ZVAL_COPY_VALUE(&enterArgv[13], docs[2]);
		ZVAL_COPY_VALUE(&enterArgv[14], docs[3]);
		ZVAL_COPY_VALUE(&enterArgv[15], docs[20]);
		zv::Val functionScope = pt_mutating_scope_enter_function(Z_OBJ_P(scope), enterArgv);
		if (UNEXPECTED(functionScope.isUndef())) return zv::Val();
		zv::Val functionReflection = pt_mutating_scope_get_function(Z_OBJ_P(functionScope.raw()));
		if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();
		bool error = false;
		if (!ptsh::isInstanceOf(functionReflection.raw(), PT_CLASS_PHP_FUNCTION_FROM_PARSER_NODE_REFLECTION, error)) {
			if (!error) pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Args inFunctionArgv{functionReflection.raw(), stmt};
		zv::Val inFunctionNode = pt_type_new(PT_CLASS_IN_FUNCTION_NODE, 2, inFunctionArgv);
		if (UNEXPECTED(inFunctionNode.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inFunctionNode.raw(), functionScope.raw(), storage))) return zv::Val();

		zv::Val gatheredReturnStatements = ptsh::newArrayReference();
		zv::Val gatheredReturnStatementsAfterFinally = ptsh::newArrayReference();
		zv::Val gatheredYieldStatements = ptsh::newArrayReference();
		zv::Val executionEnds = ptsh::newArrayReference();
		zv::Val functionImpurePoints = ptsh::newArrayReference();
		// the body's results live in a per-body storage released right after
		// the FunctionReturnStatementsNode rules ran - see the ClassMethod
		// branch for the reasoning
		zv::Val bodyStorage = pt_expression_result_storage_duplicate(storage);
		if (UNEXPECTED(bodyStorage.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), bodyStorage.raw()))) return zv::Val();
		bool walked = walkBody(nodeScopeResolver, stmt, functionScope.raw(), bodyStorage.raw(), nodeCallback, context, functionReflection.raw(), gatheredReturnStatements.raw(), gatheredReturnStatementsAfterFinally.raw(), gatheredYieldStatements.raw(), executionEnds.raw(), functionImpurePoints.raw());
		pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
		if (UNEXPECTED(!walked || EG(exception) != NULL)) return zv::Val();

		// declaring the function defines it in global state, so a negative
		// function_exists() narrowing that may refer to that function must be forgotten
		zv::Arr functionNames = zv::Arr::create(1);
		functionNames.push(zv::Val::string(pt_fh_function_exists));
		zv::Val functionName = getFunctionName(functionReflection.raw());
		if (UNEXPECTED(functionName.isUndef())) return zv::Val();
		zv::Val finalScope = pt_mutating_scope_invalidate_existence_check_expressions(Z_OBJ_P(scope), functionNames.raw(), functionName.raw());
		if (UNEXPECTED(finalScope.isUndef())) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(finalScope.raw(), false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return FunctionHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the outer try block: the gatherer frame pushed around the body walk,
	 * then the FunctionReturnStatementsNode and the liveness node emitted;
	 * false = pending exception */
	[[nodiscard]] static bool walkBody(zval *nodeScopeResolver, zval *stmt, zval *functionScope, zval *bodyStorage, zval *nodeCallback, zval *context, zval *functionReflection, zval *gatheredReturnStatements, zval *gatheredReturnStatementsAfterFinally, zval *gatheredYieldStatements, zval *executionEnds, zval *functionImpurePoints)
	{
		zval captures[6];
		ZVAL_COPY_VALUE(&captures[0], functionScope);
		ZVAL_COPY_VALUE(&captures[1], gatheredReturnStatements);
		ZVAL_COPY_VALUE(&captures[2], gatheredReturnStatementsAfterFinally);
		ZVAL_COPY_VALUE(&captures[3], gatheredYieldStatements);
		ZVAL_COPY_VALUE(&captures[4], executionEnds);
		ZVAL_COPY_VALUE(&captures[5], functionImpurePoints);
		zv::Val gatherer = pt_native_closure_new(&gathererBody, 6, captures, 0b111110);
		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return false;

		zv::Val internalStatementResult;
		zv::Val statementResult;
		{
			zval *stmts = ptsh::readNodeProperty(pt_fh_stmts_site, stmt, PT_LC("stmts"));
			bool resolveTemplateArguments;
			if (EXPECTED(stmts != NULL) && EXPECTED(pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) {
				zv::Val statementContext = pt_statement_context_create_top_level(resolveTemplateArguments);
				if (EXPECTED(!statementContext.isUndef())) {
					internalStatementResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmts, functionScope, bodyStorage, nodeCallback, statementContext.raw());
					if (EXPECTED(!internalStatementResult.isUndef())) {
						statementResult = pt_internal_statement_result_to_public(internalStatementResult.raw());
					}
				}
			}
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
		if (UNEXPECTED(statementResult.isUndef() || EG(exception) != NULL)) return false;

		zv::Val impurePointsHold;
		zval *resultImpurePoints = pt_statement_result_impure_points(statementResult.raw(), impurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return false;
		zv::Arr impurePoints = zv::Arr::empty();
		if (UNEXPECTED(!pt_callable_array_merge_into(impurePoints, resultImpurePoints) || !pt_callable_array_merge_into(impurePoints, Z_REFVAL_P(functionImpurePoints)))) return false;
		zval nodeArgv[8];
		ZVAL_COPY_VALUE(&nodeArgv[0], stmt);
		ZVAL_COPY_VALUE(&nodeArgv[1], Z_REFVAL_P(gatheredReturnStatements));
		ZVAL_COPY_VALUE(&nodeArgv[2], Z_REFVAL_P(gatheredReturnStatementsAfterFinally));
		ZVAL_COPY_VALUE(&nodeArgv[3], Z_REFVAL_P(gatheredYieldStatements));
		ZVAL_COPY_VALUE(&nodeArgv[4], statementResult.raw());
		ZVAL_COPY_VALUE(&nodeArgv[5], Z_REFVAL_P(executionEnds));
		ZVAL_COPY_VALUE(&nodeArgv[6], impurePoints.raw());
		ZVAL_COPY_VALUE(&nodeArgv[7], functionReflection);
		zv::Val returnStatementsNode = pt_type_new(PT_CLASS_FUNCTION_RETURN_STATEMENTS_NODE, 8, nodeArgv);
		if (UNEXPECTED(returnStatementsNode.isUndef())) return false;
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnStatementsNode.raw(), functionScope, bodyStorage))) return false;

		zv::Val flowHold;
		zval *variableFlow = pt_internal_statement_result_variable_flow(internalStatementResult.raw(), flowHold);
		if (UNEXPECTED(variableFlow == NULL)) return false;
		zv::Val livenessNode = pt_variable_liveness_resolver_resolve(stmt, variableFlow);
		if (UNEXPECTED(livenessNode.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, livenessNode.raw(), functionScope, bodyStorage);
	}

	/* static function (Node $node, Scope $scope) use ($functionScope,
	 * &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally,
	 * &$gatheredYieldStatements, &$executionEnds, &$functionImpurePoints): void —
	 * captures in that order */
	static void gathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\StmtHandler\\FunctionHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *node = &argv[0];
		zval *scope = &argv[1];
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			memberCallOnNonObject("getFunction", scope);
			return;
		}
		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return;
			zv::Val functionScopeFunction = pt_mutating_scope_get_function(Z_OBJ(captures[0]));
			if (UNEXPECTED(functionScopeFunction.isUndef())) return;
			if (!zend_is_identical(function.raw(), functionScopeFunction.raw())) return;
		}
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return;
		if (inAnonymousFunction) return;

		bool error = false;
		bool isPropertyAssign = ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isPropertyAssign) {
			zv::Val impurePoint = pt_impure_point_new(scope, node, pt_fh_property_assign, pt_fh_property_assignment, true);
			if (UNEXPECTED(impurePoint.isUndef())) return;
			ptsh::appendToReference(&captures[5], impurePoint.raw());
			return;
		}
		bool isExecutionEnd = ptsh::isInstanceOf(node, PT_CLASS_EXECUTION_END_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isExecutionEnd) {
			ptsh::appendToReference(&captures[4], node);
			return;
		}
		bool isReturnAfterFinally = ptsh::isInstanceOf(node, PT_CLASS_RETURN_AFTER_FINALLY_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isReturnAfterFinally) {
			zv::Val returnNode = getReturnNode(node);
			if (UNEXPECTED(returnNode.isUndef())) return;
			zv::Args statementArgv{scope, returnNode.raw()};
			zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
			if (UNEXPECTED(statement.isUndef())) return;
			ptsh::appendToReference(&captures[2], statement.raw());
			return;
		}
		bool isYield = ptsh::isInstanceOf(node, PT_CLASS_YIELD, error) || ptsh::isInstanceOf(node, PT_CLASS_YIELD_FROM, error);
		if (UNEXPECTED(error)) return;
		if (isYield) {
			ptsh::appendToReference(&captures[3], node);
		}
		bool isReturn = ptsh::isInstanceOf(node, PT_CLASS_RETURN_STMT, error);
		if (!isReturn) return;

		zv::Args statementArgv{scope, node};
		zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
		if (UNEXPECTED(statement.isUndef())) return;
		ptsh::appendToReference(&captures[1], statement.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::FunctionHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_function_handler)
{
	pt_fh_property_assign = zend_string_init_interned(PT_LC("propertyAssign"), 1);
	pt_fh_property_assignment = zend_string_init_interned(PT_LC("property assignment"), 1);
	pt_fh_function_exists = zend_string_init_interned(PT_LC("function_exists"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\FunctionHandler");
	ptdecl::FunctionHandler::declareClass(cls);
	ptdecl::FunctionHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *deprecatedAttributeResolver, *phpDocsResolver, *attributesHandler, *parametersProcessor;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, deprecatedAttributeResolver, phpDocsResolver, attributesHandler, parametersProcessor)) RETURN_THROWS();
		FunctionHandler(Z_OBJ_P(ZEND_THIS)).construct(deprecatedAttributeResolver, phpDocsResolver, attributesHandler, parametersProcessor);
	});

	cls.method<&FunctionHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(FunctionHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_function_handler);
	pt_stmt_handler_entry_register(&pt_ce_function_handler, &FunctionHandler::processStmtEntry);
}

/* }}} */
