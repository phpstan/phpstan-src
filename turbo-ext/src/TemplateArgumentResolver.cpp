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

/* TemplateArgumentStats::increment($counter, $by); false = pending exception */
[[nodiscard]] bool statsIncrement(const char *counter, size_t len, zend_long by)
{
	zval argv[2];
	ZVAL_STRINGL(&argv[0], counter, len);
	ZVAL_LONG(&argv[1], by);
	zv::Val result = pt_call_static_cached(pt_tar_stats_increment_site, PT_CLASS_TEMPLATE_ARGUMENT_STATS, PT_LC("increment"), 2, argv);
	zval_ptr_dtor(&argv[0]);
	return !result.isUndef();
}

/* TemplateArgumentStats::increment('sitesCreated' / 'closureSitesCreated');
 * false = pending exception */
[[nodiscard]] bool statsIncrementSitesCreated(bool closureSite)
{
	zval counter;
	if (closureSite) {
		ZVAL_STRINGL(&counter, "closureSitesCreated", sizeof("closureSitesCreated") - 1);
	} else {
		ZVAL_STRINGL(&counter, "sitesCreated", sizeof("sitesCreated") - 1);
	}
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
	/* Mirrors resolve(); $closureSignatureBody / $closureSignatureStmts NULL
	 * for null / [] */
	static zv::Val resolve(zval *constraints, zval *parent, zval *statementStartTokenPositions, zval *closureSignatureBody, zval *closureSignatureStmts)
	{
		State state{zv::Arr::create(0), zv::Arr::create(0), zv::Arr::create(0), zv::Arr::create(0), statementStartTokenPositions, false};
		if (UNEXPECTED(!pt_template_argument_constraints_facts(constraints, &fact, &state))) return zv::Val();
		zv::Val resolutions = solve(state.observations.raw(), parent);
		if (UNEXPECTED(resolutions.isUndef())) return zv::Val();
		zv::Val settledClosureSites = settleClosureSites(state.observations.raw(), resolutions.raw());
		if (UNEXPECTED(settledClosureSites.isUndef())) return zv::Val();

		if (zend_hash_num_elements(Z_ARRVAL_P(settledClosureSites.raw())) < zend_hash_num_elements(state.closureSiteIds.table())) {
			bool changed;
			if (UNEXPECTED(!hasChangedTemplateArgument(state.observations.raw(), resolutions.raw(), changed))) return zv::Val();
			if (changed) {
				zv::Arr templateSiteStatementIndexes = zv::Arr::create(0);
				for (auto entry : zv::ArrRef(state.siteIndexes.raw())) {
					if (zend_hash_index_exists(state.closureSiteIds.table(), entry.indexKey())) continue;
					zval marked;
					ZVAL_TRUE(&marked);
					zend_hash_index_update(templateSiteStatementIndexes.table(), (zend_ulong) Z_LVAL_P(entry.value().raw()), &marked);
				}
				/* array_diff_key($resolutions, $closureKeys) */
				zval templateResolutions;
				ZVAL_ARR(&templateResolutions, zend_array_dup(Z_ARRVAL_P(resolutions.raw())));
				zv::Val templateResolutionsHolder = zv::Val::adopt(templateResolutions);
				for (auto entry : zv::ArrRef(state.observations.raw())) {
					zval *marker = observationMarker(entry.value().deref().raw());
					if (UNEXPECTED(marker == NULL)) continue;
					bool closureMarker;
					if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(marker, closureMarker))) return zv::Val();
					if (!closureMarker) continue;
					zend_string *key = entry.stringKeyOrNull();
					if (key != NULL) {
						zend_symtable_del(Z_ARRVAL_P(templateResolutionsHolder.raw()), key);
					} else {
						zend_hash_index_del(Z_ARRVAL_P(templateResolutionsHolder.raw()), entry.indexKey());
					}
				}
				bool enabled;
				if (UNEXPECTED(!statsEnabled(enabled))) return zv::Val();
				if (enabled && UNEXPECTED(!statsIncrement(PT_LC("closureObservationPasses"), 1))) return zv::Val();

				return pt_template_argument_frame_new(parent, templateResolutionsHolder.raw(), templateSiteStatementIndexes.raw(), closureSignatureBody, closureSignatureStmts, NULL, true);
			}
		}

		if (UNEXPECTED(!countSettled(settledClosureSites.raw()))) return zv::Val();
		zv::Val siteStatementIndexes = collectSiteStatementIndexes(state.siteIndexes.raw(), settledClosureSites.raw());
		zv::Val byRefSites = collectByRefSites(state.observations.raw(), state.siteIndexes.raw());
		if (UNEXPECTED(byRefSites.isUndef())) return zv::Val();
		return pt_template_argument_frame_new(parent, resolutions.raw(), siteStatementIndexes.raw(), closureSignatureBody, closureSignatureStmts, settledClosureSites.raw(), false, byRefSites.raw());
	}

	/* Mirrors resolveObservedClosures(). */
	static zv::Val resolveObservedClosures(zval *constraints, zval *frame, zval *statementStartTokenPositions)
	{
		State state{zv::Arr::create(0), zv::Arr::create(0), zv::Arr::create(0), zv::Arr::create(0), statementStartTokenPositions, true};
		if (UNEXPECTED(!pt_template_argument_constraints_facts(constraints, &fact, &state))) return zv::Val();
		zv::Val resolutions = solve(state.observations.raw(), frame);
		if (UNEXPECTED(resolutions.isUndef())) return zv::Val();
		zv::Val settledClosureSites = settleClosureSites(state.observations.raw(), resolutions.raw());
		if (UNEXPECTED(settledClosureSites.isUndef())) return zv::Val();
		if (UNEXPECTED(!countSettled(settledClosureSites.raw()))) return zv::Val();
		zv::Val siteStatementIndexes = collectSiteStatementIndexes(state.siteIndexes.raw(), settledClosureSites.raw());
		zv::Val byRefSites = collectByRefSites(state.observations.raw(), state.siteIndexes.raw());
		if (UNEXPECTED(byRefSites.isUndef())) return zv::Val();

		return pt_template_argument_frame_with_observed_closures(frame, resolutions.raw(), siteStatementIndexes.raw(), settledClosureSites.raw(), byRefSites.raw());
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
	/* (new TemplateArgumentSolver($observations, $parent))->solve() - [] for
	 * no observations whatever the parent */
	static zv::Val solve(zval *observations, zval *parent)
	{
		zv::Val resolutions;
		if (zend_hash_num_elements(Z_ARRVAL_P(observations)) == 0) {
			resolutions = zv::Val(zv::Arr::empty());
		} else {
			zval nullParent;
			ZVAL_NULL(&nullParent);
			zv::Args argv{observations, parent != NULL ? parent : &nullParent};
			zv::Val solver = pt_type_new(PT_CLASS_TEMPLATE_ARGUMENT_SOLVER, 2, argv);
			if (UNEXPECTED(solver.isUndef())) return zv::Val();
			resolutions = pt_call_method_cached(pt_tar_solve_site, Z_OBJ_P(solver.raw()), PT_LC("solve"), 0, NULL);
			if (UNEXPECTED(resolutions.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(resolutions.raw()) != IS_ARRAY)) {
			zend_type_error("PHPStan\\Analyser\\Generics\\TemplateArgumentFrame::__construct(): Argument #2 ($resolutions) must be of type ?array, %s given", zend_zval_value_name(resolutions.raw()));
			return zv::Val();
		}
		return resolutions;
	}

	/* $observation['marker'] (borrowed); NULL for none */
	static zval *observationMarker(zval *observation)
	{
		zval *marker = Z_TYPE_P(observation) == IS_ARRAY ? zend_hash_find(Z_ARRVAL_P(observation), pt_tar_marker) : NULL;
		if (marker == NULL) return NULL;
		ZVAL_DEREF(marker);
		return marker;
	}

	/* $resolutions[$key] ?? null for the observation entry (borrowed); NULL for none */
	static zval *resolutionOf(zval *resolutions, const zv::ArrayEntry &entry)
	{
		zend_string *key = entry.stringKeyOrNull();
		zval *resolution = key != NULL ? zend_symtable_find(Z_ARRVAL_P(resolutions), key) : zend_hash_index_find(Z_ARRVAL_P(resolutions), entry.indexKey());
		if (resolution == NULL) return NULL;
		ZVAL_DEREF(resolution);
		return Z_TYPE_P(resolution) == IS_NULL ? NULL : resolution;
	}

	/* Mirrors settleClosureSites(). */
	static zv::Val settleClosureSites(zval *observations, zval *resolutions)
	{
		zv::Arr settledClosureSites = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(observations)) {
			zval *marker = observationMarker(entry.value().deref().raw());
			if (UNEXPECTED(marker == NULL)) continue;
			bool closureMarker;
			if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(marker, closureMarker))) return zv::Val();
			if (!closureMarker) continue;
			zv::Val site = markerSite(marker);
			if (UNEXPECTED(site.isUndef())) return zv::Val();
			zend_ulong id = Z_OBJ_HANDLE_P(site.raw());
			zval *existing = zend_hash_index_find(settledClosureSites.table(), id);
			if (existing != NULL && Z_TYPE_P(existing) != IS_TRUE) continue;
			zval *resolution = resolutionOf(resolutions, entry);
			bool byRefMarker;
			if (UNEXPECTED(!pt_closure_signature_inference_is_by_ref_marker(marker, byRefMarker))) return zv::Val();
			bool returnMarker = false;
			if (!byRefMarker && UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_return(marker, returnMarker))) return zv::Val();
			bool settled;
			if (byRefMarker) {
				// every invocation seen: the second pass applies the effects where
				// it runs; escaped: the creation-time fixpoint, seeded with the
				// states it was invoked from
				zval *unconstraining = zend_hash_find(Z_ARRVAL_P(entry.value().deref().raw()), pt_tar_unconstraining_send);
				settled = unconstraining != NULL && zend_is_true(unconstraining);
				if (settled && resolution != NULL) {
					zv::Val delegate = pt_type_call(Z_OBJ_P(marker), PT_LC("getdelegate"), 0, NULL);
					if (UNEXPECTED(delegate.isUndef())) return zv::Val();
					zv::Val equal = pt_type_op(Z_OBJ_P(resolution), PT_OP_EQUALS, 1, delegate.raw());
					if (UNEXPECTED(equal.isUndef())) return zv::Val();
					settled = Z_TYPE_P(equal.raw()) == IS_TRUE;
				}
			} else if (returnMarker) {
				settled = resolution == NULL || instanceof_function(Z_OBJCE_P(resolution), pt_ce_mixed_type);
			} else if (resolution == NULL) {
				settled = true;
			} else {
				zv::Val delegate = pt_type_call(Z_OBJ_P(marker), PT_LC("getdelegate"), 0, NULL);
				if (UNEXPECTED(delegate.isUndef())) return zv::Val();
				zv::Val equal = pt_type_op(Z_OBJ_P(resolution), PT_OP_EQUALS, 1, delegate.raw());
				if (UNEXPECTED(equal.isUndef())) return zv::Val();
				settled = Z_TYPE_P(equal.raw()) == IS_TRUE;
			}
			zval settledZv;
			ZVAL_BOOL(&settledZv, settled);
			zend_hash_index_update(settledClosureSites.table(), id, &settledZv);
		}
		zv::Arr settledTrue = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(settledClosureSites.raw())) {
			if (Z_TYPE_P(entry.value().raw()) != IS_TRUE) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_update(settledTrue.table(), entry.indexKey(), &marked);
		}
		return zv::Val(std::move(settledTrue));
	}

	/* Mirrors collectByRefSites(): spl_object_id() of the closure => [the
	 * closure, its statement index, whether no observation of its by-ref
	 * markers escaped] */
	static zv::Val collectByRefSites(zval *observations, zval *siteIndexes)
	{
		zv::Arr byRefSites = zv::Arr::empty();
		for (auto entry : zv::ArrRef(observations)) {
			zval *observation = entry.value().deref().raw();
			zval *marker = observationMarker(observation);
			if (UNEXPECTED(marker == NULL)) continue;
			bool byRefMarker;
			if (UNEXPECTED(!pt_closure_signature_inference_is_by_ref_marker(marker, byRefMarker))) return zv::Val();
			if (!byRefMarker) continue;
			zv::Val site = markerSite(marker);
			if (UNEXPECTED(site.isUndef())) return zv::Val();
			zend_ulong id = Z_OBJ_HANDLE_P(site.raw());
			zval *statementIndex = zend_hash_index_find(Z_ARRVAL_P(siteIndexes), id);
			if (statementIndex == NULL || Z_TYPE_P(statementIndex) == IS_NULL) continue;
			zval *unconstraining = zend_hash_find(Z_ARRVAL_P(observation), pt_tar_unconstraining_send);
			bool local = !(unconstraining != NULL && zend_is_true(unconstraining));
			if (local) {
				zval *existing = zend_hash_index_find(byRefSites.table(), id);
				if (existing != NULL && Z_TYPE_P(existing) == IS_ARRAY) {
					zval *existingLocal = zend_hash_index_find(Z_ARRVAL_P(existing), 2);
					local = existingLocal == NULL || zend_is_true(existingLocal);
				}
			}
			zv::Arr byRefSite = zv::Arr::create(3);
			byRefSite.push(std::move(site));
			byRefSite.push(zv::Ref(statementIndex));
			byRefSite.push(zv::Val::boolean(local));
			byRefSites.separate();
			zval value;
			ZVAL_COPY_VALUE(&value, byRefSite.raw());
			ZVAL_UNDEF(byRefSite.raw());
			zend_hash_index_update(byRefSites.table(), id, &value);
		}
		return zv::Val(std::move(byRefSites));
	}

	/* TemplateArgumentStats::increment('closureSitesSettled', count($settledClosureSites))
	 * when there are any; false = pending exception */
	[[nodiscard]] static bool countSettled(zval *settledClosureSites)
	{
		uint32_t settledCount = zend_hash_num_elements(Z_ARRVAL_P(settledClosureSites));
		if (settledCount == 0) return true;
		bool enabled;
		if (UNEXPECTED(!statsEnabled(enabled))) return false;
		return !enabled || statsIncrement(PT_LC("closureSitesSettled"), settledCount);
	}

	/* Mirrors hasChangedTemplateArgument(); false = pending exception */
	[[nodiscard]] static bool hasChangedTemplateArgument(zval *observations, zval *resolutions, bool &out)
	{
		for (auto entry : zv::ArrRef(observations)) {
			zval *observation = entry.value().deref().raw();
			zval *marker = observationMarker(observation);
			if (UNEXPECTED(marker == NULL)) continue;
			bool closureMarker;
			if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(marker, closureMarker))) return false;
			if (closureMarker) continue;
			zval *resolution = resolutionOf(resolutions, entry);
			if (resolution == NULL) continue;
			/* $observation['initial'] ?? $marker->getDelegate() */
			zval *initial = zend_hash_find(Z_ARRVAL_P(observation), pt_tar_initial);
			if (initial != NULL) ZVAL_DEREF(initial);
			zv::Val delegate;
			if (initial == NULL || Z_TYPE_P(initial) == IS_NULL) {
				delegate = pt_type_call(Z_OBJ_P(marker), PT_LC("getdelegate"), 0, NULL);
				if (UNEXPECTED(delegate.isUndef())) return false;
				initial = delegate.raw();
			}
			zv::Val equal = pt_type_op(Z_OBJ_P(resolution), PT_OP_EQUALS, 1, initial);
			if (UNEXPECTED(equal.isUndef())) return false;
			if (Z_TYPE_P(equal.raw()) != IS_TRUE) {
				out = true;
				return true;
			}
		}
		out = false;
		return true;
	}

	/* Mirrors collectSiteStatementIndexes(). */
	static zv::Val collectSiteStatementIndexes(zval *siteIndexes, zval *settledClosureSites)
	{
		zv::Arr siteStatementIndexes = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(siteIndexes)) {
			if (zend_hash_index_exists(Z_ARRVAL_P(settledClosureSites), entry.indexKey())) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_update(siteStatementIndexes.table(), (zend_ulong) Z_LVAL_P(entry.value().raw()), &marked);
		}
		return zv::Val(std::move(siteStatementIndexes));
	}

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
		zv::Arr siteIndexes; /* spl_object_id($site) => its statement index */
		zv::Arr closureSiteIds; /* spl_object_id($site) => true for the closure sites */
		zval *statementStartTokenPositions;
		bool closuresOnly;
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
		bool closureMarker;
		if (UNEXPECTED(!pt_unresolved_template_argument_type_is_closure_signature(marker, closureMarker))) return false;
		if (state.closuresOnly && !closureMarker) return true;

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
		zval indexZv;
		ZVAL_LONG(&indexZv, index);
		zend_hash_index_update(state.siteIndexes.table(), id, &indexZv);
		if (closureMarker) {
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_update(state.closureSiteIds.table(), id, &marked);
		}

		bool enabled;
		if (UNEXPECTED(!statsEnabled(enabled))) return false;
		if (!enabled || state.closuresOnly) return true;
		return statsIncrementSitesCreated(closureMarker);
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArgumentResolver;

