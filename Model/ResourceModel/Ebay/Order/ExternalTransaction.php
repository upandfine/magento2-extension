<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Order;

class ExternalTransaction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_order_external_transaction', 'id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}