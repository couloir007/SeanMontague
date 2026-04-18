Table of contents
-----------------

* Introduction
* Features
* Requirements
* Notes


Introduction
------------

The **Schema.org Blueprints Demo Headless module** provides a demo 
of the Schema.org Blueprints module used as a headless backend.


Features
--------

Module

- Removes all permissions from anonymous users.
- Imports 'schemadotorg_demo_headless' user role configuration.
- Import JSON:API resource configuration for exposing entity and field 
  data to headless applications.  
- Creates 'headless' user account used for basic authentication preview.
- Creates 'headless' consumer with uuid, client id, and secret with image styles.
- Sets Oauth public and private keys. (i.e. /Users/USER/Sites/drupal_headless/keys)
- Allow 'Headless' role to view entity and field data. 

Postman (@see postman/schemadotorg_demo_headless.postman_collection.json)

- Shows how to a subrequest to access entity and field configuration using subrequests.


References
----------

[Incredible Decoupled Performance with Subrequests](https://www.lullabot.com/articles/incredible-decoupled-performance-with-subrequests)

### Oauth Credentials

Generate key using the below commands.

```
# Generate keys.
mkdir ~/Sites/schemadotorg/keys
cd ~/Sites/schemadotorg/keys
openssl genrsa -out private.key 2048
openssl rsa -in private.key -pubout > public.key
chmod 660 public.key
chmod 660 private.key
```

You can set the keys via Simple Oauth settings (/admin/config/people/simple_oauth) 
or by adding the below code settings.php

```
// Set Oauth public and private keys.
$key_directory = dirname(DRUPAL_ROOT) . '/keys';
$config['simple_oauth.settings']['public_key'] = $key_directory . '/public.key';
$config['simple_oauth.settings']['private_key'] = $key_directory . '/private.key';
```

The below credentials give the 'headless'' access to view content, 
entity, and field data.

**These credential should never be used on production website**.

    Client ID: 00000000-0000-0000-0000-000000000000
    Client Secret: secret
    Username: headless
    Password: headless

