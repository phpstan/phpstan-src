/*
 * PHPStanTurbo\ClassLikeHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ClassLikeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the #[AutowiredExtensions] collection included) so Nette
 * autowires it. processStmt() is registered as the class's statement-handler
 * entry (Engine.h).
 *
 * The class-member ordering usort() runs zend_hash_sort() — the engine's
 * own sort usort() uses — with the twin's comparator natively and the
 * engine's stable fallback. MutatingScope, ClassReflection,
 * ClassStatementsGatherer, CalledMethodProcessor and NodeScopeResolver are
 * called through their direct entries; AttributesHandler through the shared
 * helper of StmtHandlerCalls.h; ReflectionProvider, ClassReflectionFactory,
 * BetterReflection and the php-parser node methods through the sites below.
 */

#include "support.h"
#include "generated/ClassLikeHandler.h"

namespace slots = ptdecl::ClassLikeHandler::slot;
namespace sigs = ptdecl::ClassLikeHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_class_like_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_clh_name_to_string_site;
pt_method_site pt_clh_is_anonymous_site;
pt_method_site pt_clh_has_class_site;
pt_method_site pt_clh_get_class_site;
pt_method_site pt_clh_get_anonymous_class_reflection_site;
pt_method_site pt_clh_native_start_line_site;
pt_method_site pt_clh_node_start_line_site;
pt_method_site pt_clh_node_to_reflection_invoke_site;
pt_method_site pt_clh_file_reader_read_site;
pt_method_site pt_clh_better_reflection_get_name_site;
pt_method_site pt_clh_class_reflection_factory_create_site;

/* $name->toString() */
zv::Val nameToString(zval *name)
{
	return pt_call_method_cached(pt_clh_name_to_string_site, Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
}

/* $class->isAnonymous() */
zv::Val isAnonymous(zval *classNode)
{
	return pt_call_method_cached(pt_clh_is_anonymous_site, Z_OBJ_P(classNode), PT_LC("isanonymous"), 0, NULL);
}

/* $reflectionProvider->hasClass($className) */
zv::Val hasClass(zval *reflectionProvider, zval *className)
{
	return pt_call_method_cached(pt_clh_has_class_site, Z_OBJ_P(reflectionProvider), PT_LC("hasclass"), 1, className);
}

/* $reflectionProvider->getClass($className) */
zv::Val getClass(zval *reflectionProvider, zval *className)
{
	return pt_call_method_cached(pt_clh_get_class_site, Z_OBJ_P(reflectionProvider), PT_LC("getclass"), 1, className);
}

/* $reflectionProvider->getAnonymousClassReflection($classNode, $scope) */
zv::Val getAnonymousClassReflection(zval *reflectionProvider, zval *classNode, zval *scope)
{
	zv::Args argv{classNode, scope};
	return pt_call_method_cached(pt_clh_get_anonymous_class_reflection_site, Z_OBJ_P(reflectionProvider), PT_LC("getanonymousclassreflection"), 2, argv);
}

/* $nativeReflection->getStartLine() */
zv::Val nativeReflectionStartLine(zval *nativeReflection)
{
	return pt_call_method_cached(pt_clh_native_start_line_site, Z_OBJ_P(nativeReflection), PT_LC("getstartline"), 0, NULL);
}

/* $stmt->getStartLine() */
zv::Val nodeStartLine(zval *node)
{
	return pt_call_method_cached(pt_clh_node_start_line_site, Z_OBJ_P(node), PT_LC("getstartline"), 0, NULL);
}

/* $nodeToReflection->__invoke($reflector, $node, $locatedSource, $namespace) */
zv::Val nodeToReflectionInvoke(zval *nodeToReflection, zval *argv)
{
	return pt_call_method_cached(pt_clh_node_to_reflection_invoke_site, Z_OBJ_P(nodeToReflection), PT_LC("__invoke"), 4, argv);
}

/* FileReader::read($fileName) */
zv::Val fileReaderRead(zval *fileName)
{
	return pt_call_static_cached(pt_clh_file_reader_read_site, PT_CLASS_FILE_READER, PT_LC("read"), 1, fileName);
}

/* $betterReflectionClass->getName() */
zv::Val betterReflectionName(zval *betterReflectionClass)
{
	return pt_call_method_cached(pt_clh_better_reflection_get_name_site, Z_OBJ_P(betterReflectionClass), PT_LC("getname"), 0, NULL);
}

/* $classReflectionFactory->create(...) with its six arguments */
zv::Val classReflectionFactoryCreate(zval *classReflectionFactory, zval *argv)
{
	return pt_call_method_cached(pt_clh_class_reflection_factory_create_site, Z_OBJ_P(classReflectionFactory), PT_LC("create"), 6, argv);
}

/* }}} */

pt_property_site pt_clh_namespaced_name_site;
pt_property_site pt_clh_name_site;
pt_property_site pt_clh_attr_groups_site;
pt_property_site pt_clh_stmts_site;
pt_property_site pt_clh_class_method_flags_site;
pt_property_site pt_clh_class_method_name_site;
pt_property_site pt_clh_identifier_name_site;

/* the existence-check function names, permanent interned strings (module
 * startup) */
zend_string *pt_clh_interface_exists = nullptr;
zend_string *pt_clh_class_exists = nullptr;
zend_string *pt_clh_enum_exists = nullptr;

/* php-parser's Modifiers::STATIC */
constexpr zend_long PT_CLH_MODIFIER_STATIC = 8;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* $node->prop of a declared property without the dereferencing of an
 * uninitialized typed property: NULL when the class declares none */
zval *rawNodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	return pt_property_cached(site, Z_OBJ_P(node), name, len);
}

