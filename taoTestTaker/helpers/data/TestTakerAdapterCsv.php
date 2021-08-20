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
namespace oat\taoTestTaker\helpers\data;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ServiceManager;
use oat\tao\helpers\data\ValidationException;
use oat\tao\model\upload\UploadService;
use oat\oatbox\filesystem\File;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdf;


class TestTakerAdapterCsv extends \tao_helpers_data_GenerisAdapterCsv
{
    public function import($source, \core_kernel_classes_Class $destination = null)
    {
        if (!isset($this->options['map'])) {
            throw new \BadFunctionCallException("import map not set");
        }
        if (is_null($destination)) {
            throw new \InvalidArgumentException("${destination} must be a valid core_kernel_classes_Class");
        }

        /** @var UploadService $uploadService */
        $uploadService = ServiceManager::getServiceManager()->get(UploadService::SERVICE_ID);

        if (!$source instanceof File) {
            $file = $uploadService->getUploadedFlyFile($source);
        } else {
            $file = $source;
        }

        if (@preg_match('//u', $file->read()) === false) {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __("The imported file is not properly UTF-8 encoded."));
        }

        $csvData = $this->load($file);

        $createdResources = 0;
        $toImport = $csvData->count();
        $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Data not imported. All records are invalid.'));

        for ($rowIterator = 0; $rowIterator < $csvData->count(); $rowIterator++) {
            \helpers_TimeOutHelper::setTimeOutLimit(\helpers_TimeOutHelper::SHORT);
            \common_Logger::d("CSV - Importing CSV row ${rowIterator}.");

            $resource = null;
            $csvRow = $csvData->getRow($rowIterator);

            try {
                // default values
                $evaluatedData = $this->options['staticMap'];

                // validate csv values
                foreach ($this->options['map'] as $propUri => $csvColumn) {
                    $this->validate($destination, $propUri, $csvRow, $csvColumn, $evaluatedData);
                }

                // evaluate csv values
                foreach ($this->options['map'] as $propUri => $csvColumn) {

                    if ($csvColumn != 'csv_null' && $csvColumn != 'csv_select') {
                        // process value
                        if (isset($csvRow[$csvColumn]) && !is_null($csvRow[$csvColumn])) {
                            $property = new \core_kernel_classes_Property($propUri);
                            $evaluatedData[$propUri] = $this->evaluateValues($csvColumn, $property, $csvRow[$csvColumn]);
                        }
                    }
                }

                $login = reset($evaluatedData[GenerisRdf::PROPERTY_USER_LOGIN]);
                $filter_array = array(GenerisRdf::PROPERTY_USER_LOGIN => $login);
                $prop = GenerisRdf::PROPERTY_USER_LOGIN;
                //common_Logger::d("${prop}");
                //common_Logger::d("${login}");
                $existingSubjects = $destination->getInstances(false, $filter_array);;
                $count = count($existingSubjects);
                //common_Logger::d("${count}");
                $update_existing = false;
                foreach($existingSubjects as $existing) {
                    //common_Logger::d("${existing}");
                    $exist_properties = $existing->getPropertyValues(new \core_kernel_classes_Property($prop));
                    $first_exist_prop = reset($exist_properties);
                    if($first_exist_prop == $login) {
                        //common_Logger::d("Match: ${first_exist_prop}");
                        $resource = $existing;
                        $update_existing = true;
                    }
                }
                if($update_existing) {
                    // Update
                    \common_Logger::d("Update");
                    foreach($evaluatedData as $propertyUri => $propertyValue){
                        if($propertyUri == OntologyRdf::RDF_TYPE){
                            foreach($instance->getTypes() as $type){
                                $resource->removeType($type);
                            }
                            if(!is_array($propertyValue)){
                                $types = array($propertyValue) ;
                            }
                            foreach($types as $type){
                                $instance->setType(new \core_kernel_classes_Class($type));
                            }
                            continue;
                        }
                        $resource->editPropertyValues(new \core_kernel_classes_Property($propertyUri), $propertyValue);
                        \common_Logger::d("${propertyUri}: ${propertyValue}");
                    }
                    $msg = 'Updated resource "%s"';
                }
                else {
                    // create resource
                    \common_Logger::d("Create");
                    $resource = $destination->createInstanceWithProperties($evaluatedData);
                    $msg = 'Imported resource "%s"';
                }

                // Apply 'resourceImported' callbacks.
                foreach ($this->resourceImported as $callback) {
                    $callback($resource);
                }

                $report->add(new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __($msg, $resource->getLabel()), $resource));
                $createdResources++;

            } catch (ValidationException $valExc) {
                $failure = \common_report_Report::createFailure(
                    __('Row %s', $rowIterator + 1) . ' ' . $valExc->getProperty()->getLabel() . ': ' . $valExc->getUserMessage() . ' "' . $valExc->getValue() . '"'
                );
                $report->add($failure);
            }

            \helpers_TimeOutHelper::reset();
        }

        $this->addOption('to_import', $toImport);
        $this->addOption('imported', $createdResources);

        if ($createdResources == $toImport) {
            $report->setType(\common_report_Report::TYPE_SUCCESS);
            $report->setMessage(__('Imported %d resources', $toImport));
        } elseif ($createdResources > 0) {
            $report->setType(\common_report_Report::TYPE_WARNING);
            $report->setMessage(__('Imported %1$d/%2$d. Some records are invalid.', $createdResources, $toImport));
        }

        $uploadService->remove($file);

        return $report;
    }
}

