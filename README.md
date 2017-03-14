
# MetaTech PwsClient

a php webservice client managing [ PwsAuth ](https://github.com/meta-tech/pws-auth) protocol

### Requirements

PHP >= 5.4

### Install

The package can be installed using [ Composer ](https://getcomposer.org/). (not yet)
```
composer require meta-tech/pws-client
```

Or add the package to your `composer.json`.

```
"require": {
    "meta-tech/pws-client" : "1.0"
}
```

### Usage
When instantiating, PwsClient automatically checks if it has been authenticated. Otherwise, or if the session has expired, 
the client will perform the authentication. Then, you can initiate get or post call

```php
<?php
require_once(__dir__ . '/vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;
use MetaTech\PwsAuth\Authenticator;
use MetaTech\Ws\Client;

$config        = Yaml::parse(file_get_contents(__dir__ . '/config/pwsauth.yml'));
$authenticator = new Authenticator($config);

$config        = Yaml::parse(file_get_contents(__dir__ . '/config/pwsclient.yml'));
$client        = new Client($config, $authenticator);
// on instanciation the client init this calls :
// $client->check();
// enventually $client->call() (depending on previous response);

// get example
$response = $client->get('/ws/person/222');
if ($response->done) {
    // do stuff
    
}
// post example
$client->post('/ws/person/222/update', [ 'firstname' => 'toto']);
if ($response->done) {
    // do stuff
}

// to close and destroy session on serverside :
// $client->logout();

```

### Config

```yaml
# pwsclient config

# 0 : disable, 1 : verboose, 2 : most verboose
debug       : 1
protocol    : https://
hostname    : pwsserver.docker
# file storing the server 's session id - must be out of DocumentRoot and read/writable by server
store       : wsess
login       : test
password    : test
key         : test
# 0 : display cli, 1 : display html
html_output : 0
# http authentication
http        : 
    user        :
    password    :
# server uris for authentication
uri         :
    auth        : /ws/auth
    logout      : /ws/logout
    check       : /ws/isauth

```

### Server Response

PwsClient intend to receiv any JsonResponse, the structure of the response is free.  
However, meta-tech always return this simple Json Structure :  
`{ done : boolean, msg : 'string contextual msg', data : whatever }`


### License

The project is released under the MIT license, see the LICENSE file.
