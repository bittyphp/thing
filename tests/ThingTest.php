<?php
namespace BittyPHP\Thing\Test;

use BittyPHP\Thing;

class ThingTest extends \PHPUnit_Framework_TestCase
{
    public $sample = array(
        'a' => 'aa',
        'b' => array(
            'bb' => 'bbb',
        ),
        'c' => true,
    );

    public function testConstructNormal()
    {
        $thing = new Thing($this->sample);
        $this->assertSame($thing['a'], $this->sample['a']);
    }

    public function testSetter()
    {
        $thing = new Thing();
        $thing->set($this->sample);
        $this->assertSame($thing->a, $this->sample['a']);
    }

    public function testHook()
    {
        $sample = $this->sample;
        $thing = new Thing($sample);
        $thing->hook('c', function () use ($sample) {
            return $sample['a'];
        });
        $this->assertSame($thing['c'], $this->sample['a']);
    }

    public function testConstructHook()
    {
        $sample = $this->sample;
        $thing = new Thing($sample, array(
            'c' => function () use ($sample) {
                return $sample['a'];
            },
        ));

        $this->assertSame($thing['c'], $this->sample['a']);
    }

    public function testHasChild()
    {
        $thing = new Thing($this->sample);
        $this->assertTrue($thing->hasChild('b'));
    }

    public function testToJSON()
    {
        $thing = new Thing($this->sample);
        $this->assertSame($thing->toJSON(), json_encode($this->sample));
    }

    public function testArrayKeys()
    {
        $thing = new Thing($this->sample);
        $this->assertSame($thing->keys(), array_keys($this->sample));
    }

    public function testArrayValues()
    {
        $thing = new Thing($this->sample);
        $this->assertSame($thing->values(), array_values($this->sample));
    }

    public function testArraySlice()
    {
        $thing = new Thing($this->sample);
        $this->assertSame($thing->slice(2, 1), array_slice($this->sample, 2, 1));
    }
}
