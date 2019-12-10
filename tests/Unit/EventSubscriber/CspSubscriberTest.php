<?php

namespace Drupal\Tests\attachinline\Unit\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\attachinline\EventSubscriber\CspSubscriber;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\attachinline\EventSubscriber\CspSubscriber
 * @group csp
 */
class CspSubscriberTest extends UnitTestCase {

  /**
   * The response object.
   *
   * @var \Drupal\Core\Render\HtmlResponse|\PHPUnit\Framework\MockObject\MockObject
   */
  private $response;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->response = $this->getMockBuilder(HtmlResponse::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Check that the subscriber listens to the Policy Alter event.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEvents() {
    $this->assertArrayHasKey(CspEvents::POLICY_ALTER, CspSubscriber::getSubscribedEvents());
  }

  /**
   * Shouldn't alter the policy if no directives are enabled.
   *
   * @covers ::onCspPolicyAlter
   * @covers ::registerHash
   * @covers ::fallbackAwareAppendIfEnabled
   */
  public function testNoDirectives() {
    $policy = new Csp();
    $alterEvent = new PolicyAlterEvent($policy, $this->response);

    $subscriber = new CspSubscriber();
    $subscriber->registerHash('script-src-elem', 'test256-abc123');
    $subscriber->onCspPolicyAlter($alterEvent);

    $this->assertFalse($alterEvent->getPolicy()->hasDirective('default-src'));
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src'));
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src-attr'));
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src-elem'));
  }

  /**
   * Test that enabled directives are modified.
   *
   * @covers ::onCspPolicyAlter
   * @covers ::registerHash
   * @covers ::fallbackAwareAppendIfEnabled
   */
  public function testDirectivesSet() {

    $policy = new Csp();
    $policy->setDirective('default-src', [Csp::POLICY_ANY]);
    $policy->setDirective('script-src', [Csp::POLICY_SELF]);
    $policy->setDirective('script-src-attr', [Csp::POLICY_SELF]);
    $policy->setDirective('script-src-elem', [Csp::POLICY_SELF]);

    $alterEvent = new PolicyAlterEvent($policy, $this->response);

    $subscriber = new CspSubscriber();
    $subscriber->registerHash('script-src', 'test256-abc123');
    $subscriber->registerHash('script-src-elem', 'test256-def456');
    $subscriber->onCspPolicyAlter($alterEvent);

    $this->assertArrayEquals(
      [Csp::POLICY_SELF, "'test256-abc123'"],
      $alterEvent->getPolicy()->getDirective('script-src')
    );
    $this->assertArrayEquals(
      [Csp::POLICY_SELF],
      $alterEvent->getPolicy()->getDirective('script-src-attr')
    );
    $this->assertArrayEquals(
      [Csp::POLICY_SELF, "'test256-def456'"],
      $alterEvent->getPolicy()->getDirective('script-src-elem')
    );
  }

  /**
   * Test that directives are copied and modified from fallback.
   *
   * @covers ::onCspPolicyAlter
   * @covers ::registerHash
   * @covers ::fallbackAwareAppendIfEnabled
   */
  public function testDirectivesSetFromFallback() {

    $policy = new Csp();
    $policy->setDirective('default-src', [Csp::POLICY_SELF]);

    $alterEvent = new PolicyAlterEvent($policy, $this->response);

    $subscriber = new CspSubscriber();
    $subscriber->registerHash('script-src-elem', 'test256-def456');
    $subscriber->onCspPolicyAlter($alterEvent);

    // Other directives are not modified.
    // Script-src-elem should include values from default-src.
    $this->assertArrayEquals(
      [Csp::POLICY_SELF],
      $alterEvent->getPolicy()->getDirective('default-src')
    );
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src'));
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src-attr'));
    $this->assertArrayEquals(
      [Csp::POLICY_SELF, "'test256-def456'"],
      $alterEvent->getPolicy()->getDirective('script-src-elem')
    );
  }

  /**
   * Test that hashes are not added if directive has 'unsafe-inline' set.
   *
   * @covers ::onCspPolicyAlter
   * @covers ::registerHash
   * @covers ::fallbackAwareAppendIfEnabled
   */
  public function testDirectivesUnsafeInline() {

    $policy = new Csp();
    $policy->setDirective('script-src', [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE]);

    $alterEvent = new PolicyAlterEvent($policy, $this->response);

    $subscriber = new CspSubscriber();
    $subscriber->registerHash('script-src', 'test256-def456');
    $subscriber->registerHash('script-src-elem', 'test256-def456');
    $subscriber->onCspPolicyAlter($alterEvent);

    $this->assertFalse($alterEvent->getPolicy()->hasDirective('default-src'));
    $this->assertArrayEquals(
      [Csp::POLICY_SELF, Csp::POLICY_UNSAFE_INLINE],
      $alterEvent->getPolicy()->getDirective('script-src')
    );
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src-attr'));
    // Should not copy values from fallback with 'unsafe-inline'.
    $this->assertFalse($alterEvent->getPolicy()->hasDirective('script-src-elem'));
  }

}
