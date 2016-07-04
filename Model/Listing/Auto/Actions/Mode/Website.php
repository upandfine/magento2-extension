<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

class Website extends AbstractMode
{
    //########################################

    public function synchWithAddedWebsiteId($websiteId)
    {
        if ($websiteId == 0) {
            $storeIds = array(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        } else {
            /** @var $websiteObject \Magento\Store\Model\Website */
            $websiteObject = $this->storeManager->getWebsite($websiteId);
            $storeIds = (array)$websiteObject->getStoreIds();
        }

        if (count($storeIds) <= 0) {
            return;
        }

        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        $collection->addFieldToFilter('auto_mode',\Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE);
        $collection->addFieldToFilter('auto_website_adding_mode',
                                      array('neq'=>\Ess\M2ePro\Model\Listing::ADDING_MODE_NONE));
        $collection->addFieldToFilter('store_id',array('in'=>$storeIds));

        foreach ($collection->getItems() as $listing) {

            /** @var \Ess\M2ePro\Model\Listing $listing */

            if (!$listing->isAutoWebsiteAddingAddNotVisibleYes()) {
                if ($this->getProduct()->getVisibility()
                    == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                    continue;
                }
            }

            $this->getListingObject($listing)->addProductByWebsiteListing($this->getProduct(), $listing);
        }
    }

    public function synchWithDeletedWebsiteId($websiteId)
    {
        /** @var \Magento\Store\Model\Website $websiteObject */
        $websiteObject = $this->storeManager->getWebsite($websiteId);
        $storeIds = (array)$websiteObject->getStoreIds();

        if (count($storeIds) <= 0) {
            return;
        }

        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        $collection->addFieldToFilter('auto_mode',\Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE);
        $collection->addFieldToFilter('auto_website_deleting_mode',
                                      array('neq'=>\Ess\M2ePro\Model\Listing::DELETING_MODE_NONE));

        $collection->addFieldToFilter('store_id',array('in'=>$storeIds));

        foreach ($collection->getItems() as $listing) {

            /** @var \Ess\M2ePro\Model\Listing $listing */

            $this->getListingObject($listing)->deleteProduct(
                $this->getProduct(),
                $listing->getAutoWebsiteDeletingMode()
            );
        }
    }

    //########################################
}