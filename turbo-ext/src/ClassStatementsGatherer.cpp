/*
 * PHPStanTurbo\ClassStatementsGatherer — native implementation of
 * PHPStan\Node\ClassStatementsGatherer, declared under that name at
 * activation (final, like the twin).
 *
 * ClassLikeHandler hands one to the walk of a class body as its node
 * callback: every node of the class reaches __invoke(), which forwards the
 * pair to the wrapped callback (the rule and collector dispatch, PHP) and
 * then gathers the properties, methods, method calls, property usages,
 * constants, constant fetches, return statements and property assigns the
 * Class*Node emissions after the walk carry. The gathering asks the scope
 * through MutatingScope's and ScopeContext's direct entries and the class
 * reflections through ClassReflection's, so the scope and reflection calls
 * made from here (~2.7M per self-analysis) stay native; anything a subclass
 * may override or a PHP twin answers goes by name. The collected value
 * objects are the PHP classes, built through the class map; the PHPStan
 * node classes read here are final with plain getters, so their slots are
 * read directly.
 *
 * State is the twin's properties in declaration order: the untyped
 * $nodeCallback, the eight collected arrays, then the promoted
 * $classReflection.
 */

#include "ParserVisitors.h"
#include "generated/ClassStatementsGatherer.h"

namespace slots = ptdecl::ClassStatementsGatherer::slot;
namespace sigs = ptdecl::ClassStatementsGatherer::sig;
#include "TypeTraits.h"
#include "Engine.h"

static zend_class_entry *pt_ce_class_statements_gatherer = nullptr;

/* private const PROPERTY_ENUMERATING_FUNCTIONS = ['get_object_vars', 'array_walk'] */
/* a persistent immutable list of interned strings, as the twin's constant
 * (the engine references it for the process lifetime) */
static HashTable *pt_csg_persistent_list(const pt_superglobal_name *names, size_t count)
{
	HashTable *list = (HashTable *) pemalloc(sizeof(HashTable), 1);
	zend_hash_init(list, (uint32_t) count, NULL, NULL, 1);
	for (size_t i = 0; i < count; i++) {
		zval value;
		ZVAL_INTERNED_STR(&value, zend_string_init_interned(names[i].name, names[i].len, 1));
		zend_hash_next_index_insert(list, &value);
	}
	GC_ADD_FLAGS(list, IS_ARRAY_IMMUTABLE);
	GC_SET_REFCOUNT(list, 2);
	return list;
}

static const pt_superglobal_name pt_csg_property_enumerating_functions[] = {
	{"get_object_vars", sizeof("get_object_vars") - 1},
	{"array_walk", sizeof("array_walk") - 1},
};

static void pt_csg_property_enumerating_functions_constant(zval *out)
{
	ZVAL_ARR(out, pt_csg_persistent_list(pt_csg_property_enumerating_functions, sizeof(pt_csg_property_enumerating_functions) / sizeof(pt_csg_property_enumerating_functions[0])));
	Z_TYPE_INFO_P(out) = IS_ARRAY;
}

namespace phpstanturbo {

using visitors::NodeProp;
using visitors::isInstanceOf;

static NodeProp pt_csg_class_property_node_name = PT_NODE_PROP(PT_CLASS_CLASS_PROPERTY_NODE, "name");
static NodeProp pt_csg_class_property_node_is_promoted = PT_NODE_PROP(PT_CLASS_CLASS_PROPERTY_NODE, "isPromoted");
static NodeProp pt_csg_static_call_name = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
static NodeProp pt_csg_static_call_class = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
static NodeProp pt_csg_identifier_name = PT_IDENTIFIER_PROP;
static NodeProp pt_csg_name_name = PT_NAME_PROP;
static NodeProp pt_csg_method_callable_original = PT_NODE_PROP(PT_CLASS_METHOD_CALLABLE_NODE, "originalNode");
static NodeProp pt_csg_static_method_callable_original = PT_NODE_PROP(PT_CLASS_STATIC_METHOD_CALLABLE_NODE, "originalNode");
static NodeProp pt_csg_function_callable_original = PT_NODE_PROP(PT_CLASS_FUNCTION_CALLABLE_NODE, "originalNode");
static NodeProp pt_csg_instantiation_callable_original = PT_NODE_PROP(PT_CLASS_INSTANTIATION_CALLABLE_NODE, "originalNode");
static NodeProp pt_csg_return_statements_class_method = PT_NODE_PROP(PT_CLASS_METHOD_RETURN_STATEMENTS_NODE, "classMethod");
static NodeProp pt_csg_class_method_stmt_name = PT_NODE_PROP(PT_CLASS_CLASS_METHOD_STMT, "name");
static NodeProp pt_csg_class_method_stmt_params = PT_NODE_PROP(PT_CLASS_CLASS_METHOD_STMT, "params");
static NodeProp pt_csg_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
static NodeProp pt_csg_array_items = PT_NODE_PROP(PT_CLASS_ARRAY_EXPR, "items");
static NodeProp pt_csg_property_assign_fetch = PT_NODE_PROP(PT_CLASS_PROPERTY_ASSIGN_NODE, "propertyFetch");
static NodeProp pt_csg_property_assign_expr = PT_NODE_PROP(PT_CLASS_PROPERTY_ASSIGN_NODE, "assignedExpr");
static NodeProp pt_csg_coalesce_var = PT_NODE_PROP(PT_CLASS_COALESCE_ASSIGN_OP_EXPR, "var");
static NodeProp pt_csg_assign_ref_expr = PT_NODE_PROP(PT_CLASS_ASSIGN_REF_EXPR, "expr");
static NodeProp pt_csg_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");
static NodeProp pt_csg_array_dim_fetch_var = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "var");
static NodeProp pt_csg_param_flags = PT_NODE_PROP(PT_CLASS_PARAM, "flags");
static NodeProp pt_csg_param_hooks = PT_NODE_PROP(PT_CLASS_PARAM, "hooks");
static NodeProp pt_csg_param_var = PT_NODE_PROP(PT_CLASS_PARAM, "var");
static NodeProp pt_csg_arg_value = PT_NODE_PROP(PT_CLASS_ARG, "value");
static NodeProp pt_csg_gathered_class_method_node = PT_NODE_PROP(PT_CLASS_GATHERED_CLASS_METHOD, "node");

