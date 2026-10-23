<?php

namespace Drupal\search_api_opensolr\OpenSolrApi;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Psr7\Response;

/**
 * Class RestResponse.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi
 */
class OpenSolrResponse extends Response {
  use StringTranslationTrait;

  /**
   * The original Response used to build this object.
   *
   * @var \GuzzleHttp\Psr7\Response
   * @see __get()
   */
  protected $response;

  /**
   * The json-decoded response body.
   *
   * @var mixed
   * @see __get()
   */
  protected $data;

  public function __construct(Response $response) {
    $this->response = $response;
    parent::__construct($response->getStatusCode(), $response->getHeaders(), $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
    $this->handleJsonResponse();
  }

  /**
   * Magic getter method to return the given property.
   *
   * @param string $key
   *   A string representing the key name from the response object.
   *
   * @return mixed
   *   Returns the value of the associated key, otherwise it throws an
   *   exception.
   *
   * @throws \Exception
   *   Ff $key is not a property.
   */
  public function __get($key) {
    if (!property_exists($this, $key)) {
      throw new \Exception("Undefined property $key");
    }
    return $this->$key;
  }

  /**
   * Parses the JSON body into $this->data.
   *
   * $this->data is ALWAYS an array afterwards: [] for an empty body or an
   * empty JSON list (e.g. an account with no indexes — a perfectly valid
   * answer, it used to be reported as "Invalid response").
   *
   * @throws \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrException
   *   When the body is not valid JSON (HTML error page, maintenance page…).
   */
  private function handleJsonResponse() {
    $this->data = [];
    $body = $this->getBody();
    // The body may already have been read (Guzzle reads it for its exception
    // message summary): rewind before consuming it.
    if ($body->isSeekable()) {
      $body->rewind();
    }
    $response_body = $body->getContents();
    if (trim($response_body) === '') {
      return;
    }

    $data = Json::decode($response_body);
    if (!is_array($data)) {
      // NULL = invalid JSON; a bare scalar is not an API answer either.
      throw new OpenSolrException($this, 'Invalid response');
    }

    $this->data = $data;
  }

  /**
   * Returns request status.
   *
   * @return bool
   *   If the request was successful returns TRUE, FALSE otherwise.
   */
  public function isSuccess() {
    return isset($this->data['status']) && $this->data['status'] === TRUE;
  }

  /**
   * Retrieves full response data.
   *
   * @return mixed
   *   Returns the response data from this object.
   */
  public function getFullResponseData() {
    return $this->data;
  }

  /**
   * Retrieves a part of data by key.
   *
   * @param array|string $key
   *   A valid response data key or a set of parents.
   *
   * @return mixed|null
   *   Returns data if it exists, NULL otherwise.
   */
  public function getResponseData(array|string $key = 'msg') {
    $keys = (array) $key;
    $value = NestedArray::getValue($this->data, $keys);
    if ($key == 'error') {
      $errorCode = !empty($this->data['errorCode']) ? sprintf('HTTP Code: %s, ', $this->data['errorCode']) : '';
      return $errorCode . static::redactCredentials((string) $value);
    }
    return $value;
  }

  /**
   * Redacts credentials from a URL or free text (query-string style params).
   *
   * Guzzle puts the full request URL in its exception messages and the API
   * takes email/api_key/username/password as GET parameters, so anything we
   * log or show must go through here.
   *
   * @param string $text
   *   URL or message possibly containing credentials.
   * @param array $params
   *   Query parameter names to redact.
   *
   * @return string
   *   The text with the parameter values replaced by XXX.
   */
  public static function redactCredentials(string $text, array $params = ['email', 'api_key', 'username', 'password']): string {
    return preg_replace(
      array_map(fn($p) => '/(' . preg_quote($p, '/') . '=)[^&\s]*/', $params),
      '$1XXX',
      $text
    );
  }

}
