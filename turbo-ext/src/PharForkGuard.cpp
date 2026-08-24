/*
 * PharForkGuard — makes phar:// reads safe across pcntl_fork().
 *
 * libphar keeps one open handle per archive per process and serves every
 * phar:// entry read as a seek-then-read pair on it (a "stream position
 * proxy" over phar->fp). After fork() the parent and every child share the
 * same open file description — one kernel seek cursor — so concurrent reads
 * across the processes interleave and return bytes from wrong offsets,
 * surfacing as parse errors in phar-internal files. php-src has no fix
 * (the running phar's fp is hidden extension state userland cannot reopen).
 *
 * The guard privatizes the cursor without touching phar internals, at the
 * fd level via pthread_atfork():
 *
 *   prepare (parent, pre-fork): find every read-only fd whose backing file
 *   is the registered phar (dev/inode match) and record its cursor. Nothing
 *   runs between prepare and fork, so the recorded cursors are exactly the
 *   fork-time state the child's copied php_stream buffers assume.
 *
 *   child (post-fork): open the phar fresh — a private open file
 *   description — restore the recorded cursor, and dup2 it onto the old fd
 *   number. The php_stream structs are untouched (they hold only the fd
 *   number); the inherited read buffer, stream position and now-private
 *   cursor are exactly as consistent as the parent's were at fork.
 *
 * The parent keeps its own description and is unaffected; each forked child
 * privatizes its own, so no two processes share a cursor. The child hook
 * uses only async-signal-safe calls (open/lseek/dup2/close/fcntl/write) and
 * static storage — it also runs in fork-and-exec children (proc_open),
 * where the swap is harmless because FD_CLOEXEC state is preserved.
 */

#include "support.h"

#ifndef PHP_WIN32

#include <dirent.h>
#include <fcntl.h>
#include <pthread.h>
#include <stdlib.h>
#include <string.h>
#include <sys/stat.h>
#include <unistd.h>

/* More than one fd on the archive would mean phar opened it twice; libphar
 * holds exactly one (phar->fp), so the table is generous already. */
#define PT_PFG_MAX_FDS 8

typedef struct _pt_pfg_entry {
	int fd;
	off_t cursor;
	int fd_flags; /* F_GETFD result, preserves FD_CLOEXEC across the dup2 */
} pt_pfg_entry;

static char pt_pfg_path[PATH_MAX];
static bool pt_pfg_registered = false;
static bool pt_pfg_atfork_installed = false;
static pt_pfg_entry pt_pfg_table[PT_PFG_MAX_FDS];
static int pt_pfg_count = 0;

static void pt_pfg_prepare(void)
{
	pt_pfg_count = 0;
	if (!pt_pfg_registered) {
		return;
	}

	struct stat target;
	if (stat(pt_pfg_path, &target) != 0) {
		return;
	}

	/* /dev/fd is a symlink to /proc/self/fd on Linux and native on the BSDs
	 * and macOS; listing it beats fstat()ing every fd up to the rlimit. */
	DIR *dir = opendir("/dev/fd");
	if (dir == NULL) {
		return;
	}
	int dir_fd = dirfd(dir);

	struct dirent *entry;
	while ((entry = readdir(dir)) != NULL && pt_pfg_count < PT_PFG_MAX_FDS) {
		char *end = NULL;
		long fd = strtol(entry->d_name, &end, 10);
		if (end == entry->d_name || *end != '\0' || fd < 0 || fd == dir_fd) {
			continue;
		}

		struct stat st;
		if (fstat((int) fd, &st) != 0
			|| !S_ISREG(st.st_mode)
			|| st.st_dev != target.st_dev
			|| st.st_ino != target.st_ino) {
			continue;
		}

		/* A write-mode fd would mean someone is rebuilding the archive —
		 * swapping its description out from under them is not ours to do. */
		int fl_flags = fcntl((int) fd, F_GETFL);
		if (fl_flags == -1 || (fl_flags & O_ACCMODE) != O_RDONLY) {
			continue;
		}

		off_t cursor = lseek((int) fd, 0, SEEK_CUR);
		if (cursor == (off_t) -1) {
			continue;
		}

		pt_pfg_table[pt_pfg_count].fd = (int) fd;
		pt_pfg_table[pt_pfg_count].cursor = cursor;
		pt_pfg_table[pt_pfg_count].fd_flags = fcntl((int) fd, F_GETFD);
		pt_pfg_count++;
	}

	closedir(dir);
}

static void pt_pfg_child(void)
{
	for (int i = 0; i < pt_pfg_count; i++) {
		int new_fd = open(pt_pfg_path, O_RDONLY);
		if (new_fd < 0) {
			/* Continuing would corrupt phar reads through the still-shared
			 * cursor; the archive vanishing mid-run is fatal anyway. */
			static const char message[] = "phpstan_turbo: cannot reopen the phar archive in the forked child\n";
			ssize_t ignored = write(2, message, sizeof(message) - 1);
			(void) ignored;
			_exit(70);
		}

		lseek(new_fd, pt_pfg_table[i].cursor, SEEK_SET);
		dup2(new_fd, pt_pfg_table[i].fd);
		close(new_fd);
		if (pt_pfg_table[i].fd_flags != -1) {
			fcntl(pt_pfg_table[i].fd, F_SETFD, pt_pfg_table[i].fd_flags);
		}
	}
	pt_pfg_count = 0;
}

void pt_phar_fork_guard_register(zend_string *path)
{
	if (ZSTR_LEN(path) == 0 || ZSTR_LEN(path) >= sizeof(pt_pfg_path)) {
		return;
	}

	memcpy(pt_pfg_path, ZSTR_VAL(path), ZSTR_LEN(path) + 1);
	pt_pfg_registered = true;

	/* pthread_atfork() registrations cannot be removed, so install once and
	 * gate the hooks on pt_pfg_registered instead. */
	if (!pt_pfg_atfork_installed) {
		pthread_atfork(pt_pfg_prepare, NULL, pt_pfg_child);
		pt_pfg_atfork_installed = true;
	}
}

#else /* PHP_WIN32 */

/* No fork() on Windows — parallel workers are always spawned there. */
void pt_phar_fork_guard_register(zend_string *path)
{
	(void) path;
}

#endif
