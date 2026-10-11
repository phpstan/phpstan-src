/*
 * PHPStanTurbo\ArrayHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ArrayHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin keeps only each non-scalar item's
 * [type, nativeType] pair ($itemTypes) — the scalar keys/values re-derive
 * their constant type from the node — and evicts each item's results from
 * the storage once its callback ran (unless the literal has a by-reference
 * item), so a large literal array does not pin every ExpressionResult. The
 * twin's closures are native closures capturing what the PHP closures
 * capture: the typeCallback ($this, $expr, $itemTypes, $beforeScope); the
 * item-type callback it hands to InitializerExprTypeResolver::getArrayType()
 * ($itemTypes, $nativeTypesPromoted) is a pt_ietr_get_type over the
 * typeCallback's frame (the resolver calls it synchronously); the
 * specifyTypesCallback is SpecifiedTypes::emptySpecifyCallback(), as in the
 * twin.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), VariableWriteOffset, TypeCombinator and the Type
 * kernel are called through their direct entries; the VariableWrite value
 * target through its slots (VariableFlow.cpp's pt_variable_write_slots_of()),
 * InitializerExprTypeResolver::getArrayType() through its direct entry; the
 * LiteralArrayItem / LiteralArrayNode / VariableWrite virtual
 * nodes and the synthetic is_callable() call are instantiated through the
 * class map.
 *
 * The twin's $nextIndex is int|null that `$nextIndex++` / `max($nextIndex,
 * $offset + 1)` can overflow into a float: it is kept as a zval with the
 * engine's own increment and comparison, a long fast path first.
 */

#include "support.h"
#include "generated/ArrayHandler.h"

namespace slots = ptdecl::ArrayHandler::slot;
namespace sigs = ptdecl::ArrayHandler::sig;
#include "CallHandlerSupport.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_array_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_arh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ArrayHandler::{closure}";

/* VariableWrite::KIND_ARRAY_LITERAL_ITEM */
constexpr zend_long PT_ARH_KIND_ARRAY_LITERAL_ITEM = 14;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

/* $initializerExprTypeResolver->getArrayType($expr, $getTypeCallback) */
zv::Val getArrayType(zval *initializerExprTypeResolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	return pt_initializer_expr_type_resolver_get_array_type(initializerExprTypeResolver, expr, getTypeCallback);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_arh_items_site;
pt_property_site pt_arh_item_key_site;
pt_property_site pt_arh_item_value_site;
pt_property_site pt_arh_item_unpack_site;
pt_property_site pt_arh_item_by_ref_site;

zval *exprItems(zval *expr) { return nodeProperty(pt_arh_items_site, expr, PT_LC("items")); }
zval *itemKey(zval *item) { return nodeProperty(pt_arh_item_key_site, item, PT_LC("key")); }
zval *itemValue(zval *item) { return nodeProperty(pt_arh_item_value_site, item, PT_LC("value")); }
zval *itemUnpack(zval *item) { return nodeProperty(pt_arh_item_unpack_site, item, PT_LC("unpack")); }
zval *itemByRef(zval *item) { return nodeProperty(pt_arh_item_by_ref_site, item, PT_LC("byRef")); }

/* }}} */

/* {{{ the nodes the twin resolves without a stored result: the plain
 * scalars' value properties and TypeExpr::getExprType() */

using phpstanturbo::visitors::NodeProp;

NodeProp pt_arh_scalar_string_value = PT_NODE_PROP(PT_CLASS_SCALAR_STRING, "value");
NodeProp pt_arh_scalar_int_value = PT_NODE_PROP(PT_CLASS_SCALAR_INT, "value");
NodeProp pt_arh_scalar_float_value = PT_NODE_PROP(PT_CLASS_SCALAR_FLOAT, "value");
NodeProp pt_arh_type_expr_type = PT_NODE_PROP(PT_CLASS_TYPE_EXPR, "exprType");

/* $node-><prop> (dereferenced); NULL = pending exception */
zval *nodePropValue(NodeProp &prop, zval *node)
{
	zval *value = prop.of(Z_OBJ_P(node));
	if (EXPECTED(value != NULL && Z_TYPE_P(value) != IS_UNDEF)) return value;
	if (EG(exception) != NULL) return NULL;
	if (value == NULL) {
		zend_throw_error(NULL, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), prop.name);
		return NULL;
	}
	zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), prop.name);
	return NULL;
}

/* $inner instanceof Scalar\String_ ? new ConstantStringType($inner->value)
 * : (Int_ / Float_ alike): 1 = produced, 0 = not a plain scalar node,
 * -1 = pending exception */
