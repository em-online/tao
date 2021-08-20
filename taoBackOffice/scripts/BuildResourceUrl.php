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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 */
namespace oat\taoBackOffice\scripts;

use common_report_Report as Report;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\extension\script\ScriptAction;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\entryPoint\EntryPointService;
use oat\tao\model\user\TaoRoles;
use oat\taoBackOffice\model\routing\ResourceUrlBuilder;
use oat\taoDeliveryRdf\model\guest\GuestAccess;


/**
 * Run example:
 *
 * ```
 * sudo -u www-data php index.php 'oat\taoBackOffice\scripts\BuildResourceUrl' -u {resourceUri}
 * ```
 */
class BuildResourceUrl extends ScriptAction
{
    protected function provideOptions()
    {
        return [
            'uri' => [
                'prefix' => 'u',
                'longPrefix' => 'uri',
                'required' => true,
                'description' => 'Resource uri'
            ],
        ];
    }

    protected function provideDescription()
    {
        return 'The script generates a full url for a resource.';
    }

    protected function run()
    {
        try {
            $resource = new \core_kernel_classes_Resource($this->getOption('uri'));

            if ($resource->isClass()) {
                $resource = new \core_kernel_classes_Class($resource);
            }

            if (!$resource->exists()) {
                throw new \RuntimeException('Resource "'. $this->getOption('uri'). '" not found');
            }

            /** @var ResourceUrlBuilder $urlBuilder */
            $urlBuilder = $this->getServiceLocator()->get(ResourceUrlBuilder::SERVICE_ID);

            $report = Report::createSuccess('URL: '. $urlBuilder->buildUrl($resource));

        } catch (\Exception $e) {
            $report = Report::createFailure($e->getMessage());
        }

        return $report;
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
        ];
    }
}