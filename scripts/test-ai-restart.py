#!/usr/bin/env python3
"""Isolated real-process restart smoke test; no database, credentials or business writes."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
TOKEN = 'restart-test-only-' + 'x' * 48


def request(url):
    with urllib.request.urlopen(url, timeout=5) as response:
        return json.load(response)


def wait_ready(url, process):
    for _ in range(200):
        if process.poll() is not None:
            raise RuntimeError('Processo de teste encerrou antes de ficar pronto.')
        try:
            return request(url)
        except Exception:
            time.sleep(.1)
    raise RuntimeError('Timeout iniciando processo de teste.')


def stop(process):
    if process:
        process.terminate()
        process.wait(timeout=10)


with tempfile.TemporaryDirectory(prefix='ai-restart-') as directory:
    temporary = Path(directory)
    env = dict(os.environ, AI_SERVICE_TOKEN=TOKEN, AI_SERVICE_PORT='18000',
        AI_SERVICE_URL='http://127.0.0.1:18000', APP_ENV='production', APP_DEBUG='false',
        APP_CONFIG_CACHE=str(temporary / 'config.php'))
    router = temporary / 'router.php'
    router.write_text('''<?php
require %s;
$app = require %s;
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/up') {
    header('Content-Type: application/json'); echo json_encode(['status'=>'ok']); return;
}
try {
    $client = Illuminate\\Support\\Facades\\Http::baseUrl(config('ai_evaluation.service_url'))
        ->withToken(config('ai_evaluation.token'))->timeout(3);
    $health = $client->get('/health')->status();
    $valid = $client->get('/v1/diagnostics')->status();
    $invalid = $client->withToken('invalid-test-token')->get('/v1/diagnostics')->status();
    header('Content-Type: application/json');
    echo json_encode(['health'=>$health, 'valid'=>$valid, 'invalid'=>$invalid]);
} catch (Throwable) { http_response_code(503); echo '{}'; }
''' % (json.dumps(str(ROOT / 'vendor/autoload.php')), json.dumps(str(ROOT / 'bootstrap/app.php'))))
    fastapi = laravel = None
    with (temporary / 'process.log').open('w+') as log:
        try:
            for cached in (False, True):
                if cached:
                    result = subprocess.run(['php', 'artisan', 'config:cache'], cwd=ROOT, env=env, capture_output=True)
                    if result.returncode:
                        raise RuntimeError('Falha ao criar cache isolado de configuração.')
                fastapi = subprocess.Popen([str(ROOT / 'scripts/ai-service.sh')], cwd=temporary, env=env, stdout=log, stderr=log)
                wait_ready('http://127.0.0.1:18000/health', fastapi)
                laravel = subprocess.Popen(['php', '-S', '127.0.0.1:18001', str(router)], cwd=ROOT, env=env, stdout=log, stderr=log)
                wait_ready('http://127.0.0.1:18001/up', laravel)
                expected = {'health':200, 'valid':200, 'invalid':401}
                assert request('http://127.0.0.1:18001/probe') == expected
                stop(fastapi); fastapi = None
                try:
                    request('http://127.0.0.1:18001/probe')
                    raise AssertionError('Offline não detectado.')
                except urllib.error.HTTPError as error:
                    assert error.code == 503
                fastapi = subprocess.Popen([str(ROOT / 'scripts/ai-service.sh')], cwd=temporary, env=env, stdout=log, stderr=log)
                wait_ready('http://127.0.0.1:18000/health', fastapi)
                assert request('http://127.0.0.1:18001/probe') == expected
                stop(laravel); laravel = None
                laravel = subprocess.Popen(['php', '-S', '127.0.0.1:18001', str(router)], cwd=ROOT, env=env, stdout=log, stderr=log)
                wait_ready('http://127.0.0.1:18001/up', laravel)
                assert request('http://127.0.0.1:18001/probe') == expected
                print(f'config_cache={cached}: FastAPI restart PASS; Laravel restart PASS; token remained valid PASS; offline/401 PASS')
                stop(fastapi); fastapi = None
                stop(laravel); laravel = None
            log.flush(); log.seek(0)
            assert TOKEN not in log.read()
            print('Process logs: token absent PASS')
        finally:
            stop(fastapi)
            stop(laravel)