int scalarConstantType(zval *node, zv::Val &out)
{
	if (Z_TYPE_P(node) != IS_OBJECT) return 0;
	zend_class_entry *ce = Z_OBJCE_P(node);
	zend_class_entry *stringCe = pt_class(PT_CLASS_SCALAR_STRING);
	zend_class_entry *intCe = pt_class(PT_CLASS_SCALAR_INT);
	zend_class_entry *floatCe = pt_class(PT_CLASS_SCALAR_FLOAT);
	if (UNEXPECTED(stringCe == NULL || intCe == NULL || floatCe == NULL)) return -1;
	if (instanceof_function(ce, stringCe)) {
		zval *value = nodePropValue(pt_arh_scalar_string_value, node);
		if (UNEXPECTED(value == NULL)) return -1;
		if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) {
			zval z;
			if (UNEXPECTED(!pt_constant_string_type_new(&z, Z_STR_P(value), false))) return -1;
			out = zv::Val::adopt(z);
			return 1;
		}
		out = pt_type_new_ce(pt_ce_constant_string_type, 1, value);
		return out.isUndef() ? -1 : 1;
	}
	if (instanceof_function(ce, intCe)) {
		zval *value = nodePropValue(pt_arh_scalar_int_value, node);
		if (UNEXPECTED(value == NULL)) return -1;
		if (EXPECTED(Z_TYPE_P(value) == IS_LONG)) {
			zval z;
			if (UNEXPECTED(!pt_constant_integer_type_new(&z, Z_LVAL_P(value)))) return -1;
			out = zv::Val::adopt(z);
			return 1;
		}
		out = pt_type_new_ce(pt_ce_constant_integer_type, 1, value);
		return out.isUndef() ? -1 : 1;
	}
	if (instanceof_function(ce, floatCe)) {
		zval *value = nodePropValue(pt_arh_scalar_float_value, node);
		if (UNEXPECTED(value == NULL)) return -1;
		if (EXPECTED(Z_TYPE_P(value) == IS_DOUBLE)) {
			zval z;
			if (UNEXPECTED(!pt_constant_float_type_new(&z, Z_DVAL_P(value)))) return -1;
			out = zv::Val::adopt(z);
			return 1;
		}
		out = pt_type_new_ce(pt_ce_constant_float_type, 1, value);
		return out.isUndef() ? -1 : 1;
	}
	return 0;
}

/* whether $node is a Scalar\String_ / Scalar\Int_ / Scalar\Float_ (the twin's
 * store skip); false = pending exception */
[[nodiscard]] bool isPlainScalar(zval *node, bool &out)
{
	if (Z_TYPE_P(node) != IS_OBJECT) {
		out = false;
		return true;
	}
	zend_class_entry *ce = Z_OBJCE_P(node);
	zend_class_entry *stringCe = pt_class(PT_CLASS_SCALAR_STRING);
	zend_class_entry *intCe = pt_class(PT_CLASS_SCALAR_INT);
	zend_class_entry *floatCe = pt_class(PT_CLASS_SCALAR_FLOAT);
	if (UNEXPECTED(stringCe == NULL || intCe == NULL || floatCe == NULL)) return false;
	out = instanceof_function(ce, stringCe) || instanceof_function(ce, intCe) || instanceof_function(ce, floatCe);
	return true;
}

/* $inner->getExprType() of a TypeExpr; UNDEF = pending exception */
zv::Val typeExprType(zval *node)
{
	zval *type = nodePropValue(pt_arh_type_expr_type, node);
	if (UNEXPECTED(type == NULL)) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Node\\Expr\\TypeExpr::getExprType(): Return value must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(type));
		return zv::Val();
	}
	return zv::Val::copyOf(zv::Ref(type));
}

/* }}} */

/* {{{ hasArrayReference()'s non-throwing reads (an Array_ node's items are
 * always initialized ArrayItem nodes - a missing one simply answers "no
 * reference here", like AssignHandler's port of the same twin static) */

pt_property_site pt_arh_ref_items_site;
pt_property_site pt_arh_ref_value_site;
pt_property_site pt_arh_ref_by_ref_site;

/* $object->$name through the site; NULL when absent or uninitialized */
zval *rawNodeProp(pt_property_site &site, zval *object, const char *name, size_t len)
{
	if (Z_TYPE_P(object) != IS_OBJECT) return NULL;
	zval *slot = pt_property_cached(site, Z_OBJ_P(object), name, len);
	if (slot == NULL) return NULL;
	ZVAL_DEREF(slot);
	if (Z_TYPE_P(slot) == IS_UNDEF) return NULL;
	return slot;
}

/* }}} */

/* {{{ the VariableWrite value-flow target's getters */

/* $write->isOffsetWrite() / ->getVariableName() / ->getId(): the slot of
 * the final PHP class, the getter otherwise; UNDEF = pending exception */
zv::Val variableWriteGetter(zval *write, uint32_t pt_variable_write_slots::*member, const char *lcname, size_t len)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (EXPECTED(writeSlots != NULL)) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->*member));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), lcname, len, 0, NULL);
}

/* }}} */

/* {{{ small value helpers */

/* the permanent interned 'is_callable' (module startup) */
zend_string *pt_arh_is_callable = nullptr;

/* a PHP `$x !== null` read of a $nextIndex zval */
inline bool isSet(const zval *value)
{
	return Z_TYPE_P(value) != IS_NULL;
}

