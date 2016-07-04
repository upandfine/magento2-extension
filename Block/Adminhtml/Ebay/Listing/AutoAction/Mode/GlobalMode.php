<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\GlobalMode
{

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionModeGlobal');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();

        $autoGlobalAddingMode = $form->getElement('auto_global_adding_mode');
        $autoGlobalAddingMode->addElementValues([
            \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => $this->__(
                'Add to the Listing and Assign eBay Category'
            )
        ]);

        return $this;
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Listing')
        );
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData(
            [
                'content' => $this->__(
                    '<p>These Rules of the automatic product adding and removal act globally for all Magento Catalog.
                    When a new Magento Product is added to Magento Catalog, it will be automatically added to
                    the current M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Accordingly, if a Magento Product present in the the M2E Pro Listing is removed from 
                    Magento Catalog,  the Item will be removed from the Listing and its sale 
                    will be stopped on Channel.</p><br>
                    <p>More detailed information you can find <a href="%url%" target="_blank">here</a>.</p>',
                    $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/_QItAQ')
                )
            ]
        );

        $breadcrumb = $this->createBlock('Ebay\Listing\AutoAction\Mode\Breadcrumb', '', ['data' => [
            'id_prefix' => 'global'
        ]]);
        $breadcrumb->setSelectedStep(1);

        return $helpBlock->toHtml() . $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}