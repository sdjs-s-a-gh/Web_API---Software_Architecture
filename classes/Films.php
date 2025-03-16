<?php

/**
 * Films endpoint.
 * 
 * This class represents the Films endpoint, supporting access to five HTTP
 * methods. These methods include GET, POST, PATCH, DELETE AND OPTIONS.
 * 
 * @author Scott Berston
 */
class Films extends Endpoint
{
    private Database $database;

    /** 
     * Constructor for the Films endpoint.
     * 
     * @param Database $database An instance of the Database class to allow for
     * database interactions.
     */
    public function __construct(Request $request, Database $database, ApiKey $api_key)
    {
        $this->database = $database;
        parent::__construct($request, $api_key);
    }

    /**
     * Queries the database for films based on many parameters passed in the
     * URL.
     * 
     * This method handles GET requests and allows users to filter the list of
     * films by passing in a potential of four different query parameters in
     * the URL.
     * 
     * Supported Parameters:
     * - `film_id`: Returns a film with the corresponding film_id.
     * - `actor_id`: Filter films to only those where the actor_id is present.
     * - `search`: Performs a search in the film titles and descriptions. This
     * parameter uses a logical OR across both attributes.
     * - `page`: Implements pagination, offsetting the returned films by a
     * given number.
     *  
     * @throws PDOException If there is a database query error.
     * @throws ClientError If any query parameter has an invalid data type.
     */
    protected function get(): void
    {
        $db = $this->database;
        
        /** The WHERE parameter would start to become very hard to read/comprehend if you where to include more parameters
         * So, I have instead added a JOIN, which is also a mixture of me finally wrapping my head around the concept.*/ 
        $sql_query = "SELECT film.film_id, film.title, film.description, film.release_year, category.name as category_name, language.name as language, film.length, film.rating
        FROM film
        JOIN category ON film.category_id = category.category_id
        JOIN language ON film.language_id = language.language_id";
        
        $url_query_parameters = $this->request->get_query_parameters();
        $sql_query_parameters = [];
        
        foreach ($url_query_parameters as $param_name=>$param_value) {
            $sql_condition = "";
            
            switch ($param_name)
            {
                case "page":
                    if (!is_numeric($param_value)) {
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }
                    break;
                case "actor_id":
                    if (!is_numeric($param_value)) {
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }
                    // Remove films that do not have any actors in
                    $sql_query .= " JOIN film_actor ON film.film_id = film_actor.film_id";
                    $sql_condition = " film_actor.actor_id = :actor_id";
                    $sql_query_parameters["actor_id"] = $param_value;
                    break;
                case "film_id":
                    if (!is_numeric($param_value)){
                        throw new ClientError("$param_name. Expected a number.", 422);
                    }                      
                    $sql_condition = " film.film_id = :film_id";
                    $sql_query_parameters["film_id"] = $param_value;
                    break;
                case "search":
                    $sql_condition = " (film.title LIKE :search OR film.description LIKE :search)";
                    $sql_query_parameters["search"] = "%". $param_value. "%";
                    break;
                default:
                    throw new ClientError("'$param_name' is an Unknown Parameter.", 422);
            }

            // Check whether the query should append a WHERE or an AND clause.
            if (!str_contains($sql_query, "WHERE") AND $sql_condition !== "") {
                $sql_query.= " WHERE";
            } elseif ($sql_condition !== "") {
                $sql_query.= " AND";
            }

            // Append the new condition to the overall query 
            $sql_query .= $sql_condition;
        }
        // As the SQL query uses a one-to-many relationship (film_actors to film), I must add a group by to remove all duplicates that are created.
        $sql_query .= " GROUP BY film.film_id";

        // The offset must be handled last
        if (isset($url_query_parameters["page"])) {
            $offset = ($url_query_parameters["page"] - 1) * 10;
            $sql_query .= " LIMIT 10 OFFSET :offset";
            $sql_query_parameters["offset"] = $offset;
        }

        $data = $db->execute_SQL($sql_query, $sql_query_parameters);
        
        $this->set_status_code(200);
        $this->set_data($data);
    }

    /**
     * Creates a new film.
     * 
     * This method handles POST requests and creates a new film based off
     * parameters passed in the request body of an incoming request.
     * 
     * Required Parameters:
     * - `title`: A string representation of the film's title. 
     * - `description`: A string representation of the film's description.
     * - `language_id`: An integer that must be present in the `language` table.
     * - `category_id`: An integer that must be present in the `category` table.
     * 
     * @throws ClientError If any required parameters are missing or invalid.
     */
    protected function post(): void
    {
        $db = $this->database;

        $sql_insert_query = "INSERT INTO film (title, description, language_id, category_id) VALUES (:title, :description, :language_id, :category_id)";

        $request_body = $this->request->get_body_parameters();

        if ($request_body === null) {
            throw new ClientError("No data provided", 400);
        }
        
        $sql_parameters = [];

        // Check all parameters have been provided in the request body. If not, a ClientError exception will be thrown.
        $required_parameters = ["title", "description", "language_id", "category_id"];
        foreach ($required_parameters as $required_parameter) {
            if (!array_key_exists($required_parameter, $request_body)) {
                throw new ClientError("$required_parameter is required", 400);
            } else {
                // Copy the value of the parameter from the request body into a separate associative array.
                $sql_parameters[$required_parameter] = $request_body[$required_parameter]; 
            }
        }

        // Check if any unexpected parameters have been passed in the response body.
        foreach ($request_body as $param_name=>$param_value) {
            if (!in_array($param_name, $required_parameters)) {
                throw new ClientError("Unexpected Parameter: $param_name", 400); 
            }
        }        
                
        // Check if the language attribute is valid
        $this->validate_foreign_key("language", "language_id", $request_body["language_id"]);
        $sql_parameters["language_id"] = $request_body["language_id"];


        // Check if the category exists in the database
        $this->validate_foreign_key("category", "category_id", $request_body["category_id"]);
        $sql_parameters["category_id"] = $request_body["category_id"];

        $db->execute_SQL($sql_insert_query, $sql_parameters);
        
        $this->set_status_code(201);
        $this->set_data("Film Added");
    }
    
    /**
     * Verify whether a value in a specified atrribute exists in a table in the
     * database.
     * 
     * @param string $table The table to be queried.
     * @param string $attribute The attribute to be searched for.
     * @param mixed $value The value to be checked in the given attribute to
     * identify whether it exists.
     * 
     * @throws ClientError If the provided value does not exist in the table.
     */
    private function validate_foreign_key(string $table, string $attribute, mixed $value)
    {
        $db = $this->database;
        $sql_query = "SELECT * FROM $table WHERE $attribute = :$attribute";
        $result = $db->execute_SQL($sql_query, ["$attribute" => $value]);
        if (empty($result)) {
            throw new ClientError("$attribute not found", 400);
        }
    }
    
    /**
     * Updates a films details.
     * 
     * This method handles PATCH requests and modifies an existing films
     * information.
     */
    protected function patch(): void
    {
        $this->set_status_code(200);
        $this->set_data("patch films");
    }

    /**
     * Deletes a film.
     * 
     * This method handles DELETE requests and removes an existing film from
     * the database.
     */
    protected function delete(): void
    {
        $this->set_status_code(200);
        $this->set_data("delete films");
    }

    /** Sets the allowed HTTP methods for this (the Films) endpoint. */
    protected function options(): void
    {
        $this->set_status_code(204);
        $this->set_headers("Access-Control-Allow-Methods", "GET, POST, PATCH, DELETE, OPTIONS");
    }
}