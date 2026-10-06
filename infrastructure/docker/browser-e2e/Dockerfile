FROM python:3.14.7-slim-bookworm

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

COPY tests/browser/requirements.txt /tmp/aurevia-browser-e2e-requirements.txt

RUN python -m pip install --no-cache-dir -r /tmp/aurevia-browser-e2e-requirements.txt \
    && python -m playwright install --with-deps chromium \
    && rm -f /tmp/aurevia-browser-e2e-requirements.txt

WORKDIR /workspace
COPY tests/browser/ /workspace/

CMD ["python", "-m", "pytest"]
