/*
 * PHPStanTurbo\ExpressionTypeHolder — native implementation of
 * PHPStan\Analyser\ExpressionTypeHolder.
 *
 * Not final: a PHP stub subclass extends this class, and instances are
 * created from the configured expressionTypeHolderImpl class (see
 * pt_holder_create() in support.cpp) so userland type hints keep working.
 *
 * The type/certainty logic stays in the shared pt_holder_* helpers
 * (support.cpp), which ScopeOps also uses without crossing the method-call
 * ABI; the handle class below is the method-level face over them, structured
 * to mirror src/Analyser/ExpressionTypeHolder.php.
 */

#include "support.h"
#include "zv.h"

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionTypeHolder. State lives in the PHP
 * object's expr/type/certainty properties. */
class ExpressionTypeHolder
{
public:
	explicit ExpressionTypeHolder(zval *self) : self(self) {}

	void construct(zv::Ref expr, zv::Ref type, zv::Ref certainty)
	{
		zv::ObjRef obj(self);
		obj.propAtWrite(PT_ETH_PROP_EXPR, zv::Val::copyOf(expr));
		obj.propAtWrite(PT_ETH_PROP_TYPE, zv::Val::copyOf(type));
		obj.propAtWrite(PT_ETH_PROP_CERTAINTY, zv::Val::copyOf(certainty));
	}

	/*
	 * Mirrors ExpressionTypeHolder::getContainedNodeKeys(): lazily indexes
	 * the node keys of every sub-expression of the holder's expr, keyed to
	 * the classes it appears as. The walk replicates the twin's explicit
	 * stack (LIFO pop order) so the cached array is key-order identical.
	 * UNDEF = pending exception.
	 */
	zv::Val getContainedNodeKeys(zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		zv::ObjRef obj(self);
		zval *cached = obj.propAt(PT_ETH_PROP_CONTAINED_NODE_KEYS).raw();
		if (Z_TYPE_P(cached) == IS_ARRAY) {
			return zv::Val::copyOf(zv::Ref(cached));
		}

		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		zend_class_entry *nodeCe = pt_class(PT_CLASS_NODE);
		if (UNEXPECTED(exprCe == NULL || nodeCe == NULL)) {
			return zv::Val();
		}

		zv::Arr keys = zv::Arr::create(8);

		/* borrowed node pointers — pinned by the expr tree the holder owns */
		uint32_t cap = 32, top = 0;
		zend_object **stack = (zend_object **) emalloc(sizeof(*stack) * cap);
		stack[top++] = obj.propAt(PT_ETH_PROP_EXPR).deref().asObject();

		bool failed = false;
		while (top > 0) {
			zend_object *node = stack[--top];

			if (instanceof_function(node->ce, exprCe)) {
				/* $keys[$keyBuilder($node)][get_class($node)] = true */
				zval arg, retval;
				ZVAL_OBJ(&arg, node);
				fci->retval = &retval;
				fci->param_count = 1;
				fci->params = &arg;
				fci->named_params = NULL;
				if (UNEXPECTED(zend_call_function(fci, fcc) != SUCCESS || EG(exception))) {
					failed = true;
					break;
				}
				if (UNEXPECTED(Z_TYPE(retval) != IS_STRING)) {
					zval_ptr_dtor(&retval);
					zend_throw_error(NULL, "phpstan_turbo: getContainedNodeKeys() keyBuilder must return a string");
					failed = true;
					break;
				}
				zval *innerSlot = zend_symtable_find(keys.table(), Z_STR(retval));
				if (innerSlot == NULL) {
					zval innerZv;
					array_init(&innerZv);
					innerSlot = zend_symtable_update(keys.table(), Z_STR(retval), &innerZv);
				}
				zval trueZv;
				ZVAL_TRUE(&trueZv);
				zend_hash_update(Z_ARRVAL_P(innerSlot), node->ce->name, &trueZv);
				zval_ptr_dtor(&retval);
			}

			pt_node_class_info *info = pt_node_class_info_for_object(node);
			if (info == NULL || !PT_HAS_SUBNODES(info)) {
				continue;
			}
			for (uint32_t i = 0; i < info->subnode_count; i++) {
				zval *val = OBJ_PROP(node, info->subnode_offsets[i]);
				ZVAL_DEREF(val);
				if (Z_TYPE_P(val) == IS_OBJECT) {
					if (instanceof_function(Z_OBJCE_P(val), nodeCe)) {
						if (UNEXPECTED(top == cap)) {
							cap *= 2;
							stack = (zend_object **) erealloc(stack, sizeof(*stack) * cap);
						}
						stack[top++] = Z_OBJ_P(val);
					}
				} else if (Z_TYPE_P(val) == IS_ARRAY) {
					zval *el;
					ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(val), el) {
						zval *elDeref = el;
						ZVAL_DEREF(elDeref);
						if (Z_TYPE_P(elDeref) == IS_OBJECT && instanceof_function(Z_OBJCE_P(elDeref), nodeCe)) {
							if (UNEXPECTED(top == cap)) {
								cap *= 2;
								stack = (zend_object **) erealloc(stack, sizeof(*stack) * cap);
							}
							stack[top++] = Z_OBJ_P(elDeref);
						}
					} ZEND_HASH_FOREACH_END();
				}
			}
		}
		efree(stack);