/* Mirrors PHPStan\Node\ClassStatementsGatherer. State lives in the PHP
 * object's slots. */
class ClassStatementsGatherer
{
public:
	explicit ClassStatementsGatherer(zend_object *self) : self(self) {}

	void construct(zval *classReflection, zval *nodeCallback)
	{
		zv::ObjRef(self).propAtWrite(slots::classReflection, zv::Val::copyOf(zv::Ref(classReflection)));
		zv::ObjRef(self).propAtWrite(slots::nodeCallback, zv::Val::copyOf(zv::Ref(nodeCallback)));
	}

	/* __invoke(): the wrapped callback, then the gathering; false = pending
	 * exception */
	[[nodiscard]] bool invoke(zval *node, zval *scope)
	{
		if (UNEXPECTED(!pt_engine_call_node_callback(OBJ_PROP_NUM(self, slots::nodeCallback), node, scope))) return false;
		return gatherNodes(Z_OBJ_P(node), scope);
	}

private:
	zend_object *self;

	/* $this->{slot}[] = $value (borrowed) */
	void push(uint32_t slotIndex, zval *value)
	{
		visitors::pushStack(OBJ_PROP_NUM(self, slotIndex), value);
	}

	/* $this->{slot}[] = new <classIdx>(...$args); false = pending exception */
	[[nodiscard]] bool pushNew(uint32_t slotIndex, int classIdx, uint32_t argc, zval *argv)
	{
		zv::Val object = pt_type_new(classIdx, argc, argv);
		if (UNEXPECTED(object.isUndef())) return false;
		push(slotIndex, object.raw());
		return true;
	}

	/* new <classIdx>($node, $scope) appended to the slot */
	bool pushNodeScope(uint32_t slotIndex, int classIdx, zval *node, zval *scope)
	{
		zv::Args args{node, scope};
		return pushNew(slotIndex, classIdx, 2, args);
	}

	/* new PropertyWrite($fetch, $scope, $promoted[, $originalNode]) appended
	 * to $propertyUsages */
	bool pushPropertyWrite(zval *fetch, zval *scope, bool promoted, zval *originalNode)
	{
		zval args[4];
		ZVAL_COPY_VALUE(&args[0], fetch);
		ZVAL_COPY_VALUE(&args[1], scope);
		ZVAL_BOOL(&args[2], promoted);
		if (originalNode != NULL) {
			ZVAL_COPY_VALUE(&args[3], originalNode);
		}
		return pushNew(slots::propertyUsages, PT_CLASS_PROPERTY_WRITE, originalNode != NULL ? 4 : 3, args);
	}

