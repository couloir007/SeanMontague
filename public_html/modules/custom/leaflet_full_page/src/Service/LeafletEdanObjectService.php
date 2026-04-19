<?php

namespace Drupal\leaflet_full_page\Service;

use Drupal\edan_connector\Connector\SimpleObjectConnector;

/**
 * Service for Leaflet Full Page to fetch EDAN Objects via edan_connector.
 *
 * This is a thin wrapper around the edan_connector.simple service, exposing
 * convenient methods for retrieving EDAN Objects (edanmdm records) by ID or URL
 * for use within the leaflet_full_page module or anything else that chooses
 * to inject this service.
 */
class LeafletEdanObjectService {

    /**
     * The simple EDAN connector service.
     *
     * @var \Drupal\edan_connector\Connector\SimpleObjectConnector
     */
    protected SimpleObjectConnector $simpleConnector;

    /**
     * Constructor.
     */
    public function __construct(SimpleObjectConnector $simpleConnector) {
        $this->simpleConnector = $simpleConnector;
    }

    /**
     * Fetch an EDAN Object (edanmdm) by its ID.
     *
     * Example: edanmdm:nmnhanthro_12345
     *
     * @param string $edan_id
     *   The edanmdm identifier including the prefix "edanmdm:".
     *
     * @return array
     *   JSend-like response array as returned by EdanClient->processRequest().
     */
    public function getObjectById(string $edan_id): array {
        return $this->simpleConnector->getObjectById($edan_id);
    }

    /**
     * Fetch an EDAN Object by its canonical URL.
     *
     * Example: url:edanmdm:nmnhanthro_12345
     *
     * @param string $edan_url
     *   The EDAN URL string (usually prefixed by "url:").
     *
     * @return array
     *   JSend-like response array as returned by EdanClient->processRequest().
     */
    public function getObjectByUrl(string $edan_url): array {
        return $this->simpleConnector->getObjectByUrl($edan_url);
    }
}
