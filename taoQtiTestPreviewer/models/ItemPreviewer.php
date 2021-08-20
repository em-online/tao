<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoQtiTestPreviewer\models;

use oat\taoDelivery\model\RuntimeService;
use oat\taoItems\model\pack\ItemPack;
use oat\taoItems\model\pack\Packer;
use oat\taoQtiItem\model\qti\Service;
use oat\taoQtiItem\model\QtiJsonItemCompiler;
use oat\taoQtiTest\models\container\QtiTestDeliveryContainer;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class ItemPreviewer implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var string
     */
    private $userLanguage;

    /**
     * @var string
     */
    private $itemDefinition;

    /**
     * @var \core_kernel_classes_Resource
     */
    private $delivery;

    /**
     * @var \tao_models_classes_service_FileStorage
     */
    private $fileStorage;

    /**
     * @var string
     */
    private $itemUri;

    /**
     * @var \tao_models_classes_service_StorageDirectory
     */
    private $itemPublicDir;

    /**
     * @var \tao_models_classes_service_StorageDirectory
     */
    private $itemPrivateDir;

    /**
     * @var array
     */
    private $itemHrefs = [];

    public function __construct()
    {
        $this->fileStorage = \tao_models_classes_service_FileStorage::singleton();
    }

    /**
     * @param string $userLanguage
     * @return ItemPreviewer
     */
    public function setUserLanguage($userLanguage)
    {
        $this->userLanguage = $userLanguage;

        return $this;
    }

    /**
     * @param string $itemDefinition
     * @return ItemPreviewer
     */
    public function setItemDefinition($itemDefinition)
    {
        $this->itemDefinition = $itemDefinition;

        return $this;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return ItemPreviewer
     * @throws \common_exception_NotFound
     */
    public function setDelivery($delivery)
    {
        if (!$delivery->exists()) {
            throw new \common_exception_NotFound('Delivery "'. $delivery->getUri() .'" not found');
        }

        $this->delivery = $delivery;

        return $this;
    }

    /**
     * @return array
     *
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_InconsistentData
     * @throws \common_exception_NotFound
     */
    public function loadCompiledItemData()
    {
        if (empty($this->userLanguage) || empty($this->itemDefinition) || empty($this->delivery)) {
            throw new \LogicException('UserLanguage, ItemDefiniton and Delivery are mandatory for loading of compiled item data');
        }

        $jsonFile = $this->getItemPrivateDir()->getFile($this->userLanguage . DIRECTORY_SEPARATOR . QtiJsonItemCompiler::ITEM_FILE_NAME);
        $xmlFile = $this->getItemPrivateDir()->getFile($this->userLanguage . DIRECTORY_SEPARATOR . Service::QTI_ITEM_FILE);

        if ($jsonFile->exists()) {
            // new test runner is used
            $itemData = json_decode($jsonFile->read(), true);
        } elseif ($xmlFile->exists()) {
            // old test runner is used
            /** @var Packer $packer */
            $packer = (new Packer(new \core_kernel_classes_Resource($this->getItemUri()), $this->userLanguage))
                ->setServiceLocator($this->getServiceLocator());

            /** @var ItemPack $itemPack */
            $itemPack = $packer->pack();

            $itemData = $itemPack->JsonSerialize();
        } else {
            throw new \common_exception_NotFound('Either item.json or qti.xml should exist');
        }

        return $itemData;
    }

    /**
     * @return string
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_InconsistentData
     */
    public function getBaseUrl()
    {
        return $this->getItemPublicDir()->getPublicAccessUrl() . $this->userLanguage . '/';
    }

    /**
     * @return \tao_models_classes_service_StorageDirectory
     * @throws \common_exception_InconsistentData
     */
    private function getItemPublicDir()
    {
        if (is_null($this->itemPublicDir)) {
            $this->itemPublicDir = $this->fileStorage->getDirectoryById($this->getItemPublicHref());
        }

        return $this->itemPublicDir;
    }

    /**
     * @return \tao_models_classes_service_StorageDirectory
     * @throws \common_exception_InconsistentData
     */
    private function getItemPrivateDir()
    {
        if (is_null($this->itemPrivateDir)) {
            $this->itemPrivateDir = $this->fileStorage->getDirectoryById($this->getItemPrivateHref());
        }

        return $this->itemPrivateDir;
    }

    /**
     * @return string
     * @throws \common_exception_InconsistentData
     */
    public function getItemUri()
    {
        if (empty($this->itemHrefs)) {
            $this->loadItemHrefs();
        }

        return $this->itemHrefs[0];
    }

    /**
     * @return string
     * @throws \common_exception_InconsistentData
     */
    private function getItemPublicHref()
    {
        if (empty($this->itemHrefs)) {
            $this->loadItemHrefs();
        }

        return $this->itemHrefs[1];
    }

    /**
     * @return string
     * @throws \common_exception_InconsistentData
     */
    private function getItemPrivateHref()
    {
        if (empty($this->itemHrefs)) {
            $this->loadItemHrefs();
        }

        return $this->itemHrefs[2];
    }

    private function loadItemHrefs()
    {
        $runtimeService = $this->getServiceLocator()->get(RuntimeService::SERVICE_ID);
        /** @var \oat\taoDelivery\model\container\delivery\AbstractContainer $deliveryContainer */
        $deliveryContainer = $runtimeService->getDeliveryContainer($this->delivery->getUri());

        $deliveryPrivateDir = null;
        if ($deliveryContainer instanceof QtiTestDeliveryContainer) {
            // in case of new test runner
            $deliveryPrivateDir = $deliveryContainer->getRuntimeParams()['private'];
        } else {
            // in case of old test runner
            $inParams = $deliveryContainer->getRuntimeParams()['in'];

            foreach ($inParams as $param) {
                if ($param['def'] == \taoQtiTest_models_classes_QtiTestService::INSTANCE_FORMAL_PARAM_TEST_COMPILATION) {
                    $deliveryPrivateDir = explode('|', $param['const'])[0];
                    break;
                }
            }
        }

        if (!$deliveryPrivateDir){
            throw new \common_exception_InconsistentData('Could not determine private dir of delivery');
        }

        $deliveryPrivateStorageDir = $this->fileStorage->getDirectoryById($deliveryPrivateDir);

        $itemHrefIndexPath = \taoQtiTest_models_classes_QtiTestCompiler::buildHrefIndexPath($this->itemDefinition);

        $itemHrefs = explode('|', $deliveryPrivateStorageDir->getFile($itemHrefIndexPath)->read());

        if (count($itemHrefs) < 3) {
            throw new \common_exception_InconsistentData('The itemRef is not formatted correctly');
        }

        $this->itemHrefs = $itemHrefs;
    }
}
