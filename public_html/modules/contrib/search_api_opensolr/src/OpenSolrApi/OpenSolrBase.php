<?php

namespace Drupal\search_api_opensolr\OpenSolrApi;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api_opensolr\Services\OpenSolrConfig;
use Drupal\search_api_opensolr\Traits\OpensolrKeyDependencyTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * The core of the opensolr API calls.
 *
 * @package Drupal\search_api_opensolr\OpenSolrApi
 */
class OpenSolrBase {
  use OpensolrKeyDependencyTrait;
  use StringTranslationTrait;

  const OPENSOLR_ENDPOINT_URL = 'https://opensolr.com/solr_manager/api';

  /**
   * GuzzleHttp client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The OpenSolr API credentials as key valued array.
   *
   * @var array
   */
  protected $apiCredentials;

  /**
   * The JSON serializer service.
   *
   * @var \Drupal\Component\Serialization\Json
   */
  protected $json;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  public function __construct(ClientInterface $http_client, Json $json, OpenSolrConfig $openSolrConfig, FileUrlGeneratorInterface $file_url_generator, protected readonly LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->json = $json;
    $this->fileUrlGenerator = $file_url_generator;
    $this->apiCredentials = $openSolrConfig->getApiCredentials();
  }

  /**
   * General api call method.
   *
   * @param string $path
   *   The path of the api endpoint.
   * @param string $method
   *   The HTTP method. E.g. GET, POST, etc.
   * @param array $params
   *   An array of params to add to the request.
   * @param bool $returnObject
   *   A boolean indicating if we want the object as response or not.
   * @param bool $useMultipart
   *   A boolean indicating if we use multipart arguments or not.
   * @param bool $attachApiCredentials
   *   A boolean indicating if the stored API credentials should be
   *   automatically attached to the request or not. Defaults to TRUE.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse|mixed|\Psr\Http\Message\ResponseInterface|null
   *   Returns the response from the API.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function apiCall(string $path, string $method = 'GET', array $params = [], bool $returnObject = FALSE, bool $useMultipart = FALSE, bool $attachApiCredentials = TRUE) {
    $options = [];
    if ($attachApiCredentials) {
      // Add API credentials.
      $params = array_merge($this->apiCredentials, $params);
    }
    if (!$useMultipart) {
      // Add necessary headers.
      $headers = [
        'Content-type' => 'application/json',
      ];
      $data = !empty($params) ? $this->json->encode($params) : NULL;
      // Build the request, including path and headers. Internal use.
      $options['headers'] = $headers;
      $options['body'] = $data;
    }
    else {
      $this->attachMultipart($options, $params);
    }
    $url = static::OPENSOLR_ENDPOINT_URL . $path;
    if ($method == 'GET' && !empty($params)) {
      $url .= '?' . UrlHelper::buildQuery($params);
    }
    try {
      $response = new OpenSolrResponse($this->httpClient->request($method, $url, $options));
    }
    catch (\Exception $e) {
      // Network errors, HTTP 4xx/5xx (Guzzle throws on those), non-JSON bodies:
      // never let any of them escape — every caller gets a well-formed error
      // response and decides how to degrade.
      $response = $this->buildErrorResponse($e);
    }
    if ($returnObject) {
      return $response;
    }
    // Always an array (possibly empty) — never NULL/'' — so callers can safely
    // do $data['status'] / $data['msg'] lookups.
    return $response->getFullResponseData();
  }

  /**
   * Turns a failed API call into a well-formed error response.
   *
   * Historically a ConnectException (code 0) or a JSON parse error ended up in
   * `new Response($e->getCode())`, which Guzzle rejects with an
   * InvalidArgumentException — an uncaught fatal on whatever admin page had
   * triggered the API call (server view page, server save, ...). This method
   * guarantees a valid HTTP status and a JSON body with the same shape the
   * opensolr API uses ({"status": false, "msg": "..."}), and it never leaks the
   * credentials Guzzle embeds in its exception messages into the log.
   *
   * @param \Exception $e
   *   The exception thrown by the HTTP client or the response parser.
   *
   * @return \Drupal\search_api_opensolr\OpenSolrApi\OpenSolrResponse
   *   The error response, isSuccess() === FALSE.
   */
  protected function buildErrorResponse(\Exception $e): OpenSolrResponse {
    $message = OpenSolrResponse::redactCredentials($e->getMessage());
    $this->logger->error('opensolr API request failed: @message', ['@message' => $message]);

    // Prefer the API's own error body when there is one, e.g. a 403 with
    // {"status":false,"msg":"ERROR_AUTHENTICATION_FAILED"} — that is the
    // message the user needs to see.
    if ($e instanceof BadResponseException && $e->getResponse() instanceof Response) {
      try {
        $apiResponse = new OpenSolrResponse($e->getResponse());
        if ($apiResponse->getFullResponseData() !== []) {
          return $apiResponse;
        }
      }
      catch (\Exception) {
        // Not JSON (HTML error page, maintenance page…): fall through.
      }
    }

    $code = (int) $e->getCode();
    // Only real HTTP statuses are accepted by the Response constructor;
    // anything else (cURL error codes, 0) becomes 503 Service Unavailable.
    $status = ($code >= 100 && $code < 600) ? $code : 503;
    return new OpenSolrResponse(new Response($status, [], $this->json->encode([
      'status' => FALSE,
      'msg' => $message,
      'error' => $message,
      'errorCode' => $code,
    ])));
  }

  /**
   * Attaches the multipart values for requests that send files.
   *
   * @param array $options
   *   An array with the extra options for the API request.
   * @param array $params
   *   An array of params to add to the request.
   */
  protected function attachMultipart(array &$options, array $params) {
    $multipart = [];
    foreach ($params as $key => $value) {
      // If it's a file, we need to add the filename and create a url for it.
      if (file_exists($value)) {
        $multipart[] = [
          'name' => $key,
          'contents' => file_get_contents($value),
          'filename' => substr($value, strrpos($value, '/') + 1),
        ];
      }
      else {
        $multipart[] = [
          'name' => $key,
          'contents' => $value,
        ];
      }
    }
    $options['multipart'] = $multipart;
    $options['decode_content'] = FALSE;
  }

}
