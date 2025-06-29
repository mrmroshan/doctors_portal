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
    
    private const MAX_RETRIES = 2;
    private const CACHE_DURATION = 600; // 10 minutes
    private $retryCount = 0;

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

    
    



    /**
     * Get product data from Odoo
     *
     * @param int $productId
     * @return array|null
     */
    public function getProductData(int $productId)
    {
        try {
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'product.product',
                'method' => 'search_read',
                'args' => [
                    [['id', '=', $productId]],
                    ['id', 'name', 'default_code', 'list_price']
                ],
                'kwargs' => [
                    'context' => ['lang' => 'en_US']
                ]
            ]);

            return $result[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch product data from Odoo', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to fetch product data: ' . $e->getMessage());
        }
    }






    /**
     * Get list of medications from Odoo
     */
    public function getMedicationList()
    {
        try {
            $this->retryCount = 0;
    
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'product.product',
                'method' => 'search_read',
                'args' => [
                    [
                        ['type', '=', 'product'],  // Only physical products
                        ['sale_ok', '=', true],    // Can be sold
                        ['active', '=', true]      // Active products only
                    ],
                    [
                        'id',
                        'name',
                        'default_code',  // SKU/Internal Reference
                        'list_price',    // Include the list_price field
                        'qty_available'
                    ]
                ],
                'kwargs' => [
                    'context' => ['lang' => 'en_US']
                ]
            ]);
    
            // Transform the result to a more usable format
            return collect($result)->map(function ($product) {
                return [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'default_code' => $product['default_code'] ?? '',
                    'price' => $product['list_price'] ?? 0,
                    'qty_available' => $product['qty_available'] ?? 0
                ];
            })->values()->all();
    
        } catch (\Exception $e) {
            Log::error('Failed to fetch medications from Odoo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to fetch medications: ' . $e->getMessage());
        }
    }



    /**
     * Get cached medication list
     */
    public function getCachedMedicationList()
    {
        return Cache::remember('odoo_medications', self::CACHE_DURATION, function () {
            return $this->getMedicationList();
        });
    }





    public function getSalesOrdersByDoctor($filter = null)
    {
        try {
            // Base filters - always exclude cancelled orders
            $filters = [
                ['state', '!=', 'cancel']
            ];
    
            // Add doctor filter if specified
            if ($filter) {
                if (is_numeric($filter)) {
                    // Filter by doctor ID
                    $filters[] = ['doctor_id', '=', (int) $filter];
                } else {
                    // Filter by doctor name
                    $doctorId = $this->getDoctorIdByName($filter);
                    if ($doctorId) {
                        $filters[] = ['doctor_id', '=', $doctorId];
                    } else {
                        return response()->json([
                            'error' => 'Doctor not found'
                        ], 404);
                    }
                }
            }
    
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'sale.order',
                'method' => 'search_read',
                'args' => [
                    $filters,
                    ['id', 'name', 'date_order', 'state', 'amount_total', 'doctor_id']
                ],
                'kwargs' => [
                    'order' => 'date_order desc',
                    'limit' => 10,
                    'context' => ['lang' => 'en_US']
                ]
            ]);
    
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales orders by doctor from Odoo', [
                'error' => $e->getMessage(),
                'doctor_filter' => $filter,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to fetch sales orders by doctor: ' . $e->getMessage()
            ], 500);
        }
    }






    public function getDoctorIdByName(string $doctorName)
{
    try {
        $result = $this->call('/web/dataset/call_kw', [
            'model' => 'res.partner',
            'method' => 'search_read',
            'args' => [
                [
                    ['is_doctor', '=', true],
                    ['name', 'ilike', $doctorName]
                ],
                ['id']
            ],
            'kwargs' => [
                'limit' => 1
            ]
        ]);

        return $result ? $result[0]['id'] : null;
    } catch (\Exception $e) {
        Log::error('Failed to get doctor ID by name', [
            'error' => $e->getMessage(),
            'doctor_name' => $doctorName
        ]);
        throw new OdooApiException('Failed to get doctor ID by name: ' . $e->getMessage());
    }
}






    

    /**
     * Add a line to an existing sales order
     * 
     * @param int $orderId The ID of the sales order
     * @param array $lineData The line data to add
     * @return int The created order line ID
     * @throws OdooApiException
     */
    public function addOrderLine(int $orderId, array $lineData): int
    {
        try {
            // Format the order line data according to Odoo's expected structure
            $orderLine = [
                'order_id' => $orderId,
                'product_id' => (int)$lineData['product_id'],
                'name' => $lineData['name'] ?? '/',  // Product description
                'product_uom_qty' => (int)$lineData['product_uom_qty'],
                'price_unit' => $lineData['price_unit'] ?? 0,
                'product_uom' => $lineData['product_uom'] ?? 1,  // Default unit of measure
                'tax_id' => $lineData['tax_id'] ?? [[6, 0, []]]  // Default empty tax
            ];

            Log::info('Adding order line to sales order', [
                'order_id' => $orderId,
                'line_data' => $orderLine
            ]);

            $lineId = $this->call('/web/dataset/call_kw', [
                'model' => 'sale.order.line',
                'method' => 'create',
                'args' => [$orderLine],
                'kwargs' => [
                    'context' => [
                        'lang' => 'en_US',
                        'tz' => 'Asia/Riyadh',
                        'uid' => $this->uid
                    ]
                ]
            ]);

            Log::info('Order line added successfully', [
                'order_id' => $orderId,
                'line_id' => $lineId
            ]);

            return $lineId;

        } catch (\Exception $e) {
            Log::error('Failed to add order line', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'line_data' => $lineData
            ]);
            throw new OdooApiException('Failed to add order line: ' . $e->getMessage());
        }
    }




    /**
     * @deprecated Use createPrescriptionOrder instead
     */
    // public function createSalesOrder(array $data)
    // {
    //     Log::warning('Deprecated method createSalesOrder called. Use createPrescriptionOrder instead.');
        
    //     try {
    //         $this->retryCount = 0;
            
    //         // Convert old format to new format
    //         $orderData = [
    //             'partner_id' => (int)$data['partner_id'],
    //             'date_order' => $data['date_order'] ?? now()->format('Y-m-d H:i:s'),
    //             'doctor_id' => (int)$data['doctor_id'],
    //             'patient_phone' => $data['patient_phone'] ?? '',
    //             'patient' => $data['patient'] ?? '',
    //             'patient_portal_id' => $data['patient_portal_id'] ?? null,
    //             'prescription_reference' => $data['prescription_reference'] ?? null,
    //         ];
            
    //         // Create the order without lines for backward compatibility
    //         return $this->createPrescriptionOrder($orderData, []);
            
    //     } catch (\Exception $e) {
    //         Log::error('Failed to create sales order', [
    //             'error' => $e->getMessage(),
    //             'data' => $data
    //         ]);
    //         throw new OdooApiException('Failed to create sales order: ' . $e->getMessage());
    //     }
    // }



    
    // Helper method to get sales order details
    public function getSalesOrder(int $orderId)
    {
        try {
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'sale.order',
                'method' => 'read',
                'args' => [[$orderId]],
                'kwargs' => [
                    'fields' => [
                        'name',
                        'date_order',
                        'partner_id',
                        'doctor_id',
                        'patient_phone',
                        'patient',
                        'amount_total',
                        'state',
                        'order_line'
                    ]
                ]
            ]);
    
            return $result[0] ?? null;
    
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales order', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            throw new OdooApiException('Failed to fetch sales order: ' . $e->getMessage());
        }
    }






    // app/Services/OdooApi.php
    /**
     * Update an existing sales order in Odoo
     *
     * @param int $orderId
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function updateSalesOrder(int $orderId, array $data): bool
    {
        $this->ensureAuthenticated();

        $params = [
            'model' => 'sale.order',
            'method' => 'write',
            'args' => [
                [$orderId],
                $data
            ],
        ];

        $response = $this->call('/web/dataset/call_kw', $params);

        if ($response['status'] === 'success') {
            return true;
        } else {
            throw new \Exception($response['error']['data']['message'] ?? 'Failed to update sales order');
        }
    }






    /**
     * Create a new partner in Odoo
     */
    public function createPartner(array $data)
    {
        try {
            $this->retryCount = 0;
            
            // Prepare partner data according to Odoo's expected format
            $partnerData = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? false,
                'mobile' => $data['mobile'] ?? false,
                'email' => $data['email'] ?? false,
                'street' => $data['street'] ?? false,
                'street2' => $data['street2'] ?? false, 
                'city' => $data['city'] ?? false,               
                'customer_rank' => 1,   
                'company_type' => 'person',
                'type' => 'contact' 
            ];

            Log::info('Creating partner in Odoo', ['data' => $partnerData]);

            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'res.partner',
                'method' => 'create',
                'args' => [$partnerData],  // Note: args should be an array containing the data array
                'kwargs' => [
                    'context' => [
                        'lang' => 'en_US',
                        'tz' => 'Asia/Riyadh',  // Adjust timezone as needed
                        'tracking_disable' => true
                    ]
                ]
            ]);

            if (!$result) {
                throw new OdooApiException('Failed to create partner: No ID returned');
            }

            Log::info('Partner created successfully', ['partner_id' => $result]);
            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to create partner', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new OdooApiException('Failed to create partner: ' . $e->getMessage());
        }
    }


    public function getPartner(int $id)
    {
        try {
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'res.partner',
                'method' => 'search_read',
                'args' => [
                    [['id', '=', $id]],  // Search condition
                    ['id', 'name', 'phone', 'email']  // Fields to fetch
                ],
                'kwargs' => [
                    'limit' => 1
                ]
            ]);

            return $result[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch partner', [
                'error' => $e->getMessage(),
                'partner_id' => $id
            ]);
            throw new OdooApiException('Failed to fetch partner: ' . $e->getMessage());
        }
    }



    public function getLatestPartners(int $limit = 100)
    {
        try {
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'res.partner',
                'method' => 'search_read',
                'args' => [
                    [
                        ['type', '=', 'contact'],
                        ['customer_rank', '>', 0]
                    ],  // Search conditions
                    ['id', 'name', 'phone', 'email', 'create_date']  // Fields to fetch
                ],
                'kwargs' => [
                    'order'=> 'create_date desc',
                    'limit' => $limit
                ]
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to fetch latest partners', [
                'error' => $e->getMessage(),
                'limit' => $limit
            ]);
            throw new OdooApiException('Failed to fetch latest partners: ' . $e->getMessage());
        }
    }




    /**
     * Make an authenticated API call to Odoo
     */
    public function call(string $endpoint, array $params, bool $allowRetry = true)
    {
        try {
            $this->ensureAuthenticated();

            $requestData = [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => $params,
                'id' => mt_rand(1, 999999999)
            ];

            Log::info('Odoo API Request', [
                'endpoint' => $endpoint,
                'data' => $requestData
            ]);

            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Cookie' => $this->session_id,
                    'Content-Type' => 'application/json'
                ],
                'json' => $requestData
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new OdooApiException('Invalid JSON response: ' . json_last_error_msg());
            }

            // Handle session expiration
            if ($this->isSessionExpired($result) && $allowRetry && $this->retryCount < self::MAX_RETRIES) {
                $this->retryCount++;
                $this->resetSession();
                return $this->call($endpoint, $params, true);
            }

            if (isset($result['error'])) {
                $errorMessage = $result['error']['data']['message'] 
                    ?? $result['error']['message'] 
                    ?? 'Unknown Odoo error';
                throw new OdooApiException($errorMessage);
            }

            return $result['result'];

        } catch (\Exception $e) {
            Log::error('Odoo API call failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'retry_count' => $this->retryCount
            ]);
            throw new OdooApiException($e->getMessage());
        }
    }






    /**
     * Authenticate with Odoo
     */
    public function authenticate(): void
    {
        try {
            $response = $this->client->post('/web/session/authenticate', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'method' => 'call',
                    'params' => [
                        'db' => $this->db,
                        'login' => $this->username,
                        'password' => $this->password,
                    ],
                    'id' => mt_rand(1, 999999999)
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['result']) || !isset($result['result']['uid'])) {
                throw new OdooApiException('Invalid authentication response');
            }

            $this->session_id = $response->getHeader('Set-Cookie')[0] ?? null;
            $this->uid = $result['result']['uid'];

            if (!$this->session_id || !$this->uid) {
                throw new OdooApiException('Failed to obtain session information');
            }

            Log::info('Authenticated with Odoo', [
                'uid' => $this->uid,
                'has_session' => !empty($this->session_id)
            ]);

        } catch (\Exception $e) {
            Log::error('Authentication failed', ['error' => $e->getMessage()]);
            throw new OdooApiException('Authentication failed: ' . $e->getMessage());
        }
    }





    /**
     * Check if session is expired
     */
    private function isSessionExpired(array $result): bool
    {
        return isset($result['error']) && 
               (strpos($result['error']['message'] ?? '', 'Session expired') !== false ||
                strpos($result['error']['data']['message'] ?? '', 'Session expired') !== false);
    }




    /**
     * Reset session data
     */
    private function resetSession(): void
    {
        $this->uid = null;
        $this->session_id = null;
        Log::info('Session reset, will re-authenticate');
    }




    /**
     * Ensure valid authentication
     */
    private function ensureAuthenticated(): void
    {
        if (!$this->uid || !$this->session_id) {
            $this->authenticate();
        }
    }




    /**
     * Clear medication cache
     */
    public function clearMedicationCache(): void
    {
        Cache::forget('odoo_medications');
    }




    /**
     * Search medications/products in Odoo
     *
     * @param string $searchTerm
     * @param int $limit
     * @return array
     * @throws OdooApiException
     */
    public function searchMedications(string $searchTerm, int $limit = 10): array
    {
        try {
            // Cache key based on search term
            $cacheKey = 'medications_search_' . md5($searchTerm);

            // Try to get from cache first
            return Cache::remember($cacheKey, 300, function () use ($searchTerm, $limit) {
                $result = $this->call('/web/dataset/call_kw', [
                    'model' => 'product.product',
                    'method' => 'search_read',
                    'args' => [
                        [
                            ['name', 'ilike', $searchTerm],
                            ['type', '=', 'product'],
                            ['sale_ok', '=', true],
                            ['active', '=', true]
                        ],
                        [
                            'id',
                            'name',
                            'default_code',
                            'list_price',
                            'qty_available',
                            'uom_id'  // Add unit of measure
                        ]
                    ],
                    'kwargs' => [
                        'limit' => $limit,
                        'context' => ['lang' => 'en_US']
                    ]
                ]);

                // Transform the result
                return collect($result)->map(function ($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'default_code' => $product['default_code'] ?? '',
                        'list_price' => $product['list_price'] ?? 0,
                        'qty_available' => $product['qty_available'] ?? 0,
                        'uom' => $product['uom_id'] ? [
                            'id' => $product['uom_id'][0],
                            'name' => $product['uom_id'][1]
                        ] : null
                    ];
                })->values()->all();
            });

        } catch (\Exception $e) {
            Log::error('Failed to search medications in Odoo', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to search medications: ' . $e->getMessage());
        }
    }



    /**
     * Create a sales order from a prescription
     * Standardized method to create sales orders from prescriptions
     *
     * @param array $orderData Core order data (patient, doctor, etc.)
     * @param array $orderLines Array of product lines to add to the order
     * @return int The created sales order ID
     * @throws OdooApiException
     */
    public function createPrescriptionOrder(array $orderData, array $orderLines): int
    {
        try {
            $this->retryCount = 0;
            
            // Standardize required fields with sensible defaults
            $standardOrderData = [
                'partner_id' => (int)$orderData['partner_id'],
                'date_order' => $orderData['date_order'] ?? now()->format('Y-m-d H:i:s'),
                'state' => 'draft',
                'company_id' => $orderData['company_id'] ?? 1,
                'pricelist_id' => $orderData['pricelist_id'] ?? 1,
                'user_id' => $orderData['user_id'] ?? $this->uid,
                'doctor_id' => (int)$orderData['doctor_id'],
                'patient_phone' => $orderData['patient_phone'] ?? '',
                'patient' => $orderData['patient'] ?? '',
                'patient_portal_id' => $orderData['patient_portal_id'] ?? null,
                //'prescription_reference' => $orderData['prescription_reference'] ?? null,
            ];

           // dd($standardOrderData);
        
            Log::info('Creating prescription sales order in Odoo', ['data' => $standardOrderData]);
        
            // Create the sales order
            $orderId = $this->call('/web/dataset/call_kw', [
                'model' => 'sale.order',
                'method' => 'create',
                'args' => [$standardOrderData],
                'kwargs' => [
                    'context' => [
                        'lang' => 'en_US',
                        'tz' => 'Asia/Riyadh',
                        'uid' => $this->uid,
                        'default_company_id' => $standardOrderData['company_id']
                    ]
                ]
            ]);
        
            Log::info('Sales order created successfully', ['sale_order_id' => $orderId]);

            
            // Add order lines
            foreach ($orderLines as $line) {
                $this->addOrderLine($orderId, $line);
            }
            
            return $orderId;
        
        } catch (\Exception $e) {
            Log::error('Failed to create prescription sales order', [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            throw new OdooApiException('Failed to create prescription sales order: ' . $e->getMessage());
        }
    }

    /**
     * Get sales orders that need status updates
     * 
     * @param array $orderIds List of order IDs to check
     * @return array Updated order data
     * @throws OdooApiException
     */
    public function getSalesOrdersStatus(array $orderIds)
    {
        try {
            $this->retryCount = 0;
            $this->ensureAuthenticated();
            
            if (empty($orderIds)) {
                return [];
            }
            
            $result = $this->call('/web/dataset/call_kw', [
                'model' => 'sale.order',
                'method' => 'search_read',
                'args' => [
                    [['id', 'in', $orderIds]],
                    ['id', 'name', 'state', 'date_order', 'amount_total', 'invoice_status']
                ],
                'kwargs' => [
                    'context' => ['lang' => 'en_US']
                ]
            ]);
            
            // Transform the result to a more usable format
            return collect($result)->keyBy('id')->map(function ($order) {
                return [
                    'id' => $order['id'],
                    'name' => $order['name'],
                    'state' => $this->mapOrderState($order['state']),
                    'date_order' => $order['date_order'],
                    'amount_total' => $order['amount_total'],
                    'invoice_status' => $order['invoice_status']
                ];
            })->all();
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales orders status from Odoo', [
                'error' => $e->getMessage(),
                'order_ids' => $orderIds,
                'trace' => $e->getTraceAsString()
            ]);
            throw new OdooApiException('Failed to fetch sales orders status: ' . $e->getMessage());
        }
    }

    /**
     * Map Odoo order state to a more user-friendly status
     * 
     * @param string $state Odoo order state
     * @return string User-friendly status
     */
    private function mapOrderState(string $state): string
    {
        $stateMap = [
            'draft' => 'Draft',
            'sent' => 'Quotation Sent',
            'sale' => 'Confirmed',
            'done' => 'Locked',
            'cancel' => 'Cancelled',
        ];
        
        return $stateMap[$state] ?? $state;
    }

}
