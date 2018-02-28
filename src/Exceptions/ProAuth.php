<?php

namespace ProAuth\Exceptions;

class ProAuth extends \Exception
{
    public function __construct($errors, $code = 0)
    {
        if (is_array($errors)) {
            $message = '';
            foreach ($errors as $error) {
                if ($message) {
                    $message .= PHP_EOL;
                }

                $message .= $error['message'];
            }
        } else {
            $message = $errors;
        }
        
        parent::__construct($message, $code);
    }
}
