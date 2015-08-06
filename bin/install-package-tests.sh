#!/usr/bin/env bash

set -ex

PACKAGE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/../ && pwd )"

install_wp_cli() {
	mkdir -p /tmp/wp-cli-phar
	wget https://github.com/wp-cli/builds/raw/gh-pages/phar/wp-cli-nightly.phar
	mv wp-cli-nightly.phar /tmp/wp-cli-phar/wp
	chmod +x /tmp/wp-cli-phar/wp
}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wp_cli_test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wp_cli_test.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "password1"' -uroot
}

install_wp_cli
install_db
