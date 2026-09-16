/*
 * PHPStanTurbo\PropertyHooksProcessor — native implementation of
 * PHPStan\Analyser\PropertyHooksProcessor.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processPropertyHooks() — called by
 * PropertyHandler and ClassMethodHandler (promoted constructor parameters)
 * — is exported as pt_property_hooks_processor_process_property_hooks(); an
 * empty hook list answers after the twin's isInClass() check without
 * anything else.
 *
 * The gatherer frame pushed around each hook body is a native closure
 * capturing what the PHP closure captures: $hookScope by value, the gathered
 * lists and $hookImpurePoints (reset for each hook) by reference.
 *
 * PhpDocsResolver, DeprecatedAttributeResolver, AttributesHandler,
 * ParametersProcessor, MutatingScope, ClassReflection, ImpurePoint, the
 * statement results, VariableLivenessResolver and NodeScopeResolver are
 * called through their direct entries; the php-parser hook, the node
 * classes and LineAttributesVisitor through the sites below.
 */

#include "support.h"
#include "generated/PropertyHooksProcessor.h"

namespace slots = ptdecl::PropertyHooksProcessor::slot;
namespace sigs = ptdecl::PropertyHooksProcessor::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_property_hooks_processor = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_php_get_stmts_site;
pt_method_site pt_php_body_start_line_site;
pt_method_site pt_php_body_end_line_site;
pt_method_site pt_php_traverse_site;
pt_method_site pt_php_get_return_node_site;

/* }}} */

pt_property_site pt_php_attr_groups_site;
pt_property_site pt_php_params_site;
pt_property_site pt_php_body_site;

/* the impure point's literals, permanent interned strings (module startup) */
zend_string *pt_php_property_assign = nullptr;
zend_string *pt_php_property_assignment = nullptr;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* the list() items getPhpDocs() is destructured into: [, $phpDocParameterTypes,,,,
 * $phpDocThrowType,,,,, $isPure,,, $phpDocComment,,,,,, $resolvedPhpDoc] */
constexpr uint32_t PT_PHP_DESTRUCTURED_PHP_DOCS = (1u << PT_PHP_DOCS_PARAMETER_TYPES) | (1u << PT_PHP_DOCS_THROW_TYPE) | (1u << PT_PHP_DOCS_IS_PURE) | (1u << PT_PHP_DOCS_DOC_COMMENT) | (1u << PT_PHP_DOCS_RESOLVED_PHP_DOC);

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\PropertyHooksProcessor; false = pending
 * exception. */
