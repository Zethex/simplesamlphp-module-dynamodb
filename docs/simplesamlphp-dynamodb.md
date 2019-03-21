DynamoDb Store module
=================

<!--
	This file is written in Markdown syntax.
	For more information about how to use the Markdown syntax, read here:
	http://daringfireball.net/projects/markdown/syntax
-->


<!-- {{TOC}} -->

Introduction
------------

The dynamodb module implements a Store that can be used as a backend
for SimpleSAMLphp session data like the phpsession, sql, or memcache
backends.

Preparations
------------

An AWS DynamoDb table is required to be pre-created.  Please refer 
to the AWS documentation for this.  Part of this creation, define the 
key as `id` and a field of `expiry` as the TTL.

Additionally, it will require some form of IAM credentials.  Currently the
module uses a naive approach and uses the default AWS SDK credential provider.

The IAM permissions required from the credentials set are
below (replace {TABLE_NAME} with the above created table):
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "SimpleSamlDynamoDb",
            "Effect": "Allow",
            "Action": [
                "dynamodb:PutItem",
                "dynamodb:DeleteItem",
                "dynamodb:GetItem"
            ],
            "Resource": "arn:aws:dynamodb:*:*:table/{TABLE_NAME}"
        }
    ]
}
```


Finally, you need to config SimpleSAMLphp to use the dynamodb Store by
enabling the following modules:

 1. dynamodb

Enabling the dynamodb module allows it to be loaded and used as a storage
backend.

You also need to copy the `config-templates` files from the cron
module above into the global `config/` directory.

	$ cd /var/simplesamlphp
	$ touch modules/dynamodb/enable
	$ cp modules/dynamodb/config-templates/*.php config/


Configuring the dynamodb module
---------------------------

The dynamodb module uses the following configuration options specified
in `config/module_dynamodb.php`. The defaults are listed:

	$config = [
        'path' => 'aws/aws-sdk-php',
        'region' => 'ap-southeast-2',
        'table' => 'sessions',
        'version' => 'latest',   
	];

Finally, the module can be specified as the Store in `config/config.php`
with the following setting:

		'store.type' => 'dynamodb:DynamoDb',
