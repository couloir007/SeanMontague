<?php

declare(strict_types=1);

namespace Drupal\Tests\embedded_content\Unit;

use Drupal\embedded_content\Entity\EmbeddedContentButton;
use Drupal\Tests\UnitTestCase;

/**
 * Test description.
 *
 * @group embedded_content
 */
final class EmbeddedContentButtonTest extends UnitTestCase
{

    /**
     * Test the meet condition method.
     */
    public function testConditions(): void
    {
        $button = new EmbeddedContentButton(
            [
            'settings' => [
            'conditions' => implode(
                PHP_EOL, [
                'foo',
                'bar.*',
                'baz.*.qux',
                '/^quux$/',
                ]
            ),
            ],
            ], 'embedded_content_button'
        );

        $this->assertTrue($button->meetsCondition('foo'));
        $this->assertFalse($button->meetsCondition('bar'));
        $this->assertFalse($button->meetsCondition('foo_bar'));
        $this->assertTrue($button->meetsCondition('bar.foo'));
        $this->assertTrue($button->meetsCondition('baz.foo.qux'));
        $this->assertFalse($button->meetsCondition('baz.qux'));
        $this->assertTrue($button->meetsCondition('quux'));
    }

}
