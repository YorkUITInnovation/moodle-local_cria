#!/bin/bash

docker buildx build --push \
--platform linux/amd64,linux/arm64 \
--tag uitadmin/cria:latest \
--tag uitadmin/cria:rf_v0.0.4 .
