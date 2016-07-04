<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonAccountEdit');
        $this->_controller = 'adminhtml_amazon_account';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        /* @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {

            // ---------------------------------------
            $this->addButton('save_and_continue', array(
                'label'     => $this->__('Save And Continue Edit'),
                'onclick'   => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                'class'     => 'action-primary'
            ));
            // ---------------------------------------

            if ($this->getRequest()->getParam('id')) {
                // ---------------------------------------
                $url = $this->getUrl('*/amazon_account/new', array('wizard' => true));
                $this->addButton('add_new_account', array(
                    'label'     => $this->__('Add New Account'),
                    'onclick'   => 'setLocation(\''. $url .'\')',
                    'class'     => 'action-primary'
                ));
                // ---------------------------------------
            }
        } else {

            if ((bool)$this->getRequest()->getParam('close_on_save',false)) {

                if ($this->getRequest()->getParam('id')) {
                    $this->addButton('save', array(
                        'label'     => $this->__('Save And Close'),
                        'onclick'   => 'AmazonAccountObj.saveAndClose()',
                        'class'     => 'action-primary'
                    ));
                } else {
                    $this->addButton('save_and_continue', array(
                        'label'     => $this->__('Save And Continue Edit'),
                        'onclick'   => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                        'class'     => 'action-primary'
                    ));
                }
                return;
            }
            
            // ---------------------------------------
            $url = $this->getHelper('Data')->getBackUrl('list');
            $this->addButton('back', array(
                'label'     => $this->__('Back'),
                'onclick'   => 'AmazonAccountObj.backClick(\''. $url .'\')',
                'class'     => 'back'
            ));
            // ---------------------------------------

            // ---------------------------------------
            if ($this->getHelper('Data\GlobalData')->getValue('temp_data') &&
                $this->getHelper('Data\GlobalData')->getValue('temp_data')->getId()
            ) {
                // ---------------------------------------
                $this->addButton('delete', array(
                    'label'     => $this->__('Delete'),
                    'onclick'   => 'AmazonAccountObj.deleteClick()',
                    'class'     => 'delete M2ePro_delete_button primary'
                ));
                // ---------------------------------------
            }

            // ---------------------------------------
            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick'   => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label'     => $this->__('Save And Back'),
                        'onclick'   => 'AmazonAccountObj.saveClick()',
                        'class'     => 'action-primary'
                ]]
            ];

            $this->addButton('save_buttons', $saveButtons);
            // ---------------------------------------
        }
    }
}