<?php
/**
 * A front door script for my Web API.
 * 
 * This script serves as a single point of entry for handling API requests, 
 * routing them to their corresponding endpoint. These endpoints are classes
 * that have been designed to accommodate different behaviour depending on the
 * endpoint and its requested HTTP method. 
 * 
 * This API acts as an information resource based on RESTful principles,
 * returning data about actors and films as well as allowing creation, updating
 * and deletion (CRUD).
 * 
 * This API is publically accessible. or
 * This API is limited to devices submitting request from the same domain,
 * which is w23027648.nuwebspace.co.uk.
 *  
 * @author Scott Berston
 */

require "exception_handler.php";
require "autoloader.php";
$env = require "env.php";

$database = new Database($env["db_file_path"]);
$api_key = new ApiKey($env["api_key"]);

$request = new Request();

$router = new Router($database, $api_key);
$router->route($request);