	static bool throwShouldNotHappen()
	{
		zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 0, NULL);
		if (UNEXPECTED(exception.isUndef())) return false;
		zval thrown;
		ZVAL_COPY(&thrown, exception.raw());
		zend_throw_exception_object(&thrown);
		return false;
	}

	static bool isPropertyFetchLike(zend_object *node)
	{
		return isInstanceOf(node, PT_CLASS_PROPERTY_FETCH) || isInstanceOf(node, PT_CLASS_STATIC_PROPERTY_FETCH);
	}

	/* new PropertyFetch(new Variable('this'), new Identifier($name)[, $attributes]) */
	static zv::Val thisPropertyFetch(zval *name, zval *attributes)
	{
		zval thisName;
		ZVAL_INTERNED_STR(&thisName, ZSTR_KNOWN(ZEND_STR_THIS));
		zv::Val variable = pt_type_new(PT_CLASS_VARIABLE, 1, &thisName);
		if (UNEXPECTED(variable.isUndef())) return zv::Val();
		zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER, 1, name);
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zval args[3];
		ZVAL_COPY_VALUE(&args[0], variable.raw());
		ZVAL_COPY_VALUE(&args[1], identifier.raw());
		if (attributes != NULL) {
			ZVAL_COPY_VALUE(&args[2], attributes);
		}
		return pt_type_new(PT_CLASS_PROPERTY_FETCH, attributes != NULL ? 3 : 2, args);
	}

	/* $object->method() whose result must be an object; UNDEF = pending
	 * exception (the engine's Error for a member call on a non-object) */
	static zv::Val callObject(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(object, lcname, len, argc, argv);
		if (UNEXPECTED(!result.isUndef() && Z_TYPE_P(result.raw()) != IS_OBJECT && Z_TYPE_P(result.raw()) != IS_NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s() must return an object, %s returned", lcname, zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}

	/* $classReflection->getName() of a (non-null) class reflection value */
	static zv::Val classReflectionName(zval *classReflection)
	{
		if (UNEXPECTED(Z_TYPE_P(classReflection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection));
			return zv::Val();
		}
		return pt_class_reflection_get_name(Z_OBJ_P(classReflection));
	}

	/* $this->classReflection->getName() */
	zv::Val ownClassName()
	{
		zval *own = OBJ_PROP_NUM(self, slots::classReflection);
		if (UNEXPECTED(Z_TYPE_P(own) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Node\\ClassStatementsGatherer::$classReflection must not be accessed before initialization");
			return zv::Val();
		}
		return pt_class_reflection_get_name(Z_OBJ_P(own));
	}

	/* PHPStan\Type\TypeUtils::findThisType($type), resolved by name: the
	 * native class in a production run, the PHP twin under the prefixed
	 * differential activation */
	static zv::Val findThisType(zval *type)
	{
		static const char name[] = "PHPStan\\Type\\TypeUtils";
		zv::Str className = zv::Str::adopt(zend_string_init(name, sizeof(name) - 1, 0));
		zend_class_entry *typeUtils = zend_lookup_class(className.get());
		if (UNEXPECTED(typeUtils == NULL)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "Class \"%s\" not found", name);
			}
			return zv::Val();
		}
		return pt_type_call_static_ce(typeUtils, PT_LC("findthistype"), 1, type);
	}

	bool gatherNodes(zend_object *node, zval *scope)
	{
		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		if (UNEXPECTED(!inClass)) return throwShouldNotHappen();
		zv::Val scopeClass = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(scopeClass.isUndef())) return false;
		zv::Val scopeClassName = classReflectionName(scopeClass.raw());
		if (UNEXPECTED(scopeClassName.isUndef())) return false;
		zv::Val ownName = ownClassName();
		if (UNEXPECTED(ownName.isUndef())) return false;
		if (!zend_is_identical(scopeClassName.raw(), ownName.raw())) return true;

		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);

		if (isInstanceOf(node, PT_CLASS_CLASS_PROPERTY_NODE)) {
			push(slots::properties, &nodeZv);
			zval *isPromoted = pt_csg_class_property_node_is_promoted.of(node);
			if (isPromoted != NULL && Z_TYPE_P(isPromoted) == IS_TRUE) {
				zval *name = pt_csg_class_property_node_name.of(node);
				if (UNEXPECTED(name == NULL || Z_TYPE_P(name) != IS_STRING)) {
					zend_throw_error(NULL, "phpstan_turbo: ClassPropertyNode::$name is not a string");
					return false;
				}
				zv::Val fetch = thisPropertyFetch(name, NULL);
				if (UNEXPECTED(fetch.isUndef())) return false;
				return pushPropertyWrite(fetch.raw(), scope, true, &nodeZv);
			}
			return true;
		}
		if (isInstanceOf(node, PT_CLASS_CLASS_METHOD_STMT)) {
			bool inTrait;
			if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), inTrait))) return false;
			zv::Args args{&nodeZv, inTrait};
			return pushNew(slots::methods, PT_CLASS_GATHERED_CLASS_METHOD, 2, args);
		}
		if (isInstanceOf(node, PT_CLASS_CLASS_CONST_STMT)) {
			push(slots::constants, &nodeZv);
			return true;
		}
		bool isStaticCall = isInstanceOf(node, PT_CLASS_STATIC_CALL);
		if (isStaticCall || isInstanceOf(node, PT_CLASS_METHOD_CALL)) {
			if (UNEXPECTED(!pushNodeScope(slots::methodCalls, PT_CLASS_GATHERED_METHOD_CALL, &nodeZv, scope))) return false;
			if (isStaticCall) {
				zend_object *name = pt_csg_static_call_name.objectOf(node, PT_CLASS_IDENTIFIER);
				zend_string *nameString = name != NULL ? visitors::nameString(name, pt_csg_identifier_name) : NULL;
				if (nameString != NULL && visitors::lowerEquals(nameString, "__construct")) return tryToApplyPropertyWritesFromAncestorConstructor(node, scope);
			}
			return true;
		}
		zval *callableOriginal = NULL;
		if (isInstanceOf(node, PT_CLASS_METHOD_CALLABLE_NODE)) {
			callableOriginal = pt_csg_method_callable_original.of(node);
		} else if (isInstanceOf(node, PT_CLASS_STATIC_METHOD_CALLABLE_NODE)) {
			callableOriginal = pt_csg_static_method_callable_original.of(node);
		}
		if (callableOriginal != NULL) return pushNodeScope(slots::methodCalls, PT_CLASS_GATHERED_METHOD_CALL, callableOriginal, scope);
		if (isInstanceOf(node, PT_CLASS_METHOD_RETURN_STATEMENTS_NODE)) return addReturnStatementsNode(node, &nodeZv);
		if (isInstanceOf(node, PT_CLASS_FUNC_CALL)) {
			zend_object *callee = pt_csg_func_call_name.objectOf(node, PT_CLASS_NAME);
			zend_string *calleeName = callee != NULL ? visitors::nameString(callee, pt_csg_name_name) : NULL;
			if (calleeName != NULL && (visitors::lowerEquals(calleeName, "get_object_vars") || visitors::lowerEquals(calleeName, "array_walk"))) {
				return tryToApplyPropertyReads(node, scope);
			}
		}
		if (isInstanceOf(node, PT_CLASS_ARRAY_EXPR)) {
			zval *items = pt_csg_array_items.of(node);
			if (items != NULL && Z_TYPE_P(items) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(items)) == 2) {
				return pushNodeScope(slots::methodCalls, PT_CLASS_GATHERED_METHOD_CALL, &nodeZv, scope);
			}
		}
		if (isInstanceOf(node, PT_CLASS_CLASS_CONST_FETCH)) return pushNodeScope(slots::constantFetches, PT_CLASS_CLASS_CONSTANT_FETCH, &nodeZv, scope);
		if (isInstanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE)) return addPropertyAssign(node, &nodeZv, scope);
		if (!isInstanceOf(node, PT_CLASS_EXPR)) return !EG(exception);
		if (isInstanceOf(node, PT_CLASS_COALESCE_ASSIGN_OP_EXPR)) {
			zval *var = pt_csg_coalesce_var.of(node);
			if (UNEXPECTED(var == NULL || Z_TYPE_P(var) != IS_OBJECT)) return !EG(exception);
			return gatherNodes(Z_OBJ_P(var), scope);
		}
		if (isInstanceOf(node, PT_CLASS_ASSIGN_REF_EXPR)) {
			zval *expr = pt_csg_assign_ref_expr.of(node);
			if (UNEXPECTED(expr == NULL || Z_TYPE_P(expr) != IS_OBJECT)) return !EG(exception);
			if (!isPropertyFetchLike(Z_OBJ_P(expr))) return gatherNodes(Z_OBJ_P(expr), scope);
			if (UNEXPECTED(!pushNodeScope(slots::propertyUsages, PT_CLASS_PROPERTY_READ, expr, scope))) return false;
			return pushPropertyWrite(expr, scope, false, &nodeZv);
		}
		if (isInstanceOf(node, PT_CLASS_VARIABLE)) return tryToApplyPromotedParameterRead(node, scope);
		zval *original = NULL;
		if (isInstanceOf(node, PT_CLASS_FUNCTION_CALLABLE_NODE)) {
			original = pt_csg_function_callable_original.of(node);
		} else if (isInstanceOf(node, PT_CLASS_INSTANTIATION_CALLABLE_NODE)) {
			original = pt_csg_instantiation_callable_original.of(node);
		}
		if (original != NULL && Z_TYPE_P(original) == IS_OBJECT) {
			node = Z_OBJ_P(original);
		}

		bool inAssign;
		if (UNEXPECTED(!pt_mutating_scope_is_in_expression_assign(Z_OBJ_P(scope), node, inAssign))) return false;
		if (inAssign) return true;

		while (isInstanceOf(node, PT_CLASS_ARRAY_DIM_FETCH)) {
			zval *var = pt_csg_array_dim_fetch_var.of(node);
			if (UNEXPECTED(var == NULL || Z_TYPE_P(var) != IS_OBJECT)) return !EG(exception);
			node = Z_OBJ_P(var);
		}
		if (!isPropertyFetchLike(node)) return !EG(exception);
		zval fetch;
		ZVAL_OBJ(&fetch, node);
		return pushNodeScope(slots::propertyUsages, PT_CLASS_PROPERTY_READ, &fetch, scope);
	}

	/* $this->returnStatementNodes[strtolower($node->getMethodName())] = $node */
	bool addReturnStatementsNode(zend_object *node, zval *nodeZv)
	{
		zend_object *classMethod = pt_csg_return_statements_class_method.objectOf(node, PT_CLASS_CLASS_METHOD_STMT);
		zend_object *name = classMethod != NULL ? pt_csg_class_method_stmt_name.objectOf(classMethod, PT_CLASS_IDENTIFIER) : NULL;
		zend_string *methodName = name != NULL ? visitors::nameString(name, pt_csg_identifier_name) : NULL;
		if (UNEXPECTED(methodName == NULL)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "phpstan_turbo: MethodReturnStatementsNode without a method name");
			}
			return false;
		}
		zval *nodes = OBJ_PROP_NUM(self, slots::returnStatementNodes);
		if (UNEXPECTED(Z_TYPE_P(nodes) != IS_ARRAY)) return true;
		SEPARATE_ARRAY(nodes);
		zend_string *key = zend_string_tolower(methodName);
		Z_ADDREF_P(nodeZv);
		zend_symtable_update(Z_ARRVAL_P(nodes), key, nodeZv);
		zend_string_release(key);
		return true;
	}

	bool addPropertyAssign(zend_object *node, zval *nodeZv, zval *scope)
	{
		zval *propertyFetch = pt_csg_property_assign_fetch.of(node);
		zval *assignedExpr = pt_csg_property_assign_expr.of(node);
		if (UNEXPECTED(propertyFetch == NULL || Z_TYPE_P(propertyFetch) != IS_OBJECT || assignedExpr == NULL || Z_TYPE_P(assignedExpr) != IS_OBJECT)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "phpstan_turbo: PropertyAssignNode without its property fetch or assigned expression");
			}
			return false;
		}
		if (isInstanceOf(Z_OBJ_P(assignedExpr), PT_CLASS_SET_OFFSET_VALUE_TYPE_EXPR) || isInstanceOf(Z_OBJ_P(assignedExpr), PT_CLASS_SET_EXISTING_OFFSET_VALUE_TYPE_EXPR)) {
			zv::Val propertyType = callObject(Z_OBJ_P(scope), PT_LC("gettype"), 1, propertyFetch);
			if (UNEXPECTED(propertyType.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(propertyType.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isObject() on null");
				return false;
			}
			zend_long isObject = pt_type_call_trinary(Z_OBJ_P(propertyType.raw()), PT_LC("isobject"), 0, NULL);
			if (UNEXPECTED(isObject < 0)) return false;
			if (isObject != PT_TRI_NO && UNEXPECTED(!pushNodeScope(slots::propertyUsages, PT_CLASS_PROPERTY_READ, propertyFetch, scope))) return false;
		}
		if (UNEXPECTED(!pushPropertyWrite(propertyFetch, scope, false, nodeZv))) return false;
		return pushNodeScope(slots::propertyAssigns, PT_CLASS_PROPERTY_ASSIGN, nodeZv, scope);
	}

	bool tryToApplyPropertyReads(zend_object *node, zval *scope)
	{
		zv::Val argsHold;
		zval *args = pt_call_like_args(node, argsHold);
		if (UNEXPECTED(args == NULL)) return false;
		if (Z_TYPE_P(args) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(args)) == 0) return true;
		zend_object *firstArg = visitors::argAt(args, 0);
		zval *firstArgValue = firstArg != NULL ? pt_csg_arg_value.of(firstArg) : NULL;
		if (UNEXPECTED(firstArgValue == NULL)) {
			if (!EG(exception)) {
				zend_throw_error(NULL, "phpstan_turbo: FuncCall::getArgs()[0] is not an Arg");
			}
			return false;
		}
		zv::Val type = callObject(Z_OBJ_P(scope), PT_LC("gettype"), 1, firstArgValue);
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Val thisType = findThisType(type.raw());
		if (UNEXPECTED(thisType.isUndef())) return false;
		if (Z_TYPE_P(thisType.raw()) == IS_NULL) return true;

		zval *own = OBJ_PROP_NUM(self, slots::classReflection);
		if (UNEXPECTED(Z_TYPE_P(own) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Node\\ClassStatementsGatherer::$classReflection must not be accessed before initialization");
			return false;
		}
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(own));
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getProperties() on %s", zend_zval_value_name(nativeReflection.raw()));
			return false;
		}
		zv::Val properties = pt_type_call(Z_OBJ_P(nativeReflection.raw()), PT_LC("getproperties"), 0, NULL);
		if (UNEXPECTED(properties.isUndef())) return false;
		if (Z_TYPE_P(properties.raw()) != IS_ARRAY) return true;
		for (zv::ArrayEntry entry : zv::ArrRef(properties.raw())) {
			zval *property = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(property) != IS_OBJECT)) continue;
			zv::Val isStatic = pt_type_call(Z_OBJ_P(property), PT_LC("isstatic"), 0, NULL);
			if (UNEXPECTED(isStatic.isUndef())) return false;
			if (zend_is_true(isStatic.raw())) continue;
			zv::Val name = pt_type_call(Z_OBJ_P(property), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return false;
			if (Z_TYPE_P(name.raw()) == IS_STRING && Z_STRLEN_P(name.raw()) == 0) return throwShouldNotHappen();
			zv::Val fetch = thisPropertyFetch(name.raw(), NULL);
			if (UNEXPECTED(fetch.isUndef())) return false;
			if (UNEXPECTED(!pushNodeScope(slots::propertyUsages, PT_CLASS_PROPERTY_READ, fetch.raw(), scope))) return false;
		}
		return true;
	}

	bool tryToApplyPromotedParameterRead(zend_object *node, zval *scope)
	{
		zval *variableName = pt_csg_variable_name.of(node);
		if (variableName == NULL || Z_TYPE_P(variableName) != IS_STRING || Z_STRLEN_P(variableName) == 0) return !EG(exception);
		bool inAssign;
		if (UNEXPECTED(!pt_mutating_scope_is_in_expression_assign(Z_OBJ_P(scope), node, inAssign))) return false;
		if (inAssign) return true;
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return false;
		if (inAnonymousFunction) return true;
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return false;
		zend_class_entry *methodReflection = pt_class(PT_CLASS_METHOD_REFLECTION);
		if (Z_TYPE_P(function.raw()) != IS_OBJECT || methodReflection == NULL || !instanceof_function(Z_OBJCE_P(function.raw()), methodReflection)) {
			return !EG(exception);
		}
		zv::Val functionName = pt_type_call(Z_OBJ_P(function.raw()), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(functionName.isUndef())) return false;
		if (Z_TYPE_P(functionName.raw()) != IS_STRING || !visitors::lowerEquals(Z_STR_P(functionName.raw()), "__construct")) return true;
		zv::Val declaringClass = pt_type_call(Z_OBJ_P(function.raw()), PT_LC("getdeclaringclass"), 0, NULL);
		if (UNEXPECTED(declaringClass.isUndef())) return false;
		zv::Val declaringClassName = classReflectionName(declaringClass.raw());
		if (UNEXPECTED(declaringClassName.isUndef())) return false;
		zv::Val ownName = ownClassName();
		if (UNEXPECTED(ownName.isUndef())) return false;
		if (!zend_is_identical(declaringClassName.raw(), ownName.raw())) return true;

		zend_object *constructorNode = NULL;
		for (zv::ArrayEntry entry : zv::ArrRef(OBJ_PROP_NUM(self, slots::methods))) {
			zval *method = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(method) != IS_OBJECT)) continue;
			zend_object *methodNode = pt_csg_gathered_class_method_node.objectOf(Z_OBJ_P(method), PT_CLASS_CLASS_METHOD_STMT);
			zend_object *name = methodNode != NULL ? pt_csg_class_method_stmt_name.objectOf(methodNode, PT_CLASS_IDENTIFIER) : NULL;
			zend_string *nameString = name != NULL ? visitors::nameString(name, pt_csg_identifier_name) : NULL;
			if (nameString == NULL || !visitors::lowerEquals(nameString, "__construct")) continue;
			constructorNode = methodNode;
			break;
		}
		if (constructorNode == NULL) return !EG(exception);

		zval *params = pt_csg_class_method_stmt_params.of(constructorNode);
		if (params == NULL || Z_TYPE_P(params) != IS_ARRAY) return !EG(exception);
		for (zv::ArrayEntry entry : zv::ArrRef(params)) {
			zval *param = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT)) continue;
			zval *flags = pt_csg_param_flags.of(Z_OBJ_P(param));
			zval *hooks = pt_csg_param_hooks.of(Z_OBJ_P(param));
			bool noFlags = flags != NULL && Z_TYPE_P(flags) == IS_LONG && Z_LVAL_P(flags) == 0;
			bool noHooks = hooks != NULL && Z_TYPE_P(hooks) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(hooks)) == 0;
			if (noFlags && noHooks) continue;
			zend_object *var = pt_csg_param_var.objectOf(Z_OBJ_P(param), PT_CLASS_VARIABLE);
			zval *paramName = var != NULL ? pt_csg_variable_name.of(var) : NULL;
			if (paramName == NULL || Z_TYPE_P(paramName) != IS_STRING) continue;
			if (!zend_string_equals(Z_STR_P(paramName), Z_STR_P(variableName))) continue;
			zv::Val attributes = pt_type_call(node, PT_LC("getattributes"), 0, NULL);
			if (UNEXPECTED(attributes.isUndef())) return false;
			zv::Val fetch = thisPropertyFetch(variableName, attributes.raw());
			if (UNEXPECTED(fetch.isUndef())) return false;
			return pushNodeScope(slots::propertyUsages, PT_CLASS_PROPERTY_READ, fetch.raw(), scope);
		}
		return !EG(exception);
	}

	bool tryToApplyPropertyWritesFromAncestorConstructor(zend_object *ancestorConstructorCall, zval *scope)
	{
		zval *classNode = pt_csg_static_call_class.of(ancestorConstructorCall);
		if (classNode == NULL || Z_TYPE_P(classNode) != IS_OBJECT || !isInstanceOf(Z_OBJ_P(classNode), PT_CLASS_NAME)) return !EG(exception);
		zv::Val calledOnType = callObject(Z_OBJ_P(scope), PT_LC("resolvetypebyname"), 1, classNode);
		if (UNEXPECTED(calledOnType.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(calledOnType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getClassReflection() on null");
			return false;
		}
		zv::Val classReflection = pt_type_call(Z_OBJ_P(calledOnType.raw()), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(classReflection.isUndef())) return false;
		if (Z_TYPE_P(classReflection.raw()) == IS_NULL) return true;
		zv::Val thisType = findThisType(calledOnType.raw());
		if (UNEXPECTED(thisType.isUndef())) return false;
		if (Z_TYPE_P(thisType.raw()) == IS_NULL) return true;

		classReflection = pt_type_call(Z_OBJ_P(calledOnType.raw()), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(classReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getNativeReflection() on %s", zend_zval_value_name(classReflection.raw()));
			return false;
		}
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(classReflection.raw()));
		if (UNEXPECTED(nativeReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(nativeReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getProperties() on %s", zend_zval_value_name(nativeReflection.raw()));
			return false;
		}
		zval filter;
		/* ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED */
		ZVAL_LONG(&filter, ZEND_ACC_PUBLIC | ZEND_ACC_PROTECTED);
		zv::Val properties = pt_type_call(Z_OBJ_P(nativeReflection.raw()), PT_LC("getproperties"), 1, &filter);
		if (UNEXPECTED(properties.isUndef())) return false;
		if (Z_TYPE_P(properties.raw()) != IS_ARRAY) return true;
		for (zv::ArrayEntry entry : zv::ArrRef(properties.raw())) {
			zval *property = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(property) != IS_OBJECT)) continue;
			zv::Val isPromoted = pt_type_call(Z_OBJ_P(property), PT_LC("ispromoted"), 0, NULL);
			if (UNEXPECTED(isPromoted.isUndef())) return false;
			if (!zend_is_true(isPromoted.raw())) continue;
			zv::Val declaringClass = callObject(Z_OBJ_P(property), PT_LC("getdeclaringclass"), 0, NULL);
			if (UNEXPECTED(declaringClass.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(declaringClass.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getName() on null");
				return false;
			}
			zv::Val declaringName = pt_type_call(Z_OBJ_P(declaringClass.raw()), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(declaringName.isUndef())) return false;
			zv::Val reflectionName = pt_type_call(Z_OBJ_P(nativeReflection.raw()), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(reflectionName.isUndef())) return false;
			if (!zend_is_identical(declaringName.raw(), reflectionName.raw())) continue;
			zv::Val name = pt_type_call(Z_OBJ_P(property), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return false;
			if (Z_TYPE_P(name.raw()) == IS_STRING && Z_STRLEN_P(name.raw()) == 0) return throwShouldNotHappen();
			zv::Val attributes = pt_type_call(ancestorConstructorCall, PT_LC("getattributes"), 0, NULL);
			if (UNEXPECTED(attributes.isUndef())) return false;
			zv::Val fetch = thisPropertyFetch(name.raw(), attributes.raw());
			if (UNEXPECTED(fetch.isUndef())) return false;
			if (UNEXPECTED(!pushPropertyWrite(fetch.raw(), scope, false, NULL))) return false;
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassStatementsGatherer;

/* {{{ direct entries for ClassLikeHandler.cpp */

zv::Val pt_class_statements_gatherer_new(zval *classReflection, zval *nodeCallback)
{
	zval object;
	if (UNEXPECTED(object_init_ex(&object, pt_ce_class_statements_gatherer) != SUCCESS)) return zv::Val();
	zv::Val gatherer = zv::Val::adopt(object);
	if (UNEXPECTED(!zend_is_callable(nodeCallback, 0, NULL))) {
		zend_type_error("PHPStan\\Node\\ClassStatementsGatherer::__construct(): Argument #2 ($nodeCallback) must be of type callable, %s given", zend_zval_value_name(nodeCallback));
		return zv::Val();
	}
	ClassStatementsGatherer(Z_OBJ_P(gatherer.raw())).construct(classReflection, nodeCallback);
	return gatherer;
}

zv::Val pt_class_statements_gatherer_get(zval *gatherer, pt_class_statements_gatherer_list list)
{
	static const uint32_t listSlots[] = { slots::properties, slots::methods, slots::methodCalls, slots::propertyUsages, slots::constants, slots::constantFetches, slots::returnStatementNodes, slots::propertyAssigns };
	return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(gatherer), listSlots[list])));
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_class_statements_gatherer()
{
	reg::Class cls("PHPStan\\Node\\ClassStatementsGatherer");
	ptdecl::ClassStatementsGatherer::declareClass(cls);
	/* the slots PT_CSG_PROP_*: the nine class-body properties first, the
	 * promoted constructor property after them */
	cls.privateNullProperty("nodeCallback");
	cls.privateTypedArrayPropertyDefaultEmpty("properties");
	cls.privateTypedArrayPropertyDefaultEmpty("methods");
	cls.privateTypedArrayPropertyDefaultEmpty("methodCalls");
	cls.privateTypedArrayPropertyDefaultEmpty("propertyUsages");
	cls.privateTypedArrayPropertyDefaultEmpty("constants");
	cls.privateTypedArrayPropertyDefaultEmpty("constantFetches");
	cls.privateTypedArrayPropertyDefaultEmpty("returnStatementNodes");
	cls.privateTypedArrayPropertyDefaultEmpty("propertyAssigns");
	cls.privateTypedClassProperty("classReflection", "PHPStan\\Reflection\\ClassReflection", false);
	cls.privateClassConstantValue("PROPERTY_ENUMERATING_FUNCTIONS", pt_csg_property_enumerating_functions_constant);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *nodeCallback;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, classReflection, nodeCallback)) RETURN_THROWS();
		if (UNEXPECTED(!zend_is_callable(nodeCallback, 0, NULL))) {
			zend_argument_type_error(2, "must be of type callable, %s given", zend_zval_value_name(nodeCallback));
			RETURN_THROWS();
		}
		ClassStatementsGatherer(Z_OBJ_P(ZEND_THIS)).construct(classReflection, nodeCallback);
	});

	cls.method(sigs::getProperties, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::properties));
	});
	cls.method(sigs::getMethods, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::methods));
	});
	cls.method(sigs::getMethodCalls, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::methodCalls));
	});
	cls.method(sigs::getPropertyUsages, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::propertyUsages));
	});
	cls.method(sigs::getConstants, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::constants));
	});
	cls.method(sigs::getConstantFetches, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::constantFetches));
	});
	cls.method(sigs::getReturnStatementsNodes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::returnStatementNodes));
	});
	cls.method(sigs::getPropertyAssigns, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(OBJ_PROP_NUM(Z_OBJ_P(ZEND_THIS), slots::propertyAssigns));
	});

	cls.method("__invoke", reg::Public, 2, { reg::obj("node", "PhpParser\\Node"), reg::obj("scope", "PHPStan\\Analyser\\Scope") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, node, scope)) RETURN_THROWS();
		if (UNEXPECTED(!ClassStatementsGatherer(Z_OBJ_P(ZEND_THIS)).invoke(node, scope))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_class_statements_gatherer);
}

/* }}} */

/* {{{ direct entry for the native walk (Engine.h) */

bool pt_class_statements_gatherer_invoke(zend_object *gatherer, zval *node, zval *scope, bool &handled)
{
	handled = gatherer->ce == pt_ce_class_statements_gatherer;
	if (!handled) return false;
	/* the engine keeps a called object alive for the duration of the call */
	GC_ADDREF(gatherer);
	bool ok = ClassStatementsGatherer(gatherer).invoke(node, scope);
	OBJ_RELEASE(gatherer);
	return ok;
}

/* }}} */
