<?php

namespace Drupal\leaflet_full_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for proxying EDAN viewer requests.
 */
class EdanProxyController extends ControllerBase {

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Constructs an EdanProxyController object.
     *
     * @param \GuzzleHttp\ClientInterface $http_client
     *   The HTTP client service.
     */
    public function __construct(ClientInterface $http_client) {
        $this->httpClient = $http_client;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('http_client')
        );
    }

    /**
     * Proxy EDAN viewer requests to bypass X-Frame-Options restrictions.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   The proxied response.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function proxy() {
        try {
            // Read ID from query parameter to avoid encoded slash issues.
            $rawId = \Drupal::request()->query->get('id');

            if (empty($rawId)) {
                throw new NotFoundHttpException('Missing required id parameter.');
            }

            // Decode the ARK ID
            $idsId = urldecode($rawId);

            // Log the decoded ID for debugging
            \Drupal::logger('leaflet_full_page')->info('Proxy request for ARK ID: @id', ['@id' => $idsId]);

            // Build the Smithsonian IDS URL
            $url = 'https://ids.si.edu/ids/dynamic?id=' . urlencode($idsId) . '&container=fullpage&inline=true';

            // Log the full URL
            \Drupal::logger('leaflet_full_page')->info('Proxying to URL: @url', ['@url' => $url]);

            // Fetch the content from Smithsonian
            $response = $this->httpClient->get($url, [
                'timeout' => 30,
                'verify' => TRUE,
                'allow_redirects' => TRUE,
            ]);

            // Get the content
            $content = $response->getBody()->getContents();

            // Adjust relative asset URLs so they load correctly within our domain.
            // The upstream HTML references assets like "webjars/..." which the browser would
            // resolve relative to "/leaflet-full-page/edan-proxy" and 404. We fix this by
            // inserting a <base> tag pointing to the upstream base path, or rewriting common
            // relative paths to absolute upstream URLs as a fallback. Note: assets live under
            // https://ids.si.edu/ids/, not the root, so include the /ids/ segment.
            $upstreamBase = 'https://ids.si.edu/ids/';

            if (stripos($content, '<base') === FALSE) {
                // Try to inject <base href="https://ids.si.edu/ids/"> right after <head>.
                if (preg_match('/<head[^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
                    $insertPos = $m[0][1] + strlen($m[0][0]);
                    $content = substr($content, 0, $insertPos)
                        . '<base href="' . $upstreamBase . '">'
                        . substr($content, $insertPos);
                }
                else {
                    // Fallback: rewrite common relative URL patterns to absolute upstream URLs.
                    // Handle src/href starting with "webjars/" or "/webjars/".
                    $replacements = [
                        'src="webjars/' => 'src="' . $upstreamBase . 'webjars/',
                        'href="webjars/' => 'href="' . $upstreamBase . 'webjars/',
                        'src="/webjars/' => 'src="' . $upstreamBase . 'webjars/',
                        'href="/webjars/' => 'href="' . $upstreamBase . 'webjars/',
                    ];
                    $content = strtr($content, $replacements);
                }
            }

            // Return as response with permissive headers to allow framing
            return new Response($content, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Frame-Options' => 'ALLOWALL',
                'Cache-Control' => 'public, max-age=3600',
            ]);

        } catch (RequestException $e) {
            $this->getLogger('leaflet_full_page')->error('Failed to proxy EDAN request for @id: @error', [
                '@id' => isset($idsId) ? $idsId : 'unknown',
                '@error' => $e->getMessage(),
            ]);
            throw new NotFoundHttpException('Failed to load EDAN viewer: ' . $e->getMessage());
        }
    }

}