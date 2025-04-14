<?php
// Ray debugger configuration.
// @see: https://spatie.be/docs/ray/v1/configuration/framework-agnostic-php

return [
  /*
  * This settings controls whether data should be sent to Ray.
  */
  'enable' => true,

  /*
   *  The host used to communicate with the Ray app.
   */
  'host' => 'host.docker.internal',

  /*
   *  The port number used to communicate with the Ray app.
   */
  'port' => 23517,

  /*
   *  Absolute base path for your sites or projects in Homestead, Vagrant, Docker, or another remote development server.
   */
  'remote_path' => '/app/web',

  /*
   *  Absolute base path for your sites or projects on your local computer where your IDE or code editor is running on.
   */
  'local_path' => '/media/sean-montague/0c49c450-91fc-4810-9e56-9c253853713d/Shared/Dropbox/www/TrailMapperPsql/web',

  /*
   * When this setting is enabled, the package will not try to format values sent to Ray.
   */
  'always_send_raw_values' => false,

];
