FROM python:alpine
RUN apk add --no-cache git

ENV PYTHONUNBUFFERED=1

COPY --chmod=+x install_extensions.py /usr/local/bin/install-extensions

ENTRYPOINT ["install-extensions"]