/*
 * PHPStanTurbo\CalledMethodProcessor — native implementation of
 * PHPStan\Analyser\CalledMethodProcessor.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it; the two memo arrays are its declared
 * properties. processCalledMethod() (MethodCallHandler: a method called on
 * $this from a constructor) and clearCalledMethodResults() (ClassLikeHandler,
 * after every class) are exported as pt_called_method_processor_*().
 *
 * The node callback the called method's walk runs is a native closure over
 * $methodReflection and &$returnStatement. The search for the declaring
 * class's node recurses over the parsed file natively under
 * pt_engine_with_stack(), stopping at class-likes and function-likes like
 * the twin. MutatingScope, ClassReflection, the method reflection, the
 * statement results, ExpressionResult, ExpressionResultStorage and
 * NodeScopeResolver are called through their direct entries; FileHelper,
 * Parser, ScopeFactory, ScopeContext, BetterReflection and the node classes
 * through the sites below.
 */

#include "support.h"
#include "generated/CalledMethodProcessor.h"

namespace slots = ptdecl::CalledMethodProcessor::slot;
namespace sigs = ptdecl::CalledMethodProcessor::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_called_method_processor = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_cmp_normalize_path_site;
pt_method_site pt_cmp_parse_file_site;
pt_method_site pt_cmp_scope_factory_create_site;
pt_method_site pt_cmp_node_class_reflection_site;
pt_method_site pt_cmp_get_execution_ends_site;
pt_method_site pt_cmp_get_statement_result_site;
pt_method_site pt_cmp_get_expr_result_site;
pt_method_site pt_cmp_get_return_statements_site;
pt_method_site pt_cmp_return_statement_scope_site;
pt_method_site pt_cmp_node_start_line_site;
pt_method_site pt_cmp_node_end_line_site;
pt_method_site pt_cmp_get_sub_node_names_site;

/* $object->method() of a PHP collaborator */
inline zv::Val call0(pt_method_site &site, zval *object, const char *lcname, size_t len)
{
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* }}} */

pt_property_site pt_cmp_namespaced_name_site;
pt_property_site pt_cmp_stmts_site;
pt_property_site pt_cmp_method_name_site;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* $methodReflection->getDeclaringClass(), checked to be an object for the
 * member call the twin makes on it */
zv::Val declaringClassOf(zval *methodReflection, const char *method)
{
	zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
	if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) {
		memberCallOnNonObject(method, declaringClass.raw());
		return zv::Val();
	}
	return declaringClass;
}

/* static function (Node $node, Scope $scope) use ($methodReflection,
 * &$returnStatement): void */
void returnStatementCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) return_value;
	if (UNEXPECTED(argc < 2)) {
		zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\CalledMethodProcessor::{closure}(), %u passed and exactly 2 expected", argc);
		return;
	}
	zval *node = &argv[0];
	bool error = false;
	if (!ptsh::isInstanceOf(node, PT_CLASS_METHOD_RETURN_STATEMENTS_NODE, error)) return;

	zv::Val nodeClassReflection = call0(pt_cmp_node_class_reflection_site, node, PT_LC("getclassreflection"));
	if (UNEXPECTED(nodeClassReflection.isUndef())) return;
	if (UNEXPECTED(Z_TYPE_P(nodeClassReflection.raw()) != IS_OBJECT)) {
		memberCallOnNonObject("getName", nodeClassReflection.raw());
		return;
	}
	zv::Val nodeClassName = pt_class_reflection_get_name(Z_OBJ_P(nodeClassReflection.raw()));
	if (UNEXPECTED(nodeClassName.isUndef())) return;
	zv::Val declaringClass = declaringClassOf(&captures[0], "getName");
	if (UNEXPECTED(declaringClass.isUndef())) return;
	zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
	if (UNEXPECTED(declaringClassName.isUndef())) return;
	if (!zend_is_identical(nodeClassName.raw(), declaringClassName.raw())) return;

	zval *returnStatement = Z_REFVAL(captures[1]);
	if (Z_TYPE_P(returnStatement) != IS_NULL) return;
	zval old;
	ZVAL_COPY_VALUE(&old, returnStatement);
	ZVAL_COPY(returnStatement, node);
	zval_ptr_dtor(&old);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\CalledMethodProcessor; UNDEF / false = pending
 * exception. */