/* [!$method->isStatic(), $method->name->toLowerString() !== '__construct']
 * of a ClassMethod: the two bools */
void methodSortKey(zval *method, bool &notStatic, bool &notConstructor)
{
	zval *flags = pt_property_cached(pt_clh_class_method_flags_site, Z_OBJ_P(method), PT_LC("flags"));
	notStatic = !(flags != NULL && Z_TYPE_P(flags) == IS_LONG && (Z_LVAL_P(flags) & PT_CLH_MODIFIER_STATIC) != 0);
	zval *name = pt_property_cached(pt_clh_class_method_name_site, Z_OBJ_P(method), PT_LC("name"));
	zend_string *nameString = NULL;
	if (name != NULL && Z_TYPE_P(name) == IS_OBJECT) {
		zval *identifier = pt_property_cached(pt_clh_identifier_name_site, Z_OBJ_P(name), PT_LC("name"));
		if (identifier != NULL && Z_TYPE_P(identifier) == IS_STRING) nameString = Z_STR_P(identifier);
	}
	notConstructor = nameString == NULL || !zend_string_equals_literal_ci(nameString, "__construct");
}

/* the class entries the comparator tests, resolved before the sort */
zend_class_entry *pt_clh_sort_property_ce = nullptr;
zend_class_entry *pt_clh_sort_class_method_ce = nullptr;

/* the twin's usort() comparator over two class-body statements */
int compareClassStatementsUnstable(zval *a, zval *b)
{
	if (Z_TYPE_P(a) == IS_OBJECT && instanceof_function(Z_OBJCE_P(a), pt_clh_sort_property_ce)) return 1;
	if (Z_TYPE_P(b) == IS_OBJECT && instanceof_function(Z_OBJCE_P(b), pt_clh_sort_property_ce)) return -1;
	if (Z_TYPE_P(a) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(a), pt_clh_sort_class_method_ce) || Z_TYPE_P(b) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(b), pt_clh_sort_class_method_ce)) return 0;

	bool aNotStatic, aNotConstructor, bNotStatic, bNotConstructor;
	methodSortKey(a, aNotStatic, aNotConstructor);
	methodSortKey(b, bNotStatic, bNotConstructor);
	if (aNotStatic != bNotStatic) return aNotStatic ? 1 : -1;
	if (aNotConstructor != bNotConstructor) return aNotConstructor ? 1 : -1;
	return 0;
}

/* php_usort()'s comparator: the user result, then the stable fallback on the
 * original positions zend_hash_sort() stores in Z_EXTRA */
