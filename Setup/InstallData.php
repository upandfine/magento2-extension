<?php

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Helper\Factory;
use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Setup\Modifier\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Ess\M2ePro\Setup\Modifier\ConfigFactory;

class InstallData implements InstallDataInterface
{
    /** @var Tables $tablesObject */
    private $tablesObject;

    /** @var ConfigFactory $configModifierFactory */
    private $configModifierFactory;

    /** @var Factory $helperFactory */
    private $helperFactory;

    /** @var ModuleListInterface $moduleList */
    private $moduleList;

    /** @var ModuleDataSetupInterface $installer */
    private $installer;

    //########################################

    public function __construct(
        Tables $tablesObject,
        ConfigFactory $configModifierFactory,
        Factory $helperFactory,
        ModuleListInterface $moduleList
    ) {
        $this->tablesObject = $tablesObject;
        $this->configModifierFactory = $configModifierFactory;
        $this->helperFactory = $helperFactory;
        $this->moduleList = $moduleList;
    }

    //########################################

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->installer = $setup;
        $this->installer->startSetup();

        try {
            $this->installGeneral();
            $this->installEbay();
            $this->installAmazon();
        } catch (\Exception $exception) {
            return;
        }

        $this->installer->endSetup();

        $this->getConnection()->insert($this->getFullTableName('setup'), [
            'version_from' => NULL,
            'version_to'   => $this->getConfigVersion(),
            'is_completed' => 1,
            'update_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
            'create_date'  => $this->helperFactory->getObject('Data')->getCurrentGmtDate(),
        ]);

