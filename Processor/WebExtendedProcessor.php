<?php

namespace Splash\Tasking\Processor;

use Lexik\Bundle\MonologBrowserBundle\Processor\WebExtendedProcessor as BaseWebExtendedProcessor;

class WebExtendedProcessor extends BaseWebExtendedProcessor
{
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['http_server'] = $this->serverData;
        $record['http_post']   = $this->postData;
        $record['http_get']    = $this->getData;
        return $record;
    }
}
