<?php

namespace SteveSteele\GroceriesTest;

use WP_Mock;
use WP_Mock\Tools\TestCase;

class BaseTestCase extends TestCase
{
    public function setUp()
    {
        WP_Mock::setUp();
    }

    public function tearDown()
    {
        WP_Mock::tearDown();
    }

    /**
     * Mock WP function `wp_kses`
     * @param  mixed $input  User input
     * @param  mixed $output Filtered output
     * @return void
     */
    protected function mockWpKses($input, $output = null)
    {
        $output = ($output) ?: $input;
        WP_Mock::userFunction('wp_kses', [
            'times' => 1,
            'args' => [$input, []],
            'return' => $output,
        ]);
    }
}