        $this->helperFactory->getObject('Module\Maintenance\Setup')->disable();
    }

    //########################################

    private function installGeneral()
    {
        $installationKey       = sha1(microtime(1));
        $servicingInterval     = rand(43200, 86400);
        $magentoMarketplaceUrl = 'TODO LINK';

        $primaryConfigModifier = $this->getConfigModifier('primary');

        $primaryConfigModifier->insert('/M2ePro/license/', 'key', NULL, 'License Key');
        $primaryConfigModifier->insert('/M2ePro/license/', 'status', 1, NULL);
        $primaryConfigModifier->insert('/M2ePro/license/', 'domain', NULL, 'Valid domain');
        $primaryConfigModifier->insert('/M2ePro/license/', 'ip', NULL, 'Valid ip');
        $primaryConfigModifier->insert('/M2ePro/license/info/', 'email', NULL, 'Associated Email');
        $primaryConfigModifier->insert('/M2ePro/license/valid/', 'domain', NULL, '0 - Not valid\r\n1 - Valid');
        $primaryConfigModifier->insert('/M2ePro/license/valid/', 'ip', NULL, '0 - Not valid\r\n1 - Valid');
        $primaryConfigModifier->insert('/M2ePro/server/', 'messages', '[]', 'Server messages');
        $primaryConfigModifier->insert(
            '/M2ePro/server/', 'application_key', 'b79a495170da3b081c9ebae6c255c7fbe1b139b5', NULL
        );
        $primaryConfigModifier->insert(
            '/M2ePro/server/', 'installation_key', $installationKey, 'Unique identifier of M2E instance'
        );
        $primaryConfigModifier->insert('/modules/', 'M2ePro', '0.0.0.r0', NULL);
        $primaryConfigModifier->insert('/server/', 'baseurl_1', 'https://s1.m2epro.com/', 'Support server base url');

        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/cron/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/', 'runner', 'magento', NULL);
        $moduleConfigModifier->insert('/cron/', 'last_access', NULL, 'Time of last cron synchronization');
        $moduleConfigModifier->insert('/cron/', 'last_runner_change', NULL, 'Time of last change cron runner');
        $moduleConfigModifier->insert('/cron/', 'last_executed_slow_task', NULL, '');
        $moduleConfigModifier->insert('/cron/checker/task/repair_crashed_tables/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert('/cron/service/', 'auth_key', NULL, NULL);
        $moduleConfigModifier->insert('/cron/service/', 'disabled', '0', NULL);
        $moduleConfigModifier->insert('/cron/magento/', 'disabled', '0', NULL);
        $moduleConfigModifier->insert('/cron/service/', 'hostname', 'cron.m2epro.com', NULL);
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'interval', '86400', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/logs_clearing/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/request_pending_single/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/request_pending_partial/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/request_pending_partial/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/request_pending_partial/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/request_pending_partial/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/connector_requester_pending_single/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_single/', 'last_run', NULL, 'date of last run'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/', 'interval', '60', 'in seconds'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert(
            '/cron/task/connector_requester_pending_partial/', 'last_run', NULL, 'date of last run'
        );
        $moduleConfigModifier->insert('/cron/task/amazon_actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/amazon_actions/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/amazon_actions/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/amazon_actions/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_update_settings/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_update_settings/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_update_settings/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_update_settings/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_synchronization/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_synchronization/', 'interval', '86400', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_synchronization/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_synchronization/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_inspect_products/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_inspect_products/', 'interval', '3600', 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/repricing_inspect_products/', 'last_access', NULL, 'date of last access'
        );
        $moduleConfigModifier->insert('/cron/task/repricing_inspect_products/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'interval', '300', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/synchronization/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'interval', $servicingInterval, 'in seconds');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/servicing/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert('/logs/clearing/listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/logs/clearing/listings/', 'days', '30', 'in days');
        $moduleConfigModifier->insert('/logs/clearing/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/logs/clearing/other_listings/', 'days', '30', 'in days');
        $moduleConfigModifier->insert('/logs/clearing/synchronizations/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/logs/clearing/synchronizations/', 'days', '30', 'in days');
        $moduleConfigModifier->insert('/logs/clearing/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/logs/clearing/orders/', 'days', '90', 'in days');
        $moduleConfigModifier->insert('/logs/clearing/ebay_pickup_store/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/logs/clearing/ebay_pickup_store/', 'days', '30', 'in days');
        $moduleConfigModifier->insert('/logs/listings/', 'last_action_id', '0', NULL);
        $moduleConfigModifier->insert('/logs/other_listings/', 'last_action_id', '0', NULL);
        $moduleConfigModifier->insert('/logs/ebay_pickup_store/', 'last_action_id', '0', NULL);
        $moduleConfigModifier->insert(
            '/support/', 'knowledge_base_url', 'https://support.m2epro.com/knowledgebase', NULL
        );
        $moduleConfigModifier->insert('/support/', 'documentation_url', 'https://docs.m2epro.com', NULL);
        $moduleConfigModifier->insert('/support/', 'clients_portal_url', 'https://clients.m2epro.com/', NULL);
        $moduleConfigModifier->insert('/support/', 'main_website_url', 'https://m2epro.com/', NULL);
        $moduleConfigModifier->insert('/support/', 'main_support_url', 'https://support.m2epro.com/', NULL);
        $moduleConfigModifier->insert('/support/', 'magento_connect_url', $magentoMarketplaceUrl, NULL);
        $moduleConfigModifier->insert('/support/', 'contact_email', 'support@m2epro.com', NULL);
        $moduleConfigModifier->insert('/support/', 'community', 'https://community.m2epro.com/');
        $moduleConfigModifier->insert('/support/', 'ideas', 'https://support.m2epro.com/ideas/');
        $moduleConfigModifier->insert('/view/', 'show_block_notices', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/view/', 'show_products_thumbnails', '1', 'Visibility thumbnails into grid');
        $moduleConfigModifier->insert(
            '/view/products_grid/', 'use_alternative_mysql_select', '0', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/view/requirements/popup/', 'closed', '0', '0 - false, - true');
        $moduleConfigModifier->insert(
            '/view/synchronization/revise_total/', 'show', '0', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/view/amazon/autocomplete/', 'max_records_quantity', '100', NULL);
        $moduleConfigModifier->insert('/view/ebay/', 'mode', 'simple', 'simple, advanced');
        $moduleConfigModifier->insert('/view/ebay/notice/', 'disable_collapse', '0', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert(
            '/view/ebay/template/selling_format/', 'show_tax_category', '0', '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/view/ebay/feedbacks/notification/', 'mode', '0', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert(
            '/view/ebay/feedbacks/notification/', 'last_check', NULL, 'Date last check new buyers feedbacks'
        );
        $moduleConfigModifier->insert('/view/ebay/advanced/autoaction_popup/', 'shown', '0', NULL);
        $moduleConfigModifier->insert('/view/ebay/motors_epids_attribute/', 'listing_notification_shown', '0', NULL);
        $moduleConfigModifier->insert('/view/ebay/multi_currency_marketplace_2/', 'notification_shown', '0', NULL);
        $moduleConfigModifier->insert('/view/ebay/multi_currency_marketplace_19/', 'notification_shown', '0', NULL);
        $moduleConfigModifier->insert('/view/ebay/terapeak/', 'mode', '1', NULL);
        $moduleConfigModifier->insert('/debug/exceptions/', 'send_to_server', '1', '0 - disable,\r\n1 - enable');
        $moduleConfigModifier->insert('/debug/exceptions/', 'filters_mode', '0', '0 - disable,\r\n1 - enable');
        $moduleConfigModifier->insert('/debug/fatal_error/', 'send_to_server', '1', '0 - disable,\r\n1 - enable');
        $moduleConfigModifier->insert('/debug/logging/', 'send_to_server', 1, '0 - disable,\r\n1 - enable');
        $moduleConfigModifier->insert('/debug/maintenance/', 'mode', '0', '0 - disable,\r\n1 - enable');
        $moduleConfigModifier->insert('/debug/maintenance/', 'restore_date', NULL, NULL);
        $moduleConfigModifier->insert('/renderer/description/', 'convert_linebreaks', '1', '0 - No\r\n1 - Yes');
        $moduleConfigModifier->insert('/other/paypal/', 'url', 'paypal.com/cgi-bin/webscr/', 'PayPal url');
        $moduleConfigModifier->insert('/product/index/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/product/force_qty/', 'mode', '0', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/product/force_qty/', 'value', '10', 'min qty value');
        $moduleConfigModifier->insert('/qty/percentage/', 'rounding_greater', '0', NULL);
        $moduleConfigModifier->insert(
            '/order/magento/settings/',
            'create_with_first_product_options_when_variation_unavailable', '1',
            '0 - disable, \r\n1 - enabled'
        );
        $moduleConfigModifier->insert('/setup/upgrade/', 'is_need_rollback_backup', 0, NULL);

        $synchronizationConfigModifier = $this->getConfigModifier('synchronization');

        $synchronizationConfigModifier->insert(NULL, 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(NULL, 'last_access', NULL, NULL);
        $synchronizationConfigModifier->insert(NULL, 'last_run', NULL, NULL);
        $synchronizationConfigModifier->insert('/settings/product_change/', 'max_count_per_one_time', '500', NULL);
        $synchronizationConfigModifier->insert('/settings/product_change/', 'max_lifetime', '172800', 'in seconds');
        $synchronizationConfigModifier->insert('/global/', 'mode', '1', NULL);
        $synchronizationConfigModifier->insert('/global/magento_products/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/', 'interval', '3600', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/deleted_products/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/added_products/', 'last_magento_product_id', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'mode', '0', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'last_listing_product_id', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'min_interval_between_circles', '3600', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'max_count_times_for_full_circle', '50', NULL
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'min_count_items_per_one_time', '100', NULL
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'max_count_items_per_one_time', '500', NULL
        );
        $synchronizationConfigModifier->insert(
            '/global/magento_products/inspector/', 'last_time_start_circle', NULL, NULL
        );
        $synchronizationConfigModifier->insert('/global/processing/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'interval', '3600', 'in seconds');
        $synchronizationConfigModifier->insert('/global/stop_queue/', 'last_time', NULL, 'Last check time');

        $this->getConnection()->insertMultiple($this->getFullTableName('wizard'), [
            [
                'nick'     => 'migrationFromMagento1',
                'view'     => '*',
                'status'   => 2,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 1,
            ],
            [
                'nick'     => 'installationEbay',
                'view'     => 'ebay',
                'status'   => 0,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 2,
            ],
            [
                'nick'     => 'installationAmazon',
                'view'     => 'amazon',
                'status'   => 0,
                'step'     => NULL,
                'type'     => 1,
                'priority' => 3,
            ]
        ]);
    }

    private function installEbay()
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/component/ebay/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert(
            '/ebay/order/settings/marketplace_8/',
            'use_first_street_line_as_company', '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/ebay/connector/listing/', 'check_the_same_product_already_listed', '1', NULL);
        $moduleConfigModifier->insert(
            '/view/ebay/template/category/', 'use_last_specifics', '0', '0 - false, \r\n1 - true'
        );
        $moduleConfigModifier->insert('/ebay/motors/', 'epids_attribute', NULL, NULL);
        $moduleConfigModifier->insert('/ebay/motors/', 'ktypes_attribute', NULL, NULL);
        $moduleConfigModifier->insert('/ebay/sell_on_another_marketplace/', 'tutorial_shown', '0', NULL);
        $moduleConfigModifier->insert('/ebay/translation_services/gold/', 'avg_cost', '7.21', NULL);
        $moduleConfigModifier->insert('/ebay/translation_services/silver/', 'avg_cost', '1.21', NULL);
        $moduleConfigModifier->insert('/ebay/translation_services/platinum/', 'avg_cost', '17.51', NULL);
        $moduleConfigModifier->insert('/ebay/description/', 'upload_images_mode', 2, NULL);
        $moduleConfigModifier->insert('/cron/task/ebay_actions/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert('/cron/task/ebay_actions/', 'interval', '60', 'in seconds');
        $moduleConfigModifier->insert('/cron/task/ebay_actions/', 'last_access', NULL, 'date of last access');
        $moduleConfigModifier->insert('/cron/task/ebay_actions/', 'last_run', NULL, 'date of last run');
        $moduleConfigModifier->insert(
            '/cron/task/update_ebay_accounts_preferences/', 'mode', 1, '0 - disable,\r\n1 - enable'
        );
        $moduleConfigModifier->insert('/cron/task/update_ebay_accounts_preferences/', 'interval', 86400, 'in seconds');
        $moduleConfigModifier->insert(
            '/cron/task/update_ebay_accounts_preferences/', 'last_run', NULL, 'date of last run'
        );

        $synchronizationConfigModifier = $this->getConfigModifier('synchronization');

        $synchronizationConfigModifier->insert('/ebay/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/general/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/process/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/account_pickup_store/update/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/receive/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/receive/', 'interval', '10800', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/receive/', 'last_time', NULL, 'date of last access'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/general/feedbacks/response/', 'interval', '10800', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/', 'last_time', NULL, 'date of last access'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/general/feedbacks/response/', 'attempt_interval', '86400', 'in seconds'
        );
        $synchronizationConfigModifier->insert('/ebay/listings_products/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/listings_products/remove_duplicates/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/listings_products/update/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/marketplaces/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/categories/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/details/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/motors_epids/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/marketplaces/motors_ktypes/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/orders/receive/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/orders/update/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/orders/cancellation/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'last_time', NULL, 'date of last access');
        $synchronizationConfigModifier->insert('/ebay/orders/cancellation/', 'start_date', NULL, 'date of first run');
        $synchronizationConfigModifier->insert('/ebay/orders/reserve_cancellation/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/orders/reserve_cancellation/', 'interval', '3600', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/ebay/orders/reserve_cancellation/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert('/ebay/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/other_listings/update/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/other_listings/sku/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/ebay/templates/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/list/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/relist/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/total/', 'last_listing_product_id', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/revise/total/', 'start_date', NULL, NULL
        );
        $synchronizationConfigModifier->insert('/ebay/templates/synchronization/revise/total/', 'end_date', NULL, NULL);
        $synchronizationConfigModifier->insert(
            '/ebay/templates/synchronization/stop/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/ebay/templates/remove_unused/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/ebay/templates/remove_unused/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/ebay/templates/remove_unused/', 'last_time', NULL, 'Last check time');

        $this->getConnection()->insertMultiple($this->getFullTableName('marketplace'), [
            [
                'id'             => 1,
                'native_id'      => 0,
                'title'          => 'United States',
                'code'           => 'US',
                'url'            => 'ebay.com',
                'status'         => 0,
                'sorder'         => 1,
                'group_title'    => 'America',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 2,
                'native_id'      => 2,
                'title'          => 'Canada',
                'code'           => 'Canada',
                'url'            => 'ebay.ca',
                'status'         => 0,
                'sorder'         => 8,
                'group_title'    => 'America',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 3,
                'native_id'      => 3,
                'title'          => 'United Kingdom',
                'code'           => 'UK',
                'url'            => 'ebay.co.uk',
                'status'         => 0,
                'sorder'         => 2,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 4,
                'native_id'      => 15,
                'title'          => 'Australia',
                'code'           => 'Australia',
                'url'            => 'ebay.com.au',
                'status'         => 0,
                'sorder'         => 4,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 5,
                'native_id'      => 16,
                'title'          => 'Austria',
                'code'           => 'Austria',
                'url'            => 'ebay.at',
                'status'         => 0,
                'sorder'         => 5,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 6,
                'native_id'      => 23,
                'title'          => 'Belgium (French)',
                'code'           => 'Belgium_French',
                'url'            => 'befr.ebay.be',
                'status'         => 0,
                'sorder'         => 7,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 7,
                'native_id'      => 71,
                'title'          => 'France',
                'code'           => 'France',
                'url'            => 'ebay.fr',
                'status'         => 0,
                'sorder'         => 10,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 8,
                'native_id'      => 77,
                'title'          => 'Germany',
                'code'           => 'Germany',
                'url'            => 'ebay.de',
                'status'         => 0,
                'sorder'         => 3,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 9,
                'native_id'      => 100,
                'title'          => 'eBay Motors',
                'code'           => 'eBayMotors',
                'url'            => 'motors.ebay.com',
                'status'         => 0,
                'sorder'         => 23,
                'group_title'    => 'Other',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 10,
                'native_id'      => 101,
                'title'          => 'Italy',
                'code'           => 'Italy',
                'url'            => 'ebay.it',
                'status'         => 0,
                'sorder'         => 14,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 11,
                'native_id'      => 123,
                'title'          => 'Belgium (Dutch)',
                'code'           => 'Belgium_Dutch',
                'url'            => 'benl.ebay.be',
                'status'         => 0,
                'sorder'         => 6,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 12,
                'native_id'      => 146,
                'title'          => 'Netherlands',
                'code'           => 'Netherlands',
                'url'            => 'ebay.nl',
                'status'         => 0,
                'sorder'         => 16,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 13,
                'native_id'      => 186,
                'title'          => 'Spain',
                'code'           => 'Spain',
                'url'            => 'ebay.es',
                'status'         => 0,
                'sorder'         => 19,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 14,
                'native_id'      => 193,
                'title'          => 'Switzerland',
                'code'           => 'Switzerland',
                'url'            => 'ebay.ch',
                'status'         => 0,
                'sorder'         => 22,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 15,
                'native_id'      => 201,
                'title'          => 'Hong Kong',
                'code'           => 'HongKong',
                'url'            => 'ebay.com.hk',
                'status'         => 0,
                'sorder'         => 11,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 16,
                'native_id'      => 203,
                'title'          => 'India',
                'code'           => 'India',
                'url'            => 'ebay.in',
                'status'         => 0,
                'sorder'         => 12,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 17,
                'native_id'      => 205,
                'title'          => 'Ireland',
                'code'           => 'Ireland',
                'url'            => 'ebay.ie',
                'status'         => 0,
                'sorder'         => 13,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 18,
                'native_id'      => 207,
                'title'          => 'Malaysia',
                'code'           => 'Malaysia',
                'url'            => 'ebay.com.my',
                'status'         => 0,
                'sorder'         => 15,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 19,
                'native_id'      => 210,
                'title'          => 'Canada (French)',
                'code'           => 'CanadaFrench',
                'url'            => 'cafr.ebay.ca',
                'status'         => 0,
                'sorder'         => 9,
                'group_title'    => 'America',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 20,
                'native_id'      => 211,
                'title'          => 'Philippines',
                'code'           => 'Philippines',
                'url'            => 'ebay.ph',
                'status'         => 0,
                'sorder'         => 17,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 21,
                'native_id'      => 212,
                'title'          => 'Poland',
                'code'           => 'Poland',
                'url'            => 'ebay.pl',
                'status'         => 0,
                'sorder'         => 18,
                'group_title'    => 'Europe',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 22,
                'native_id'      => 216,
                'title'          => 'Singapore',
                'code'           => 'Singapore',
                'url'            => 'ebay.com.sg',
                'status'         => 0,
                'sorder'         => 20,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'ebay',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ]
        ]);

        $this->getConnection()->insertMultiple($this->getFullTableName('ebay_marketplace'), [
            [
                'marketplace_id'                       => 1,
                'currency'                             => 'USD',
                'origin_country'                       => 'us',
                'language_code'                        => 'en_US',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 1,
                'is_calculated_shipping'               => 1,
                'is_tax_table'                         => 1,
                'is_vat'                               => 0,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 1,
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 1,
                'is_english_measurement_system'        => 1,
                'is_metric_measurement_system'         => 0,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 1,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 1,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 2,
                'currency'                             => 'CAD,USD',
                'origin_country'                       => 'ca',
                'language_code'                        => 'en_CA',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 1,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 1,
                'is_calculated_shipping'               => 1,
                'is_tax_table'                         => 1,
                'is_vat'                               => 0,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 1,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 3,
                'currency'                             => 'GBP',
                'origin_country'                       => 'gb',
                'language_code'                        => 'en_GB',
                'translation_service_mode'             => 3,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 1,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 1,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 1,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 1,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 1,
                'is_in_store_pickup'                   => 1,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 4,
                'currency'                             => 'AUD',
                'origin_country'                       => 'au',
                'language_code'                        => 'en_AU',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 1,
                'is_calculated_shipping'               => 1,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 1,
                'is_in_store_pickup'                   => 1,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 5,
                'currency'                             => 'EUR',
                'origin_country'                       => 'at',
                'language_code'                        => 'de_AT',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 6,
                'currency'                             => 'EUR',
                'origin_country'                       => 'be',
                'language_code'                        => 'nl_BE',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 7,
                'currency'                             => 'EUR',
                'origin_country'                       => 'fr',
                'language_code'                        => 'fr_FR',
                'translation_service_mode'             => 1,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 8,
                'currency'                             => 'EUR',
                'origin_country'                       => 'de',
                'language_code'                        => 'de_DE',
                'translation_service_mode'             => 3,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 1,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 1,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 9,
                'currency'                             => 'USD',
                'origin_country'                       => 'us',
                'language_code'                        => 'en_US',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 1,
                'is_tax_table'                         => 1,
                'is_vat'                               => 0,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 1,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 1,
                'is_metric_measurement_system'         => 0,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 1,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 10,
                'currency'                             => 'EUR',
                'origin_country'                       => 'it',
                'language_code'                        => 'it_IT',
                'translation_service_mode'             => 1,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 1,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 11,
                'currency'                             => 'EUR',
                'origin_country'                       => 'be',
                'language_code'                        => 'fr_BE',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 12,
                'currency'                             => 'EUR',
                'origin_country'                       => 'nl',
                'language_code'                        => 'nl_NL',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 13,
                'currency'                             => 'EUR',
                'origin_country'                       => 'es',
                'language_code'                        => 'es_ES',
                'translation_service_mode'             => 1,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 14,
                'currency'                             => 'CHF',
                'origin_country'                       => 'ch',
                'language_code'                        => 'fr_CH',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 15,
                'currency'                             => 'HKD',
                'origin_country'                       => 'hk',
                'language_code'                        => 'zh_HK',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 16,
                'currency'                             => 'INR',
                'origin_country'                       => 'in',
                'language_code'                        => 'hi_IN',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 17,
                'currency'                             => 'EUR',
                'origin_country'                       => 'ie',
                'language_code'                        => 'en_IE',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 1,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 18,
                'currency'                             => 'MYR',
                'origin_country'                       => 'my',
                'language_code'                        => 'ms_MY',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 19,
                'currency'                             => 'CAD,USD',
                'origin_country'                       => 'ca',
                'language_code'                        => 'fr_CA',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 1,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 1,
                'is_calculated_shipping'               => 1,
                'is_tax_table'                         => 1,
                'is_vat'                               => 0,
                'is_stp'                               => 1,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 1,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 1
            ],
            [
                'marketplace_id'                       => 20,
                'currency'                             => 'PHP',
                'origin_country'                       => 'ph',
                'language_code'                        => 'fil_PH',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 1,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 21,
                'currency'                             => 'PLN',
                'origin_country'                       => 'pl',
                'language_code'                        => 'pl_PL',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ],
            [
                'marketplace_id'                       => 22,
                'currency'                             => 'SGD',
                'origin_country'                       => 'sg',
                'language_code'                        => 'zh_SG',
                'translation_service_mode'             => 0,
                'is_multi_currency'                    => 0,
                'is_multivariation'                    => 0,
                'is_freight_shipping'                  => 0,
                'is_calculated_shipping'               => 0,
                'is_tax_table'                         => 0,
                'is_vat'                               => 0,
                'is_stp'                               => 0,
                'is_stp_advanced'                      => 0,
                'is_map'                               => 0,
                'is_local_shipping_rate_table'         => 0,
                'is_international_shipping_rate_table' => 0,
                'is_english_measurement_system'        => 0,
                'is_metric_measurement_system'         => 1,
                'is_cash_on_delivery'                  => 0,
                'is_global_shipping_program'           => 0,
                'is_charity'                           => 1,
                'is_click_and_collect'                 => 0,
                'is_in_store_pickup'                   => 0,
                'is_holiday_return'                    => 0
            ]
        ]);
    }

    private function installAmazon()
    {
        $moduleConfigModifier = $this->getConfigModifier('module');

        $moduleConfigModifier->insert('/amazon/', 'application_name', 'M2ePro - Amazon Magento Integration', NULL);
        $moduleConfigModifier->insert('/component/amazon/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert(
            '/amazon/order/settings/marketplace_25/',
            'use_first_street_line_as_company', '1',
            '0 - disable, \r\n1 - enable'
        );
        $moduleConfigModifier->insert('/amazon/repricing/', 'mode', '0', '0 - disable, \r\n1 - enable');
        $moduleConfigModifier->insert(
            '/amazon/repricing/',
            'base_url', 'https://repricer.m2epro.com/connector/m2epro/',
            'Repricing Tool base url'
        );

        $synchronizationConfigModifier = $this->getConfigModifier('synchronization');

        $synchronizationConfigModifier->insert('/amazon/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/amazon/general/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/', 'interval', '300', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/general/run_parent_processors/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/listings_products/update/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/', 'interval', '259200', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/defected/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/', 'interval', '3600', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/listings_products/update/blocked/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert('/amazon/marketplaces/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/categories/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/details/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/marketplaces/specifics/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert('/amazon/orders/reserve_cancellation/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert(
            '/amazon/orders/reserve_cancellation/', 'interval', '3600', 'in seconds'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/orders/reserve_cancellation/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert('/amazon/orders/update/', 'mode', '1', 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/other_listings/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert('/amazon/other_listings/update/', 'interval', '86400', 'in seconds');
        $synchronizationConfigModifier->insert('/amazon/other_listings/update/', 'last_time', NULL, 'Last check time');
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/title/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/', 'last_time', NULL, 'Last check time'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/other_listings/update/blocked/', 'interval', '3600', 'in seconds'
        );
        $synchronizationConfigModifier->insert('/amazon/templates/', 'mode', '1', '0 - disable, \r\n1 - enable');
        $synchronizationConfigModifier->insert(
            '/amazon/templates/repricing/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/list/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/relist/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/', 'last_listing_product_id', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/', 'start_date', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/revise/total/', 'end_date', NULL, NULL
        );
        $synchronizationConfigModifier->insert(
            '/amazon/templates/synchronization/stop/', 'mode', '1', '0 - disable, \r\n1 - enable'
        );

        $this->getConnection()->insertMultiple($this->getFullTableName('marketplace'), [
            [
                'id'             => 24,
                'native_id'      => 4,
                'title'          => 'Canada',
                'code'           => 'CA',
                'url'            => 'amazon.ca',
                'status'         => 0,
                'sorder'         => 4,
                'group_title'    => 'America',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 25,
                'native_id'      => 3,
                'title'          => 'Germany',
                'code'           => 'DE',
                'url'            => 'amazon.de',
                'status'         => 0,
                'sorder'         => 3,
                'group_title'    => 'Europe',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 26,
                'native_id'      => 5,
                'title'          => 'France',
                'code'           => 'FR',
                'url'            => 'amazon.fr',
                'status'         => 0,
                'sorder'         => 7,
                'group_title'    => 'Europe',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 27,
                'native_id'      => 6,
                'title'          => 'Japan',
                'code'           => 'JP',
                'url'            => 'amazon.co.jp',
                'status'         => 0,
                'sorder'         => 6,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 28,
                'native_id'      => 2,
                'title'          => 'United Kingdom',
                'code'           => 'UK',
                'url'            => 'amazon.co.uk',
                'status'         => 0,
                'sorder'         => 2,
                'group_title'    => 'Europe',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 29,
                'native_id'      => 1,
                'title'          => 'United States',
                'code'           => 'US',
                'url'            => 'amazon.com',
                'status'         => 0,
                'sorder'         => 1,
                'group_title'    => 'America',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 30,
                'native_id'      => 7,
                'title'          => 'Spain',
                'code'           => 'ES',
                'url'            => 'amazon.es',
                'status'         => 0,
                'sorder'         => 8,
                'group_title'    => 'Europe',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 31,
                'native_id'      => 8,
                'title'          => 'Italy',
                'code'           => 'IT',
                'url'            => 'amazon.it',
                'status'         => 0,
                'sorder'         => 5,
                'group_title'    => 'Europe',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ],
            [
                'id'             => 32,
                'native_id'      => 9,
                'title'          => 'China',
                'code'           => 'CN',
                'url'            => 'amazon.cn',
                'status'         => 0,
                'sorder'         => 9,
                'group_title'    => 'Asia / Pacific',
                'component_mode' => 'amazon',
                'update_date'    => '2013-05-08 00:00:00',
                'create_date'    => '2013-05-08 00:00:00'
            ]
        ]);

        $this->getConnection()->insertMultiple($this->getFullTableName('amazon_marketplace'), [
            [
                'marketplace_id'                    => 24,
                'developer_key'                     => '8636-1433-4377',
                'default_currency'                  => 'CAD',
                'is_asin_available'                 => 0,
                'is_merchant_fulfillment_available' => 0
            ],
            [
                'marketplace_id'                    => 25,
                'developer_key'                     => '7078-7205-1944',
                'default_currency'                  => 'EUR',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 1
            ],
            [
                'marketplace_id'                    => 26,
                'developer_key'                     => '7078-7205-1944',
                'default_currency'                  => 'EUR',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 0
            ],
            [
                'marketplace_id'                    => 27,
                'developer_key'                     => NULL,
                'default_currency'                  => '',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 0
            ],
            [
                'marketplace_id'                    => 28,
                'developer_key'                     => '7078-7205-1944',
                'default_currency'                  => 'GBP',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 1
            ],
            [
                'marketplace_id'                    => 29,
                'developer_key'                     => '8636-1433-4377',
                'default_currency'                  => 'USD',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 1
            ],
            [
                'marketplace_id'                    => 30,
                'developer_key'                     => '7078-7205-1944',
                'default_currency'                  => 'EUR',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 0
            ],
            [
                'marketplace_id'                    => 31,
                'developer_key'                     => '7078-7205-1944',
                'default_currency'                  => 'EUR',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 0
            ],
            [
                'marketplace_id'                    => 32,
                'developer_key'                     => NULL,
                'default_currency'                  => '',
                'is_asin_available'                 => 1,
                'is_merchant_fulfillment_available' => 0
            ]
        ]);
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getFullTableName($tableName)
    {
        return $this->tablesObject->getFullName($tableName);
    }

    /**
     * @param $configName
     * @return Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName.'_config';

        return $this->configModifierFactory->create(
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    private function getConfigVersion()
    {
        return $this->moduleList->getOne(Module::IDENTIFIER)['setup_version'];
    }

    //########################################
}