#!/usr/bin/env bash
set -euo pipefail
project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_dir/ai-service"
python_bin="${AI_PYTHON:-$project_dir/ai-service/.venv/bin/python}"
if [[ ! -x "$python_bin" ]]; then
    echo 'Virtualenv ausente: crie ai-service/.venv e instale ai-service/requirements.txt.' >&2
    exit 1
fi
exec "$python_bin" -m uvicorn app.main:app --host 127.0.0.1 --port "${AI_SERVICE_PORT:-8000}" "$@"
