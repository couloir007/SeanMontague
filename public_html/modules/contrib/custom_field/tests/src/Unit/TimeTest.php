<?php

declare(strict_types=1);

namespace Drupal\Tests\custom_field\Unit;

use Drupal\custom_field\Time;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Time class.
 *
 * @coversDefaultClass \Drupal\custom_field\Time
 * @group custom_field
 */
class TimeTest extends TestCase {

  /**
   * Tests Time class creation.
   */
  public function testCreationWithInvalidArguments(): void {
    $this->expectException(\InvalidArgumentException::class);
    new Time(50);
  }

  /**
   * @covers ::createFromDateTime
   */
  public function testItCanBeCreatedByDateTime(): void {
    $time = Time::createFromDateTime(new \DateTime('2020-09-08 13:33:26'));
    static::assertEquals('13', $time->getHour());
    static::assertEquals('33', $time->getMinute());
    static::assertEquals('26', $time->getSecond());
  }

  /**
   * @covers ::createFromHtml5Format
   */
  public function testItCanBeCreatedByHtml5String(): void {
    $time = Time::createFromHtml5Format('13:40:30');
    static::assertEquals('13', $time->getHour());
    static::assertEquals('40', $time->getMinute());
    static::assertEquals('30', $time->getSecond());

    $time = Time::createFromHtml5Format('14:50');
    static::assertEquals('14', $time->getHour());
    static::assertEquals('50', $time->getMinute());
    static::assertEquals('0', $time->getSecond());

    $time = Time::createFromHtml5Format('');
    static::assertEquals('0', $time->getHour());
    static::assertEquals('0', $time->getMinute());
    static::assertEquals('0', $time->getSecond());
  }

  /**
   * @covers ::createFromHtml5Format
   *
   * Free-form strings must throw cleanly (not emit undefined array key
   * warnings). Requires the structural guard in createFromHtml5Format().
   */
  public function testCreateFromHtml5FormatRejectsInvalid(): void {
    $this->expectException(\InvalidArgumentException::class);
    Time::createFromHtml5Format('not-a-time');
  }

  /**
   * @covers ::createFromHtml5Format
   *
   * Structurally valid but out of range (hour 25) is rejected by the
   * constructor assertInRange().
   */
  public function testCreateFromHtml5FormatRejectsOutOfRange(): void {
    $this->expectException(\InvalidArgumentException::class);
    Time::createFromHtml5Format('25:00');
  }

  /**
   * @covers ::createFromTimestamp
   */
  public function testItCanBeCreatedFromDayTimestamp(): void {
    $time = Time::createFromTimestamp(3700);
    static::assertEquals('01:01:40', $time->format('H:i:s'));
  }

  /**
   * @covers ::createFromTimestamp
   */
  public function testCreateFromTimestampMidnightAndEmpty(): void {
    $midnight = Time::createFromTimestamp(0);
    static::assertNotNull($midnight);
    static::assertEquals(0, $midnight->getHour());
    static::assertEquals(0, $midnight->getMinute());
    static::assertEquals(0, $midnight->getSecond());
    static::assertEquals(0, $midnight->getTimestamp());

    static::assertNull(Time::createFromTimestamp(NULL));
    static::assertNull(Time::createFromTimestamp(''));
  }

  /**
   * @covers ::createFromTimestamp
   */
  public function testCreateFromTimestampRejectsOutOfRange(): void {
    $this->expectException(\InvalidArgumentException::class);
    Time::createFromTimestamp(90000);
  }

  /**
   * @covers ::getTimestamp
   */
  public function testGetTimestamp(): void {
    // 13*3600 + 40*60 + 30 = 49230.
    $time = new Time(13, 40, 30);
    static::assertEquals(49230, $time->getTimestamp());
    static::assertEquals(0, (new Time(0, 0, 0))->getTimestamp());
  }

  /**
   * @covers ::isEmpty
   */
  public function testItDetectsEmpty(): void {
    static::assertTrue(Time::isEmpty(NULL));
    static::assertTrue(Time::isEmpty(''));
    static::assertTrue(Time::isEmpty('86401'));
    static::assertFalse(Time::isEmpty('0'));
    static::assertFalse(Time::isEmpty(0));
  }

  /**
   * @covers ::format
   */
  public function testItFormatsInExpectedFormat(): void {
    $time = new Time(13, 40, 30);
    static::assertEquals('01:40 pm', $time->format('h:i a'));
  }

  /**
   * @covers ::formatForWidget
   */
  public function testItFormatsForWidgetsInExpectedFormat(): void {
    $time = new Time(13, 40, 30);
    static::assertEquals('13:40:30', $time->formatForWidget());
    static::assertEquals('13:40', $time->formatForWidget(FALSE));
  }

  /**
   * Test it computes correctly on days when the time changes.
   */
  public function testItWorksOnDstDates(): void {
    $original_tz = date_default_timezone_get();
    date_default_timezone_set('America/New_York');
    $date = new \DateTime('2022-03-13', new \DateTimeZone('America/New_York'));
    $time = new Time(13, 0, 0);
    $date_with_time = $time->on($date);
    static::assertEquals('2022-03-13T13:00:00-04:00', $date_with_time->format('c'));
    $date = new \DateTime('2022-11-06', new \DateTimeZone('America/New_York'));
    $time = new Time(13, 0, 0);
    $date_with_time = $time->on($date);
    static::assertEquals('2022-11-06T13:00:00-05:00', $date_with_time->format('c'));
    date_default_timezone_set($original_tz);
  }

}
