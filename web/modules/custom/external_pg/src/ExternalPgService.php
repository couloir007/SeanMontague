<?php

namespace Drupal\external_pg;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Provides a connection to an external PostgreSQL database.
 */
class ExternalPgService {
  /**
   * The external database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  private $database;
  private $username;
  private $password;
  private $host;

  /**
   * Constructs an ExternalPgService object.
   *
   * Note: For security and flexibility, credentials should ideally be defined
   * in settings.php rather than hardcoded.
   */
  public function __construct() {
    // Define connection options for the external PostgreSQL database.
    $this->database = 'TrailMapper';
    $this->username = 'postgres';
    $this->password = 'postgres';
    $this->host = '192.168.86.112';

    $connection_options = [
      'driver'   => 'pgsql',
      'database' => 'TrailMapper', // Replace with your DB name.
      'username' => 'postgres',              // Replace with your DB username.
      'password' => 'postgres',              // Replace with your DB password.
      'host'     => '192.168.86.112',
      'port'     => '5432',
      'prefix'   => '',
    ];

    // Register the external connection with a key 'external_pg'.
    Database::addConnectionInfo('external_pg', 'default', $connection_options);

    // Retrieve the connection.
    $this->connection = Database::getConnection('default', 'external_pg');

    echo "Connected to the <strong>192.168.86.112</strong> database successfully!";
  }

  /**
   * Example method to fetch data from a table in the external DB.
   *
   * @param string $table
   *   The name of the table to fetch data from.
   *
   */
  public function fetchData($query) {
    return $this->connection->query($query);
  }

}
