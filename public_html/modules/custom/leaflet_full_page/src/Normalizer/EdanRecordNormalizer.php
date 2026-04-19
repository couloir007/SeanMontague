<?php

namespace Drupal\leaflet_full_page\Normalizer;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Markup;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Drupal\edan_connector\Connector\SimpleObjectConnector;

/**
 * Normalizer for field_edan_record field.
 */
class EdanRecordNormalizer extends NormalizerBase implements ContainerInjectionInterface {

    /**
     * The interface or class that this normalizer supports.
     *
     * @var string
     */
    protected string $supportedInterfaceOrClass = Serializer::class;

    /**
     * The EDAN connector service.
     *
     * @var \Drupal\edan_connector\Connector\SimpleObjectConnector
     */
    protected SimpleObjectConnector $edanConnector;

    /**
     * Constructs a new EdanRecordNormalizer object.
     *
     * @param \Drupal\edan_connector\Connector\SimpleObjectConnector $edan_connector
     *   The EDAN connector service.
     */
    public function __construct(SimpleObjectConnector $edan_connector) {
        $this->edanConnector = $edan_connector;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = NULL, array $context = []): bool {
        // Ensure this is a Views REST export response.
        // The $context parameter can be checked for specific keys if needed.
        return is_array($data) && $format === 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, $format = NULL, array $context = []): float|array|\ArrayObject|bool|int|string|null {
        foreach ($data as $index => $item) {
            // Capture the original field_edan_record value before any modifications
            if (isset($item['field_edan_object']) && $item['field_edan_object'] !== '') {
                $original_edan_record = $item['field_edan_object'] ?? NULL;

                // Extract string value from Markup object if it's a Markup object
                if ($original_edan_record instanceof Markup) {
                    $original_edan_record_string = (string) $original_edan_record;
                } else {
                    $original_edan_record_string = $original_edan_record;
                }

                $object = $this->edanConnector->getObjectByUrl($original_edan_record_string);

                if (isset($object['data']['content']['descriptiveNonRepeating']['online_media']['media'])) {
                    $media = $object['data']['content']['descriptiveNonRepeating']['online_media']['media'];

                    $imagesToSerialize = [];

                    foreach ($media as $image) {
                        if (str_contains($image['content'], 'https://collections.nmnh.si.edu/media/?irn=')) {
                            $imagesToSerialize[] = [
                                'content' => 'https://ids.si.edu/ids/deliveryService?id=' . $image['content'],
                                'thumb' => 'https://ids.si.edu/ids/deliveryService?id=' . $image['content'] . '&thumb=yes&max_w=100',
                                'idsId' => $image['idsId'],
                            ];
                        } else {
                            $imagesToSerialize[] = [
                                'content' => $image['content'],
                                'thumb' => $image['content'] . '/100',
                                'idsId' => $image['idsId'],
                            ];
                        }
                    }

                    // Keep the original value without any modifications
                    $data[$index]['field_edan_object'] = $imagesToSerialize;
                }
            }
        }

        // Return the properly structured serialized output
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container): static {
        return new static(
            $container->get('edan_connector.simple')
        );
    }

}
