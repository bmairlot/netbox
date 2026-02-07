<?php
namespace unamur\CloudGen;

class CloudGenException extends \Exception
{
    // You can add custom methods or properties here if needed

    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        // Call the parent constructor
        parent::__construct($message, $code, $previous);
    }



}

