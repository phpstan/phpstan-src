/*
 * What the operator handler ports (BooleanAndHandler.cpp,
 * BooleanOrHandler.cpp, BooleanNotHandler.cpp, TernaryHandler.cpp,
 * BinaryOpHandler.cpp, CoalesceHandler.cpp, CoalesceCompositionHelper.cpp)
 * share: the operand reads of the php-parser nodes, the array_merge() of
 * throw / impure points, the explicit-never test, the boolean verdicts their
 * type callbacks ask and the argument count check of their closures.
 */

#ifndef PHPSTANTURBO_OPERATOR_HANDLERS_H
#define PHPSTANTURBO_OPERATOR_HANDLERS_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "ParserVisitors.h"

namespace ptoh {

using phpstanturbo::visitors::NodeProp;

/* {{{ the php-parser nodes' operands */

/* the property offsets, resolved once per request through the class map on
 * the declaring class (every subclass keeps the declaring class's slot) */
inline NodeProp binaryOpLeftProp = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "left");
inline NodeProp binaryOpRightProp = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "right");
inline NodeProp booleanNotExprProp = PT_NODE_PROP(PT_CLASS_BOOLEAN_NOT_EXPR, "expr");
inline NodeProp ternaryCondProp = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "cond");
inline NodeProp ternaryIfProp = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "if");
inline NodeProp ternaryElseProp = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "else");

/* $node->$name of a node the handler dispatch guarantees to be an instance
 * of the property's class (dereferenced); NULL with the engine's Error
 * pending (an uninitialized typed property, or the class map failing) */
inline zval *operand(NodeProp &prop, zval *node)
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

inline zval *binaryOpLeft(zval *expr) { return operand(binaryOpLeftProp, expr); }
inline zval *binaryOpRight(zval *expr) { return operand(binaryOpRightProp, expr); }

/* whether a value is an object of the class-map class; -1 = pending
 * exception (the class map failing) */
inline int isInstance(zval *value, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return -1;
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce) ? 1 : 0;
}

/* }}} */

/* {{{ small value helpers */

/* array_merge($a, $b) of two arrays (string keys kept, integer keys
 * renumbered), with array_merge()'s own shortcut: an empty side yields the
 * other array itself when that one is a hole-free list */
inline zv::Val arrayMerge(zval *a, zval *b)
{
	HashTable *first = Z_ARRVAL_P(a);
	HashTable *second = Z_ARRVAL_P(b);
	zval *only = zend_hash_num_elements(first) == 0 ? b : (zend_hash_num_elements(second) == 0 ? a : NULL);
	if (only != NULL && HT_IS_PACKED(Z_ARRVAL_P(only)) && HT_IS_WITHOUT_HOLES(Z_ARRVAL_P(only))) return zv::Val::copyOf(zv::Ref(only));
	zv::Arr merged = zv::Arr::create(zend_hash_num_elements(first) + zend_hash_num_elements(second));
	for (HashTable *source : { first, second }) {
		for (zv::ArrayEntry entry : zv::TableRef(source)) {
			if (entry.hasStringKey()) {
				merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
			} else {
				merged.push(entry.value());
			}
		}
	}
	return zv::Val(std::move(merged));
}

/* $type instanceof NeverType && $type->isExplicit(); false = pending exception */
[[nodiscard]] inline bool isExplicitNever(zval *type, bool &out)
{
	out = false;
	if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return true;
	return pt_never_type_is_explicit(Z_OBJ_P(type), out);
}

/* new ConstantBooleanType($value) / new BooleanType(); UNDEF = pending
 * exception */
inline zv::Val constantBoolean(bool value)
{
	zval result;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
	return zv::Val::adopt(result);
}

inline zv::Val booleanType()
{
	zval result;
	if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
	return zv::Val::adopt(result);
}

/* the closure's `Too few arguments` ArgumentCountError; false = thrown */
[[nodiscard]] inline bool requireArgs(uint32_t argc, uint32_t expected, const char *function)
{
	if (EXPECTED(argc >= expected)) return true;
	zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and exactly %u expected", function, argc, expected);
	return false;
}

/* }}} */

/* {{{ $type->toBoolean() and its isTrue()->yes() / isFalse()->yes()
 *
 * The verdict queries are asked lazily, in the twin's order. The exact
 * native ConstantBooleanType (toBoolean() is `$this`, the verdicts compare
 * its value) and BooleanType (toBoolean() is a fresh BooleanType, both
 * verdicts maybe) are answered without a call — pure queries whose answer
 * the call would return; any other type calls the methods through the
 * engine. */

class BooleanOf
{
public:
	/* false = pending exception */
	[[nodiscard]] bool init(zval *type)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type));
			return false;
		}
		zend_class_entry *ce = Z_OBJCE_P(type);
		if (ce == pt_ce_constant_boolean_type) {
			bool value;
			if (EXPECTED(pt_constant_boolean_type_value(Z_OBJ_P(type), value))) {
				trueVerdict = value ? 1 : 0;
				falseVerdict = value ? 0 : 1;
				return true;
			}
			if (UNEXPECTED(EG(exception) != NULL)) return false;
		} else if (ce == pt_ce_boolean_type) {
			trueVerdict = 0;
			falseVerdict = 0;
			return true;
		}
		boolean = pt_type_call(Z_OBJ_P(type), PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(boolean.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(boolean.raw()));
			return false;
		}
		return true;
	}

	/* $boolean->isTrue()->yes(); -1 = pending exception */
	int isTrue() { return verdict(trueVerdict, PT_LC("istrue")); }

	/* $boolean->isFalse()->yes(); -1 = pending exception */
	int isFalse() { return verdict(falseVerdict, PT_LC("isfalse")); }

private:
	zv::Val boolean;
	int trueVerdict = -2;
	int falseVerdict = -2;

	int verdict(int &cached, const char *lcname, size_t len)
	{
		if (cached != -2) return cached;
		zend_long value = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), lcname, len, 0, NULL);
		if (UNEXPECTED(value < 0)) return -1;
		cached = value == PT_TRI_YES ? 1 : 0;
		return cached;
	}
};

/* $type->isTrue()->yes() / ->isFalse()->yes() of a Type as it is (no
 * toBoolean()), with the same exact-class answers; -1 = pending exception */
inline int typeVerdict(zval *type, bool wantTrue)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", wantTrue ? "isTrue" : "isFalse", zend_zval_value_name(type));
		return -1;
	}
	zend_class_entry *ce = Z_OBJCE_P(type);
	if (ce == pt_ce_constant_boolean_type) {
		bool value;
		if (EXPECTED(pt_constant_boolean_type_value(Z_OBJ_P(type), value))) return value == wantTrue ? 1 : 0;
		return -1;
	}
	if (ce == pt_ce_boolean_type) return 0;
	zend_long value = wantTrue ? pt_type_call_trinary(Z_OBJ_P(type), PT_LC("istrue"), 0, NULL) : pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isfalse"), 0, NULL);
	if (UNEXPECTED(value < 0)) return -1;
	return value == PT_TRI_YES ? 1 : 0;
}

/* }}} */

} // namespace ptoh

#endif /* PHPSTANTURBO_OPERATOR_HANDLERS_H */
