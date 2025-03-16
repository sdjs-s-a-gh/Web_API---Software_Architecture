<?php

/** 
 * General exception handler.
 * 
 * This subroutine catches every uncaught runtime error caused in the program. Since the
 * Router class explicitly catches ClientError exceptions, this handler only
 * deals with server-related issues. These issues would notably include
 * database execution errors.
 * 
 * As this script - and by extension this subroutine - runs independently to
 * the rest of the API, the HTTP response code has to be explicitly called
 * within rather than in the Router class.
 *  
 * @author Scott Berston
 */
set_exception_handler(function($exception): void
{
    http_response_code(500);
    $data["message"] = $exception->getMessage();
    $data["code"] = $exception->getCode();
    $data["file"] = $exception->getFile();
    $data["line"] = $exception->getLine();    
    echo json_encode($data);
    exit();
});