<?php

class grastinShipping extends waShipping
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $url = 'https://bringer.pro/plus/grastin/wa';
    protected $yandexUrl = 'https://geocode-maps.yandex.ru/1x/'

    public function calculate()
    {
       $items = $this->getItems();
       $length = $width = $height = 0;
       foreach ($items as $item) {
           if (!is_null($item['length']) && $item['length'] > 0) {
               $length += $item['length'];
           }
           if (!is_null($item['width']) && $item['width'] > 0) {
               $width += $item['width'];
           }
           if (!is_null($item['height']) && $item['height'] > 0) {
               $height += $item['height'];
           }
       }

       $services = array();
       $calc = array(
           'packages' => array(
               array(
                   'weight' => $this->getTotalWeight() * 1000,
                   'width' => $width,
                   'length' => $length,
                   'height' => $height
               )
           ),
           'declaredValue' => $this->getTotalPrice(),
           'deliveryAddress' => array(
               'city' => $this->getAddress('city')
           ),
           'shipmentAddress' => array(
               'city' => file_get_contents($this->yandexUrl . '?city=' . $GLOBALS['yandex']['city'])
           ),
           'extraData' => array(
               'services' => $this->services,
               'contract' => $this->contract,
               'service' => $this->service,
               'paiddistance' => $this->paiddistance,
               'transport' => $this->transport,
               'cargotype' => $this->cargotype
           )
       );

       $result = $this->getCalculation($calc);
       if (!$result['success'] && isset($result['errorMsg'])) {
           return $result['errorMsg'];
       } elseif (!$result['success']) {
           return false;
       }

       $maxCost = 0;
       foreach ($result['result'] as $pickup) {
           $name = $pickup['name'];
           if (array_key_exists('pickuppointList', $pickup)) {
               $point = reset($pickup['pickuppointList']);
               $name = $point['address'];
           }
           $services[$pickup['code']] = array(
               'name' => $name,
               'description' => $pickup['description'],
               'id' => $pickup['code'],
               'est_delivery' => $pickup['minTerm'] . '-' . $pickup['maxTerm'] . ' дн.',
               'currency' => 'RUB',
               'rate' => $pickup['cost']
           );
           if ($pickup['cost'] > $maxCost) {
               $maxCost = $pickup['cost'];
           }
       }

       foreach ($services as $code => $service) {
           $services[$code]['rate'] = $maxCost;
       }

        return $services;
    }

    public function customFields()
    {
        $fields = array(
            'comment' => array(
                'value'        => '',
                'title'        => 'Комментарий по доставке',
                'control_type' => waHtmlControl::TEXTAREA,
            )
        );

        if ($this->service == 12) {
            $fields['pasport'] = array(
                'value'        => '',
                'title'        => 'Паспортные данные',
                'control_type' => waHtmlControl::INPUT,
            );
        }

        return $fields;
    }

    protected function draftPackage(waOrder $order, $shipping_data = array())
    {
        $appSettingsModel = new waAppSettingsModel();
        //var_dmp($shipping_data);var_dump($order);die();
        $save = array(
            'order' => $order['id'],
            'orderNumber' => $order['id_str'],
            'site' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'siteName' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'customer' => array(
                'id' => $order->contact_id,
                'lastName' => $order['shipping_address']['lastname'],
                'firstName' => $order['shipping_address']['firstname'],
                'patronymic' => '',
                'email' => $order->getContactField('email'),
                'phones' => array('+7' . $order->getContactField('phone')),
            ),
            'packages' => array(),
            'delivery' => array(
                'shipmentAddress' => array(
                    'city' => $this->city,
                ),
                'deliveryAddress' => array(
                    'index' => $order['shipping_address']['zip'],
                    'region' => $order['shipping_address']['region'],
                    'city' => $order['shipping_address']['city'],
                    'street' => $order['shipping_address']['street'],
                    'text' => $order['shipping_address']['address'],
                ),
                'deliveryDate' => date('Y-m-d', time()),
                'withCod' => (bool) $this->cod,
                'cod' => ((bool) $this->cod) ? $this->getTotalPrice() : 0,
                'cost' => $order['shipping'],
                'vatRate' => 0,
                'tariff' => $order['shipping_rate_id'],
                'extraData' => array(
                    'services' => $this->services,
                    'contract' => $this->contract,
                    'service' => $this->service,
                    'paiddistance' => $this->paiddistance,
                    'transport' => $this->transport,
                    'cargotype' => $this->cargotype,
                    'comment' => array_key_exists('comment', $order['shipping_params']) ? $order['shipping_params']['comment'] : '',
                    'pasport' => array_key_exists('pasport', $order['shipping_params']) ? $order['shipping_params']['pasport'] : '',
                )
            )
        );

        $package = array(
            'packageId' => 1,
            'weight' => $this->getTotalWeight(),
            'width' => 0,
            'length' => 0,
            'height' => 0,
            'items' => array()
        );

        foreach ($order['items'] as $item) {
            if (!is_null($item['width']) && $item['width'] > 0) {
                $package['width'] += $item['width'];
            }
            if (!is_null($item['length']) && $item['length'] > 0) {
                $package['length'] += $item['length'];
            }
            if (!is_null($item['height']) && $item['height'] > 0) {
                $package['height'] += $item['height'];
            }

            $package['items'][] = array(
                'offerId' => $item['id'],
                'name' => $item['name'],
                'declaredValue' => $item['price'] - $item['discount'],
                'cod' => ((bool) $this->cod) ? $item['price'] - $item['discount'] : 0,
                'vatRate' => (int) $item['tax_rate'],
                'quantity' => $item['quantity'],
                'properties' => array(
                    'article' => array('value' => $item['sku'])
                )
            );
        }

        $save['packages'][] = $package;
        //var_dump($save);die();
        try {
            $result = $this->getSave($save);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $statusses = $this->getStatusses();

        $currentStatus = $statusses[$result['result']['status']];

        $shippingData = array(
            'delivery_id'  => $result['result']['deliveryId'],
            'status'    => $result['result']['status'],
            'client_id' => $this->client_id,
            'view_data' => sprintf('Создан заказ в статусе «%s» №%s', $currentStatus['name'], $result['result']['deliveryId']),
        );

        return $shippingData;
    }

    protected function shippingPackage(waOrder $order, $shipping_data = array())
    {
        $appSettingsModel = new waAppSettingsModel();
        //var_dmp($shipping_data);var_dump($order);die();
        $save = array(
            'order' => $order['id'],
            'orderNumber' => $order['id_str'],
            'site' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'siteName' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'customer' => array(
                'id' => $order->contact_id,
                'lastName' => $order['shipping_address']['lastname'],
                'firstName' => $order['shipping_address']['firstname'],
                'patronymic' => '',
                'email' => $order->getContactField('email'),
                'phones' => array('+7' . $order->getContactField('phone')),
            ),
            'packages' => array(),
            'delivery' => array(
                'shipmentAddress' => array(
                    'city' => $this->city,
                ),
                'deliveryAddress' => array(
                    'index' => $order['shipping_address']['zip'],
                    'region' => $order['shipping_address']['region'],
                    'city' => $order['shipping_address']['city'],
                    'street' => $order['shipping_address']['street'],
                    'text' => $order['shipping_address']['address'],
                ),
                'deliveryDate' => date('Y-m-d', time()),
                'withCod' => (bool) $this->cod,
                'cod' => ((bool) $this->cod) ? $this->getTotalPrice() : 0,
                'cost' => $order['shipping'],
                'vatRate' => 0,
                'tariff' => $order['shipping_rate_id'],
                'extraData' => array(
                    'services' => $this->services,
                    'contract' => $this->contract,
                    'service' => $this->service,
                    'paiddistance' => $this->paiddistance,
                    'transport' => $this->transport,
                    'cargotype' => $this->cargotype,
                    'comment' => array_key_exists('comment', $order['shipping_params']) ? $order['shipping_params']['comment'] : '',
                    'pasport' => array_key_exists('pasport', $order['shipping_params']) ? $order['shipping_params']['pasport'] : '',
                )
            )
        );

        $package = array(
            'packageId' => 1,
            'weight' => $this->getTotalWeight(),
            'width' => 0,
            'length' => 0,
            'height' => 0,
            'items' => array()
        );

        foreach ($order['items'] as $item) {
            if (!is_null($item['width']) && $item['width'] > 0) {
                $package['width'] += $item['width'];
            }
            if (!is_null($item['length']) && $item['length'] > 0) {
                $package['length'] += $item['length'];
            }
            if (!is_null($item['height']) && $item['height'] > 0) {
                $package['height'] += $item['height'];
            }

            $package['items'][] = array(
                'offerId' => $item['id'],
                'name' => $item['name'],
                'declaredValue' => $item['price'] - $item['discount'],
                'cod' => ((bool) $this->cod) ? $item['price'] - $item['discount'] : 0,
                'vatRate' => (int) $item['tax_rate'],
                'quantity' => $item['quantity'],
                'properties' => array(
                    'article' => array('value' => $item['sku'])
                )
            );
        }

        $save['packages'][] = $package;
        //var_dump($save);die();

        if (array_key_exists('delivery_id', $shipping_data)) {
            $save['deliveryId'] = $shipping_data['deliveryId'];
        }

        try {
            $result = $this->getSave($save);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $statusses = $this->getStatusses();

        $currentStatus = $statusses[$result['result']['status']];

        $shippingData = array(
            'delivery_id'  => $result['result']['deliveryId'],
            'status'    => $result['result']['status'],
            'client_id' => $this->client_id,
            'view_data' => sprintf('Статаус заказа изменен на «%s» по отправлению №%s', $currentStatus['name'], $result['result']['deliveryId']),
        );

        return $shippingData;
    }

    protected function readyPackage(waOrder $order, $shipping_data = array())
    {
        $appSettingsModel = new waAppSettingsModel();
        //var_dmp($shipping_data);var_dump($order);die();
        $save = array(
            'order' => $order['id'],
            'orderNumber' => $order['id_str'],
            'site' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'siteName' => $appSettingsModel->get('webasyst', 'name', $order['params']['storefront']),
            'customer' => array(
                'id' => $order->contact_id,
                'lastName' => $order['shipping_address']['lastname'],
                'firstName' => $order['shipping_address']['firstname'],
                'patronymic' => '',
                'email' => $order->getContactField('email'),
                'phones' => array('+7' . $order->getContactField('phone')),
            ),
            'packages' => array(),
            'delivery' => array(
                'shipmentAddress' => array(
                    'city' => $this->city,
                ),
                'deliveryAddress' => array(
                    'index' => $order['shipping_address']['zip'],
                    'region' => $order['shipping_address']['region'],
                    'city' => $order['shipping_address']['city'],
                    'street' => $order['shipping_address']['street'],
                    'text' => $order['shipping_address']['address'],
                ),
                'deliveryDate' => date('Y-m-d', time()),
                'withCod' => (bool) $this->cod,
                'cod' => ((bool) $this->cod) ? $this->getTotalPrice() : 0,
                'cost' => $order['shipping'],
                'vatRate' => 0,
                'tariff' => $order['shipping_rate_id'],
                'extraData' => array(
                    'services' => $this->services,
                    'contract' => $this->contract,
                    'service' => $this->service,
                    'paiddistance' => $this->paiddistance,
                    'transport' => $this->transport,
                    'cargotype' => $this->cargotype,
                    'comment' => array_key_exists('comment', $order['shipping_params']) ? $order['shipping_params']['comment'] : '',
                    'pasport' => array_key_exists('pasport', $order['shipping_params']) ? $order['shipping_params']['pasport'] : '',
                )
            )
        );

        $package = array(
            'packageId' => 1,
            'weight' => $this->getTotalWeight(),
            'width' => 0,
            'length' => 0,
            'height' => 0,
            'items' => array()
        );

        foreach ($order['items'] as $item) {
            if (!is_null($item['width']) && $item['width'] > 0) {
                $package['width'] += $item['width'];
            }
            if (!is_null($item['length']) && $item['length'] > 0) {
                $package['length'] += $item['length'];
            }
            if (!is_null($item['height']) && $item['height'] > 0) {
                $package['height'] += $item['height'];
            }

            $package['items'][] = array(
                'offerId' => $item['id'],
                'name' => $item['name'],
                'declaredValue' => $item['price'] - $item['discount'],
                'cod' => ((bool) $this->cod) ? $item['price'] - $item['discount'] : 0,
                'vatRate' => (int) $item['tax_rate'],
                'quantity' => $item['quantity'],
                'properties' => array(
                    'article' => array('value' => $item['sku'])
                )
            );
        }

        $save['packages'][] = $package;
        //var_dump($save);die();

        if (array_key_exists('delivery_id', $shipping_data)) {
            $save['deliveryId'] = $shipping_data['deliveryId'];
        }

        try {
            $result = $this->getSave($save);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $statusses = $this->getStatusses();

        $currentStatus = $statusses[$result['result']['status']];

        $shippingData = array(
            'delivery_id'  => $result['result']['deliveryId'],
            'status'    => $result['result']['status'],
            'client_id' => $this->client_id,
            'view_data' => sprintf('Статаус заказа изменен на «%s» по отправлению №%s', $currentStatus['name'], $result['result']['deliveryId']),
        );

        return $shippingData;
    }

    protected function cancelPackage(waOrder $order, $shipping_data = array())
    {
        if (array_key_exists('delivery_id', $shipping_data)) {
            $this->getDelete(array('deliveryId' => $shipping_data['delivery_id']));

            $shippingData = array(
                'delivery_id'  => $shipping_data['delivery_id'],
                'status'    => 'canceled',
                'client_id' => $this->client_id,
                'view_data' => sprintf('Заказ №%s отменен', $shipping_data['delivery_id']),
            );

            return $shippingData;
        }

        return $shipping_data;
    }

    public function getSettingsHTML($params = array())
    {
        if (!empty($this->api_key)) {
            $contracts = array();
            try{
                $contracts = $this->getContracts();
            } catch (Exception $e){}

            if (!isset($params['options'])) {
                $params['options'] = array();
            }

            if (count($contracts) > 0) {
                $params['options']['contract'] = array();
                foreach ($contracts as $contract) {
                    $params['options']['contract'][$contract['value']] = $contract['label'];
                }
            }

            $transports = array();
            try{
                $transports = $this->getTransports();
            } catch (Exception $e){}

            if (count($transports) > 0) {
                $params['options']['transport'] = array();
                foreach ($transports as $transport) {
                    $params['options']['transport'][$transport['value']] = $transport['label'];
                }
            }
        }

        return parent::getSettingsHTML($params);
    }

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function allowedWeightUnit()
    {
        return 'kg';
    }

    public function requestedAddressFields()
    {
        $required = array(
            'city'    => array('cost' => true),
            'street'  => array(),
        );

        if ($this->services == 'russian.post') {
            $required['zip'] = array();
        }

        return $required;
    }

    private function getDelete($delete)
    {
        $params = array(
            'apiKey' => $this->api_key,
            'delete' => json_encode($delete)
        );

        return $this->makeRequest(
            '/delete/', self::METHOD_POST,
            $params
        );
    }

    private function getSave($save)
    {
        $params = array(
            'apiKey' => $this->api_key,
            'save' => json_encode($save)
        );

        if ($this->prefix) {
            $params['prefix'] = (bool) $this->prefix;
        }

        if ($this->sitename) {
            $params['sitename'] = (bool) $this->sitename;
        }

        return $this->makeRequest(
            '/save/', self::METHOD_POST,
            $params
        );
    }

    private function getStatusses()
    {
        return $this->makeRequest(
            '/statusses/', self::METHOD_POST,
            array('apiKey' => $this->api_key, '')
        );
    }

    private function getCalculation($calc)
    {
        return $this->makeRequest(
            '/calculate/', self::METHOD_POST,
            array('apiKey' => $this->api_key, 'calculate' => json_encode($calc))
        );
    }

    private function getTransports()
    {
        return $this->makeRequest(
            '/transports/', self::METHOD_POST,
            array('apiKey' => $this->api_key, '')
        );
    }

    private function getContracts()
    {
        return $this->makeRequest(
            '/contracts/', self::METHOD_POST,
            array('apiKey' => $this->api_key)
        );
    }

    private function makeRequest($path, $method, $parameters = array(), $timeout = 30)
    {
        $allowedMethods = array(self::METHOD_GET, self::METHOD_POST);
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException(sprintf(
                'Method "%s" is not valid. Allowed methods are %s',
                $method,
                implode(', ', $allowedMethods)
            ));
        }

        $path = $this->url . $path;
        if (self::METHOD_GET === $method && sizeof($parameters)) {
            $path .= '?' . http_build_query($parameters);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout); // times out after 30s
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (self::METHOD_POST === $method) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }

        $responseBody = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        //var_dump($responseBody);
        if ($errno || $statusCode > 400) {
            throw new Exception($error, $errno);
        }

        return json_decode($responseBody, true);
    }
}
