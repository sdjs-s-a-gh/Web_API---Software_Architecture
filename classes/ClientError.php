<?php

/** 
 * Client Error Exception.
 * 
 * This class is used to handle any client errors. Notably, these errors have
 * the 4XX status codes.
 * 
 * @author Scott Berston
 */
class ClientError extends Exception
{
    /** 
     * Constructs a new ClientError exception.
     * 
     * This constructor allows setting an additional custom message and a HTTP
     * error code. If a valid HTTP status code is provided, a relevant default
     * message will be applied. Additional information, such as a specific
     * parameter that caused the error, can be appended with the $message
     * parameter.
     *  
     * @param string $message [optional] Additional information about what went
     * wrong.
     * @param int $code [optional] The HTTP status code for the error.
     */
    public function __construct(string $message="", int $code=0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->set_reason_phrase();
    }

    /**
     * Return the HTTP status code of the error.
     * 
     * @return int The HTTP status code that is associated with the error.
     */
    public function get_status_code(): int 
    {
        return $this->code;
    }
    
    /**
     * Sets a reason phrase for the ClientError's HTTP status code.
     * 
     * This method assigns a standard reason phrase to the Exception based on
     * its HTTP status code. If an additional message is supplied in the
     * constructor, this will be appended to the standard reason phrase to
     * further contextualise what went wrong to the user.
     */
    private function set_reason_phrase(): void
    {
        $code = $this->code;

        // Save the original message to a new variable if one is set.
        if (isset($this->message)) {
            $msg_to_append = ": ".$this->getMessage();
        }

        switch ($code)
        {   
            case 400:
                $this->message = "Bad Request";
                break;
            case 401: 
                $this->message = "Unauthorised";
                break;
            case 404:
                $this->message = "Resource Not found";
                break;
            case 405:
                $this->message = "Method not allowed";
                break;
            case 422:
                $this->message = "Invalid Parameter";
                break;
            default:
            // If no status code is provided.
                $this->message = "Unknown Error";
                $this->code = 500;
                break;
        }

        // Append the original message to the standardised message to provide further context of an error.
        if (isset($msg_to_append)) {
            $this->message .= $msg_to_append;
        }

    }
}