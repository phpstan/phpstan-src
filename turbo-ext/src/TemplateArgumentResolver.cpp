/*
 * PHPStanTurbo\TemplateArgumentResolver — native implementation of
 * PHPStan\Analyser\Generics\TemplateArgumentResolver.
 *
 * The stateless DI service turning a function body's collected template
 * argument facts into the frame of the resolved pass, once per body with
 * observed sites (~12K per self-analysis). The facts are walked in place
 * (no getFacts() list); the observations keep the twin's array shape, which
 * the PHP TemplateArgumentSolver takes. A body whose facts leave no
 * observation needs no solver: solve() over no observations is [] whatever
 * the parent, so the frame is built directly — the case of nearly every
 * body. Native callers use pt_template_argument_resolver_resolve()
 * (support.h).
 */

#include "support.h"
#include "generated/TemplateArgumentResolver.h"
#include "generated/UnresolvedTemplateArgumentType.h"

namespace sigs = ptdecl::TemplateArgumentResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

#include "zend_smart_str.h"

zend_class_entry *pt_ce_template_argument_resolver = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_tar_get_start_token_pos_site;
pt_method_site pt_tar_stats_increment_site;
pt_method_site pt_tar_solve_site;

/* the interned array keys of an observation */
zend_string *pt_tar_marker = nullptr;
zend_string *pt_tar_initial = nullptr;
zend_string *pt_tar_sends = nullptr;
zend_string *pt_tar_lower_bounds = nullptr;
zend_string *pt_tar_unconstraining_send = nullptr;

/* TemplateArgumentStats::$enabled; false = pending exception */
[[nodiscard]] bool statsEnabled(bool &out)
{
	zend_class_entry *ce = pt_class(PT_CLASS_TEMPLATE_ARGUMENT_STATS);
	if (UNEXPECTED(ce == NULL)) return false;
	zval *enabled = zend_read_static_property(ce, PT_LC("enabled"), 0);
	if (UNEXPECTED(enabled == NULL)) return false;
	ZVAL_DEREF(enabled);
	out = Z_TYPE_P(enabled) == IS_TRUE;
	return true;
}

/* TemplateArgumentStats::increment('sitesCreated'); false = pending exception */
[[nodiscard]] bool statsIncrementSitesCreated()
{
	zval counter;
	ZVAL_STRINGL(&counter, "sitesCreated", sizeof("sitesCreated") - 1);
	zv::Val result = pt_call_static_cached(pt_tar_stats_increment_site, PT_CLASS_TEMPLATE_ARGUMENT_STATS, PT_LC("increment"), 1, &counter);
	zval_ptr_dtor(&counter);
	return !result.isUndef();
}

/* $marker->getSite() / ->getInitialType(): the slots of the native class,
 * the methods otherwise */
