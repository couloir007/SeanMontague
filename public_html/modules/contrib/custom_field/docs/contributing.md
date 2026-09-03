# Contributing to the documentation
This documentation site is built with [MkDocs](https://www.mkdocs.org){target="_blank"} and the
[Material for MkDocs](https://squidfunk.github.io/mkdocs-material){target="_blank"} theme, and lives
alongside the module's code in this repository's `docs/` directory. To preview your
changes locally as you write, this project uses the
[ddev-mkdocs](https://github.com/Metadrop/ddev-mkdocs){target="_blank"} DDEV add-on, which runs MkDocs
in its own container with live reload.

## Installing the add-on
1. From your project root, install the add-on:
    ```bash
    ddev add-on get Metadrop/ddev-mkdocs
    ```
   If you're on DDEV older than v1.23.5, use `ddev get Metadrop/ddev-mkdocs` instead.
2. Restart DDEV to pick up the new service:
    ```bash
    ddev restart
    ```

This generates a `.ddev/docker-compose.mkdocs.yaml` file in your project.

## Configuring the add-on for this repository
The add-on's default configuration serves docs from your project root and exposes them
on a fixed port. Since this documentation lives inside the `custom_field` module
directory rather than at the project root, replace the generated
`.ddev/docker-compose.mkdocs.yaml` with the project's version, included below for
reference.

```yaml
#ddev-generated
services:
  mkdocs:
    container_name: "ddev-${DDEV_SITENAME}-mkdocs"
    image: squidfunk/mkdocs-material:latest
    environment:
      - VIRTUAL_HOST=docs.${DDEV_SITENAME}.${DDEV_TLD}
      - HTTP_EXPOSE=${DDEV_ROUTER_HTTP_PORT}:8000
      - HTTPS_EXPOSE=${DDEV_ROUTER_HTTPS_PORT}:8000
      - WATCHDOG_FORCE_POLLING=true
    # --livereload: required with click 8.3+ (mkdocs#4032); default serve often skips watching.
    command: ["serve", "--dev-addr=0.0.0.0:8000", "--livereload"]
    volumes:
      # Mount the project directory into the container.
      - $DDEV_APPROOT/web/modules/contrib/custom_field:/docs:delegated
      - .:/mnt/ddev_config:ro
      - ddev-global-cache:/mnt/ddev-global-cache
    user:  '$DDEV_UID:$DDEV_GID'
    working_dir: /docs
    labels:
      com.ddev.site-name: ${DDEV_SITENAME}
      com.ddev.approot: $DDEV_APPROOT
    networks:
      ddev_default: null
      default: null
    expose:
      - "8000"
  web:
   links:
     - mkdocs:mkdocs
```

A few notable differences from the add-on's default file:

- The `web/modules/contrib/custom_field` directory is mounted as `/docs` inside the
  container, so MkDocs serves this module's `docs/` folder directly rather than the
  project root's. If you are working off a fork of this repository, change the path to
  match your fork's location.
- `VIRTUAL_HOST` exposes the site at a dedicated subdomain (`docs.<sitename>.<tld>`)
  instead of the add-on's default port-based URL.
- The `serve` command explicitly sets `--dev-addr=0.0.0.0:8000`, matching the
  `dev_addr` already configured in `mkdocs.yml`, and adds `--livereload`.

After saving the file, restart DDEV again:
```bash
ddev restart
```

## Previewing your changes
Once running, visit `https://docs.<your-ddev-sitename>.ddev.site` to view the docs
site. Any changes you save under `docs/` will trigger a live reload in the browser, so
you can write and preview at the same time.

> [!note]
> Before you commit your changes, make sure to run `ddev mkdocs build --clean` to
> generate the static site locally and ensure it builds without errors.