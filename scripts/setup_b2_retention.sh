#!/bin/sh
set -e

SCRIPT_DIR=$(dirname "$0")
ROOT_DIR="${SCRIPT_DIR}/.."

docker run \
    --rm \
    --volume "$(cd "${SCRIPT_DIR}" && pwd)/setup_b2_retention.py:/work/setup_b2_retention.py" \
    --volume "$(cd "${ROOT_DIR}" && pwd)/.env:/work/.env" \
    --workdir /work \
    python:alpine sh -c "pip install --quiet --root-user-action=ignore b2sdk python-dotenv && python setup_b2_retention.py"