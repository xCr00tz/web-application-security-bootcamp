FROM ghcr.io/concrete5-community/docker5:base

ARG CCM_PHP_VERSION=8.1
ARG CCM_COMPOSER_VERSION=2
ARG CCM_C5_ARCHIVE=https://github.com/concretecms/concretecms/archive/refs/tags/8.5.4.tar.gz
ARG CCM_STARTING_POINT=atomik_full

COPY build /

RUN /build && rm /build