		if (UNEXPECTED(failed)) {
			return zv::Val();
		}

		/* $this->containedNodeKeys = $keys (cache), then return it */
		zval *slot = obj.propAt(PT_ETH_PROP_CONTAINED_NODE_KEYS).raw();
		zval_ptr_dtor(slot);
		ZVAL_COPY(slot, keys.raw());
		return zv::Val(std::move(keys));
	}

	static zv::Val createYes(zval *expr, zval *type)
	{
		zval holder;
		pt_holder_create(&holder, expr, type, PT_TRI_YES);
		return zv::Val::adopt(holder);
	}

	static zv::Val createMaybe(zval *expr, zval *type)
	{
		zval holder;
		pt_holder_create(&holder, expr, type, PT_TRI_MAYBE);
		return zv::Val::adopt(holder);
	}

	/* false = pending exception */
	bool equalTypes(zval *other, bool &out) const { return pt_holder_equal_types(self, other, &out); }

	/* false = pending exception */
	bool equals(zval *other, bool &out) const { return pt_holder_equals(self, other, &out); }

	/* and() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val and_(zval *other) const
	{
		zval result;
		if (UNEXPECTED(!pt_holder_and(self, other, &result))) {
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	zv::Val getExpr() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_EXPR)); }
	zv::Val getType() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_TYPE)); }
	zv::Val getCertainty() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_CERTAINTY)); }

private:
	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionTypeHolder;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define ETH_CLASS "PHPStanTurbo\\ExpressionTypeHolder"
#define TRINARY_CLASS "PHPStanTurbo\\TrinaryLogic"

void pt_register_expression_type_holder()
{
	reg::Class cls("PHPStanTurbo\\ExpressionTypeHolder");
	/* not final: a PHP stub subclass extends this class; expr/type/certainty
	 * must stay in this order (OBJ_PROP_NUM slots) */
	cls.privateNullProperty("expr");
	cls.privateNullProperty("type");
	cls.privateNullProperty("certainty");
	cls.privateNullProperty("containedNodeKeys");

	cls.method("__construct", reg::Public, 3, { reg::any("expr"), reg::any("type"), reg::obj("certainty", TRINARY_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type, *certainty;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT_OF_CLASS(certainty, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder(ZEND_THIS).construct(zv::Ref(expr), zv::Ref(type), zv::Ref(certainty));
	});

	cls.method("getContainedNodeKeys", reg::Public, 1, { reg::callableArg("keyBuilder") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ExpressionTypeHolder(ZEND_THIS).getContainedNodeKeys(&fci, &fcc);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("createYes", reg::PublicStatic, 2, { reg::any("expr"), reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder::createYes(expr, type).intoReturnValue(return_value);
	});

	cls.method("createMaybe", reg::PublicStatic, 2, { reg::any("expr"), reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder::createMaybe(expr, type).intoReturnValue(return_value);
	});

	cls.method("equalTypes", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equalTypes(other, out))) {
			RETURN_THROWS();
		}
		RETURN_BOOL(out);
	});

	cls.method("equals", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equals(other, out))) {
			RETURN_THROWS();
		}
		RETURN_BOOL(out);
	});

	cls.method("and", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ExpressionTypeHolder(ZEND_THIS).and_(other);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("getExpr", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getExpr().intoReturnValue(return_value);
	});

	cls.method("getType", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getType().intoReturnValue(return_value);
	});

	cls.method("getCertainty", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getCertainty().intoReturnValue(return_value);
	});

	pt_ce_expr_type_holder = cls.register_();
}

/* }}} */