zv::Val markerSite(zval *marker)
{
	if (EXPECTED(Z_TYPE_P(marker) == IS_OBJECT && Z_OBJCE_P(marker) == pt_ce_unresolved_template_argument_type)) {
		zval *site = OBJ_PROP_NUM(Z_OBJ_P(marker), ptdecl::UnresolvedTemplateArgumentType::slot::site);
		if (EXPECTED(Z_TYPE_P(site) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(site));
	}
	if (UNEXPECTED(Z_TYPE_P(marker) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getSite() on %s", zend_zval_value_name(marker));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(marker), PT_LC("getsite"), 0, NULL);
}

zv::Val markerInitialType(zval *marker)
{
	if (EXPECTED(Z_OBJCE_P(marker) == pt_ce_unresolved_template_argument_type)) {
		zval *initial = OBJ_PROP_NUM(Z_OBJ_P(marker), ptdecl::UnresolvedTemplateArgumentType::slot::initialType);
		if (EXPECTED(Z_TYPE_P(initial) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(initial));
	}
	return pt_type_call(Z_OBJ_P(marker), PT_LC("getinitialtype"), 0, NULL);
}

/* the fact's element (borrowed; the null zval for an absent one) */
zval *factItem(zval *fact, zend_ulong index)
{
	zval *item = Z_TYPE_P(fact) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(fact), index) : NULL;
	if (item == NULL) return &EG(uninitialized_zval);
	ZVAL_DEREF(item);
	return item;
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\TemplateArgumentResolver (stateless);
 * UNDEF = pending exception. */
class TemplateArgumentResolver
{
public:
	/* Mirrors resolve(). */
	static zv::Val resolve(zval *constraints, zval *parent, zval *statementStartTokenPositions)
	{
		State state{zv::Arr::create(0), zv::Arr::create(0), zv::Arr::create(0), statementStartTokenPositions};
		if (UNEXPECTED(!pt_template_argument_constraints_facts(constraints, &fact, &state))) return zv::Val();

		zv::Val resolutions;
		if (zend_hash_num_elements(state.observations.table()) == 0) {
			/* (new TemplateArgumentSolver([], $parent))->solve() */
			resolutions = zv::Val(zv::Arr::empty());
		} else {
			zval nullParent;
			ZVAL_NULL(&nullParent);
			zv::Args argv{state.observations.raw(), parent != NULL ? parent : &nullParent};
			zv::Val solver = pt_type_new(PT_CLASS_TEMPLATE_ARGUMENT_SOLVER, 2, argv);
			if (UNEXPECTED(solver.isUndef())) return zv::Val();
			resolutions = pt_call_method_cached(pt_tar_solve_site, Z_OBJ_P(solver.raw()), PT_LC("solve"), 0, NULL);
			if (UNEXPECTED(resolutions.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(resolutions.raw()) != IS_ARRAY)) {
			zend_type_error("PHPStan\\Analyser\\Generics\\TemplateArgumentFrame::__construct(): Argument #2 ($resolutions) must be of type ?array, %s given", zend_zval_value_name(resolutions.raw()));
			return zv::Val();
		}
		return pt_template_argument_frame_new(parent, resolutions.raw(), state.siteStatementIndexes.raw());
	}

	/* Mirrors locateStatement(). */
	static zend_long locateStatement(zend_long tokenPosition, zval *positions)
	{
		zend_long low = 0;
		zend_long high = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(positions)) - 1;
		while (low < high) {
			zend_long mid = (low + high + 1) >> 1;
			zval *position = zend_hash_index_find(Z_ARRVAL_P(positions), (zend_ulong) mid);
			bool lessOrEqual;
			if (EXPECTED(position != NULL && Z_TYPE_P(position) == IS_LONG)) {
				lessOrEqual = Z_LVAL_P(position) <= tokenPosition;
			} else {
				zval null;
				ZVAL_NULL(&null);
				if (position == NULL) {
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, mid);
					position = &null;
				}
				ZVAL_DEREF(position);
				zval token;
				ZVAL_LONG(&token, tokenPosition);
				lessOrEqual = zend_compare(position, &token) <= 0;
			}
			if (lessOrEqual) {
				low = mid;
			} else {
				high = mid - 1;
			}
		}
		return low;
	}

private:
	/* $observation[$key][] = $value ($value owned); false = pending exception */
	[[nodiscard]] static bool appendTo(HashTable *table, zend_string *key, zval *value)
	{
		zval *list = zend_hash_find(table, key);
		if (UNEXPECTED(list == NULL)) {
			zval empty;
			array_init(&empty);
			list = zend_hash_add_new(table, key, &empty);
		}
		ZVAL_DEREF(list);
		if (UNEXPECTED(Z_TYPE_P(list) != IS_ARRAY)) {
			zval_ptr_dtor(value);
			zend_throw_error(NULL, "Cannot use a scalar value as an array");
			return false;
		}
		SEPARATE_ARRAY(list);
		zend_hash_next_index_insert(Z_ARRVAL_P(list), value);
		return true;
	}

	struct State
	{
		zv::Arr observations;
		zv::Arr sites;
		zv::Arr siteStatementIndexes;
		zval *statementStartTokenPositions;
	};

	/* the foreach body over one [$marker, $type, $variance, $unconstraining]
	 * fact; false = pending exception */
	static bool fact(void *data, zval *factZv)
	{
		State &state = *static_cast<State *>(data);
		zval *marker = factItem(factZv, 0);
		zval *type = factItem(factZv, 1);
		zval *variance = factItem(factZv, 2);
		bool unconstraining = zend_is_true(factItem(factZv, 3));

		/* $key = spl_object_id($marker->getSite()) . '#' . $marker->getTemplateName() */
		zv::Val site = markerSite(marker);
		if (UNEXPECTED(site.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(site.raw()) != IS_OBJECT)) {
			zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(site.raw()));
			return false;
		}
		uint32_t siteId = Z_OBJ_HANDLE_P(site.raw());
		site.release();
		zv::Val templateName = pt_type_call(Z_OBJ_P(marker), PT_LC("gettemplatename"), 0, NULL);
		if (UNEXPECTED(templateName.isUndef())) return false;
		zend_string *nameString = zval_try_get_string(templateName.raw());
		if (UNEXPECTED(nameString == NULL)) return false;
		smart_str keyBuffer = {};
		smart_str_append_unsigned(&keyBuffer, siteId);
		smart_str_appendc(&keyBuffer, '#');
		smart_str_append(&keyBuffer, nameString);
		smart_str_0(&keyBuffer);
		zend_string_release(nameString);
		zv::Str key = zv::Str::adopt(keyBuffer.s);

		zval *existing = zend_symtable_find(state.observations.table(), key.get());
		if (unconstraining && existing == NULL) return true;

		/* $observation = $observations[$key] ?? [...] */
		zv::Val observation;
		if (existing != NULL && Z_TYPE_P(existing) != IS_NULL) {
			observation = zv::Val::copyOf(zv::Ref(existing));
		} else {
			zv::Arr shaped = zv::Arr::create(5);
			zval value;
			ZVAL_COPY(&value, marker);
			zend_hash_add_new(shaped.table(), pt_tar_marker, &value);
			ZVAL_NULL(&value);
			zend_hash_add_new(shaped.table(), pt_tar_initial, &value);
			ZVAL_EMPTY_ARRAY(&value);
			zend_hash_add_new(shaped.table(), pt_tar_sends, &value);
			zend_hash_add_new(shaped.table(), pt_tar_lower_bounds, &value);
			ZVAL_FALSE(&value);
			zend_hash_add_new(shaped.table(), pt_tar_unconstraining_send, &value);
			observation = zv::Val(std::move(shaped));
		}
		SEPARATE_ARRAY(observation.raw());
		HashTable *table = Z_ARRVAL_P(observation.raw());

		zv::Val initial = markerInitialType(marker);
		if (UNEXPECTED(initial.isUndef())) return false;
		if (Z_TYPE_P(initial.raw()) != IS_NULL) {
			zval *current = zend_hash_find(table, pt_tar_initial);
			zval next;
			if (current == NULL || Z_TYPE_P(current) == IS_NULL) {
				ZVAL_COPY(&next, initial.raw());
			} else {
				zv::Args argv{current, initial.raw()};
				zv::Val union_ = pt_type_combinator_union(2, argv);
				if (UNEXPECTED(union_.isUndef())) return false;
				next = union_.take();
			}
			zend_hash_update(table, pt_tar_initial, &next);
		}
		if (unconstraining) {
			zval value;
			ZVAL_TRUE(&value);
			zend_hash_update(table, pt_tar_unconstraining_send, &value);
		} else if (Z_TYPE_P(type) != IS_NULL && Z_TYPE_P(variance) != IS_NULL) {
			/* $observation['sends'][] = [$type, $variance] */
			zval pair;
			array_init_size(&pair, 2);
			Z_TRY_ADDREF_P(type);
			zend_hash_next_index_insert_new(Z_ARRVAL(pair), type);
			Z_TRY_ADDREF_P(variance);
			zend_hash_next_index_insert_new(Z_ARRVAL(pair), variance);
			if (UNEXPECTED(!appendTo(table, pt_tar_sends, &pair))) return false;
		} else if (Z_TYPE_P(type) != IS_NULL) {
			Z_TRY_ADDREF_P(type);
			zval copy;
			ZVAL_COPY_VALUE(&copy, type);
			if (UNEXPECTED(!appendTo(table, pt_tar_lower_bounds, &copy))) return false;
		}
		zval stored = observation.take();
		zend_symtable_update(state.observations.table(), key.get(), &stored);
		if (Z_TYPE_P(type) != IS_NULL || unconstraining) return true;

		/* $site = $marker->getSite(); $id = spl_object_id($site); */
		zv::Val siteAgain = markerSite(marker);
		if (UNEXPECTED(siteAgain.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(siteAgain.raw()) != IS_OBJECT)) {
			zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(siteAgain.raw()));
			return false;
		}
		zend_ulong id = Z_OBJ_HANDLE_P(siteAgain.raw());
		if (zend_hash_index_exists(state.sites.table(), id)) return true;
		/* $sites[$id] = $site */
		zend_object *siteObject = Z_OBJ_P(siteAgain.raw());
		zval siteCopy = siteAgain.take();
		zend_hash_index_add_new(state.sites.table(), id, &siteCopy);

		zv::Val startTokenPos = pt_call_method_cached(pt_tar_get_start_token_pos_site, siteObject, PT_LC("getstarttokenpos"), 0, NULL);
		if (UNEXPECTED(startTokenPos.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(startTokenPos.raw()) != IS_LONG)) {
			zend_type_error("PHPStan\\Analyser\\Generics\\TemplateArgumentResolver::locateStatement(): Argument #1 ($tokenPosition) must be of type int, %s given", zend_zval_value_name(startTokenPos.raw()));
			return false;
		}
		zend_long index = locateStatement(Z_LVAL_P(startTokenPos.raw()), state.statementStartTokenPositions);
		zval marked;
		ZVAL_TRUE(&marked);
		zend_hash_index_update(state.siteStatementIndexes.table(), (zend_ulong) index, &marked);

		bool enabled;
		if (UNEXPECTED(!statsEnabled(enabled))) return false;
		if (!enabled) return true;
		return statsIncrementSitesCreated();
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArgumentResolver;

/* {{{ direct entries (support.h) */

zv::Val pt_template_argument_resolver_resolve(zval *resolver, zval *constraints, zval *parent, zval *statementStartTokenPositions)
{
	if (UNEXPECTED(Z_TYPE_P(resolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function resolve() on %s", zend_zval_value_name(resolver));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_template_argument_resolver && Z_TYPE_P(constraints) == IS_OBJECT && Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(statementStartTokenPositions) == IS_ARRAY)) {
		return TemplateArgumentResolver::resolve(constraints, parent, statementStartTokenPositions);
	}
	zval nullParent;
	ZVAL_NULL(&nullParent);
	zv::Args argv{constraints, parent != NULL ? parent : &nullParent, statementStartTokenPositions};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("resolve"), 3, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_template_argument_resolver()
{
	pt_tar_marker = zend_string_init_interned(PT_LC("marker"), 1);
	pt_tar_initial = zend_string_init_interned(PT_LC("initial"), 1);
	pt_tar_sends = zend_string_init_interned(PT_LC("sends"), 1);
	pt_tar_lower_bounds = zend_string_init_interned(PT_LC("lowerBounds"), 1);
	pt_tar_unconstraining_send = zend_string_init_interned(PT_LC("unconstrainingSend"), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\TemplateArgumentResolver");
	ptdecl::TemplateArgumentResolver::declareClass(cls);
	ptdecl::TemplateArgumentResolver::declareProperties(cls);

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constraints, *parent, *statementStartTokenPositions;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(constraints, pt_ce_template_argument_constraints)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(parent, pt_ce_template_argument_frame)
			Z_PARAM_ARRAY(statementStartTokenPositions)
		ZEND_PARSE_PARAMETERS_END();
		zval nullParent;
		ZVAL_NULL(&nullParent);
		PT_RETURN_VAL(TemplateArgumentResolver::resolve(constraints, parent != NULL ? parent : &nullParent, statementStartTokenPositions));
	});

	cls.shadow(&pt_ce_template_argument_resolver);
}

/* }}} */
