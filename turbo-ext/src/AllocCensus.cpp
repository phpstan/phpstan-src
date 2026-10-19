/*
 * Allocation census: with -DPT_ALLOC_COUNT, support.h routes the extension's
 * own object_init_ex(), zend_new_array() and zend_array_dup() calls here, and
 * the counts by class and by call site are written at exit to the file named
 * by $PT_ALLOC_COUNT_OUT (stderr otherwise). An ordinary build compiles this
 * file to nothing.
 */

#ifdef PT_ALLOC_COUNT

#define PT_ALLOC_COUNT_IMPL
#include "support.h"

#include <algorithm>
#include <cstdio>
#include <cstdlib>
#include <dlfcn.h>
#include <map>
#include <string>
#include <vector>

namespace {

std::map<std::string, unsigned long> *counts;

void dump()
{
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &entry : *counts) rows.emplace_back(entry.second, entry.first);
	std::sort(rows.rbegin(), rows.rend());
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	for (const auto &row : rows) fprintf(out, "%10lu %s\n", row.first, row.second.c_str());
	if (out != stderr) fclose(out);
}

void hit(const std::string &key)
{
	if (counts == nullptr) {
		counts = new std::map<std::string, unsigned long>();
		atexit(dump);
	}
	(*counts)[key]++;
}

std::string site(const char *file, int line)
{
	const char *slash = strrchr(file, '/');
	return std::string(slash != nullptr ? slash + 1 : file) + ":" + std::to_string(line);
}

/* the calling function, as an offset into the shared object for atos/addr2line */
std::string caller(void *address)
{
	static char *base = nullptr;
	if (base == nullptr) {
		Dl_info info;
		if (dladdr((void *) &caller, &info) == 0) return "?";
		base = (char *) info.dli_fbase;
	}
	char buffer[32];
	snprintf(buffer, sizeof(buffer), "@%lx", (unsigned long) ((char *) address - base));
	return buffer;
}

} // namespace

zend_result pt_count_object_init_ex(zval *arg, zend_class_entry *ce, const char *file, int line)
{
	hit(std::string("O ") + ZSTR_VAL(ce->name));
	hit(std::string("o ") + site(file, line) + " " + ZSTR_VAL(ce->name));
	hit(std::string("c ") + site(file, line) + " " + ZSTR_VAL(ce->name) + " " + caller(__builtin_return_address(0)));
	return object_init_ex(arg, ce);
}

HashTable *pt_count_new_array(uint32_t size, const char *file, int line)
{
	hit(std::string("A ") + site(file, line));
	hit(std::string("a ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_new_array(size);
}

HashTable *pt_count_array_dup(const HashTable *source, const char *file, int line)
{
	const char *kind = zend_hash_num_elements(source) == 0 ? "E " : "D ";
	hit(kind + site(file, line));
	hit(std::string(kind[0] == 'E' ? "e " : "d ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_array_dup(source);
}

#endif
