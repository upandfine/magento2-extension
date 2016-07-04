<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Congratulation extends AbstractBlock
{
    protected function _toHtml()
    {
        $supportUrl = $this->getUrl('*/support/index');

        return <<<HTML
<h2>
    {$this->__('This wizard was already finished. Please <a href="%1%">Contact Us</a>, if it is need.', $supportUrl)}
</h2>

HTML;
    }
}