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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */
namespace oat\taoTestTaker\models;
use common_Logger;
use common_report_Report;
use core_kernel_classes_Resource;
use core_kernel_classes_class;
use oat\generis\Helper\UserHashForEncryption;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\user\UserLanguageService;
use oat\tao\model\TaoOntology;
use oat\generis\model\GenerisRdf;
use oat\taoTestTaker\models\events\TestTakerImportedEvent;
use oat\tao\models\classes\import\CSVMappingForm;
use oat\taoTestTaker\helpers\data\TestTakerAdapterCsv;
use oat\tao\model\event\CsvImportEvent;

/**
 * A custom subject CSV importer
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoSubjects

 */
class CsvImporter extends \tao_models_classes_import_CsvImporter
{
    public function import($class, $form)
    {
        // $report = parent::import($class, $form);
        $report = $this->importFile($class, $form);

        /** @var common_report_Report $success */
        foreach ($report->getSuccesses() as $success) {
            $resource = $success->getData();
            try {
                $this->getEventManager()->trigger(
                    new TestTakerImportedEvent($resource->getUri(), $this->getProperties($resource))
                );
            } catch (\Exception $e) {
                common_Logger::e($e->getMessage());
            }
        }

        return $report;
    }

    /**
     * (non-PHPdoc)
     * @param core_kernel_classes_Class $class
     * @param tao_helpers_form_Form|array $form
     * @return common_report_Report
     * @throws \oat\oatbox\service\ServiceNotFoundException
     * @throws \common_Exception
     */
    public function importFile($class, $form)
    {
        // for backward compatibility
        $options = $form instanceof \tao_helpers_form_Form ? $form->getValues() : $form;

        $options['file'] = $this->fetchUploadedFile($form);

        // Clean "csv_select" values from form view.
        // Transform any "csv_select" in "csv_null" in order to
        // have the same importation behaviour for both because
        // semantics are the same.

        // for backward compatibility
        $map = $form instanceof \tao_helpers_form_Form ? $form->getValues('property_mapping') : $form['property_mapping'];
        $newMap = array();

        foreach ($map as $k => $m) {
            if ($m !== 'csv_select') {
                $newMap[$k] = $map[$k];
            } else {
                $newMap[$k] = 'csv_null';
            }
            $newMap[$k] = str_replace(self::OPTION_POSTFIX, '', $newMap[$k]);
            common_Logger::d('map: ' . $k . ' => ' . $newMap[$k]);
        }
        $options['map'] = $newMap;
        $this->$options['map'] = $newMap;

        $staticMap = array();

        // for backward compatibility
        $rangedProperties = $form instanceof \tao_helpers_form_Form ? $form->getValues('ranged_property') : $form['ranged_property'];

        foreach ($rangedProperties as $propUri => $value) {
            if (strpos($propUri, \tao_models_classes_import_CSVMappingForm::DEFAULT_VALUES_SUFFIX) !== false) {
                $cleanUri = str_replace(\tao_models_classes_import_CSVMappingForm::DEFAULT_VALUES_SUFFIX, '', $propUri);
                $staticMap[$cleanUri] = $value;
            }
        }
        $options['staticMap'] = array_merge($staticMap, $this->getStaticData());

        // $report = parent::importFile($class, $options);





        if (!isset($options['file'])) {
            throw new \BadFunctionCallException("Import file is missing");
        }

        if(!isset($options['staticMap']) || !is_array($options['staticMap'])){
            $options['staticMap'] = $this->getStaticData();
        } else {
            $options['staticMap'] = array_merge($options['staticMap'], $this->getStaticData());
        }
        $options = array_merge($options, $this->getAdditionAdapterOptions());

        //import the file
        $adapter = new TestTakerAdapterCsv($options);
        $adapter->setValidators($this->getValidators());







        // $report = $adapter->import($options['file'], $class);
        $report = $adapter->import($options['file'], $class);









        if ($report->getType() == common_report_Report::TYPE_SUCCESS) {
            $report->setData($adapter->getOptions());
            $this->getEventManager()->trigger(new CsvImportEvent($report));
        }

        return $report;
    }