class CalledMethodProcessor
{
public:
	explicit CalledMethodProcessor(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *fileHelper, zval *parser, zval *scopeFactory)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::fileHelper, zv::Val::copyOf(zv::Ref(fileHelper)));
		object.propAtWrite(slots::parser, zv::Val::copyOf(zv::Ref(parser)));
		object.propAtWrite(slots::scopeFactory, zv::Val::copyOf(zv::Ref(scopeFactory)));
	}

	/* Mirrors processCalledMethod(). */
	zv::Val processCalledMethod(zval *nodeScopeResolver, zval *methodReflection) const
	{
		zv::Val declaringClass = declaringClassOf(methodReflection, "isAnonymous");
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zend_object *declaringClassObject = Z_OBJ_P(declaringClass.raw());
		{
			bool isAnonymous;
			if (UNEXPECTED(!pt_class_reflection_is_anonymous(declaringClassObject, isAnonymous))) return zv::Val();
			if (isAnonymous) return zv::Val::null();
		}
		{
			zv::Val fileName = pt_class_reflection_get_file_name(declaringClassObject);
			if (UNEXPECTED(fileName.isUndef())) return zv::Val();
			if (fileName.isNull()) return zv::Val::null();
		}

		zv::Val stackName;
		{
			zv::Val className = pt_class_reflection_get_name(declaringClassObject);
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			zv::Val methodName = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_NAME);
			if (UNEXPECTED(methodName.isUndef())) return zv::Val();
			zend_string *classNameString = zval_try_get_string(className.raw());
			if (UNEXPECTED(classNameString == NULL)) return zv::Val();
			zend_string *methodNameString = zval_try_get_string(methodName.raw());
			if (UNEXPECTED(methodNameString == NULL)) {
				zend_string_release(classNameString);
				return zv::Val();
			}
			stackName = zv::Val::adoptString(zend_strpprintf(0, "%s::%s", ZSTR_VAL(classNameString), ZSTR_VAL(methodNameString)));
			zend_string_release(classNameString);
			zend_string_release(methodNameString);
		}
		zend_string *key = Z_STR_P(stackName.raw());

		{
			zval *results = memo(slots::calledMethodResults);
			zval *cached = zend_symtable_find(Z_ARRVAL_P(results), key);
			if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached).deref());
			zval *stack = memo(slots::calledMethodStack);
			if (zend_symtable_find(Z_ARRVAL_P(stack), key) != NULL) return zv::Val::null();
			if (zend_hash_num_elements(Z_ARRVAL_P(stack)) > 0) return zv::Val::null();
			SEPARATE_ARRAY(stack);
			zval trueValue;
			ZVAL_TRUE(&trueValue);
			zend_symtable_update(Z_ARRVAL_P(stack), key, &trueValue);
		}

		zv::Val fileName;
		{
			zv::Val declaringFileName = pt_class_reflection_get_file_name(declaringClassObject);
			if (UNEXPECTED(declaringFileName.isUndef())) return zv::Val();
			fileName = pt_call_method_cached(pt_cmp_normalize_path_site, Z_OBJ_P(OBJ_PROP_NUM(self, slots::fileHelper)), PT_LC("normalizepath"), 1, declaringFileName.raw());
			if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		}
		{
			bool isAnalysed;
			if (UNEXPECTED(!pt_node_scope_resolver_is_analysed_file(nodeScopeResolver, fileName.raw(), isAnalysed))) return zv::Val();
			if (!isAnalysed) {
				unsetStackEntry(key);
				return zv::Val::null();
			}
		}
		zv::Val parserNodes = pt_call_method_cached(pt_cmp_parse_file_site, Z_OBJ_P(OBJ_PROP_NUM(self, slots::parser)), PT_LC("parsefile"), 1, fileName.raw());
		if (UNEXPECTED(parserNodes.isUndef())) return zv::Val();

		zval returnStatementNull = {};
		ZVAL_NULL(&returnStatementNull);
		zval returnStatementReference;
		ZVAL_NEW_REF(&returnStatementReference, &returnStatementNull);
		zv::Val returnStatement = zv::Val::adopt(returnStatementReference);
		{
			zval captures[2];
			ZVAL_COPY_VALUE(&captures[0], methodReflection);
			ZVAL_COPY_VALUE(&captures[1], returnStatement.raw());
			zv::Val nodeCallback = pt_native_closure_new(&returnStatementCallbackBody, 2, captures, 0b10);
			zv::Val storage = pt_expression_result_storage_new();
			if (UNEXPECTED(storage.isUndef())) return zv::Val();
			if (UNEXPECTED(!processNodesForCalledMethod(nodeScopeResolver, parserNodes.raw(), storage.raw(), fileName.raw(), methodReflection, nodeCallback.raw()))) return zv::Val();
		}

		zv::Val calledMethodEndScope = zv::Val::null();
		zval *found = Z_REFVAL_P(returnStatement.raw());
		if (Z_TYPE_P(found) != IS_NULL) {
			zv::Val node = zv::Val::copyOf(zv::Ref(found));
			if (UNEXPECTED(!mergeEndScopes(node.raw(), calledMethodEndScope))) return zv::Val();
		}

		unsetStackEntry(key);
		zval *results = memo(slots::calledMethodResults);
		SEPARATE_ARRAY(results);
		zval stored;
		ZVAL_COPY(&stored, calledMethodEndScope.raw());
		zend_symtable_update(Z_ARRVAL_P(results), key, &stored);

		return calledMethodEndScope;
	}

	/* Mirrors clearCalledMethodResults(). */
	void clearCalledMethodResults()
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		zv::ObjRef(self).propAtWrite(slots::calledMethodResults, zv::Val::adopt(empty));
	}