/* $nextIndex = max($nextIndex, $offset + 1) with $offset an int */
inline void advanceNextIndex(zval *nextIndex, zend_long offset)
{
	zval candidate;
	if (EXPECTED(offset != ZEND_LONG_MAX)) {
		ZVAL_LONG(&candidate, offset + 1);
	} else {
		ZVAL_DOUBLE(&candidate, (double) ZEND_LONG_MAX + 1.0);
	}
	if (EXPECTED(Z_TYPE_P(nextIndex) == IS_LONG && Z_TYPE(candidate) == IS_LONG)) {
		if (Z_LVAL(candidate) > Z_LVAL_P(nextIndex)) ZVAL_LONG(nextIndex, Z_LVAL(candidate));
		return;
	}
	// max() keeps the first argument unless a later one compares greater
	if (zend_compare(&candidate, nextIndex) > 0) ZVAL_COPY_VALUE(nextIndex, &candidate);
}

/* new ConstantIntegerType($nextIndex) (a float $nextIndex goes through the
 * constructor, which rejects it like the twin's call does) */
zv::Val newConstantIntegerTypeOf(zval *nextIndex)
{
	if (EXPECTED(Z_TYPE_P(nextIndex) == IS_LONG)) {
		zval out;
		if (UNEXPECTED(!pt_constant_integer_type_new(&out, Z_LVAL_P(nextIndex)))) return zv::Val();
		return zv::Val::adopt(out);
	}
	return pt_type_new_ce(pt_ce_constant_integer_type, 1, nextIndex);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ArrayHandler; UNDEF = pending
 * exception. */
class ArrayHandler
{
public:
	explicit ArrayHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_ARRAY_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *itemsSlot = exprItems(expr);
		if (UNEXPECTED(itemsSlot == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(itemsSlot) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(itemsSlot));
		}
		// foreach iterates the array it was handed
		zv::Val items = zv::Val::copyOf(zv::Ref(itemsSlot));
		uint32_t itemCount = Z_TYPE_P(items.raw()) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(items.raw())) : 0;

		zv::Arr itemNodes = zv::Arr::create(itemCount);
		zv::Arr itemTypes = zv::Arr::create(itemCount);
		zv::Arr variableFlows = zv::Arr::create(itemCount);
		zv::Arr dependencies = zv::Arr::empty();
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;

		zv::Val literalWrite = zv::Val::null();
		{
			bool valueFlowDirect;
			if (UNEXPECTED(!pt_expression_context_is_value_flow_direct(context, valueFlowDirect))) return zv::Val();
			if (valueFlowDirect) {
				literalWrite = pt_expression_context_get_value_flow_target(context);
				if (UNEXPECTED(literalWrite.isUndef())) return zv::Val();
			}
		}
		if (!literalWrite.isNull()) {
			if (UNEXPECTED(!literalWrite.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isOffsetWrite() on %s", zend_zval_value_name(literalWrite.raw()));
				return zv::Val();
			}
			zv::Val offsetWrite = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::offsetWrite, PT_LC("isoffsetwrite"));
			if (UNEXPECTED(offsetWrite.isUndef())) return zv::Val();
			if (zend_is_true(offsetWrite.raw())) literalWrite = zv::Val::null();
		}
		zv::Val passedToType;
		{
			zv::Val contextType = pt_expression_context_get_passed_to_type(context);
			if (UNEXPECTED(contextType.isUndef())) return zv::Val();
			passedToType = getExpectedArrayType(contextType.raw());
			if (UNEXPECTED(passedToType.isUndef())) return zv::Val();
		}
		zv::Val nativePassedToType;
		{
			zv::Val contextType = pt_expression_context_get_native_passed_to_type(context);
			if (UNEXPECTED(contextType.isUndef())) return zv::Val();
			nativePassedToType = getExpectedArrayType(contextType.raw());
			if (UNEXPECTED(nativePassedToType.isUndef())) return zv::Val();
		}
		bool hasExpectedType = !passedToType.isNull() || !nativePassedToType.isNull();
		bool hasArrayRef = hasArrayReference(expr);
		bool hasLiteralWrite = !literalWrite.isNull();
		zval nextIndex;
		ZVAL_LONG(&nextIndex, 0);

		zend_class_entry *arrayCe = NULL;
		zend_class_entry *closureCe = NULL;
		zend_class_entry *arrowFunctionCe = NULL;
		if (hasExpectedType) {
			arrayCe = pt_class(PT_CLASS_ARRAY_EXPR);
			closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
			arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
			if (UNEXPECTED(arrayCe == NULL || closureCe == NULL || arrowFunctionCe == NULL)) return zv::Val();
		}

		zv::Val hold;
		if (itemCount > 0) {
			for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(items.raw()))) {
				zval *arrayItem = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(arrayItem) != IS_OBJECT)) {
					// Array_ items are ArrayItem nodes (an empty element is a parse error)
					zend_type_error("PHPStan\\Node\\LiteralArrayItem::__construct(): Argument #2 ($arrayItem) must be of type ?PhpParser\\Node\\ArrayItem, %s given", zend_zval_value_name(arrayItem));
					return zv::Val();
				}
				{
					zv::Args itemNodeArgv{scope.raw(), arrayItem};
					zv::Val itemNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_ITEM, 2, itemNodeArgv);
					if (UNEXPECTED(itemNode.isUndef())) return zv::Val();
					itemNodes.push(std::move(itemNode));
				}
				zv::Val itemCallbackScope = zv::Val::copyOf(zv::Ref(scope.raw()));
				zv::Val keyResult;
				zval *key = itemKey(arrayItem);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				if (Z_TYPE_P(key) != IS_NULL) {
					zv::Val keyContext = pt_expression_context_enter_deep_keeping_value_flow(context);
					if (UNEXPECTED(keyContext.isUndef())) return zv::Val();
					zv::Val heldKey = zv::Val::copyOf(zv::Ref(key));
					keyResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldKey.raw(), scope.raw(), storage, nodeCallback, keyContext.raw());
					if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
					if (UNEXPECTED(!collectItemResult(itemTypes, dependencies, NULL, heldKey.raw(), keyResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, scope, hold))) return zv::Val();
				}

				zval *unpack = itemUnpack(arrayItem);
				if (UNEXPECTED(unpack == NULL)) return zv::Val();
				bool isUnpack = zend_is_true(unpack);
				zv::Val valueContext;
				if (!hasLiteralWrite) {
					valueContext = pt_expression_context_enter_deep_keeping_value_flow(context);
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				zv::Val keyType;
				if (hasExpectedType && !isUnpack) {
					zval *value = itemValue(arrayItem);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					if (Z_TYPE_P(value) == IS_OBJECT && (instanceof_function(Z_OBJCE_P(value), arrayCe) || instanceof_function(Z_OBJCE_P(value), closureCe) || instanceof_function(Z_OBJCE_P(value), arrowFunctionCe))) {
						if (!keyResult.isUndef()) {
							zv::Val keyResultType = pt_expression_result_get_type(keyResult.raw());
							if (UNEXPECTED(keyResultType.isUndef())) return zv::Val();
							if (UNEXPECTED(!keyResultType.ref().isObject())) {
								zend_throw_error(NULL, "Call to a member function toArrayKey() on %s", zend_zval_value_name(keyResultType.raw()));
								return zv::Val();
							}
							keyType = pt_type_op(Z_OBJ_P(keyResultType.raw()), PT_OP_TO_ARRAY_KEY, 0, NULL);
						} else if (isSet(&nextIndex)) {
							keyType = newConstantIntegerTypeOf(&nextIndex);
						} else {
							zval out;
							if (UNEXPECTED(!pt_integer_type_new(&out))) return zv::Val();
							keyType = zv::Val::adopt(out);
						}
						if (UNEXPECTED(keyType.isUndef())) return zv::Val();
					}
				}
				zval offset = {};
				ZVAL_NULL(&offset);
				zv::Val offsetHold;
				if (hasLiteralWrite || hasExpectedType) {
					if (isUnpack) {
						ZVAL_NULL(&nextIndex);
					} else if (keyResult.isUndef()) {
						ZVAL_COPY_VALUE(&offset, &nextIndex);
						if (isSet(&nextIndex)) increment_function(&nextIndex);
					} else {
						zv::Val keyResultType = pt_expression_result_get_type(keyResult.raw());
						if (UNEXPECTED(keyResultType.isUndef())) return zv::Val();
						offsetHold = pt_variable_write_offset_from_type(keyResultType.raw());
						if (UNEXPECTED(offsetHold.isUndef())) return zv::Val();
						ZVAL_COPY_VALUE(&offset, offsetHold.raw());
						if (Z_TYPE(offset) == IS_NULL) {
							ZVAL_NULL(&nextIndex);
						} else if (Z_TYPE(offset) == IS_LONG && isSet(&nextIndex)) {
							advanceNextIndex(&nextIndex, Z_LVAL(offset));
						}
					}
				}
				if (hasLiteralWrite) {
					zv::Val variableName = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::variableName, PT_LC("getvariablename"));
					if (UNEXPECTED(variableName.isUndef())) return zv::Val();
					zv::Val parentId = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::id, PT_LC("getid"));
					if (UNEXPECTED(parentId.isUndef())) return zv::Val();
					zv::Args writeArgv{variableName.raw(), arrayItem, (zend_long) Z_OBJ_HANDLE_P(arrayItem), PT_ARH_KIND_ARRAY_LITERAL_ITEM, true, &offset, parentId.raw()};
					zv::Val itemWrite = pt_type_new(PT_CLASS_VARIABLE_WRITE, 7, writeArgv);
					if (UNEXPECTED(itemWrite.isUndef())) return zv::Val();
					zv::Val writeFlow = pt_variable_flow_write(itemWrite.raw(), NULL);
					if (UNEXPECTED(writeFlow.isUndef())) return zv::Val();
					variableFlows.push(std::move(writeFlow));
					zv::Val deepContext = pt_expression_context_enter_deep(context);
					if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
					valueContext = pt_expression_context_enter_value_flow(deepContext.raw(), itemWrite.raw(), false);
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				if (!keyType.isUndef()) {
					zv::Val expectedValueType = getExpectedValueType(passedToType.raw(), keyType.raw());
					if (UNEXPECTED(expectedValueType.isUndef())) return zv::Val();
					zv::Val expectedNativeValueType = getExpectedValueType(nativePassedToType.raw(), keyType.raw());
					if (UNEXPECTED(expectedNativeValueType.isUndef())) return zv::Val();
					valueContext = pt_expression_context_enter_passed_to_type(valueContext.raw(), expectedValueType.raw(), expectedNativeValueType.raw());
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				zval *value = itemValue(arrayItem);
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val heldValue = zv::Val::copyOf(zv::Ref(value));
				zv::Val valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldValue.raw(), scope.raw(), storage, nodeCallback, valueContext.raw());
				if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
				if (UNEXPECTED(!collectItemResult(itemTypes, dependencies, arrayItem, heldValue.raw(), valueResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, scope, hold))) return zv::Val();

				// the item's callback fires after its key and value were processed,
				// with the item's entry scope
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, arrayItem, itemCallbackScope.raw(), storage))) return zv::Val();

				// an item's results are dead once their callback ran - unless a
				// by-reference item keeps the literal's inner state alive
				if (!hasArrayRef) {
					zval *evictKey = itemKey(arrayItem);
					if (UNEXPECTED(evictKey == NULL)) return zv::Val();
					if (Z_TYPE_P(evictKey) != IS_NULL && UNEXPECTED(!pt_expression_result_storage_remove(storage, evictKey))) return zv::Val();
					zval *evictValue = itemValue(arrayItem);
					if (UNEXPECTED(evictValue == NULL)) return zv::Val();
					if (UNEXPECTED(!pt_expression_result_storage_remove(storage, evictValue))) return zv::Val();
				}
			}
		}
		{
			zv::Val itemNodesValue(std::move(itemNodes));
			zv::Args nodeArgv{expr, itemNodesValue.raw()};
			zv::Val literalArrayNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_NODE, 2, nodeArgv);
			if (UNEXPECTED(literalArrayNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, literalArrayNode.raw(), scope.raw(), storage))) return zv::Val();
		}
		{
			zv::Val observingFrame = pt_node_scope_resolver_observing_template_argument_frame(nodeScopeResolver, scope.raw());
			if (UNEXPECTED(observingFrame.isUndef())) return zv::Val();
			if (!observingFrame.isNull()) {
				scope = collectAbsorbedItems(expr, itemTypes.raw(), scope.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val itemTypesValue(std::move(itemTypes));
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, itemTypesValue.raw(), beforeScope);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		zv::Val result = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		// Dependencies::merge(...$dependencies): the items' own dependencies in
		// processing order (gathered in the loop), the callable's last; an array
		// whose items depend on nothing merges to null - withDependencies(null)
		// is the same object then
		zv::Val callableDependencies = getCallableDependencies(beforeScope, expr, itemTypesValue.raw(), result.raw());
		if (UNEXPECTED(callableDependencies.isUndef())) return zv::Val();
		if (!callableDependencies.isNull()) dependencies.push(std::move(callableDependencies));
		if (zend_hash_num_elements(dependencies.table()) == 0) return result;
		zv::Val mergedDependencies = pt_dependencies_merge_list(dependencies.table());
		if (UNEXPECTED(mergedDependencies.isUndef())) return zv::Val();
		return pt_expression_result_with_dependencies(result.raw(), mergedDependencies.raw());
	}

	/* Mirrors collectAbsorbedItems() */
	zv::Val collectAbsorbedItems(zval *expr, zval *itemTypes, zval *scopeArg) const
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (Z_TYPE_P(items) != IS_ARRAY) return scope;
		zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
		zv::Arr absorbedTypes = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::ArrRef(itemsHold.raw())) {
			zval *arrayItem = entry.value().deref().raw();
			zval *value = itemValue(arrayItem);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			bool plainScalar;
			if (UNEXPECTED(!isPlainScalar(value, plainScalar))) return zv::Val();
			if (plainScalar) continue;
			if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
				return zv::Val();
			}
			zval *pair = zend_hash_index_find(Z_ARRVAL_P(itemTypes), Z_OBJ_HANDLE_P(value));
			if (pair == NULL || Z_TYPE_P(pair) == IS_NULL) continue;
			ZVAL_DEREF(pair);
			zval *itemType = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), 0) : NULL;
			if (UNEXPECTED(itemType == NULL)) {
				zend_type_error("PHPStan\\Analyser\\Generics\\ClosureSignatureInference::hasMarkers(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, null given");
				return zv::Val();
			}
			ZVAL_DEREF(itemType);
			bool hasMarkers;
			if (UNEXPECTED(!pt_closure_signature_inference_has_markers(itemType, hasMarkers))) return zv::Val();
			if (!hasMarkers) continue;
			absorbedTypes.push(zv::Ref(itemType));
		}
		if (zend_hash_num_elements(absorbedTypes.table()) == 0) return scope;

		zv::Val arrayType;
		{
			ItemTypeFrame frame{itemTypes, false};
			pt_ietr_get_type getTypeCallback{&itemTypeCallback, &frame, &itemTypeCallable};
			arrayType = getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, getTypeCallback);
			if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		}
		zv::Val absorbedTypesHold(std::move(absorbedTypes));
		zv::Val absorbed = pt_closure_signature_inference_collect_absorbed_into(absorbedTypesHold.raw(), arrayType.raw());
		if (UNEXPECTED(absorbed.isUndef())) return zv::Val();
		return pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope.raw()), absorbed.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ArrayHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* Mirrors getCallableDependencies(): an array that may be a callable -
	 * `[Foo::class, 'method']` - depends on what calling it returns; PHP null
	 * for nothing, UNDEF = pending exception */
	static zv::Val getCallableDependencies(zval *scope, zval *expr, zval *itemTypes, zval *result)
	{
		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(items));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) != 2) return zv::Val::null();
		zval *first = zend_hash_index_find(Z_ARRVAL_P(items), 0);
		if (first == NULL) return zv::Val::null();
		ZVAL_DEREF(first);
		if (Z_TYPE_P(first) == IS_NULL) return zv::Val::null();

		// a class constant, property default or enum case value is not called where it is
		// declared - testing it would reflect whatever class its first item happens to name
		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		if (inClass) {
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return zv::Val();
			if (function.isNull()) {
				bool inAnonymousFunction;
				if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return zv::Val();
				if (!inAnonymousFunction) {
					zv::Val functionCallStack = pt_type_call(Z_OBJ_P(scope), PT_LC("getfunctioncallstack"), 0, NULL);
					if (UNEXPECTED(functionCallStack.isUndef())) return zv::Val();
					if (Z_TYPE_P(functionCallStack.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(functionCallStack.raw())) == 0) return zv::Val::null();
				}
			}
		}

		if (UNEXPECTED(Z_TYPE_P(first) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(first));
			return zv::Val();
		}
		zval *value = itemValue(first);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		// $firstItemType: derived from the node of a plain string or a
		// TypeExpr, the stored [type, nativeType] pair otherwise
		zv::Val firstItemType = zv::Val::null();
		int isString = isInstanceOf(value, PT_CLASS_SCALAR_STRING);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString == 1) {
			int produced = scalarConstantType(value, firstItemType);
			if (UNEXPECTED(produced < 0)) return zv::Val();
			if (UNEXPECTED(produced != 1)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
		} else {
			int isTypeExpr = isInstanceOf(value, PT_CLASS_TYPE_EXPR);
			if (UNEXPECTED(isTypeExpr < 0)) return zv::Val();
			if (isTypeExpr == 1) {
				firstItemType = typeExprType(value);
				if (UNEXPECTED(firstItemType.isUndef())) return zv::Val();
			} else {
				if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
					zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(value));
					return zv::Val();
				}
				zval *pair = zend_hash_index_find(Z_ARRVAL_P(itemTypes), Z_OBJ_HANDLE_P(value));
				if (pair != NULL && Z_TYPE_P(pair) != IS_NULL) {
					ZVAL_DEREF(pair);
					zval *stored = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), 0) : NULL;
					if (UNEXPECTED(stored == NULL)) {
						// the pair is [type, nativeType] by construction
						pt_throw_should_not_happen();
						return zv::Val();
					}
					ZVAL_DEREF(stored);
					firstItemType = zv::Val::copyOf(zv::Ref(stored));
				}
			}
		}

		if (firstItemType.isNull()) return zv::Val::null();
		zend_long isClassString = pt_type_call_trinary(Z_OBJ_P(firstItemType.raw()), PT_LC("isclassstring"), 0, NULL);
		if (UNEXPECTED(isClassString < 0)) return zv::Val();
		if (isClassString != PT_TRI_YES) return zv::Val::null();

		zv::Val arrayType = pt_expression_result_get_type(result);
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(arrayType.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_NO) return zv::Val::null();

		zv::Val variants = pt_type_call(Z_OBJ_P(arrayType.raw()), PT_LC("getcallableparametersacceptors"), 1, scope);
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Arr returnTypes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(variants.raw())) {
			zv::Val returnTypeHold;
			zval *returnType = pt_parameters_acceptor_return_type(entry.value().deref().raw(), returnTypeHold);
			if (UNEXPECTED(returnType == NULL)) return zv::Val();
			returnTypes.push(zv::Ref(returnType));
		}

		return pt_dependencies_create_in(scope, returnTypes.raw());
	}

	/* the per-item bookkeeping of the twin's loop: a non-scalar node's
	 * [getType(), getNativeType()] pair goes to $itemTypes (the plain scalars'
	 * constant types are re-derived from the node wherever they are needed);
	 * the result's own dependencies and variable flow follow it (a
	 * by-reference value's escape too); the $hasYield / $throwPoints /
	 * $impurePoints / $isAlwaysTerminating folds; $scope =
	 * $result->getScope(); false = pending exception */
	[[nodiscard]] static bool collectItemResult(zv::Arr &itemTypes, zv::Arr &dependencies, zval *arrayItem, zval *node, zval *result, zv::Arr &variableFlows, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating, zv::Val &scope, zv::Val &hold)
	{
		bool plainScalar;
		if (UNEXPECTED(!isPlainScalar(node, plainScalar))) return false;
		if (!plainScalar) {
			if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
				zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(node));
				return false;
			}
			zv::Val type = pt_expression_result_get_type(result);
			if (UNEXPECTED(type.isUndef())) return false;
			zv::Val nativeType = pt_expression_result_get_native_type(result);
			if (UNEXPECTED(nativeType.isUndef())) return false;
			zv::Arr pair = zv::Arr::create(2);
			pair.push(std::move(type));
			pair.push(std::move(nativeType));
			zval stored;
			ZVAL_COPY(&stored, pair.raw());
			zend_hash_index_update(itemTypes.table(), Z_OBJ_HANDLE_P(node), &stored);
		}
		zval *itemDependencies = pt_expression_result_dependencies(result, hold);
		if (UNEXPECTED(itemDependencies == NULL)) return false;
		if (Z_TYPE_P(itemDependencies) != IS_NULL) dependencies.push(zv::Ref(itemDependencies));
		zv::Val flow = pt_expression_result_variable_flow(result);
		if (UNEXPECTED(flow.isUndef())) return false;
		if (!flow.isNull()) variableFlows.push(std::move(flow));
		if (arrayItem != NULL) {
			// only the value side: `if ($arrayItem->byRef)`
			zval *byRef = itemByRef(arrayItem);
			if (UNEXPECTED(byRef == NULL)) return false;
			if (zend_is_true(byRef)) {
				zval *value = itemValue(arrayItem);
				if (UNEXPECTED(value == NULL)) return false;
				zv::Val escape = pt_variable_flow_builder_escape_root(value);
				if (UNEXPECTED(escape.isUndef())) return false;
				variableFlows.push(std::move(escape));
			}
		}
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		zval *borrowed = pt_expression_result_throw_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_expression_result_impure_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(result, isAlwaysTerminating))) return false;
		borrowed = pt_expression_result_scope(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		scope = zv::Val::copyOf(zv::Ref(borrowed));
		return true;
	}

	/* Mirrors getExpectedArrayType(); $type NULL / IS_NULL for null; PHP null
	 * for null, UNDEF = pending exception */
	static zv::Val getExpectedArrayType(zval *type)
	{
		if (type == NULL || Z_TYPE_P(type) == IS_NULL) return zv::Val::null();
		zend_long isIterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(isIterable < 0)) return zv::Val();
		if (isIterable == PT_TRI_NO) return zv::Val::null();

		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(type));

		zv::Val keyType = pt_type_new_mixed_type();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val itemType = pt_type_new_mixed_type();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zval arrayType;
		if (UNEXPECTED(!pt_array_type_new(&arrayType, keyType.raw(), itemType.raw()))) return zv::Val();
		zv::Val arrayTypeValue = zv::Val::adopt(arrayType);
		zv::Args argv{type, arrayTypeValue.raw()};
		return pt_type_combinator_intersect(2, argv);
	}

	/* Mirrors getExpectedValueType(); PHP null for null, UNDEF = pending
	 * exception */
	static zv::Val getExpectedValueType(zval *arrayType, zval *keyType)
	{
		if (Z_TYPE_P(arrayType) == IS_NULL) return zv::Val::null();
		zend_long hasOffsetValueType = pt_type_op_trinary(Z_OBJ_P(arrayType), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, keyType);
		if (UNEXPECTED(hasOffsetValueType < 0)) return zv::Val();
		if (hasOffsetValueType == PT_TRI_NO) return zv::Val::null();

		return pt_type_op(Z_OBJ_P(arrayType), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType);
	}

	/* Mirrors hasArrayReference() */
	static bool hasArrayReference(zval *array)
	{
		zval *items = rawNodeProp(pt_arh_ref_items_site, array, PT_LC("items"));
		if (items == NULL || Z_TYPE_P(items) != IS_ARRAY) return false;
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(items))) {
			zval *item = entry.value().deref().raw();
			zval *byRef = rawNodeProp(pt_arh_ref_by_ref_site, item, PT_LC("byRef"));
			if (byRef != NULL && zend_is_true(byRef)) return true;
			zval *value = rawNodeProp(pt_arh_ref_value_site, item, PT_LC("value"));
			if (value == NULL) continue;
			int isArray = isInstanceOf(value, PT_CLASS_ARRAY_EXPR);
			if (isArray == 1) {
				bool nested = false;
				pt_engine_with_stack([&]() { nested = hasArrayReference(value); });
				if (nested) return true;
			}
		}
		return false;
	}

	/* static function (Expr $inner) use ($itemTypes, $nativeTypesPromoted):
	 * Type — InitializerExprTypeResolver calls it synchronously, over the
	 * typeCallback's frame */
	struct ItemTypeFrame
	{
		zval *itemTypes;
		bool nativeTypesPromoted;
	};

	static zv::Val itemTypeCallback(void *data, zval *inner)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		ZVAL_DEREF(inner);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(inner) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(inner), exprCe))) {
			zend_type_error("%s(): Argument #1 ($inner) must be of type PhpParser\\Node\\Expr, %s given", pt_arh_closure_name, zend_zval_value_name(inner));
			return zv::Val();
		}
		// a plain scalar's constant type comes from the node, a TypeExpr's
		// from itself; only the rest was stored in $itemTypes
		zv::Val scalar;
		int isScalar = scalarConstantType(inner, scalar);
		if (UNEXPECTED(isScalar < 0)) return zv::Val();
		if (isScalar == 1) return scalar;
		int isTypeExpr = isInstanceOf(inner, PT_CLASS_TYPE_EXPR);
		if (UNEXPECTED(isTypeExpr < 0)) return zv::Val();
		if (isTypeExpr == 1) return typeExprType(inner);
		zval *pair = zend_hash_index_find(Z_ARRVAL_P(frame->itemTypes), Z_OBJ_HANDLE_P(inner));
		if (pair == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		ZVAL_DEREF(pair);
		zval *stored = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), frame->nativeTypesPromoted ? 1 : 0) : NULL;
		if (UNEXPECTED(stored == NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		ZVAL_DEREF(stored);
		return zv::Val::copyOf(zv::Ref(stored));
	}

	/* the callback as a PHP callable that outlives the call (the resolver
	 * hands it to OversizedArrayBuilder): the closure over copies of
	 * $itemTypes and $nativeTypesPromoted */
	static zv::Val itemTypeCallable(void *data)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		return pt_native_closure(&itemTypeCallbackBody, frame->itemTypes, frame->nativeTypesPromoted);
	}

	/* the same closure called from PHP — captures: $itemTypes,
	 * $nativeTypesPromoted */
	static void itemTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		ItemTypeFrame frame{&captures[0], Z_TYPE(captures[1]) == IS_TRUE};
		zv::Val type = itemTypeCallback(&frame, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* the typeCallback's body */
	zv::Val resolveType(bool nativeTypesPromoted, zval *expr, zval *itemTypes, zval *beforeScope) const
	{
		zv::Val type;
		{
			ItemTypeFrame frame{itemTypes, nativeTypesPromoted};
			pt_ietr_get_type getTypeCallback{&itemTypeCallback, &frame, &itemTypeCallable};
			type = getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, getTypeCallback);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(items));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) != 2) return type;
		zval *first = zend_hash_index_find(Z_ARRVAL_P(items), 0);
		if (first == NULL || Z_TYPE_P(first) == IS_NULL) return type;
		zval *second = zend_hash_index_find(Z_ARRVAL_P(items), 1);
		if (second == NULL || Z_TYPE_P(second) == IS_NULL) return type;
		zv::Val isCallableCall;
		{
			zv::Val name = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, zv::Args{pt_arh_is_callable});
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, expr);
			if (UNEXPECTED(arg.isUndef())) return zv::Val();
			zv::Arr callArgs = zv::Arr::create(1);
			callArgs.push(std::move(arg));
			zv::Val callArgsValue(std::move(callArgs));
			zv::Args callArgv{name.raw(), callArgsValue.raw()};
			isCallableCall = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
			if (UNEXPECTED(isCallableCall.isUndef())) return zv::Val();
		}
		zend_long hasExpressionType = pt_mutating_scope_has_expression_type(Z_OBJ_P(beforeScope), isCallableCall.raw());
		if (UNEXPECTED(hasExpressionType < 0)) return zv::Val();
		if (hasExpressionType != PT_TRI_YES) return type;
		// the narrowed type read from expressionTypes directly
		zv::Val trackedType = pt_mutating_scope_get_tracked_expression_type(Z_OBJ_P(beforeScope), Z_OBJ_P(isCallableCall.raw()));
		if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
		if (UNEXPECTED(!trackedType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(trackedType.raw()));
			return zv::Val();
		}
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(trackedType.raw()), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue != PT_TRI_YES) return type;
		// isCallable() is asked last - it reflects the class named by the first
		// item, which is expensive and unnecessary for arrays never narrowed by
		// is_callable()
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(type.raw()));
			return zv::Val();
		}
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(type.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable != PT_TRI_MAYBE) return type;

		zval callableType;
		if (UNEXPECTED(!pt_callable_type_new(&callableType))) return zv::Val();
		zv::Val callableTypeValue = zv::Val::adopt(callableType);
		zv::Args intersectArgv{type.raw(), callableTypeValue.raw()};
		return pt_type_combinator_intersect(2, intersectArgv);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $itemTypes,
	 * $beforeScope): Type — captures: $this, $expr, $itemTypes, $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		zv::Val type = ArrayHandler(Z_OBJ(captures[0])).resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_array_handler)
{
	pt_arh_is_callable = zend_string_init_interned(PT_LC("is_callable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ArrayHandler");
	ptdecl::ArrayHandler::declareClass(cls);
	ptdecl::ArrayHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory)) RETURN_THROWS();
		ArrayHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory);
	});

	cls.method<&ArrayHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArrayHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_array_handler);
	pt_expr_handler_entry_register(&pt_ce_array_handler, &ArrayHandler::processExprEntry);
}

/* }}} */
