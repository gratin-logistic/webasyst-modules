<?php
return array(
    'city' => array(
        'value'        => '',
        'title'        => 'Город отправления',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
   'api_key'    => array(
        'value'        => '',
        'title'        => 'Ключ API',
        'description'  => 'Для получения ключа API обратитесь в <a href="https://grastin.ru/contacts/m.html" target="_blank">Грастин</a>.',
        'control_type' => waHtmlControl::INPUT,
    ),
    'prefix'    => array(
        'value'        => true,
        'title'        => 'Добавлять префикс/постфикс к номерам заказов',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'sitename'    => array(
        'value'        => false,
        'title'        => 'Передавать название сайта',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'cod'    => array(
        'value'        => false,
        'title'        => 'Наложенный платеж',
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'services'    => array(
        'value'        => 'courier',
        'title'        => 'Служба доставки',
        'description'  => '',
        'options' => array(
            array(
                'value'       => 'courier',
                'title'       => 'Курьерская служба',
                'description' => '',
            ),
            array(
                'value'       => 'boxberry',
                'title'       => 'Boxberry',
                'description' => '',
            ),
            array(
                'value'       => 'hermes',
                'title'       => 'Hermes',
                'description' => '',
            ),
            array(
                'value'       => 'russian.post',
                'title'       => 'Почта России',
                'description' => '',
            ),
        ),
        'control_type' => waHtmlControl::SELECT,
    ),
    'contract'    => array(
        'value'        => '',
        'title'        => 'Договор',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
    ),
    'service'    => array(
        'value'        => '',
        'title'        => 'Услуга',
        'description'  => '',
        'options' => array(
            array(
                'value'       => '-',
                'title'       => '-',
                'description' => '',
            ),
            array(
                'value'       => '1',
                'title'       => 'Доставка без оплаты',
                'description' => '',
            ),
            array(
                'value'       => '2',
                'title'       => 'Доставка с оплатой',
                'description' => '',
            ),
            array(
                'value'       => '3',
                'title'       => 'Доставка с кассовым обслуживанием',
                'description' => '',
            ),
            array(
                'value'       => '4',
                'title'       => 'Обмен/забор товара',
                'description' => '',
            ),
            array(
                'value'       => '5',
                'title'       => 'Самовывоз без оплаты',
                'description' => '',
            ),
            array(
                'value'       => '6',
                'title'       => 'Самовывоз с оплатой',
                'description' => '',
            ),
            array(
                'value'       => '7',
                'title'       => 'Самовывоз с кассовым обслуживанием',
                'description' => '',
            ),
            array(
                'value'       => '8',
                'title'       => 'Большой доставка без оплаты',
                'description' => '',
            ),
            array(
                'value'       => '9',
                'title'       => 'Большой доставка и забор наличных',
                'description' => '',
            ),
            array(
                'value'       => '10',
                'title'       => 'Большой доставка с кассовым обслуживанием',
                'description' => '',
            ),
            array(
                'value'       => '11',
                'title'       => 'Обмен/забор товара на самовывозе',
                'description' => '',
            ),
            array(
                'value'       => '12',
                'title'       => 'Транспортная компания',
                'description' => '',
            ),
            
            array(
                'value'       => '13',
                'title'       => 'Почтовая доставка',
                'description' => '',
            ),
            array(
                'value'       => '14',
                'title'       => 'Посылка онлайн',
                'description' => '',
            ),
            array(
                'value'       => '15',
                'title'       => 'Курьер онлайн',
                'description' => '',
            ),
            array(
                'value'       => '16',
                'title'       => 'Самовывоз с оплатой картой',
                'description' => '',
            ),
            array(
                'value'       => '17',
                'title'       => 'Забор товара у поставщика (закупки)',
                'description' => '',
            ),
            array(
                'value'       => '18',
                'title'       => 'Забор БОЛЬШОЙ товара у поставщика  (закупки)',
                'description' => '',
            ),
            array(
                'value'       => '19',
                'title'       => 'Доставка с оплатой картой',
                'description' => '',
            ),
            array(
                'value'       => '20',
                'title'       => 'Почтовая доставка',
                'description' => '',
            ),
            array(
                'value'       => '21',
                'title'       => 'Посылка онлайн (Почта РФ)',
                'description' => '',
            ),
            array(
                'value'       => '22',
                'title'       => 'Курьер онлайн (Почта РФ)',
                'description' => '',
            ),
        ),
        'control_type' => waHtmlControl::SELECT,
    ),
    'paiddistance'    => array(
        'value'        => 0,
        'title'        => 'Оплачиваемое расстояние в км.',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'cargotype'    => array(
        'value'        => '',
        'title'        => 'Вид груза',
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'transport'    => array(
        'value'        => '',
        'title'        => 'Транспортная компания',
        'description'  => '',
        'control_type' => waHtmlControl::SELECT,
    ),
);

//EOF