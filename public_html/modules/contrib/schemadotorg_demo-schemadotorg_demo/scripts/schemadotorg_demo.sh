#!/bin/bash

function function_exists() {
  type "$1" &>/dev/null && return 0 || return 1
}

function help() {
  echo "schemadotorg_demo.sh";
  echo;
  echo "Installs a demo of the Schema.org Blueprints module.";
  echo;
  echo "This scripts assumes you are starting with a plain vanilla standard instance of Drupal.";
  echo;
  echo "The below commands should be executed from the root of your Drupal installation.";
  echo;
  echo "Usage:"
  echo;
  IFS=$'\n'
  for f in $(declare -F); do
    function_name=${f:11}
    if [[ $function_name =~ ^(install|index) ]]; then
      echo "./schemadotorg_demo.sh $function_name";
    fi
  done
}

function install() {
  local profile=${1:-"schemadotorg_demo_profile"};

  echo "Installing $profile profile";
  drush -yv site-install --account-mail="$SCHEMADOTORG_ACCOUNT_MAIL"\
    --account-name=${SCHEMADOTORG_ACCOUNT_NAME:-''}\
    --account-pass=${SCHEMADOTORG_ACCOUNT_PASS:-''}\
    --site-mail="$SCHEMADOTORG_ACCOUNT_MAIL"\
    --site-name="Schema.org Blueprints Demo"\
    $profile;

  drush -y config-set system.site slogan 'A demo of the Schema.org Blueprints module for Drupal.';

  drush -y en config_rewrite;
}

################################################################################

function install_demo_testing() {
  install testing;
}

function install_demo_default() {
  install standard;
}

function install_demo_standard() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  index_search;
}

function install_demo_experimental() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  index_search;
}


function install_demo_admin() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  drush -y pm:enable schemadotorg_demo_admin;
  index_search;
}

function install_demo_api() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  drush -y pm:enable schemadotorg_demo_admin;
  drush -y pm:enable schemadotorg_demo_api;
  index_search;
}

function install_demo_layout() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  drush -y pm:enable schemadotorg_demo_admin;
  drush -y pm:enable schemadotorg_demo_api;
  drush -y pm:enable schemadotorg_demo_layout_paragraphs
  index_search;
}

function install_demo_headless() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  drush -y pm:enable schemadotorg_demo_admin;
  drush -y pm:enable schemadotorg_demo_api;
  drush -y pm:enable schemadotorg_demo_headless;
  index_search;
}

function install_demo_translation() {
  install;
  drush -y pm:enable schemadotorg_demo_devel;
  drush -y pm:enable schemadotorg_demo_standard;
  drush -y pm:enable schemadotorg_demo_experimental;
  drush -y pm:enable schemadotorg_demo_admin;
  drush -y pm:enable schemadotorg_demo_api;
  drush -y pm:enable schemadotorg_demo_translation;
  index_search;
}

function index_search() {
  # @todo Update to work with Drupal 11.x.
  drush eval '$config = \Drupal::configFactory()->getEditable("search.settings"); $limit = $config->get("index.cron_limit"); $config->set("index.cron_limit", 10000); function_exists("search_cron") && search_cron();$config->set("index.cron_limit", $limit);'
}

################################################################################

function_name=$1; shift;

if function_exists $function_name; then

  $function_name $@;

else

  if [[ ! -z "$function_name" ]]; then
    echo "Function to '$function_name' does not exist.";
    echo;
  fi

  help;

fi
