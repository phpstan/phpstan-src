/*
 * PHPStanTurbo\CombinationsHelper — native implementation of
 * PHPStan\Internal\CombinationsHelper::combinations() (Cartesian product).
 */

#include "support.h"
#include "generated/CombinationsHelper.h"
#include "zv.h"
#include "TypeTraits.h"

static zend_class_entry *pt_ce_combinations = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Internal\CombinationsHelper. PHP-visible calls use the
 * twin's lazy generator helper; native consumers walk the same product one
 * combination at a time without materializing it. */
class CombinationsHelper
{
public:
	static zv::Val combinations(zval *arrays)
	{
		return pt_type_call_static(PT_CLASS_ITERABLE_HELPER, PT_LC("combinations"), 1, arrays);
	}

	/* false = the consumer stopped or a pending exception */
	static bool forEach(zv::ArrRef arrays, pt_combination_consumer consumer, void *context)
	{
		uint32_t n = arrays.size();
		if (n == 0) {
			zv::Arr combination = zv::Arr::create(0);
			return consumer(combination.raw(), context);
		}

		/* borrow the inner array of each element (the input owns them) */
		zval **inner = (zval **) emalloc(n * sizeof(zval *));
		uint32_t i = 0;
		for (auto entry : arrays) {
			zv::Ref element = entry.value().deref();
			if (UNEXPECTED(!element.isArray())) {
				efree(inner);
				zend_type_error("PHPStanTurbo\\CombinationsHelper::combinations() expects an array of arrays");
				return false;
			}
			inner[i++] = element.raw();
		}

		/* flatten each inner array into a raw element-slot vector */
		uint32_t *sizes = (uint32_t *) emalloc(n * sizeof(uint32_t));
		zval ***vecs = (zval ***) emalloc(n * sizeof(zval **));
		bool hasEmptyInner = false;

		for (i = 0; i < n; i++) {
			zv::ArrRef innerArr(inner[i]);
			sizes[i] = innerArr.size();
			if (sizes[i] == 0) {
				/* any empty input array empties the whole product */
				hasEmptyInner = true;
				vecs[i] = NULL;
				continue;
			}
			vecs[i] = (zval **) emalloc(sizes[i] * sizeof(zval *));
			uint32_t j = 0;
			for (auto entry : innerArr) {
				/* deref like the twin's by-value foreach: a reference slot
				 * must not propagate a shared reference into every combination */
				vecs[i][j++] = entry.value().deref().raw();
			}
		}

		bool completed = true;
		if (!hasEmptyInner) {
			uint32_t *indices = (uint32_t *) ecalloc(n, sizeof(uint32_t));
			for (;;) {
				zv::Arr combination = zv::Arr::create(n);
				for (i = 0; i < n; i++) {
					combination.push(zv::Ref(vecs[i][indices[i]]));
				}
				if (!consumer(combination.raw(), context)) {
					completed = false;
					break;
				}

				int64_t j = (int64_t) n - 1;
				for (; j >= 0; j--) {
					if (++indices[j] < sizes[j]) break;
					indices[j] = 0;
				}
				if (j < 0) break;
			}
			efree(indices);
		}

		for (i = 0; i < n; i++) {
			if (vecs[i] != NULL) {
				efree(vecs[i]);
			}
		}
		efree(vecs);
		efree(sizes);
		efree(inner);
		return completed;
	}
};

} // namespace phpstanturbo

using phpstanturbo::CombinationsHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_combinations_helper()
{
	reg::Class cls("PHPStan\\Internal\\CombinationsHelper");
	ptdecl::CombinationsHelper::declareClass(cls);
	ptdecl::CombinationsHelper::declareProperties(cls);

	cls.method("combinations", reg::PublicStatic, 1, { reg::arrayArg("arrays") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *arrays;
		if (!zp::parse<zp::Ht>(execute_data, arrays)) RETURN_THROWS();
		zval arraysZv;
		ZVAL_ARR(&arraysZv, arrays);
		zv::Val result = CombinationsHelper::combinations(&arraysZv);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_combinations);
}

/* }}} */

/* {{{ shared with the compound family (TypeTraits.h) */

bool pt_combinations_helper_for_each(zval *arrays, pt_combination_consumer consumer, void *context)
{
	return CombinationsHelper::forEach(zv::ArrRef(arrays), consumer, context);
}

/* }}} */
