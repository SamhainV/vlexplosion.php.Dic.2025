#!/usr/bin/env python3
"""Disposable MariaDB, explicit Unix socket, no defaults, no production config."""
import argparse
import os
import pathlib
import pwd
import shutil
import subprocess
import time
import uuid
import socket
import signal

ROOT = pathlib.Path(__file__).resolve().parents[2]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--empty-schema-check', action='store_true', help='Verify the empty current schema and 005 in an isolated database')
    parser.add_argument('--with-legacy-ai', action='store_true', help='Also apply existing migrations 001/002 before 003')
    parser.add_argument('--serve', action='store_true', help='Keep the isolated development application open')
    parser.add_argument('--web-checks', action='store_true', help='Run all regressions plus new browser checks using explicit fictional HTTP fixtures')
    parser.add_argument('--web-only', action='store_true', help='Run only the new collection/browser checks with fictional metadata')
    parser.add_argument('--live-web-checks', action='store_true', help='Exercise the new browser flow with real MusicBrainz requests')
    parser.add_argument('--port', type=int, default=8094)
    options = parser.parse_args()
    if options.web_only: options.web_checks = True
    if not 1024 <= options.port <= 65535:
        raise RuntimeError('Invalid local port')
    if options.serve or options.web_checks or options.live_web_checks:
        with socket.socket() as probe:
            try: probe.bind(('127.0.0.1', options.port))
            except OSError as error: raise RuntimeError('Puerto local no disponible. Elige otro con --port; no se ha detenido ningún servicio.') from error
    binaries = {}
    for name in ('mariadb-install-db', 'mariadbd', 'mariadb', 'php', 'python3'):
        binary = shutil.which(name, path='/usr/bin:/bin:/usr/sbin:/sbin')
        if not binary:
            raise RuntimeError('Required executable missing: ' + name)
        binaries[name] = binary
    runtime = ROOT / 'tests/runtime' / ('db-enc-' + uuid.uuid4().hex[:8])
    runtime.mkdir(parents=True, mode=0o700)
    sock = runtime / 's.sock'
    if len(str(sock).encode()) > 100:
        raise RuntimeError('Socket path too long')
    env = {
        'PATH': os.environ.get('PATH', '/usr/bin:/bin:/usr/sbin:/sbin'),
        **{k: os.environ[k] for k in ('NODE_PATH','VLEX_TEST_BROWSER') if k in os.environ},
        'LANG': 'C.UTF-8', 'TMPDIR': str(runtime),
        'PYTHONDONTWRITEBYTECODE': '1',
        'VLEXPLOSION_TEST_SOCKET': str(sock),
        'VLEXPLOSION_TEST_RUN': runtime.name,
        'VLEX_AI_ENABLED': '0', 'DB_ENABLED': '0',
    }
    # Only synthetic files generated for the existing regression suite.
    from PIL import Image
    for fmt, ext in [('JPEG', 'jpg'), ('PNG', 'png'), ('WEBP', 'webp')]:
        Image.new('RGB', (16, 16), (25, 90, 50)).save(runtime / ('fixture.' + ext), format=fmt)
    with (runtime / 'initialize.log').open('x') as log:
        subprocess.run([
            binaries['mariadb-install-db'], '--no-defaults', '--datadir=' + str(runtime),
            '--auth-root-authentication-method=normal', '--skip-test-db', '--tmpdir=' + str(runtime),
        ], cwd=ROOT, env=env, stdout=log, stderr=log, check=True)
    args = [
        binaries['mariadbd'], '--no-defaults', '--datadir=' + str(runtime),
        '--socket=' + str(sock), '--pid-file=' + str(runtime / 'server.pid'),
        '--log-error=' + str(runtime / 'server.log'), '--tmpdir=' + str(runtime),
        '--skip-networking', '--skip-log-bin', '--innodb-buffer-pool-size=32M',
        '--user=' + pwd.getpwuid(os.geteuid()).pw_name,
    ]
    client = [binaries['mariadb'], '--no-defaults', '--protocol=socket', '--socket=' + str(sock), '-uroot', '--batch', '--skip-column-names']
    with (runtime / 'process.log').open('x') as log:
        server = subprocess.Popen(args, cwd=ROOT, env=env, stdout=log, stderr=log)
        try:
            for _ in range(100):
                if server.poll() is not None:
                    raise RuntimeError('Isolated MariaDB failed; see ' + str(runtime))
                if sock.exists():
                    probe = subprocess.run(client + ['-e', 'SELECT @@datadir,@@socket,@@skip_networking'], env=env, capture_output=True, text=True)
                    if probe.returncode == 0:
                        actual_dir, actual_socket, isolated = probe.stdout.strip().split('\t')
                        if pathlib.Path(actual_dir).resolve() != runtime or pathlib.Path(actual_socket).resolve() != sock or isolated != '1':
                            raise RuntimeError('Isolation verification failed')
                        break
                time.sleep(.1)
            else:
                raise RuntimeError('Isolated MariaDB startup timeout')
            print('ISOLATION VERIFIED: datadir=' + str(runtime) + '; socket=' + str(sock) + '; TCP=OFF', flush=True)
            if options.empty_schema_check:
                name = 'vlexplosion_schema_test'
                subprocess.run(client + ['-e', 'CREATE DATABASE ' + name + ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'], env=env, check=True)
                for file in ('database/schema-pre005.sql', 'migrations/005_encyclopedia_instruments_up.sql'):
                    with (ROOT / file).open() as source:
                        subprocess.run(client + [name], stdin=source, env=env, check=True)
                def query(sql):
                    return subprocess.check_output(client + [name, '-e', sql], env=env, text=True).strip()
                tables = query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME').splitlines()
                if len(tables) != 31: raise RuntimeError('Expected 31 empty tables')
                if any(query('SELECT COUNT(*) FROM `' + table + '`') != '0' for table in tables):
                    raise RuntimeError('Schema unexpectedly contains records')
                column = query("SELECT COLUMN_TYPE,IS_NULLABLE,CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ENC_CREDITS' AND COLUMN_NAME='instrument'")
                if column != 'varchar(120)\tYES\t120': raise RuntimeError('Unexpected instrument definition')
                print('OK: 31 empty tables; 005 instrument VARCHAR(120) NULL; no personal records.', flush=True)
                return
            migration_files = ['tests/schema.sql']
            if options.with_legacy_ai or options.serve or options.web_checks or options.live_web_checks:
                migration_files += ['migrations/001_album_copy_up.sql', 'migrations/002_legacy_albums.sql']
            migration_files += ['migrations/003_encyclopedia.sql', 'migrations/004_encyclopedia_web.sql','migrations/005_encyclopedia_instruments_up.sql']
            for file in migration_files:
                with (ROOT / file).open() as source:
                    command = client + ([] if file == 'tests/schema.sql' else ['vlexplosion_test'])
                    subprocess.run(command, cwd=ROOT, env=env, stdin=source, check=True)
            commands = [
                [binaries['php'], 'tests/encyclopedia/database.php'],
                [binaries['php'], 'tests/encyclopedia/metadata.php'],
                [binaries['php'], 'tests/encyclopedia/formats.php'],
                [binaries['php'], 'tests/security.php'],
                [binaries['php'], 'tests/covers.php'],
                [binaries['php'], 'tests/database.php'],
                [binaries['python3'], '-B', 'tests/http_tests.py'],
            ]
            if options.serve or options.live_web_checks or options.web_only:
                commands = []
            if options.web_checks:
                commands.append([binaries['php'], 'tests/encyclopedia/collection.php'])
                commands.append([binaries['php'], 'tests/encyclopedia/workflows.php'])
            with (runtime / 'results.txt').open('x') as results:
                for command in commands:
                    result = subprocess.run(command, cwd=ROOT, env=env, capture_output=True, text=True)
                    output = '$ ' + ' '.join(command) + '\n' + result.stdout + result.stderr
                    print(output, end='', flush=True)
                    results.write(output + 'exit=' + str(result.returncode) + '\n')
                    results.flush()
                    if result.returncode:
                        raise RuntimeError('Test failed; report: ' + str(runtime / 'results.txt'))
                    if command[-1] == 'tests/encyclopedia/database.php':
                        # The existing HTTP fixture expects Id 1. Only this new,
                        # verified database is affected; no table is truncated.
                        subprocess.run(client + ['vlexplosion_test', '-e', 'ALTER TABLE VINYLS_TBL AUTO_INCREMENT=1'], env=env, check=True)
            if options.serve or options.web_checks or options.live_web_checks:
                env.update(VLEX_ENC_ENABLED='1', VLEX_ENC_PORT=str(options.port), VLEX_ENC_FIXTURE='1' if options.web_checks else '0')
                subprocess.run([binaries['php'], 'tests/encyclopedia/seed.php'], cwd=ROOT, env=env, check=True)
                web_args = [binaries['php'], '-d', 'upload_tmp_dir=' + str(runtime), '-d', 'sys_temp_dir=' + str(runtime),
                            '-S', '127.0.0.1:' + str(options.port), '-t', str(ROOT / 'public'), str(ROOT / 'tools/encyclopedia_router.php')]
                with (runtime / 'web.log').open('x') as web_log:
                    web = subprocess.Popen(web_args, cwd=ROOT, env=env, stdout=web_log, stderr=web_log)
                    try:
                        for _ in range(50):
                            if web.poll() is not None: raise RuntimeError('Development server could not start')
                            try:
                                with socket.create_connection(('127.0.0.1', options.port), timeout=.1): break
                            except OSError: time.sleep(.1)
                        else: raise RuntimeError('Development server timed out')
                        print('VLexPlosion Enciclopedia: http://127.0.0.1:' + str(options.port) + '/ · fiction / fictional-password', flush=True)
                        print('MusicBrainz: ' + ('FICTIONAL FIXTURES' if options.web_checks else 'REAL READ-ONLY API') + '; collection: FICTIONAL', flush=True)
                        print('Runtime: ' + str(runtime), flush=True)
                        if options.serve:
                            print('Ctrl+C stops only these development processes. Generated data remain on disk.', flush=True)
                            web.wait()
                        else:
                            env.update(VLEX_ENC_BASE='http://127.0.0.1:' + str(options.port), VLEX_ENC_RUNTIME=str(runtime), VLEX_ENC_LIVE='1' if options.live_web_checks else '0')
                            node = shutil.which('node') or 'node'
                            result = subprocess.run([node, 'tests/encyclopedia/browser.cjs'], cwd=ROOT, env=env, capture_output=True, text=True)
                            with (runtime / 'web-results.txt').open('x') as report:
                                report.write(result.stdout + result.stderr)
                            print(result.stdout + result.stderr, flush=True)
                            if result.returncode: raise RuntimeError('New browser checks failed; see ' + str(runtime / 'web-results.txt'))
                    finally:
                        web.terminate()
                        try: web.wait(timeout=10)
                        except subprocess.TimeoutExpired: web.kill(); web.wait()
            print('PASS. Results: ' + str(runtime / 'results.txt'), flush=True)
        finally:
            # Only the child launched above. Existing data directories are retained.
            server.terminate()
            try:
                server.wait(timeout=10)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait()


if __name__ == '__main__':
    signal.signal(signal.SIGTERM, lambda *_: (_ for _ in ()).throw(KeyboardInterrupt()))
    try:
        main()
    except KeyboardInterrupt:
        print('Development processes stopped. Test data retained.')
