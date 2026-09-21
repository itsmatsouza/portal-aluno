#!/usr/bin/env bash
set -euo pipefail
umask 022

# Este script publica exclusivamente o portal neste subdomínio.
deploy_root=/home/u454601756/domains/aluno.leilabrito.com.br
php_bin=/opt/alt/php83/usr/bin/php
composer_bin=/usr/local/bin/composer2
release_id=${1:?Informe o identificador da versão}
run_migrations=${2:-no}

[[ "$release_id" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]] || { echo 'Identificador inválido.' >&2; exit 1; }
[[ "$run_migrations" == yes || "$run_migrations" == no ]] || exit 1
[[ "$(id -un)" == u454601756 ]] || { echo 'Usuário SSH inesperado.' >&2; exit 1; }
[[ -d "$deploy_root" && ! -L "$deploy_root" ]] || exit 1
[[ -x "$php_bin" && -f "$composer_bin" ]] || { echo 'Confira PHP 8.3 e caminho do Composer.' >&2; exit 1; }
[[ -f "$deploy_root/.env" && ! -L "$deploy_root/.env" ]] || {
    echo 'Crie .env na raiz privada do subdomínio antes de publicar.' >&2
    exit 1
}
chmod 600 "$deploy_root/.env"

release="$deploy_root/releases/$release_id"
public_root="$deploy_root/public_html"
[[ ! -e "$release" ]] || { echo 'Versão já existe; execute novamente pelo GitHub para obter outro ID.' >&2; exit 1; }

# A primeira publicação aceita somente a página padrão observada na instalação.
if [[ -L "$public_root" ]]; then
    previous_public=$(readlink -f "$public_root")
    [[ "$previous_public" == "$deploy_root/releases/"*/public ]] || {
        echo 'public_html aponta para local inesperado.' >&2; exit 1;
    }
elif [[ -d "$public_root" ]]; then
    [[ ! -e "$deploy_root/public_html.initial" ]] || exit 1
    unexpected=$(find "$public_root" -mindepth 1 -maxdepth 1 ! -name default.php -print -quit)
    [[ -z "$unexpected" ]] || { echo 'public_html contém arquivos além de default.php. Revise antes de publicar.' >&2; exit 1; }
else
    echo 'Pasta pública não encontrada.' >&2
    exit 1
fi

mkdir -p "$release"
tar -xzf "$deploy_root/.deploy/$release_id/portal.tar.gz" -C "$release"
ln -s "$deploy_root/.env" "$release/.env"
cd "$release"
"$php_bin" "$composer_bin" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts --no-plugins
"$php_bin" "$composer_bin" check-platform-reqs --no-dev
"$php_bin" -r 'foreach (["curl", "pdo_mysql", "openssl", "session"] as $extension) { if (!extension_loaded($extension)) { fwrite(STDERR, "Extensão ausente: $extension\n"); exit(1); } }'

if [[ "$run_migrations" == yes ]]; then
    "$php_bin" bin/migrate.php
fi

# Detecta configuração/banco incompletos antes de substituir a versão pública.
"$php_bin" bin/check-deployment.php

next_public="$deploy_root/.public-$release_id"
ln -s "$release/public" "$next_public"
if [[ ! -L "$public_root" ]]; then
    mv "$public_root" "$deploy_root/public_html.initial"
    if ! mv -T "$next_public" "$public_root"; then
        mv "$deploy_root/public_html.initial" "$public_root"
        exit 1
    fi
else
    mv -Tf "$next_public" "$public_root"
fi

printf 'Versão publicada: %s\n' "$release_id"
printf 'Diretório: %s\n' "$release"
printf 'Confira https://aluno.leilabrito.com.br/login e acesso aos assets.\n'
