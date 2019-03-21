<?php

/*
 * Copyright (c) 2019 The University of Queensland
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/*
 * Written by Dane Cousins <dane@uq.edu.au> as part of the IT
 * Infrastructure Services Group in the department of 
 * Information Technology Services.
 */
namespace SimpleSAML\Module\dynamodb\Store;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Webmozart\Assert\Assert;

use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;


class DynamoDb extends \SimpleSAML\Store
{
    protected $client;
    protected $table;

    public function __construct()
    {
        $config = Configuration::getConfig('module_dynamodb.php');

        $region = $config->getString('region', 'ap-southeast-2');
        $version = $config->getString('version', 'latest');

        $params = [
            'version'   => $version,
            'region'    => $region
        ];
        
        $this->client = new DynamoDbClient($params);
        $this->table = $config->getString('table', 'sessions');
    }

    /**
     * Retrieve a value from the datastore.
     *
     * @param string $type  The datatype.
     * @param string $key  The key.
     * @return mixed|NULL  The value.
     */
    public function get($type, $key)
    {
        Assert::string($type);
        Assert::string($key);

        $marshaler = new Marshaler();
        $item = $marshaler->marshalJson('
            {
                "id": "'.$key.'"
            }
        ');

        $params = [
            'TableName' => $this->table,
            'Key'       => $item
        ];

        try {
            $result = $this->client->getItem($params);
            $item = $result['Item'];
            if ($item['expire']['N'] == 0 || $item['expire']['N'] > time()) {
                $value = $item['value']['S'];

                if (is_resource($value)) {
                    $value = stream_get_contents($value);
                }

                $decoded_value = unserialize(urldecode($value));

                if ($decoded_value === false) {
                    return null;
                } else {
                    return $decoded_value;
                }
            } else {
                return null;
            }
        } catch (DynamoDbException $e) {
            error_log("Unable to get Item:");
            error_log($e->getMessage());
            raise($e);
        }
    }

    /**
     * Save a value to the datastore.
     *
     * @param string $type  The datatype.
     * @param string $key  The key.
     * @param mixed $value  The value.
     * @param int|NULL $expire  The expiration time (unix timestamp), or NULL if it never expires.
     */
    public function set($type, $key, $value, $expire = null)
    {
        Assert::string($type);
        Assert::string($key);
        Assert::nullOrInteger($expire);
        Assert::greaterThan($expire, 2592000);

        $value = rawurlencode(serialize($value));

        $marshaler = new Marshaler();
        $item = $marshaler->marshalJson('
            {
                "id": "'.$key.'",
                "expire": '.$expire.',
                "value": "'.$value.'"
            }
        ');

        $params = [
            'TableName' => $this->table,
            'Item'      => $item
        ];

        try {
            $result = $this->client->putItem($params);
        } catch (DynamoDbException $e) {
            error_log("Unable to add item:");
            error_log($e->getMessage());
            raise($e);
        }
    }

    /**
     * Delete a value from the datastore.
     *
     * @param string $type  The datatype.
     * @param string $key  The key.
     */
    public function delete($type, $key)
    {
        Assert::string($type);
        Assert::string($key);

        $marshaler = new Marshaler();

        $table_key = $marshaler->marshalJson('
            {
                "id": "'.$id.'"
            }
        ');

        $params = [
            'TableName' => $this->table,
            'Key'       => $table_key
        ];

        try {
            $result = $this->client->deleteItem($params);
        } catch (DynamoDbException $e) {
            error_log("Unable to delete item:");
            error_log($e->getMessage());
            raise($e);
        }
    }
}