private:
	zend_object *self;

	/* a memo array slot (always an array: typed, initialized `= []`) */
	zval *memo(uint32_t slot) const
	{
		zval *value = OBJ_PROP_NUM(self, slot);
		ZVAL_DEREF(value);
		return value;
	}

	/* unset($this->calledMethodStack[$stackName]) */
	void unsetStackEntry(zend_string *key) const
	{
		zval *stack = memo(slots::calledMethodStack);
		SEPARATE_ARRAY(stack);
		zend_symtable_del(Z_ARRVAL_P(stack), key);
	}

	/* the merged scope of the MethodReturnStatementsNode's non-never
	 * execution ends and return statements; false = pending exception */
	[[nodiscard]] static bool mergeEndScopes(zval *returnStatement, zv::Val &calledMethodEndScope)
	{
		zv::Val executionEnds = call0(pt_cmp_get_execution_ends_site, returnStatement, PT_LC("getexecutionends"));
		if (UNEXPECTED(executionEnds.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(executionEnds.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(executionEnds.raw()));
			if (UNEXPECTED(EG(exception))) return false;
		} else {
			for (auto entry : zv::ArrRef(executionEnds.raw())) {
				if (UNEXPECTED(!mergeExecutionEnd(entry.value().deref().raw(), calledMethodEndScope))) return false;
			}
		}

		zv::Val returnStatements = call0(pt_cmp_get_return_statements_site, returnStatement, PT_LC("getreturnstatements"));
		if (UNEXPECTED(returnStatements.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(returnStatements.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(returnStatements.raw()));
			return !EG(exception);
		}
		for (auto entry : zv::ArrRef(returnStatements.raw())) {
			zval *statement = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(statement) != IS_OBJECT)) {
				memberCallOnNonObject("getScope", statement);
				return false;
			}
			zv::Val statementScope = call0(pt_cmp_return_statement_scope_site, statement, PT_LC("getscope"));
			if (UNEXPECTED(statementScope.isUndef())) return false;
			if (UNEXPECTED(!mergeInto(calledMethodEndScope, std::move(statementScope)))) return false;
		}
		return true;
	}

	/* the first foreach's body over one execution end */
	[[nodiscard]] static bool mergeExecutionEnd(zval *executionEnd, zv::Val &calledMethodEndScope)
	{
		if (UNEXPECTED(Z_TYPE_P(executionEnd) != IS_OBJECT)) {
			memberCallOnNonObject("getStatementResult", executionEnd);
			return false;
		}
		zv::Val statementResult = call0(pt_cmp_get_statement_result_site, executionEnd, PT_LC("getstatementresult"));
		if (UNEXPECTED(statementResult.isUndef())) return false;
		zv::Val endExprResult = call0(pt_cmp_get_expr_result_site, executionEnd, PT_LC("getexprresult"));
		if (UNEXPECTED(endExprResult.isUndef())) return false;
		if (!endExprResult.isNull()) {
			zv::Val scopeHold;
			zval *statementScope = pt_statement_result_scope(statementResult.raw(), scopeHold);
			if (UNEXPECTED(statementScope == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(statementScope) != IS_OBJECT)) {
				memberCallOnNonObject("toWalkScope", statementScope);
				return false;
			}
			zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(statementScope));
			if (UNEXPECTED(walkScope.isUndef())) return false;
			bool nativeTypesPromoted;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(walkScope.raw()), nativeTypesPromoted))) return false;
			zv::Val exprType = pt_expression_result_get_type_on_scope(endExprResult.raw(), walkScope.raw(), nativeTypesPromoted);
			if (UNEXPECTED(exprType.isUndef())) return false;
			if (Z_TYPE_P(exprType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(exprType.raw()), pt_ce_never_type)) {
				zv::Val isExplicit = pt_type_call(Z_OBJ_P(exprType.raw()), PT_LC("isexplicit"), 0, NULL);
				if (UNEXPECTED(isExplicit.isUndef())) return false;
				if (zend_is_true(isExplicit.raw())) return true;
			}
		}
		zv::Val scopeHold;
		zval *statementScope = pt_statement_result_scope(statementResult.raw(), scopeHold);
		if (UNEXPECTED(statementScope == NULL)) return false;
		return mergeInto(calledMethodEndScope, zv::Val::copyOf(zv::Ref(statementScope)));
	}

	/* $calledMethodEndScope = $calledMethodEndScope === null ? $scope :
	 * $calledMethodEndScope->mergeWith($scope) */
	[[nodiscard]] static bool mergeInto(zv::Val &calledMethodEndScope, zv::Val scope)
	{
		if (calledMethodEndScope.isNull()) {
			calledMethodEndScope = std::move(scope);
			return true;
		}
		if (UNEXPECTED(Z_TYPE_P(calledMethodEndScope.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("mergeWith", calledMethodEndScope.raw());
			return false;
		}
		zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(calledMethodEndScope.raw()), scope.raw());
		if (UNEXPECTED(merged.isUndef())) return false;
		calledMethodEndScope = std::move(merged);
		return true;
	}

	/* Mirrors processNodesForCalledMethod(); false = pending exception */
	[[nodiscard]] bool processNodesForCalledMethod(zval *nodeScopeResolver, zval *node, zval *storage, zval *fileName, zval *methodReflection, zval *nodeCallback) const
	{
		bool result = false;
		pt_engine_with_stack([&]() { result = processNodesForCalledMethodStep(nodeScopeResolver, node, storage, fileName, methodReflection, nodeCallback); });
		return result;
	}

	[[nodiscard]] bool processNodesForCalledMethodStep(zval *nodeScopeResolver, zval *node, zval *storage, zval *fileName, zval *methodReflection, zval *nodeCallback) const
	{
		if (Z_TYPE_P(node) == IS_ARRAY) {
			zv::Val iterated = zv::Val::copyOf(zv::Ref(node));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!processNodesForCalledMethod(nodeScopeResolver, entry.value().deref().raw(), storage, fileName, methodReflection, nodeCallback))) return false;
			}
			return true;
		}
		bool error = false;
		if (!ptsh::isInstanceOf(node, PT_CLASS_NODE, error)) return !error;

		zv::Val declaringClass = declaringClassOf(methodReflection, "getName");
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		bool isDeclaringClassNode;
		if (UNEXPECTED(!isDeclaringClassStatement(node, Z_OBJ_P(declaringClass.raw()), isDeclaringClassNode))) return false;
		if (isDeclaringClassNode) return processDeclaringClassMethods(nodeScopeResolver, node, declaringClass.raw(), storage, fileName, methodReflection, nodeCallback);

		if (ptsh::isInstanceOf(node, PT_CLASS_CLASS_LIKE_STMT, error)) return true;
		if (UNEXPECTED(error)) return false;
		if (ptsh::isInstanceOf(node, PT_CLASS_FUNCTION_LIKE, error)) return true;
		if (UNEXPECTED(error)) return false;

		zv::Val subNodeNames = call0(pt_cmp_get_sub_node_names_site, node, PT_LC("getsubnodenames"));
		if (UNEXPECTED(subNodeNames.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(subNodeNames.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(subNodeNames.raw()));
			return !EG(exception);
		}
		for (auto entry : zv::ArrRef(subNodeNames.raw())) {
			zval *subNodeName = entry.value().deref().raw();
			zend_string *name = zval_try_get_string(subNodeName);
			if (UNEXPECTED(name == NULL)) return false;
			zval rv;
			zval *subNode = Z_OBJ_P(node)->handlers->read_property(Z_OBJ_P(node), name, BP_VAR_R, NULL, &rv);
			zend_string_release(name);
			if (UNEXPECTED(EG(exception))) {
				if (subNode == &rv) zval_ptr_dtor(&rv);
				return false;
			}
			zv::Val subNodeHold = zv::Val::copyOf(zv::Ref(subNode).deref());
			if (subNode == &rv) zval_ptr_dtor(&rv);
			if (UNEXPECTED(!processNodesForCalledMethod(nodeScopeResolver, subNodeHold.raw(), storage, fileName, methodReflection, nodeCallback))) return false;
		}
		return true;
	}

	/* $node instanceof Class_ && isset($node->namespacedName) &&
	 * $declaringClass->getName() === (string) $node->namespacedName &&
	 * $declaringClass->getNativeReflection()->getStartLine() === $node->getStartLine() */
	[[nodiscard]] static bool isDeclaringClassStatement(zval *node, zend_object *declaringClass, bool &out)
	{
		out = false;
		bool error = false;
		if (!ptsh::isInstanceOf(node, PT_CLASS_CLASS_STMT, error)) return !error;
		zval *namespacedName = pt_property_cached(pt_cmp_namespaced_name_site, Z_OBJ_P(node), PT_LC("namespacedName"));
		if (namespacedName == NULL) return true;
		ZVAL_DEREF(namespacedName);
		if (Z_TYPE_P(namespacedName) == IS_UNDEF || Z_TYPE_P(namespacedName) == IS_NULL) return true;

		zv::Val className = pt_class_reflection_get_name(declaringClass);
		if (UNEXPECTED(className.isUndef())) return false;
		zend_string *nodeName = pt_name_node_cast_string(namespacedName);
		if (UNEXPECTED(nodeName == NULL)) return false;
		bool sameName = Z_TYPE_P(className.raw()) == IS_STRING && zend_string_equals(Z_STR_P(className.raw()), nodeName);
		zend_string_release(nodeName);
		if (!sameName) return true;

		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(declaringClass);
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getStartLine", nativeReflection.raw());
			return false;
		}
		zv::Val startLine = pt_class_adapter_get_start_line(nativeReflection.raw());
		if (UNEXPECTED(startLine.isUndef())) return false;
		zv::Val nodeStartLine = call0(pt_cmp_node_start_line_site, node, PT_LC("getstartline"));
		if (UNEXPECTED(nodeStartLine.isUndef())) return false;
		out = zend_is_identical(startLine.raw(), nodeStartLine.raw());
		return true;
	}

	/* the declaring class's short methods of the called method's name, each
	 * walked in a fresh class scope */
	[[nodiscard]] bool processDeclaringClassMethods(zval *nodeScopeResolver, zval *node, zval *declaringClass, zval *storage, zval *fileName, zval *methodReflection, zval *nodeCallback) const
	{
		zval *stmts = ptsh::readNodeProperty(pt_cmp_stmts_site, node, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(stmts) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(stmts));
			return !EG(exception);
		}
		zv::Val iterated = zv::Val::copyOf(zv::Ref(stmts));
		for (auto entry : zv::ArrRef(iterated.raw())) {
			zval *stmt = entry.value().deref().raw();
			bool error = false;
			if (!ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_METHOD_STMT, error)) {
				if (UNEXPECTED(error)) return false;
				continue;
			}
			zval *name = ptsh::readNodeProperty(pt_cmp_method_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
				memberCallOnNonObject("toString", name);
				return false;
			}
			zv::Val stmtName = pt_name_node_to_string(name);
			if (UNEXPECTED(stmtName.isUndef())) return false;
			zv::Val methodName = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_NAME);
			if (UNEXPECTED(methodName.isUndef())) return false;
			if (!zend_is_identical(stmtName.raw(), methodName.raw())) continue;

			zv::Val endLine = call0(pt_cmp_node_end_line_site, stmt, PT_LC("getendline"));
			if (UNEXPECTED(endLine.isUndef())) return false;
			zv::Val startLine = call0(pt_cmp_node_start_line_site, stmt, PT_LC("getstartline"));
			if (UNEXPECTED(startLine.isUndef())) return false;
			if (zval_get_long(endLine.raw()) - zval_get_long(startLine.raw()) > 50) continue;

			zv::Val scopeContext = pt_type_call_static_ce(pt_ce_scope_context, PT_LC("create"), 1, fileName);
			if (UNEXPECTED(scopeContext.isUndef())) return false;
			zv::Val fileScope = pt_call_method_cached(pt_cmp_scope_factory_create_site, Z_OBJ_P(OBJ_PROP_NUM(self, slots::scopeFactory)), PT_LC("create"), 1, scopeContext.raw());
			if (UNEXPECTED(fileScope.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(fileScope.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("enterClass", fileScope.raw());
				return false;
			}
			zv::Val classScope = pt_mutating_scope_enter_class(Z_OBJ_P(fileScope.raw()), declaringClass);
			if (UNEXPECTED(classScope.isUndef())) return false;
			zv::Val statementContext = pt_statement_context_create_top_level();
			if (UNEXPECTED(statementContext.isUndef())) return false;
			zv::Val walked = pt_node_scope_resolver_process_stmt_node(nodeScopeResolver, stmt, classScope.raw(), storage, nodeCallback, statementContext.raw());
			if (UNEXPECTED(walked.isUndef())) return false;
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CalledMethodProcessor;

zv::Val pt_called_method_processor_process_called_method(zval *processor, zval *nodeScopeResolver, zval *methodReflection)
{
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_called_method_processor)) return CalledMethodProcessor(Z_OBJ_P(processor)).processCalledMethod(nodeScopeResolver, methodReflection);
	zv::Args argv{nodeScopeResolver, methodReflection};
	return pt_type_call(Z_OBJ_P(processor), PT_LC("processcalledmethod"), 2, argv);
}