/* {{{ direct entries (support.h) */

zv::Val pt_template_argument_resolver_resolve(zval *resolver, zval *constraints, zval *parent, zval *statementStartTokenPositions, zval *closureSignatureBody, zval *closureSignatureStmts)
{
	if (UNEXPECTED(Z_TYPE_P(resolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function resolve() on %s", zend_zval_value_name(resolver));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_template_argument_resolver && Z_TYPE_P(constraints) == IS_OBJECT && Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(statementStartTokenPositions) == IS_ARRAY)) {
		return TemplateArgumentResolver::resolve(constraints, parent, statementStartTokenPositions, closureSignatureBody, closureSignatureStmts);
	}
	zval nullZv, emptyZv;
	ZVAL_NULL(&nullZv);
	ZVAL_EMPTY_ARRAY(&emptyZv);
	zv::Args argv{constraints, parent != NULL ? parent : &nullZv, statementStartTokenPositions, closureSignatureBody != NULL ? closureSignatureBody : &nullZv, closureSignatureStmts != NULL ? closureSignatureStmts : &emptyZv};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("resolve"), 5, argv);
}

zv::Val pt_template_argument_resolver_resolve_observed_closures(zval *resolver, zval *constraints, zval *frame, zval *statementStartTokenPositions)
{
	if (UNEXPECTED(Z_TYPE_P(resolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function resolveObservedClosures() on %s", zend_zval_value_name(resolver));
		return zv::Val();
	}
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_template_argument_resolver && Z_TYPE_P(constraints) == IS_OBJECT && Z_OBJCE_P(constraints) == pt_ce_template_argument_constraints && Z_TYPE_P(statementStartTokenPositions) == IS_ARRAY)) {
		return TemplateArgumentResolver::resolveObservedClosures(constraints, frame, statementStartTokenPositions);
	}
	zv::Args argv{constraints, frame, statementStartTokenPositions};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("resolveobservedclosures"), 3, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_template_argument_resolver)
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
		zval *constraints, *parent, *statementStartTokenPositions, *closureSignatureBody = NULL, *closureSignatureStmts = NULL;
		ZEND_PARSE_PARAMETERS_START(3, 5)
			Z_PARAM_OBJECT_OF_CLASS(constraints, pt_ce_template_argument_constraints)
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(parent, pt_ce_template_argument_frame)
			Z_PARAM_ARRAY(statementStartTokenPositions)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(closureSignatureBody)
			Z_PARAM_ARRAY(closureSignatureStmts)
		ZEND_PARSE_PARAMETERS_END();
		zval nullParent;
		ZVAL_NULL(&nullParent);
		PT_RETURN_VAL(TemplateArgumentResolver::resolve(constraints, parent != NULL ? parent : &nullParent, statementStartTokenPositions, closureSignatureBody, closureSignatureStmts));
	});

	cls.method(sigs::resolveObservedClosures, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constraints, *frame, *statementStartTokenPositions;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT_OF_CLASS(constraints, pt_ce_template_argument_constraints)
			Z_PARAM_OBJECT_OF_CLASS(frame, pt_ce_template_argument_frame)
			Z_PARAM_ARRAY(statementStartTokenPositions)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TemplateArgumentResolver::resolveObservedClosures(constraints, frame, statementStartTokenPositions));
	});

	cls.shadow(&pt_ce_template_argument_resolver);
}

/* }}} */
