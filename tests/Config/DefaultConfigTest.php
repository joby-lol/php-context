<?php

namespace Joby\ContextInjection\Config;

use PHPUnit\Framework\TestCase;

class DefaultConfigTest extends TestCase
{
    public function testGettingAndSetting(): void
    {
        $c = new DefaultConfig(
            ['a' => 'default_a'],
            ['a' => 'initial_a']
        );
        $this->assertTrue($c->has('a'));
        $this->assertEquals('initial_a', $c->get('a'));
        $c->set('a', 'new_a');
        $this->assertTrue($c->has('a'));
        $this->assertEquals('new_a', $c->get('a'));
        $c->unset('a');
        $this->assertTrue($c->has('a'));
        $this->assertEquals('default_a', $c->get('a'));
    }
}