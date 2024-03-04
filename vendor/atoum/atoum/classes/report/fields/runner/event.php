<?php

namespace atoum\atoum\report\fields\runner;

use atoum\atoum\report;
use atoum\atoum\runner;
use atoum\atoum\test;

abstract class event extends report\fields\event
{
    public function __construct()
    {
        parent::__construct(
            [
                runner::runStart,
                test::fail,
                test::error,
                test::void,
                test::uncompleted,
                test::skipped,
                test::exception,
                test::success,
                runner::runStop
            ]
        );
    }
}
