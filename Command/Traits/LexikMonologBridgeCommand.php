<?php

namespace Splash\Tasking\Command\Traits;

use Splash\Tasking\Processor\WebExtendedProcessor;

/**
 * @abstract    Overide Lexik Monolog Bridge Handler to Enable Database Log on Console Command 
 */
trait LexikMonologBridgeCommand {
    
    protected function overrideLexikMonologBridge() {
        
        if ( $this->getContainer()->has("lexik_monolog_browser.handler.doctrine_dbal") ) {
            $Handler =   $this->getContainer()->get("lexik_monolog_browser.handler.doctrine_dbal");
            $Handler->popProcessor();
            $Handler->pushProcessor(new WebExtendedProcessor());      
            $this->getContainer()->get("logger")->notice("Lexik Logger Processor Replaced");
        }
        
    }
    
}
