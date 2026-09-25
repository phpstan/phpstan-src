/*
 * PHPStanTurbo\TemplateArgumentConstraints — native implementation of
 * PHPStan\Analyser\Generics\TemplateArgumentConstraints.
 *
 * The persistent collection of template inference facts a scope carries: a
 * binary tree of immutable nodes (the twin's three readonly slots, in its
 * order), empty for every call site that infers nothing. isEmpty() and
 * merge() run on every scope join and createEmpty() on every observed call
 * (~200K per self-analysis), so native callers reach them through the
 * pt_template_argument_constraints_* entries (support.h). getFacts() is a
 * generator in the twin; the public native method materializes the immutable
 * facts in the same order and returns a generator helper over that list,
 * while native consumers walk them without the array through
 * pt_template_argument_constraints_facts() (support.h).
 */

#include "support.h"
#include "generated/TemplateArgumentConstraints.h"
#include "generated/UnresolvedTemplateArgumentType.h"

namespace slots = ptdecl::TemplateArgumentConstraints::slot;
namespace sigs = ptdecl::TemplateArgumentConstraints::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

#include <vector>

zend_class_entry *pt_ce_template_argument_constraints = nullptr;

namespace {

/* $marker->getSite() of an UnresolvedTemplateArgumentType: the slot of the
 * native class, the method otherwise; UNDEF = pending exception */
zv::Val markerSite(zval *marker)
{
	if (EXPECTED(Z_OBJCE_P(marker) == pt_ce_unresolved_template_argument_type)) {
		zval *site = OBJ_PROP_NUM(Z_OBJ_P(marker), ptdecl::UnresolvedTemplateArgumentType::slot::site);
		if (EXPECTED(Z_TYPE_P(site) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(site));
	}
	return pt_type_call(Z_OBJ_P(marker), PT_LC("getsite"), 0, NULL);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\TemplateArgumentConstraints; UNDEF =
 * pending exception. */
class TemplateArgumentConstraints
{
public:
	explicit TemplateArgumentConstraints(zend_object *self) : self(self) {}

	/* the private constructor's body (borrowed; NULL = null); false =
	 * pending exception (a repeated construction modifies readonly slots) */
	[[nodiscard]] bool construct(zval *left, zval *right, zval *fact) const
	{
		if (UNEXPECTED(Z_TYPE_P(OBJ_PROP_NUM(self, slots::left)) != IS_UNDEF)) {
			zend_throw_error(NULL, "Cannot modify readonly property %s::$left", ZSTR_VAL(self->ce->name));
			return false;
		}
		write(slots::left, left);
		write(slots::right, right);
		write(slots::fact, fact);
		return true;
	}

	/* new self($left, $right, $fact) of a fresh instance (borrowed; NULL = null) */
	static zv::Val create(zend_object *left, zend_object *right, zval *fact)
	{
		zval object;
		object_init_ex(&object, pt_ce_template_argument_constraints);
		zend_object *created = Z_OBJ(object);
		zval value = {};
		if (left != NULL) {
			ZVAL_OBJ(&value, left);
			pt_write_slot(created, slots::left, &value);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(created, slots::left, &value);
		}
		if (right != NULL) {
			ZVAL_OBJ(&value, right);
			pt_write_slot(created, slots::right, &value);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(created, slots::right, &value);
		}
		if (fact != NULL) {
			pt_write_slot(created, slots::fact, fact);
		} else {
			ZVAL_NULL(&value);
			pt_write_slot(created, slots::fact, &value);
		}
		return zv::Val::adopt(object);
	}

	/* Mirrors createEmpty(): a fresh instance every time, as `new self()` */
	static zv::Val createEmpty()
	{
		return create(NULL, NULL, NULL);
	}

	/* Mirrors isEmpty(); false = pending exception */
	[[nodiscard]] bool isEmpty(bool &out) const
	{
		zval *left = pt_typed_slot(self, slots::left, self->ce, "left");
		if (UNEXPECTED(left == NULL)) return false;
		if (Z_TYPE_P(left) != IS_NULL) {
			out = false;
			return true;
		}
		zval *right = pt_typed_slot(self, slots::right, self->ce, "right");
		if (UNEXPECTED(right == NULL)) return false;
		if (Z_TYPE_P(right) != IS_NULL) {
			out = false;
			return true;
		}
		zval *fact = pt_typed_slot(self, slots::fact, self->ce, "fact");
		if (UNEXPECTED(fact == NULL)) return false;
		out = Z_TYPE_P(fact) == IS_NULL;
		return true;
	}

	/* Mirrors merge(). */
	zv::Val merge(zend_object *other) const
	{
		if (self == other) return thisValue();
		bool otherEmpty;
		if (UNEXPECTED(!pt_template_argument_constraints_is_empty_of(other, otherEmpty))) return zv::Val();
		if (otherEmpty) return thisValue();
		bool empty;
		if (UNEXPECTED(!isEmpty(empty))) return zv::Val();
		if (empty) {
			zval otherZv;
			ZVAL_OBJ(&otherZv, other);
			return zv::Val::copyOf(zv::Ref(&otherZv));
		}
		return create(self, other, NULL);
	}

	/* Mirrors withSite(). */
	zv::Val withSite(zval *marker) const
	{
		zv::Val site = markerSite(marker);
		if (UNEXPECTED(site.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(site.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getAttribute() on %s", zend_zval_value_name(site.raw()));
			return zv::Val();
		}
		zv::Val synthetic = pt_engine_node_get_attribute(Z_OBJ_P(site.raw()), PT_LC("templateArgumentSyntheticSite"));
		if (UNEXPECTED(synthetic.isUndef())) return zv::Val();
		if (Z_TYPE_P(synthetic.raw()) == IS_TRUE) return thisValue();
		return withFact(marker, NULL, NULL, false);
	}

	/* Mirrors withSend() / withLowerBound() / withUnconstrainingSend():
	 * new self($this, fact: [$marker, $type, $variance, $unconstraining])
	 * ($type / $variance NULL = null) */
	zv::Val withFact(zval *marker, zval *type, zval *variance, bool unconstraining) const
	{
		zval fact;
		array_init_size(&fact, 4);
		HashTable *table = Z_ARRVAL(fact);
		zend_hash_real_init_packed(table);
		ZEND_HASH_FILL_PACKED(table) {
			Z_ADDREF_P(marker);
			ZEND_HASH_FILL_ADD(marker);
			if (type != NULL) {
				Z_TRY_ADDREF_P(type);
				ZEND_HASH_FILL_ADD(type);
			} else {
				ZEND_HASH_FILL_SET_NULL();
				ZEND_HASH_FILL_NEXT();
			}
			if (variance != NULL) {
				Z_TRY_ADDREF_P(variance);
				ZEND_HASH_FILL_ADD(variance);
			} else {
				ZEND_HASH_FILL_SET_NULL();
				ZEND_HASH_FILL_NEXT();
			}
			zval flag = {};
			ZVAL_BOOL(&flag, unconstraining);
			ZEND_HASH_FILL_ADD(&flag);
		} ZEND_HASH_FILL_END();
		zv::Val constraints = create(self, NULL, &fact);
		zval_ptr_dtor(&fact);
		return constraints;
	}

	/* Mirrors getFacts(): materialize the immutable facts, then expose them
	 * through the same Generator helper as the PHP twin. */
	zv::Val getFacts() const
	{
		zv::Arr facts = zv::Arr::create(0);
		bool ok = forEachFact([&](zval *fact) {
			facts.push(zv::Ref(fact));
			return true;
		});
		if (UNEXPECTED(!ok)) return zv::Val();
		return pt_type_call_static(PT_CLASS_ITERABLE_HELPER, PT_LC("yieldvalues"), 1, facts.raw());
	}

	/* The generator's walk: an explicit stack of [node, expanded] pairs, a
	 * node's own fact after its left and right subtrees, a node reachable
	 * twice visited once (by object handle). fn(zval *fact) returns false
	 * for a pending exception; false = pending exception. The tree is
	 * immutable and held by the caller for the duration. */
	template <typename F>
	[[nodiscard]] bool forEachFact(F &&fn) const
	{
		struct Frame
		{
			zend_object *node;
			bool expanded;
		};
		std::vector<Frame> stack;
		stack.push_back({self, false});
		zv::ScratchTable visited(8);
		while (!stack.empty()) {
			Frame frame = stack.back();
			stack.pop_back();
			zend_object *current = frame.node;
			if (frame.expanded) {
				zval *fact = pt_typed_slot(current, slots::fact, current->ce, "fact");
				if (UNEXPECTED(fact == NULL)) return false;
				if (Z_TYPE_P(fact) != IS_NULL) {
					if (UNEXPECTED(!fn(fact))) return false;
				}
				continue;
			}
			zend_ulong id = (zend_ulong) current->handle;
			if (zend_hash_index_exists(visited.table(), id)) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_add_new(visited.table(), id, &marked);
			stack.push_back({current, true});
			zval *right = pt_typed_slot(current, slots::right, current->ce, "right");
			if (UNEXPECTED(right == NULL)) return false;
			if (Z_TYPE_P(right) != IS_NULL) stack.push_back({Z_OBJ_P(right), false});
			zval *left = pt_typed_slot(current, slots::left, current->ce, "left");
			if (UNEXPECTED(left == NULL)) return false;
			if (Z_TYPE_P(left) == IS_NULL) continue;
			stack.push_back({Z_OBJ_P(left), false});
		}
		return true;
	}

private:
	zend_object *self;

	void write(uint32_t index, zval *value) const
	{
		zval null;
		ZVAL_NULL(&null);
		pt_write_slot(self, index, value != NULL ? value : &null);
	}

	zv::Val thisValue() const
	{
		return pt_this_value(self);
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArgumentConstraints;

/* {{{ direct entries (support.h) */

bool pt_template_argument_constraints_is_empty_of(zend_object *constraints, bool &out)
{
	if (EXPECTED(constraints->ce == pt_ce_template_argument_constraints)) return TemplateArgumentConstraints(constraints).isEmpty(out);
	zv::Val result = pt_type_call(constraints, PT_LC("isempty"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_template_argument_constraints_is_empty(zval *constraints, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isEmpty() on %s", zend_zval_value_name(constraints));
		return false;
	}
	return pt_template_argument_constraints_is_empty_of(Z_OBJ_P(constraints), out);
}

zv::Val pt_template_argument_constraints_create_empty()
{
	return TemplateArgumentConstraints::createEmpty();
}

zv::Val pt_template_argument_constraints_merge(zval *constraints, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function merge() on %s", zend_zval_value_name(constraints));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_template_argument_constraints)) {
		return TemplateArgumentConstraints(Z_OBJ_P(constraints)).merge(Z_OBJ_P(other));
	}
	return pt_type_call(Z_OBJ_P(constraints), PT_LC("merge"), 1, other);
}

zv::Val pt_template_argument_constraints_with_site(zval *constraints, zval *marker)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function withSite() on %s", zend_zval_value_name(constraints));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(marker) == IS_OBJECT)) return TemplateArgumentConstraints(Z_OBJ_P(constraints)).withSite(marker);
	return pt_type_call(Z_OBJ_P(constraints), PT_LC("withsite"), 1, marker);
}

zv::Val pt_template_argument_constraints_with_send(zval *constraints, zval *marker, zval *type, zval *variance)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function withSend() on %s", zend_zval_value_name(constraints));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(marker) == IS_OBJECT)) return TemplateArgumentConstraints(Z_OBJ_P(constraints)).withFact(marker, type, variance, false);
	zv::Args argv{marker, type, variance};
	return pt_type_call(Z_OBJ_P(constraints), PT_LC("withsend"), 3, argv);
}

zv::Val pt_template_argument_constraints_with_lower_bound(zval *constraints, zval *marker, zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function withLowerBound() on %s", zend_zval_value_name(constraints));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(marker) == IS_OBJECT)) return TemplateArgumentConstraints(Z_OBJ_P(constraints)).withFact(marker, type, NULL, false);
	zv::Args argv{marker, type};
	return pt_type_call(Z_OBJ_P(constraints), PT_LC("withlowerbound"), 2, argv);
}

zv::Val pt_template_argument_constraints_with_unconstraining_send(zval *constraints, zval *marker)
{
	if (UNEXPECTED(Z_TYPE_P(constraints) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function withUnconstrainingSend() on %s", zend_zval_value_name(constraints));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(marker) == IS_OBJECT)) return TemplateArgumentConstraints(Z_OBJ_P(constraints)).withFact(marker, NULL, NULL, true);
	return pt_type_call(Z_OBJ_P(constraints), PT_LC("withunconstrainingsend"), 1, marker);
}

bool pt_template_argument_constraints_facts(zval *constraints, pt_template_argument_fact_fn fn, void *data)
{
	if (EXPECTED(Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints)) {
		return TemplateArgumentConstraints(Z_OBJ_P(constraints)).forEachFact([&](zval *fact) { return fn(data, fact); });
	}
	/* another implementation: its getFacts() iterable, flattened */
	zv::Val facts = pt_type_call(Z_OBJ_P(constraints), PT_LC("getfacts"), 0, NULL);
	if (UNEXPECTED(facts.isUndef())) return false;
	if (Z_TYPE_P(facts.raw()) != IS_ARRAY) {
		zval function;
		ZVAL_STRINGL(&function, "iterator_to_array", sizeof("iterator_to_array") - 1);
		zv::Args argv{facts.raw(), false};
		facts = pt_type_call_callable(&function, 2, argv);
		zval_ptr_dtor(&function);
		if (UNEXPECTED(facts.isUndef())) return false;
	}
	for (auto entry : zv::ArrRef(facts.raw())) {
		if (UNEXPECTED(!fn(data, entry.value().deref().raw()))) return false;
	}
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TAC_THIS TemplateArgumentConstraints(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_argument_constraints)
{
	reg::Class cls("PHPStan\\Analyser\\Generics\\TemplateArgumentConstraints");
	ptdecl::TemplateArgumentConstraints::declareClass(cls);
	ptdecl::TemplateArgumentConstraints::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *left = NULL, *right = NULL, *fact = NULL;
		ZEND_PARSE_PARAMETERS_START(0, 3)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(left, pt_ce_template_argument_constraints)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(right, pt_ce_template_argument_constraints)
			Z_PARAM_ARRAY_OR_NULL(fact)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_TAC_THIS.construct(left, right, fact))) RETURN_THROWS();
	});

	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(TemplateArgumentConstraints::createEmpty());
	});

	cls.method<&TemplateArgumentConstraints::isEmpty>(sigs::isEmpty);

	cls.method(sigs::merge, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_argument_constraints)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TAC_THIS.merge(Z_OBJ_P(other)));
	});

	cls.method(sigs::withSite, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TAC_THIS.withSite(marker));
	});

	cls.method(sigs::withSend, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker, *type, *variance;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
			Z_PARAM_OBJECT_OF_CLASS(variance, pt_ce_template_type_variance)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TAC_THIS.withFact(marker, type, variance, false));
	});

	cls.method(sigs::withLowerBound, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker, *type;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_class(PT_CLASS_TYPE))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TAC_THIS.withFact(marker, type, NULL, false));
	});

	cls.method(sigs::withUnconstrainingSend, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *marker;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(marker, pt_ce_unresolved_template_argument_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_TAC_THIS.withFact(marker, NULL, NULL, true));
	});

	cls.method<&TemplateArgumentConstraints::getFacts>(sigs::getFacts);

	cls.shadow(&pt_ce_template_argument_constraints);
}

/* }}} */
