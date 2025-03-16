<?php

/**
 * Developer endpoint.
 * 
 * This class returns data about the developer of the API, including their Name
 * and Student ID.
 * 
 * @author Scott Berston
 */

 class Developer extends Endpoint
 {
    /**
     * Sets the Name and StudentID of the developer.
     * 
     * This method handles GET requests and sets the Name and StudentID of the
     * developer.
     */
    protected function get(): void
    {        
        $data = array(
            "student_id" => "w23027648",
            "name" => "Scott Berston"            
        );

        $this->set_status_code(200);
        $this->set_data($data);
    }

    /** Sets the allowed HTTP methods for this (the Developer) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, OPTIONS");
    }
 }