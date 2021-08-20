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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoBackOffice\scripts\update;

use oat\tao\scripts\update\OntologyUpdater;
use oat\taoBackOffice\model\entryPoint\BackOfficeEntryPoint;
use oat\tao\model\entryPoint\BackOfficeEntrypoint as OldEntryPoint;
use oat\tao\model\entryPoint\EntryPointService;
use oat\taoBackOffice\model\routing\ResourceUrlBuilder;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\user\TaoRoles;
use oat\taoBackOffice\controller\Redirector;

/**
 * Class TreeService
 */
class Updater extends \common_ext_ExtensionUpdater
{

    public function update($initialVersion)
    {

        $currentVersion = $initialVersion;

        // ontology
        if ($currentVersion == '0.8') {
            OntologyUpdater::syncModels();
            $currentVersion = '0.9';
        }
        if ($currentVersion == '0.9') {
            $currentVersion = '0.10';
        }
        if ($currentVersion == '0.10') {
            $entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);

            // replace old backoffice
            $newEntryPoint = new BackOfficeEntryPoint();
            foreach ($entryPointService->getEntryPoints() as $entryPoint) {
                if ($entryPoint instanceof OldEntryPoint) {
                    $entryPointService->overrideEntryPoint($entryPoint->getId(), $newEntryPoint);
                }
            }

            $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $entryPointService);

            $currentVersion = '0.11';
        }

        $this->setVersion($currentVersion);

        $this->skip($currentVersion, '2.0.2');

        if ($this->isVersion('2.0.2')) {
            $this->getServiceManager()->register(ResourceUrlBuilder::SERVICE_ID, new ResourceUrlBuilder());

            $this->setVersion('2.1.0');
        }
        if ($this->isVersion('2.1.0')) {
            AclProxy::applyRule(new AccessRule('grant', TaoRoles::BACK_OFFICE, Redirector::class.'@redirectTaskToInstance'));
            $this->setVersion('2.1.1');
        }

        $this->skip('2.1.1', '3.3.1');
    }
}