bool pt_called_method_processor_clear_called_method_results(zval *processor)
{
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_called_method_processor)) {
		CalledMethodProcessor(Z_OBJ_P(processor)).clearCalledMethodResults();
		return true;
	}
	return !pt_type_call(Z_OBJ_P(processor), PT_LC("clearcalledmethodresults"), 0, NULL).isUndef();
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_called_method_processor)
{
	reg::Class cls("PHPStan\\Analyser\\CalledMethodProcessor");
	ptdecl::CalledMethodProcessor::declareClass(cls);
	ptdecl::CalledMethodProcessor::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fileHelper, *parser, *scopeFactory;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, fileHelper, parser, scopeFactory)) RETURN_THROWS();
		CalledMethodProcessor(Z_OBJ_P(ZEND_THIS)).construct(fileHelper, parser, scopeFactory);
	});

	cls.method(sigs::processCalledMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *methodReflection;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, nodeScopeResolver, methodReflection)) RETURN_THROWS();
		PT_RETURN_VAL(CalledMethodProcessor(Z_OBJ_P(ZEND_THIS)).processCalledMethod(nodeScopeResolver, methodReflection));
	});

	cls.method(sigs::clearCalledMethodResults, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		CalledMethodProcessor(Z_OBJ_P(ZEND_THIS)).clearCalledMethodResults();
	});

	cls.shadow(&pt_ce_called_method_processor);
}

/* }}} */
