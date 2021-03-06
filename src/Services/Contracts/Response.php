<?php

namespace Core\Services\Contracts;

/**
 * The Response class represents an HTTP response.
 */
interface Response
{
    /**
     * Clears the response.
     *
     * @return $this
     */
    public function clear();

    /**
     * Set the output.
     *
     * @param string $content The response content
     * @param int $status The response status code
     * @param array $headers An array of response headers
     *
     * @return $this
     */
    public function output($content, $status = 200, $headers = []);

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string $name Name of the view
     * @param array $variables
     * @param int $status The response status code
     * @param array $headers An array of response headers
     * @return $this
     */
    public function view($name, $variables = [], $status = 200, $headers = []);

    /**
     * Sets the HTTP status code.
     *
     * @param int $code HTTP status code.
     * @return $this
     * // @throws \Exception If invalid status code
     */
    public function status($code = 200);

    /**
     * Gets the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Gets the HTTP status text.
     *
     * @return string
     */
    public function getStatusText();

    /**
     * Adds a header to the response.
     *
     * @param string|array $name Header name or array of names and values
     * @param string $value Header value
     * @return $this
     */
    public function header($name, $value = null);

    /**
     * Returns the headers from the response
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Writes content to the response body.
     *
     * @param string $str Response content
     * @return $this
     */
    public function write($str);

    /**
     * Sets caching headers for the response.
     *
     * @param int|string $expires Expiration time
     * @return $this
     */
    public function cache($expires);

    /**
     * Sends a HTTP response.
     */
    public function send();
}