int compareClassStatements(Bucket *a, Bucket *b)
{
	zval *aValue = &a->val;
	zval *bValue = &b->val;
	ZVAL_DEREF(aValue);
	ZVAL_DEREF(bValue);
	int result = compareClassStatementsUnstable(aValue, bValue);
	if (EXPECTED(result != 0)) return result;
	if (Z_EXTRA(a->val) > Z_EXTRA(b->val)) return 1;
	if (Z_EXTRA(a->val) < Z_EXTRA(b->val)) return -1;
	return 0;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ClassLikeHandler; UNDEF = pending
 * exception. */
class ClassLikeHandler
{
public:
	explicit ClassLikeHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflector, zval *classReflectionFactory, zval *calledMethodProcessor, zval *reflectionProvider, zval *attributesHandler, zval *readWritePropertiesExtensions)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::reflector, zv::Val::copyOf(zv::Ref(reflector)));
		object.propAtWrite(slots::classReflectionFactory, zv::Val::copyOf(zv::Ref(classReflectionFactory)));
		object.propAtWrite(slots::calledMethodProcessor, zv::Val::copyOf(zv::Ref(calledMethodProcessor)));
		object.propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
		object.propAtWrite(slots::readWritePropertiesExtensions, zv::Val::copyOf(zv::Ref(readWritePropertiesExtensions)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_LIKE_STMT, error) && !ptsh::isInstanceOf(stmt, PT_CLASS_TRAIT_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		// declaring a class/interface/enum defines it in global state,
		// so a matching negative existence-check narrowing must be forgotten
		bool error = false;
		zv::Arr existenceCheckFunctionNames = zv::Arr::create(2);
		if (ptsh::isInstanceOf(stmt, PT_CLASS_INTERFACE_STMT, error)) {
			existenceCheckFunctionNames.push(zv::Val::string(pt_clh_interface_exists));
		} else if (UNEXPECTED(error)) {
			return zv::Val();
		} else if (ptsh::isInstanceOf(stmt, PT_CLASS_ENUM_STMT, error)) {
			existenceCheckFunctionNames.push(zv::Val::string(pt_clh_class_exists));
			existenceCheckFunctionNames.push(zv::Val::string(pt_clh_enum_exists));
		} else if (UNEXPECTED(error)) {
			return zv::Val();
		} else {
			existenceCheckFunctionNames.push(zv::Val::string(pt_clh_class_exists));
		}
		zval *name = rawNodeProperty(pt_clh_namespaced_name_site, stmt, PT_LC("namespacedName"));
		if (name != NULL) ZVAL_DEREF(name);
		if (name == NULL || Z_TYPE_P(name) == IS_UNDEF || Z_TYPE_P(name) == IS_NULL) {
			name = ptsh::readNodeProperty(pt_clh_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
		}
		zv::Val declaredSymbolName = zv::Val::null();
		if (ptsh::isInstanceOf(name, PT_CLASS_NAME, error)) {
			declaredSymbolName = nameToString(name);
			if (UNEXPECTED(declaredSymbolName.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(error)) return zv::Val();
		zv::Val invalidatedScope = pt_mutating_scope_invalidate_existence_check_expressions(Z_OBJ_P(scope), existenceCheckFunctionNames.raw(), declaredSymbolName.raw());
		if (UNEXPECTED(invalidatedScope.isUndef())) return zv::Val();
		zval *resultScope = invalidatedScope.raw();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		bool isTopLevel;
		if (UNEXPECTED(!pt_statement_context_is_top_level(context, isTopLevel))) return zv::Val();
		if (!isTopLevel) {
			return pt_internal_statement_result_new(resultScope, false, false, &emptyArray, &emptyArray, &emptyArray);
		}

		zv::Val classReflection;
		zval *namespacedName = rawNodeProperty(pt_clh_namespaced_name_site, stmt, PT_LC("namespacedName"));
		if (namespacedName != NULL) ZVAL_DEREF(namespacedName);
		if (namespacedName != NULL && Z_TYPE_P(namespacedName) != IS_UNDEF && Z_TYPE_P(namespacedName) != IS_NULL) {
			if (UNEXPECTED(Z_TYPE_P(namespacedName) != IS_OBJECT)) {
				memberCallOnNonObject("toString", namespacedName);
				return zv::Val();
			}
			zv::Val className = nameToString(namespacedName);
			if (UNEXPECTED(className.isUndef())) return zv::Val();
			classReflection = getCurrentClassReflection(stmt, className.raw(), resultScope);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		} else if (ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_STMT, error)) {
			zval *className = ptsh::readNodeProperty(pt_clh_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(className == NULL)) return zv::Val();
			if (Z_TYPE_P(className) == IS_NULL) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val anonymous = isAnonymous(stmt);
			if (UNEXPECTED(anonymous.isUndef())) return zv::Val();
			if (!zend_is_true(anonymous.raw())) {
				className = ptsh::readNodeProperty(pt_clh_name_site, stmt, PT_LC("name"));
				if (UNEXPECTED(className == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(className) != IS_OBJECT)) {
					memberCallOnNonObject("toString", className);
					return zv::Val();
				}
				zv::Val classNameString = nameToString(className);
				if (UNEXPECTED(classNameString.isUndef())) return zv::Val();
				classReflection = getClass(OBJ_PROP_NUM(self, slots::reflectionProvider), classNameString.raw());
			} else {
				classReflection = getAnonymousClassReflection(OBJ_PROP_NUM(self, slots::reflectionProvider), stmt, resultScope);
			}
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		} else {
			if (!error) pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val classScope = pt_mutating_scope_enter_class(Z_OBJ_P(resultScope), classReflection.raw());
		if (UNEXPECTED(classScope.isUndef())) return zv::Val();

		zv::Val classStatementsGatherer = pt_class_statements_gatherer_new(classReflection.raw(), nodeCallback);
		if (UNEXPECTED(classStatementsGatherer.isUndef())) return zv::Val();
		// the class attributes are processed before the InClassNode emission, so
		// rules firing on it (ClassAttributesRule) read the attribute arguments
		// from the storage
		zval *attrGroups = ptsh::readNodeProperty(pt_clh_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, classScope.raw(), storage, classStatementsGatherer.raw()))) return zv::Val();
		{
			zv::Args nodeArgv{stmt, classReflection.raw()};
			zv::Val inClassNode = pt_type_new(PT_CLASS_IN_CLASS_NODE, 2, nodeArgv);
			if (UNEXPECTED(inClassNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inClassNode.raw(), classScope.raw(), storage))) return zv::Val();
		}

		zval *stmts = ptsh::readNodeProperty(pt_clh_stmts_site, stmt, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		zv::Val classLikeStatements = zv::Val::copyOf(zv::Ref(stmts));
		// analyze static methods first; constructor next; instance methods and property hooks last so we can carry over the scope
		if (UNEXPECTED(!sortClassStatements(classLikeStatements))) return zv::Val();

		// Class members have their own inference context, including when the class
		// declaration is visited during an enclosing body's observation pass.
		{
			zv::Val statementContext = pt_statement_context_create_top_level();
			if (UNEXPECTED(statementContext.isUndef())) return zv::Val();
			zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, classLikeStatements.raw(), classScope.raw(), storage, classStatementsGatherer.raw(), statementContext.raw());
			if (UNEXPECTED(walked.isUndef())) return zv::Val();
		}
		{
			zval nodeArgv[8];
			ZVAL_COPY_VALUE(&nodeArgv[0], stmt);
			ZVAL_COPY_VALUE(&nodeArgv[1], OBJ_PROP_NUM(self, slots::readWritePropertiesExtensions));
			zv::Val properties = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_PROPERTIES);
			zv::Val propertyUsages = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_PROPERTY_USAGES);
			zv::Val methodCalls = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_METHOD_CALLS);
			zv::Val returnStatementNodes = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_RETURN_STATEMENT_NODES);
			zv::Val propertyAssigns = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_PROPERTY_ASSIGNS);
			ZVAL_COPY_VALUE(&nodeArgv[2], properties.raw());
			ZVAL_COPY_VALUE(&nodeArgv[3], propertyUsages.raw());
			ZVAL_COPY_VALUE(&nodeArgv[4], methodCalls.raw());
			ZVAL_COPY_VALUE(&nodeArgv[5], returnStatementNodes.raw());
			ZVAL_COPY_VALUE(&nodeArgv[6], propertyAssigns.raw());
			ZVAL_COPY_VALUE(&nodeArgv[7], classReflection.raw());
			zv::Val classPropertiesNode = pt_type_new(PT_CLASS_CLASS_PROPERTIES_NODE, 8, nodeArgv);
			if (UNEXPECTED(classPropertiesNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, classPropertiesNode.raw(), classScope.raw(), storage))) return zv::Val();
		}
		{
			zv::Val methods = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_METHODS);
			zv::Val methodCalls = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_METHOD_CALLS);
			zv::Args nodeArgv{stmt, methods.raw(), methodCalls.raw(), classReflection.raw()};
			zv::Val classMethodsNode = pt_type_new(PT_CLASS_CLASS_METHODS_NODE, 4, nodeArgv);
			if (UNEXPECTED(classMethodsNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, classMethodsNode.raw(), classScope.raw(), storage))) return zv::Val();
		}
		{
			zv::Val constants = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_CONSTANTS);
			zv::Val constantFetches = pt_class_statements_gatherer_get(classStatementsGatherer.raw(), PT_CSG_LIST_CONSTANT_FETCHES);
			zv::Args nodeArgv{stmt, constants.raw(), constantFetches.raw(), classReflection.raw()};
			zv::Val classConstantsNode = pt_type_new(PT_CLASS_CLASS_CONSTANTS_NODE, 4, nodeArgv);
			if (UNEXPECTED(classConstantsNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, classConstantsNode.raw(), classScope.raw(), storage))) return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("evictPrivateSymbols", classReflection.raw());
			return zv::Val();
		}
		if (UNEXPECTED(!pt_class_reflection_evict_private_symbols(Z_OBJ_P(classReflection.raw())))) return zv::Val();
		if (UNEXPECTED(!pt_called_method_processor_clear_called_method_results(OBJ_PROP_NUM(self, slots::calledMethodProcessor)))) return zv::Val();

		return pt_internal_statement_result_new(resultScope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ClassLikeHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* usort($classLikeStatements, ...) — the array separated and sorted in
	 * place like usort() does; false = pending exception */
	[[nodiscard]] static bool sortClassStatements(zv::Val &statements)
	{
		if (UNEXPECTED(Z_TYPE_P(statements.raw()) != IS_ARRAY)) {
			zend_type_error("usort(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(statements.raw()));
			return false;
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(statements.raw())) == 0) return true;
		pt_clh_sort_property_ce = pt_class(PT_CLASS_PROPERTY_STMT);
		if (UNEXPECTED(pt_clh_sort_property_ce == NULL)) return false;
		pt_clh_sort_class_method_ce = pt_class(PT_CLASS_CLASS_METHOD_STMT);
		if (UNEXPECTED(pt_clh_sort_class_method_ce == NULL)) return false;
		HashTable *sorted = zend_array_dup(Z_ARRVAL_P(statements.raw()));
		zend_hash_sort(sorted, compareClassStatements, true);
		statements = zv::Val(zv::Arr::adoptTable(sorted));
		return true;
	}

	/* Mirrors getCurrentClassReflection(). */
	zv::Val getCurrentClassReflection(zval *stmt, zval *className, zval *scope) const
	{
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		zv::Val has = hasClass(reflectionProvider, className);
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		if (!zend_is_true(has.raw())) return createAstClassReflection(stmt, className, scope);

		zv::Val defaultClassReflection = getClass(reflectionProvider, className);
		if (UNEXPECTED(defaultClassReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(defaultClassReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getFileName", defaultClassReflection.raw());
			return zv::Val();
		}
		zv::Val fileName = pt_class_reflection_get_file_name(Z_OBJ_P(defaultClassReflection.raw()));
		if (UNEXPECTED(fileName.isUndef())) return zv::Val();
		zv::Val scopeFile = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(scopeFile.isUndef())) return zv::Val();
		if (!zend_is_identical(fileName.raw(), scopeFile.raw())) return createAstClassReflection(stmt, className, scope);

		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(defaultClassReflection.raw()));
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getStartLine", nativeReflection.raw());
			return zv::Val();
		}
		zv::Val startLine = nativeReflectionStartLine(nativeReflection.raw());
		if (UNEXPECTED(startLine.isUndef())) return zv::Val();
		zv::Val stmtStartLine = nodeStartLine(stmt);
		if (UNEXPECTED(stmtStartLine.isUndef())) return zv::Val();
		if (!zend_is_identical(startLine.raw(), stmtStartLine.raw())) return createAstClassReflection(stmt, className, scope);

		return defaultClassReflection;
	}

	/* Mirrors createAstClassReflection(). */
	zv::Val createAstClassReflection(zval *stmt, zval *className, zval *scope) const
	{
		zv::Val nodeToReflection = pt_type_new(PT_CLASS_NODE_TO_REFLECTION, 0, NULL);
		if (UNEXPECTED(nodeToReflection.isUndef())) return zv::Val();

		zv::Val sourceFile = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(sourceFile.isUndef())) return zv::Val();
		zv::Val source = fileReaderRead(sourceFile.raw());
		if (UNEXPECTED(source.isUndef())) return zv::Val();
		zv::Val locatedFile = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(locatedFile.isUndef())) return zv::Val();
		zv::Args locatedSourceArgv{source.raw(), className, locatedFile.raw()};
		zv::Val locatedSource = pt_type_new(PT_CLASS_LOCATED_SOURCE, 3, locatedSourceArgv);
		if (UNEXPECTED(locatedSource.isUndef())) return zv::Val();

		zv::Val namespaceNode = zv::Val::null();
		zv::Val namespaceName = pt_mutating_scope_get_namespace(Z_OBJ_P(scope));
		if (UNEXPECTED(namespaceName.isUndef())) return zv::Val();
		if (!namespaceName.isNull()) {
			zv::Val name = pt_mutating_scope_get_namespace(Z_OBJ_P(scope));
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val nameNode = pt_type_new(PT_CLASS_NAME, 1, name.raw());
			if (UNEXPECTED(nameNode.isUndef())) return zv::Val();
			namespaceNode = pt_type_new(PT_CLASS_NAMESPACE_STMT, 1, nameNode.raw());
			if (UNEXPECTED(namespaceNode.isUndef())) return zv::Val();
		}

		zv::Args invokeArgv{OBJ_PROP_NUM(self, slots::reflector), stmt, locatedSource.raw(), namespaceNode.raw()};
		zv::Val betterReflectionClass = nodeToReflectionInvoke(nodeToReflection.raw(), invokeArgv);
		if (UNEXPECTED(betterReflectionClass.isUndef())) return zv::Val();
		bool error = false;
		if (!ptsh::isInstanceOf(betterReflectionClass.raw(), PT_CLASS_BETTER_REFLECTION_CLASS, error)) {
			if (!error) pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val displayName = betterReflectionName(betterReflectionClass.raw());
		if (UNEXPECTED(displayName.isUndef())) return zv::Val();
		bool isEnum = ptsh::isInstanceOf(betterReflectionClass.raw(), PT_CLASS_BETTER_REFLECTION_ENUM, error);
		if (UNEXPECTED(error)) return zv::Val();
		zv::Val adapter = pt_type_new(isEnum ? PT_CLASS_REFLECTION_ENUM : PT_CLASS_ADAPTER_REFLECTION_CLASS, 1, betterReflectionClass.raw());
		if (UNEXPECTED(adapter.isUndef())) return zv::Val();
		zv::Val cacheFile = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(cacheFile.isUndef())) return zv::Val();
		zv::Val startLine = nodeStartLine(stmt);
		if (UNEXPECTED(startLine.isUndef())) return zv::Val();
		zend_string *cacheFileString = zval_get_string(cacheFile.raw());
		zend_string *extraCacheKey = zend_strpprintf(0, "%s:" ZEND_LONG_FMT, ZSTR_VAL(cacheFileString), zval_get_long(startLine.raw()));
		zend_string_release(cacheFileString);
		zv::Val extraCacheKeyValue = zv::Val::adoptString(extraCacheKey);

		zval createArgv[6];
		ZVAL_COPY_VALUE(&createArgv[0], displayName.raw());
		ZVAL_COPY_VALUE(&createArgv[1], adapter.raw());
		ZVAL_NULL(&createArgv[2]);
		ZVAL_NULL(&createArgv[3]);
		ZVAL_NULL(&createArgv[4]);
		ZVAL_COPY_VALUE(&createArgv[5], extraCacheKeyValue.raw());
		return classReflectionFactoryCreate(OBJ_PROP_NUM(self, slots::classReflectionFactory), createArgv);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassLikeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_class_like_handler()
{
	pt_clh_interface_exists = zend_string_init_interned(PT_LC("interface_exists"), 1);
	pt_clh_class_exists = zend_string_init_interned(PT_LC("class_exists"), 1);
	pt_clh_enum_exists = zend_string_init_interned(PT_LC("enum_exists"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ClassLikeHandler");
	ptdecl::ClassLikeHandler::declareClass(cls);
	ptdecl::ClassLikeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflector, *classReflectionFactory, *calledMethodProcessor, *reflectionProvider, *attributesHandler, *readWritePropertiesExtensions;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(reflector)
			Z_PARAM_OBJECT(classReflectionFactory)
			Z_PARAM_OBJECT(calledMethodProcessor)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_OBJECT(attributesHandler)
			Z_PARAM_OBJECT(readWritePropertiesExtensions)
		ZEND_PARSE_PARAMETERS_END();
		ClassLikeHandler(Z_OBJ_P(ZEND_THIS)).construct(reflector, classReflectionFactory, calledMethodProcessor, reflectionProvider, attributesHandler, readWritePropertiesExtensions);
	});

	cls.method<&ClassLikeHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ClassLikeHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_class_like_handler);
	pt_stmt_handler_entry_register(&pt_ce_class_like_handler, &ClassLikeHandler::processStmtEntry);
}

/* }}} */
