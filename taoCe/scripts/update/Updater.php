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

namespace oat\taoCe\scripts\update;

use oat\tao\model\entryPoint\EntryPointService;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\user\TaoRoles;
use oat\taoDeliveryRdf\model\guest\GuestAccess;
/**
 * TAO Community Edition Updater.
 *
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * Perform update from $currentVersion to $versionUpdatedTo.
     *
     * @param string $currentVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion)
    {
        if ($this->isBetween('0.0.0', '1.1.3')) {
            throw new \common_exception_NotImplemented('Updates from versions prior to Tao 3.1 are not longer supported, please update to Tao 3.1 first');
        }

        $this->skip('1.1.3', '1.6.2');

        if ($this->isVersion('1.6.2')) {
            AclProxy::applyRule(new AccessRule('grant', TaoRoles::ANONYMOUS,
                ['ext'=>'taoCe', 'mod' => 'Main', 'act' => 'rootEntry']));

            $this->setVersion('1.7.0');
        }
        $this->skip('1.7.0', '1.7.1');

        // add guest login
        if ($this->isVersion('1.7.1')) {

            $entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);
            $entryPointService->addEntryPoint(new GuestAccess(), EntryPointService::OPTION_PRELOGIN);
            $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $entryPointService);

            $this->setVersion('1.8.0');
        }

        $this->skip('1.8.0', '5.4.1');
    }
}
