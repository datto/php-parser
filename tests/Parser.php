<?php

namespace Datto\Tests;

use Datto;

class Parser extends Datto\Parser
{
    protected function decode(&$output)
    {
        $output = @json_decode($this->input, true);

        if ($output === null) {
            return false;
        }

        $this->i = strlen($this->input);
        return true;
    }

    protected function encode()
    {
        $arguments = func_get_args();
        return json_encode($arguments);
    }
}