    public function getValidators()
    {
        return [
            /*GenerisRdf::PROPERTY_USER_LOGIN => [\tao_helpers_form_FormFactory::getValidator('Unique')],*/
            GenerisRdf::PROPERTY_USER_LOGIN => [\tao_helpers_form_FormFactory::getValidator('UniqueToDestination')],
            GenerisRdf::PROPERTY_USER_UILG => [\tao_helpers_form_FormFactory::getValidator('NotEmpty')],
        ];
    }

    /**
     * @param core_kernel_classes_Resource $resource
     * @return array
     * @throws \core_kernel_persistence_Exception
     * @throws \common_ext_ExtensionException
     */
    protected function getProperties($resource)
    {
        /** @var \common_ext_ExtensionsManager $extManager */
        $extManager =  ServiceManager::getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);
        $taoTestTaker = $extManager->getExtensionById('taoTestTaker');
        $config = $taoTestTaker->getConfig('csvImporterCallbacks');

        if ((bool)$config['use_properties_for_event']) {
            return [
                'hashForKey'                       => UserHashForEncryption::hash(TestTakerSavePasswordInMemory::getPassword()),
                GenerisRdf::PROPERTY_USER_PASSWORD => $resource->getOnePropertyValue(
                    new \core_kernel_classes_Property(GenerisRdf::PROPERTY_USER_PASSWORD)
                )->literal
            ];
        }



        return [];
    }

    /**
     * (non-PHPdoc)
     * @see tao_models_classes_import_CsvImporter::getExludedProperties()
     */
    protected function getExludedProperties()
    {
        return array_merge(parent::getExludedProperties(), array(
            GenerisRdf::PROPERTY_USER_DEFLG,
            GenerisRdf::PROPERTY_USER_ROLES,
            TaoOntology::PROPERTY_USER_LAST_EXTENSION,
            TaoOntology::PROPERTY_USER_FIRST_TIME,
            GenerisRdf::PROPERTY_USER_TIMEZONE
        ));
    }

    /**
     * (non-PHPdoc)
     * @see tao_models_classes_import_CsvImporter::getStaticData()
     */
    protected function getStaticData()
    {
        $lang = \tao_helpers_I18n::getLangResourceByCode(DEFAULT_LANG)->getUri();

        return array(
            GenerisRdf::PROPERTY_USER_DEFLG => $lang,
            GenerisRdf::PROPERTY_USER_TIMEZONE => TIME_ZONE,
            GenerisRdf::PROPERTY_USER_ROLES => TaoOntology::PROPERTY_INSTANCE_ROLE_DELIVERY,
        );
    }

    /**
     * (non-PHPdoc)
     * @see tao_models_classes_import_CsvImporter::getAdditionAdapterOptions()
     * @throws \common_ext_ExtensionException
     */
    protected function getAdditionAdapterOptions()
    {
        /** @var \common_ext_ExtensionsManager $extManager */
        $extManager = ServiceManager::getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);
        $taoTestTaker = $extManager->getExtensionById('taoTestTaker');
        $config = $taoTestTaker->getConfig('csvImporterCallbacks');

        if (empty($config['callbacks'])){
            $returnValue = array(
                'callbacks' => array(
                    '*' => array('trim'),
                    GenerisRdf::PROPERTY_USER_PASSWORD => array('oat\taoTestTaker\models\CsvImporter::taoSubjectsPasswordEncode')
                )
            );
        } else {
            $returnValue = array(
                'callbacks' => $config['callbacks']
            );
        }

        return $returnValue;
    }

    /**
     * Wrapper for password hash
     *
     * @param  string $value
     * @return string
     */
    public static function taoSubjectsPasswordEncode($value)
    {
        return \core_kernel_users_Service::getPasswordHash()->encrypt($value);
    }

}