class PropertyHooksProcessor
{
public:
	explicit PropertyHooksProcessor(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *deprecatedAttributeResolver, zval *phpDocsResolver, zval *attributesHandler, zval *parametersProcessor)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::deprecatedAttributeResolver, zv::Val::copyOf(zv::Ref(deprecatedAttributeResolver)));
		object.propAtWrite(slots::phpDocsResolver, zv::Val::copyOf(zv::Ref(phpDocsResolver)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
		object.propAtWrite(slots::parametersProcessor, zv::Val::copyOf(zv::Ref(parametersProcessor)));
	}

	/* Mirrors processPropertyHooks(). */
	[[nodiscard]] bool processPropertyHooks(zval *nodeScopeResolver, zval *stmt, zval *nativeTypeNode, zval *phpDocType, zval *propertyName, zval *hooks, zval *scope, zval *storage, zval *nodeCallback) const
	{
		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		if (!inClass) {
			pt_throw_should_not_happen();
			return false;
		}
		if (EXPECTED(zend_hash_num_elements(Z_ARRVAL_P(hooks)) == 0)) return true;

		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return false;

		/* foreach iterates the array it started with */
		zv::Val iterated = zv::Val::copyOf(zv::Ref(hooks));
		for (auto entry : zv::ArrRef(iterated.raw())) {
			if (UNEXPECTED(!processHook(nodeScopeResolver, stmt, nativeTypeNode, phpDocType, propertyName, entry.value().deref().raw(), scope, storage, nodeCallback, classReflection.raw()))) return false;
		}
		return true;
	}

private:
	zend_object *self;

	/* the loop body over one hook */
	[[nodiscard]] bool processHook(zval *nodeScopeResolver, zval *stmt, zval *nativeTypeNode, zval *phpDocType, zval *propertyName, zval *hook, zval *scope, zval *storage, zval *nodeCallback, zval *classReflection) const
	{
		if (UNEXPECTED(Z_TYPE_P(hook) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::callNodeCallback(): Argument #2 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(hook));
			return false;
		}
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, hook, scope, storage))) return false;
		zval *attrGroups = ptsh::readNodeProperty(pt_php_attr_groups_site, hook, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return false;
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return false;

		pt_php_docs docs;
		if (UNEXPECTED(!pt_php_docs_resolver_get_php_docs(OBJ_PROP_NUM(self, slots::phpDocsResolver), scope, hook, PT_PHP_DESTRUCTURED_PHP_DOCS, docs))) return false;

		zval *params = ptsh::readNodeProperty(pt_php_params_site, hook, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return false;
		if (UNEXPECTED(!ptsh::processParams(OBJ_PROP_NUM(self, slots::parametersProcessor), nodeScopeResolver, stmt, params, scope, storage, nodeCallback))) return false;

		zv::Val isDeprecated;
		zv::Val deprecatedDescription;
		if (UNEXPECTED(!pt_deprecated_attribute_resolver_get_deprecated_attribute(OBJ_PROP_NUM(self, slots::deprecatedAttributeResolver), scope, hook, isDeprecated, deprecatedDescription))) return false;

		zv::Val hookScope = pt_mutating_scope_enter_property_hook(Z_OBJ_P(scope), hook, propertyName, nativeTypeNode, phpDocType, &docs.items[PT_PHP_DOCS_PARAMETER_TYPES], &docs.items[PT_PHP_DOCS_THROW_TYPE], deprecatedDescription.raw(), isDeprecated.raw(), &docs.items[PT_PHP_DOCS_IS_PURE], &docs.items[PT_PHP_DOCS_DOC_COMMENT], &docs.items[PT_PHP_DOCS_RESOLVED_PHP_DOC]);
		if (UNEXPECTED(hookScope.isUndef())) return false;
		zv::Val hookReflection = pt_mutating_scope_get_function(Z_OBJ_P(hookScope.raw()));
		if (UNEXPECTED(hookReflection.isUndef())) return false;
		bool error = false;
		if (!ptsh::isInstanceOf(hookReflection.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, error)) {
			if (!error) pt_throw_should_not_happen();
			return false;
		}

		if (UNEXPECTED(Z_TYPE_P(classReflection) != IS_OBJECT)) {
			memberCallOnNonObject("hasNativeProperty", classReflection);
			return false;
		}
		bool hasNativeProperty;
		if (UNEXPECTED(!pt_class_reflection_has_native_property(Z_OBJ_P(classReflection), Z_STR_P(propertyName), hasNativeProperty))) return false;
		if (!hasNativeProperty) {
			pt_throw_should_not_happen();
			return false;
		}
		zv::Val propertyReflection = pt_class_reflection_get_native_property(Z_OBJ_P(classReflection), Z_STR_P(propertyName));
		if (UNEXPECTED(propertyReflection.isUndef())) return false;

		{
			zv::Args nodeArgv{classReflection, hookReflection.raw(), propertyReflection.raw(), hook};
			zv::Val inPropertyHookNode = pt_type_new(PT_CLASS_IN_PROPERTY_HOOK_NODE, 4, nodeArgv);
			if (UNEXPECTED(inPropertyHookNode.isUndef())) return false;
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inPropertyHookNode.raw(), hookScope.raw(), storage))) return false;
		}

		zv::Val stmts = pt_call_method_cached(pt_php_get_stmts_site, Z_OBJ_P(hook), PT_LC("getstmts"), 0, NULL);
		if (UNEXPECTED(stmts.isUndef())) return false;
		if (stmts.isNull()) {
			// abstract hook - the sibling hook of the same property may still
			// have a body, so keep going
			return true;
		}

		zval *body = ptsh::readNodeProperty(pt_php_body_site, hook, PT_LC("body"));
		if (UNEXPECTED(body == NULL)) return false;
		bool isExpr = ptsh::isInstanceOf(body, PT_CLASS_EXPR, error);
		if (UNEXPECTED(error)) return false;
		if (isExpr && UNEXPECTED(!enrichShortBodyAttributes(body, stmts.raw()))) return false;

		zv::Val gatheredReturnStatements = ptsh::newArrayReference();
		zv::Val gatheredReturnStatementsAfterFinally = ptsh::newArrayReference();
		zv::Val executionEnds = ptsh::newArrayReference();
		zv::Val hookImpurePoints = ptsh::newArrayReference();
		zval captures[5];
		ZVAL_COPY_VALUE(&captures[0], hookScope.raw());
		ZVAL_COPY_VALUE(&captures[1], gatheredReturnStatements.raw());
		ZVAL_COPY_VALUE(&captures[2], gatheredReturnStatementsAfterFinally.raw());
		ZVAL_COPY_VALUE(&captures[3], executionEnds.raw());
		ZVAL_COPY_VALUE(&captures[4], hookImpurePoints.raw());
		zv::Val gatherer = pt_native_closure_new(&gathererBody, 5, captures, 0b11110);
		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return false;

		zv::Val internalStatementResult;
		zv::Val statementResult;
		{
			zv::Args statementNodeArgv{hook};
			zv::Val statementNode = pt_type_new(PT_CLASS_PROPERTY_HOOK_STATEMENT_NODE, 1, statementNodeArgv);
			if (EXPECTED(!statementNode.isUndef())) {
				zv::Val statementContext = pt_statement_context_create_top_level();
				if (EXPECTED(!statementContext.isUndef())) {
					internalStatementResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, statementNode.raw(), stmts.raw(), hookScope.raw(), storage, nodeCallback, statementContext.raw());
					if (EXPECTED(!internalStatementResult.isUndef())) {
						statementResult = pt_internal_statement_result_to_public(internalStatementResult.raw());
					}
				}
			}
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
		if (UNEXPECTED(statementResult.isUndef() || EG(exception) != NULL)) return false;

		{
			zv::Val impurePointsHold;
			zval *resultImpurePoints = pt_statement_result_impure_points(statementResult.raw(), impurePointsHold);
			if (UNEXPECTED(resultImpurePoints == NULL)) return false;
			/* array_merge($statementResult->getImpurePoints(), $hookImpurePoints) */
			zv::Arr impurePoints = zv::Arr::empty();
			if (UNEXPECTED(!pt_callable_array_merge_into(impurePoints, resultImpurePoints) || !pt_callable_array_merge_into(impurePoints, Z_REFVAL_P(hookImpurePoints.raw())))) return false;
			zval nodeArgv[9];
			ZVAL_COPY_VALUE(&nodeArgv[0], hook);
			ZVAL_COPY_VALUE(&nodeArgv[1], Z_REFVAL_P(gatheredReturnStatements.raw()));
			ZVAL_COPY_VALUE(&nodeArgv[2], Z_REFVAL_P(gatheredReturnStatementsAfterFinally.raw()));
			ZVAL_COPY_VALUE(&nodeArgv[3], statementResult.raw());
			ZVAL_COPY_VALUE(&nodeArgv[4], Z_REFVAL_P(executionEnds.raw()));
			ZVAL_COPY_VALUE(&nodeArgv[5], impurePoints.raw());
			ZVAL_COPY_VALUE(&nodeArgv[6], classReflection);
			ZVAL_COPY_VALUE(&nodeArgv[7], hookReflection.raw());
			ZVAL_COPY_VALUE(&nodeArgv[8], propertyReflection.raw());
			zv::Val returnStatementsNode = pt_type_new(PT_CLASS_PROPERTY_HOOK_RETURN_STATEMENTS_NODE, 9, nodeArgv);
			if (UNEXPECTED(returnStatementsNode.isUndef())) return false;
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnStatementsNode.raw(), hookScope.raw(), storage))) return false;
		}

		zv::Val flowHold;
		zval *variableFlow = pt_internal_statement_result_variable_flow(internalStatementResult.raw(), flowHold);
		if (UNEXPECTED(variableFlow == NULL)) return false;
		zv::Val livenessNode = pt_variable_liveness_resolver_resolve(hook, variableFlow);
		if (UNEXPECTED(livenessNode.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, livenessNode.raw(), hookScope.raw(), storage);
	}

	/* (new NodeTraverser(new LineAttributesVisitor($hook->body->getStartLine(),
	 * $hook->body->getEndLine())))->traverse($stmts); false = pending exception */
	[[nodiscard]] static bool enrichShortBodyAttributes(zval *body, zval *stmts)
	{
		zv::Val startLine = pt_call_method_cached(pt_php_body_start_line_site, Z_OBJ_P(body), PT_LC("getstartline"), 0, NULL);
		if (UNEXPECTED(startLine.isUndef())) return false;
		zv::Val endLine = pt_call_method_cached(pt_php_body_end_line_site, Z_OBJ_P(body), PT_LC("getendline"), 0, NULL);
		if (UNEXPECTED(endLine.isUndef())) return false;
		zv::Args visitorArgv{startLine.raw(), endLine.raw()};
		zv::Val visitor = pt_type_new(PT_CLASS_LINE_ATTRIBUTES_VISITOR, 2, visitorArgv);
		if (UNEXPECTED(visitor.isUndef())) return false;
		if (UNEXPECTED(pt_ce_node_traverser == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: PhpParser\\NodeTraverser is not shadowed");
			return false;
		}
		zv::Val traverser = pt_type_new_ce(pt_ce_node_traverser, 1, visitor.raw());
		if (UNEXPECTED(traverser.isUndef())) return false;
		return !pt_call_method_cached(pt_php_traverse_site, Z_OBJ_P(traverser.raw()), PT_LC("traverse"), 1, stmts).isUndef();
	}

	/* static function (Node $node, Scope $scope) use ($hookScope,
	 * &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally,
	 * &$executionEnds, &$hookImpurePoints): void — captures in that order */
	static void gathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\PropertyHooksProcessor::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *node = &argv[0];
		zval *scope = &argv[1];
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			memberCallOnNonObject("getFunction", scope);
			return;
		}
		zval *hookScope = &captures[0];
		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return;
			zv::Val hookFunction = pt_mutating_scope_get_function(Z_OBJ_P(hookScope));
			if (UNEXPECTED(hookFunction.isUndef())) return;
			if (!zend_is_identical(function.raw(), hookFunction.raw())) return;
		}
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return;
		if (inAnonymousFunction) return;

		bool error = false;
		bool isPropertyAssign = ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isPropertyAssign) {
			zv::Val impurePoint = pt_impure_point_new(scope, node, pt_php_property_assign, pt_php_property_assignment, true);
			if (UNEXPECTED(impurePoint.isUndef())) return;
			ptsh::appendToReference(&captures[4], impurePoint.raw());
			return;
		}
		bool isExecutionEnd = ptsh::isInstanceOf(node, PT_CLASS_EXECUTION_END_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isExecutionEnd) {
			ptsh::appendToReference(&captures[3], node);
			return;
		}
		bool isReturnAfterFinally = ptsh::isInstanceOf(node, PT_CLASS_RETURN_AFTER_FINALLY_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isReturnAfterFinally) {
			zv::Val returnNode = pt_call_method_cached(pt_php_get_return_node_site, Z_OBJ_P(node), PT_LC("getreturnnode"), 0, NULL);
			if (UNEXPECTED(returnNode.isUndef())) return;
			zv::Args statementArgv{scope, returnNode.raw()};
			zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
			if (UNEXPECTED(statement.isUndef())) return;
			ptsh::appendToReference(&captures[2], statement.raw());
			return;
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

using phpstanturbo::PropertyHooksProcessor;

bool pt_property_hooks_processor_process_property_hooks(zval *processor, zval *nodeScopeResolver, zval *stmt, zval *nativeTypeNode, zval *phpDocType, zval *propertyName, zval *hooks, zval *scope, zval *storage, zval *nodeCallback)
{
	/* the method's parameter checks the direct path relies on */
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_property_hooks_processor && Z_TYPE_P(propertyName) == IS_STRING && Z_TYPE_P(hooks) == IS_ARRAY)) {
		return PropertyHooksProcessor(Z_OBJ_P(processor)).processPropertyHooks(nodeScopeResolver, stmt, nativeTypeNode, phpDocType, propertyName, hooks, scope, storage, nodeCallback);
	}
	zval argv[9];
	ZVAL_COPY_VALUE(&argv[0], nodeScopeResolver);
	ZVAL_COPY_VALUE(&argv[1], stmt);
	ZVAL_COPY_VALUE(&argv[2], nativeTypeNode);
	ZVAL_COPY_VALUE(&argv[3], phpDocType);
	ZVAL_COPY_VALUE(&argv[4], propertyName);
	ZVAL_COPY_VALUE(&argv[5], hooks);
	ZVAL_COPY_VALUE(&argv[6], scope);
	ZVAL_COPY_VALUE(&argv[7], storage);
	ZVAL_COPY_VALUE(&argv[8], nodeCallback);
	return !pt_type_call(Z_OBJ_P(processor), PT_LC("processpropertyhooks"), 9, argv).isUndef();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_property_hooks_processor()
{
	pt_php_property_assign = zend_string_init_interned(PT_LC("propertyAssign"), 1);
	pt_php_property_assignment = zend_string_init_interned(PT_LC("property assignment"), 1);

	reg::Class cls("PHPStan\\Analyser\\PropertyHooksProcessor");
	ptdecl::PropertyHooksProcessor::declareClass(cls);
	ptdecl::PropertyHooksProcessor::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *deprecatedAttributeResolver, *phpDocsResolver, *attributesHandler, *parametersProcessor;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, deprecatedAttributeResolver, phpDocsResolver, attributesHandler, parametersProcessor)) RETURN_THROWS();
		PropertyHooksProcessor(Z_OBJ_P(ZEND_THIS)).construct(deprecatedAttributeResolver, phpDocsResolver, attributesHandler, parametersProcessor);
	});

	cls.method(sigs::processPropertyHooks, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *nativeTypeNode, *phpDocType, *hooks, *scope, *storage, *nodeCallback;
		zend_string *propertyName;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT_OR_NULL(nativeTypeNode)
			Z_PARAM_OBJECT_OR_NULL(phpDocType)
			Z_PARAM_STR(propertyName)
			Z_PARAM_ARRAY(hooks)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		zval null;
		ZVAL_NULL(&null);
		zval propertyNameZv;
		ZVAL_STR(&propertyNameZv, propertyName);
		if (UNEXPECTED(!PropertyHooksProcessor(Z_OBJ_P(ZEND_THIS)).processPropertyHooks(nodeScopeResolver, stmt, nativeTypeNode != NULL ? nativeTypeNode : &null, phpDocType != NULL ? phpDocType : &null, &propertyNameZv, hooks, scope, storage, nodeCallback))) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_property_hooks_processor);
}

/* }}} */
