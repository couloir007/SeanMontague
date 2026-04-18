<?php

namespace Drupal\embedded_content_test\Plugin\EmbeddedContent;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\State;
use Drupal\embedded_content\EmbeddedContentInterface;
use Drupal\embedded_content\EmbeddedContentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a shape.
 *
 * @EmbeddedContent(
 *   id = "no_config",
 *   label = @Translation("No config"),
 * )
 */
final class NoConfig extends EmbeddedContentPluginBase implements EmbeddedContentInterface, ContainerFactoryPluginInterface
{

    /**
     * State.
     *
     * @var \Drupal\Core\State\State
     */
    protected $state;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, State $state)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('state')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(): array
    {
        $state = $this->state->get('embedded_content_state', 1);
        $build = [
        'sample' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#cache' => [
          'max-age' => 0,
        ],
        '#attributes' => [
          'class' => ['test-attached-library'],
        ],
        '#attached' => [
          'library' => [
            'embedded_content_test/test',
          ],
        ],
        '#value' => $state,
        ],
        ];
        $state++;
        $this->state->set('embedded_content_state', $state);
        return $build;
    }


    /**
     * {@inheritdoc}
     */
    public function isInline(): bool
    {
        return false;
    }

}
