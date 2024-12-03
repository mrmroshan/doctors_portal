<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OdooApi {
    protected $client;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;
    protected $session_id;

    public function __construct()
{
    $this->client = new Client(['base_uri' => config('odoo.url')]);
    $this->db = config('odoo.db');
    $this->username = config('odoo.username');
    $this->password = config('odoo.password');
}

private function ensureAuthenticated()
{
    if (!$this->uid) {
        $this->uid = $this->authenticate();
    }
}

    /*
    public function authenticate() {
        $response = $this->client->post('/web/session/authenticate', [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'db' => $this->db,
                    'login' => $this->username,
                    'password' => $this->password,
                ],
                'id' => 1,
            ],
        ]);
        $result = json_decode($response->getBody());

        // Check if the result is an object and has the 'result' property
        if (!is_object($result) || !property_exists($result, 'result')) {
            throw new \Exception('Invalid response from API: ' . json_encode($result));
        }

        if (isset($result->error)) {
            throw new \Exception('Authentication failed: ' . $result->error->message);
        }
        return $result->result;
    }
    */

 

public function authenticate() {
    $response = $this->client->post('/web/session/authenticate', [
        'json' => [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'db' => $this->db,
                'login' => $this->username,
                'password' => $this->password,
            ],
            'id' => 1,
        ],
    ]);
    $result = json_decode($response->getBody());

    if (!is_object($result) || !property_exists($result, 'result')) {
        throw new \Exception('Invalid response from API: ' . json_encode($result));
    }

    if (isset($result->error)) {
        throw new \Exception('Authentication failed: ' . $result->error->message);
    }

    $this->session_id = $response->getHeader('Set-Cookie')[0] ?? null;
    return $result->result;
}

    public function create($model, $values) {
        return $this->call('create', $model, [$values]);
    }

    public function read($model, $ids, $fields) {
        return $this->call('read', $model, [$ids, $fields]);
    }

    public function update($model, $id, $values) {
        return $this->call('write', $model, [[ $id ], $values]);
    }

    public function delete($model, $id) {
        return $this->call('unlink', $model, [[ $id ]]);
    }

    public function search_read($model, $domain = [], $fields = [], $limit = 100)
{
    $this->ensureAuthenticated();
    return $this->call('search_read', $model, [$domain, $fields], ['limit' => $limit]);
    }
    
    

        private function call($method, $model, $args = [], $kwargs = [])
        {
            try {
                $headers = [];
                if ($this->session_id) {
                    $headers['Cookie'] = $this->session_id;
                }
        
                $response = $this->client->post('/web/dataset/call_kw', [
                    'headers' => $headers,
                    'json' => [
                        'jsonrpc' => '2.0',
                        'method' => 'call',
                        'params' => [
                            'model' => $model,
                            'method' => $method,
                            'args' => $args,
                            'kwargs' => $kwargs
                        ],
                        'id' => mt_rand(1, 999999999)
                    ],
                ]);
        
                $result = json_decode($response->getBody(), true);
        
                if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to decode JSON response: ' . json_last_error_msg());
                }
        
                if (isset($result['error'])) {
                    if ($result['error']['message'] === 'Odoo Session Expired') {
                        // Re-authenticate and try again
                        $this->authenticate();
                        return $this->call($method, $model, $args, $kwargs);
                    }
                    throw new \Exception('API call failed: ' . $result['error']['message']);
                }
        
                if (!is_array($result) || !isset($result['result'])) {
                    throw new \Exception('Invalid response format from API: ' . json_encode($result));
                }
        
                return $result['result'];
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                throw new \Exception('HTTP Request failed: ' . $e->getMessage());
            }
        }


}
