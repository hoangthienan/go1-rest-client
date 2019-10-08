# GO1-Rest-Client

Find and rename `config.sample.php` to `config.php`, then edit the file and add your clientId, clientSecret

```
try {
    $auth = new GO1Auth();
    $accessToken = $auth->post('oauth/token', $params);
    $accessToken = $accessToken['access_token'] ?? '';

    $go1 = new GO1Client($accessToken);
    $portalDetails = $go1->get('v2/account');
    dump($portalDetails);
} catch (Exception $e) {
    dump($e);
}
```