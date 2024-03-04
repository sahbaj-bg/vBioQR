<?php

namespace atoum\atoum\tests\units\writers\std;

use atoum\atoum;
use atoum\atoum\writers\std\err as testedClass;

require_once __DIR__ . '/../../../runner.php';

class err extends atoum\test
{
    public function testClass()
    {
        $this
            ->testedClass
                ->extends(atoum\writers\std::class)
                ->implements(atoum\report\writers\realtime::class)
                ->implements(atoum\report\writers\asynchronous::class)
        ;
    }

    public function test__construct()
    {
        $this
            ->if($stderr = new testedClass())
            ->then
                ->object($stderr->getCli())->isEqualTo(new atoum\cli())
                ->object($stderr->getAdapter())->isEqualTo(new atoum\adapter())
            ->if($cli = new atoum\cli())
            ->and($adapter = new atoum\test\adapter())
            ->and($adapter->fopen = null)
            ->and($stderr = new testedClass($cli, $adapter))
            ->then
                ->object($stderr->getCli())->isIdenticalTo($cli)
                ->object($stderr->getAdapter())->isIdenticalTo($adapter)
        ;
    }

    public function testWrite()
    {
        $this
            ->if($adapter = new atoum\test\adapter())
            ->and($adapter->fopen = null)
            ->and($adapter->fwrite = null)
            ->and($stderr = new testedClass(null, $adapter))
            ->then
                ->object($stderr->write($string = uniqid()))->isIdenticalTo($stderr)
                ->adapter($adapter)->call('fwrite')->withArguments(null, $string)->once()
                ->object($stderr->write($string = uniqid() . "\n"))->isIdenticalTo($stderr)
                ->adapter($adapter)->call('fwrite')->withArguments(null, $string)->once()
        ;
    }
}
