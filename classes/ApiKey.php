<?php

/**
 * API Key Verifier.
 * 
 * This class is handles the validation and verification of an API key provided
 * by the client. This key is compared against the correct key stored the
 * 'env.php' file.
 * 
 * @author Scott Berston
 */
class ApiKey
{
    private string $api_key;

    /**
     * Constructs an new ApiKey instance with the correct key.
     * 
     * @param string $api_key The correct API key to verify against. This key
     * is NOT to be encoded or decoded beforehand.
     */
    public function __construct(string $api_key)
    {
        $this->set_api_key($api_key);
    }

    /**
     * Sets the API key.
     * 
     * @param string $api_key The correct API key to compare the incoming
     * response with. This key is NOT to be encoded or decoded beforehand.
     */
    private function set_api_key(string $api_key): void
    {   
        $this->api_key = $api_key;
    }

    /**
     * Validate and verify whether the API key provided by the client matches
     * the one in the env.php file.
     * 
     * This method validates if the API key is provided and is a Bearer token.
     * It then verifies whether the key provided by the client matches the one
     * in the env.php file.
     * 
     * @param array $request_headers An associative array containing the
     * headers sent in the client request.
     * 
     * @throws ClientError
     * - If the Authorization Header is not found
     * - The Header type is invalid
     * - Or the Authorization key is invalid.
     * 
     * @return bool Whether the API key is correct. If it is correct, 'true' is
     * returned. This method should not return 'false' under any circumstance
     * as a ClientError exception should be thrown if any validation fails.
     */
    public function validate_api_key(array $request_headers): bool
    {
        // Get the Authorization Header.
        if (array_key_exists("Authorization", $request_headers)) {
            $authorization_header = $request_headers["Authorization"];
        } elseif (array_key_exists("authorization", $request_headers)) {
            $authorization_header = $request_headers["Authorization"];
        } else {
            throw new ClientError("Authorization Header is not found.", 400);
            return false;
        }


        // Check if the bearer token is present.
        $header =  substr($authorization_header, 0, 6);
        if ($header != "Bearer") {
            throw new ClientError("Invalid Authorization Header: $header.", 400);
            return false;
        }

        // Extract and decode the API key.
        $client_key = trim(substr($authorization_header, 7));
        $decoded_client_key = base64_decode($client_key);

        // Verify the API key.
        if ($this->api_key != $decoded_client_key) {
            throw new ClientError("The authorization key used is invalid ($client_key).", 401);
            return false;
        }

        return true;
    }
}
