<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Exceptions\OdooApiException;
use Illuminate\Support\Facades\Cache;

class OdooApi
{
    protected Client $client;
    protected string $db;
    protected string $username;
    protected string $password;
    protected ?int $uid = null;
    protected ?string $session_id = null;
    
    // Cache duration for product list (10 minutes)
    const CACHE_DURATION = 600;

    public function __construct()
{
    $this->client = new Client([
        'base_uri' => config('odoo.url'),
        'timeout'  => 30,
        'verify' => config('odoo.verify_ssl', true)
    ]);
    
    $this->db = config('odoo.db');
    $this->username = config('odoo.username');
    $this->password = config('odoo.password');
}

// Add environment helper method
protected function isProduction(): bool
{
    return config('app.env') === 'production';
}





// Add logging enhancement
protected function logApiAction(string $action, array $data = []): void
{
    Log::info("Odoo API {$action} in " . ($this->isProduction() ? 'PRODUCTION' : 'STAGING'), [
        'action' => $action,
        'environment' => config('app.env'),
        'data' => $data
    ]);
}





    private function ensureAuthenticated(): void
    {
        if (!$this->uid || !$this->session_id) {
            $this->authenticate();
        }
    }





    public function authenticate(): array
    {
        try {
            $authData = [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'db' => $this->db,
                    'login' => $this->username,
                    'password' => $this->password,
                ],
                'id' => mt_rand(1, 999999999)
            ];
    
            Log::info('Attempting Odoo authentication', [
                'url' => config('odoo.url'),
                'db' => $this->db,
                'username' => $this->username,
                'password_length' => strlen($this->password),
            ]);
    
            $response = $this->client->post('/web/session/authenticate', [
                'json' => $authData,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]);
    
            $result = json_decode($response->getBody()->getContents(), true);
    
            Log::info('Odoo authentication response', [
                'status_code' => $response->getStatusCode(),
                'has_result' => isset($result['result']),
                'has_error' => isset($result['error']),
                'raw_response' => $result
            ]);
    
            if (!isset($result['result'])) {
                throw new OdooApiException('Invalid authentication response: ' . json_encode($result));
            }
    
            if (isset($result['error'])) {
                throw new OdooApiException("Authentication failed: {$result['error']['message']}");
            }
    
            $this->session_id = $response->getHeader('Set-Cookie')[0] ?? null;
            $this->uid = $result['result']['uid'] ?? null;
    
            return $result['result'];
        } catch (\Exception $e) {
            Log::error('Odoo authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to authenticate with Odoo: ' . $e->getMessage());
        }
    }



    /**
     * Fetch medication list from Odoo
     */
    public function getMedicationList(array $filters = [], int $limit = 100): array
    {
        $cacheKey = 'odoo_medications_' . md5(serialize($filters));
    
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters, $limit) {
            // Modified domain to use basic product filters first
            $domain = [
                ['type', '=', 'product'],  // or 'consu' for consumables
                ['sale_ok', '=', true],
                ['active', '=', true]
            ];
    
            // Add custom filters to domain
            if (!empty($filters)) {
                $domain = array_merge($domain, $filters);
            }
    
            $fields = [
                'id',
                'name',
                'default_code', // SKU
                'list_price',
                'qty_available',
                'virtual_available',
                'description',
                'detailed_type',
                'uom_id',
                // Removed image_1920 as it might be too large
            ];
    
            try {
                return $this->search_read('product.product', $domain, $fields, $limit);
            } catch (\Exception $e) {
                Log::error('Failed to fetch medications', [
                    'error' => $e->getMessage(),
                    'domain' => $domain,
                    'fields' => $fields
                ]);
                throw new OdooApiException('Failed to fetch medications: ' . $e->getMessage());
            }
        });
    }





    /**
     * Create a sales order from prescription
     */
    public function createSalesOrder(array $orderData): int
    {
        $this->ensureAuthenticated();

        try {
            // Prepare order lines
            $orderLines = array_map(function ($line) {
                return [0, 0, [
                    'product_id' => $line['product_id'],
                    'product_uom_qty' => $line['quantity'],
                    'price_unit' => $line['price_unit'],
                    'name' => $line['description'] ?? '',
                ]];
            }, $orderData['order_lines']);

            $salesOrder = [
                'partner_id' => $orderData['customer_id'],
                'order_line' => $orderLines,
                'prescription_reference' => $orderData['prescription_id'],
                'doctor_id' => $orderData['doctor_id'],
                'note' => $orderData['notes'] ?? '',
                'date_order' => date('Y-m-d H:i:s'),
            ];

            $orderId = $this->create('sale.order', $salesOrder);

            // Confirm the sale order
            if ($orderData['auto_confirm'] ?? false) {
                $this->call('action_confirm', 'sale.order', [[$orderId]]);
            }

            return $orderId;
        } catch (\Exception $e) {
            Log::error('Failed to create sales order in Odoo', [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            throw new OdooApiException('Failed to create sales order: ' . $e->getMessage());
        }
    }






    /**
     * Create or update customer in Odoo
     */
    public function syncCustomer(array $patientData): int
    {
        $this->ensureAuthenticated();

        try {
            // Check if customer exists
            $existing = $this->search_read(
                'res.partner',
                [
                    ['phone', '=', $patientData['phone']],
                    ['email', '=', $patientData['email']]
                ],
                ['id']
            );

            $customerData = [
                'name' => $patientData['name'],
                'phone' => $patientData['phone'],
                'email' => $patientData['email'],
                'street' => $patientData['address'] ?? '',
                'city' => $patientData['city'] ?? '',
                'is_patient' => true,
                'customer_rank' => 1,
            ];

            if (!empty($existing)) {
                $customerId = $existing[0]['id'];
                $this->update('res.partner', $customerId, $customerData);
                return $customerId;
            }

            return $this->create('res.partner', $customerData);
        } catch (\Exception $e) {
            Log::error('Failed to sync customer with Odoo', [
                'error' => $e->getMessage(),
                'data' => $patientData
            ]);
            throw new OdooApiException('Failed to sync customer: ' . $e->getMessage());
        }
    }






    /**
     * Check product availability
     */
    public function checkProductAvailability(array $productIds): array
    {
        $this->ensureAuthenticated();

        try {
            return $this->read(
                'product.product',
                $productIds,
                ['id', 'name', 'qty_available', 'virtual_available', 'incoming_qty', 'outgoing_qty']
            );
        } catch (\Exception $e) {
            Log::error('Failed to check product availability', [
                'error' => $e->getMessage(),
                'products' => $productIds
            ]);
            throw new OdooApiException('Failed to check product availability: ' . $e->getMessage());
        }
    }

    /**
     * Get order status
     */
    public function getOrderStatus(int $orderId): array
    {
        $this->ensureAuthenticated();

        try {
            return $this->read(
                'sale.order',
                [$orderId],
                ['name', 'state', 'invoice_status', 'delivery_status']
            )[0];
        } catch (\Exception $e) {
            Log::error('Failed to get order status', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw new OdooApiException('Failed to get order status: ' . $e->getMessage());
        }
    }




    /**
     * Fetch sales orders from Odoo
     * 
     * @param array $filters Additional domain filters
     * @param int $limit Maximum number of records to return
     * @return array Formatted sales orders
     * @throws OdooApiException
     */
    public function getSalesOrders(array $filters = [], int $limit = 100): array
    {
        try {
            $this->ensureAuthenticated();
            
            // Base domain filters
            $domain = [
                ['state', '!=', 'cancel']  // Exclude cancelled orders
            ];
            
            // Merge custom filters
            if (!empty($filters)) {
                $domain = array_merge($domain, $filters);
            }
            
            $fields = [
                'id',
                'name',           // Order reference
                'partner_id',     // Customer
                'date_order',     // Order date
                'amount_total',   // Total amount
                'state',          // Status
                'order_line',     // Order lines
                'prescription_reference',  // Custom field for prescription reference
                'doctor_id'       // Doctor reference
            ];
            
            $this->logApiAction('getSalesOrders', [
                'domain' => $domain,
                'limit' => $limit
            ]);
            
            $result = $this->search_read(
                'sale.order',
                $domain,
                $fields,
                $limit
            );
            
            // Transform the result to match the expected format
            return array_map(function ($order) {
                return [
                    'id' => $order['id'],
                    'reference' => $order['name'],
                    'customer' => $order['partner_id'] ? [
                        'id' => $order['partner_id'][0],
                        'name' => $order['partner_id'][1]
                    ] : null,
                    'date' => $order['date_order'],
                    'total_amount' => $order['amount_total'],
                    'status' => $order['state'],
                    'order_lines' => $order['order_line'],
                    'prescription_id' => $order['prescription_reference'] ?? null,
                    'doctor_id' => $order['doctor_id'] ? $order['doctor_id'][0] : null
                ];
            }, $result);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales orders from Odoo', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to fetch sales orders: ' . $e->getMessage());
        }
    }



    // Base CRUD operations
    public function create(string $model, array $values): int
    {
        $this->ensureAuthenticated();
        return $this->call('create', $model, [$values]);
    }

    public function read(string $model, array $ids, array $fields): array
    {
        $this->ensureAuthenticated();
        return $this->call('read', $model, [$ids, $fields]);
    }

    public function update(string $model, int $id, array $values): bool
    {
        $this->ensureAuthenticated();
        return $this->call('write', $model, [[$id], $values]);
    }

    public function delete(string $model, int $id): bool
    {
        $this->ensureAuthenticated();
        return $this->call('unlink', $model, [[$id]]);
    }

    public function search_read(string $model, array $domain = [], array $fields = [], int $limit = 100): array
    {
        $this->ensureAuthenticated();
        return $this->call('search_read', $model, [$domain, $fields], ['limit' => $limit]);
    }





    private function call(string $method, string $model, array $args = [], array $kwargs = [])
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

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result === null) {
                throw new OdooApiException('Failed to decode JSON response: ' . json_last_error_msg());
            }

            if (isset($result['error'])) {
                if (strpos($result['error']['message'], 'Session Expired') !== false) {
                    $this->authenticate();
                    return $this->call($method, $model, $args, $kwargs);
                }
                throw new OdooApiException('API call failed: ' . $result['error']['message']);
            }

            return $result['result'];
        } catch (\Exception $e) {
            Log::error('Odoo API call failed', [
                'method' => $method,
                'model' => $model,
                'error' => $e->getMessage()
            ]);
            throw new OdooApiException('API call failed: ' . $e->getMessage());
        }
    }
}