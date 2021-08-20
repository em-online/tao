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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoQtiTestPreviewer\scripts\update;

use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\modules\DynamicModule;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoItems\model\preview\ItemPreviewerService;
use oat\taoOutcomeUi\model\ResultsViewerService;

/**
 * Class Updater
 * @package oat\taoQtiTestPreviewer\scripts\update
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     *
     * @param string $initialVersion
     * @return string $versionUpdatedTo
     * @throws \common_exception_InconsistentData
     */
    public function update($initialVersion)
    {
        if ($this->isVersion('0.0.0')) {
            $registry = $this->getServiceManager()->get(ItemPreviewerService::SERVICE_ID);
            $registry->registerAdapter(
                DynamicModule::fromArray(
                    [
                        'id' => 'qtiItem',
                        'name' => 'QTI Item Previewer',
                        'module' => 'taoQtiTestPreviewer/previewer/adapter/item/qtiItem',
                        'bundle' => 'taoQtiTestPreviewer/loader/qtiPreviewer.min',
                        'description' => 'QTI implementation of the item previewer',
                        'category' => 'previewer',
                        'active' => true,
                        'tags' => [ 'core', 'qti', 'previewer' ]
                    ]
                )
            );

            $service = $this->getServiceManager()->get(ResultsViewerService::SERVICE_ID);
            $service->setDefaultItemType('qtiItem');
            $this->getServiceManager()->register(ResultsViewerService::SERVICE_ID , $service);

            $this->setVersion('0.1.0');
        }

        $this->skip('0.1.0', '0.1.1');

        if ($this->isVersion('0.1.1')) {
            AclProxy::revokeRule(new AccessRule(
                AccessRule::GRANT,
                'http://www.tao.lu/Ontologies/TAOTest.rdf#TaoQtiManagerRole',
                ['ext' => 'taoQtiTestPreviewer', 'mod' => 'Previewer']
            ));
            AclProxy::revokeRule(new AccessRule(
                AccessRule::GRANT,
                'http://www.tao.lu/Ontologies/TAOTest.rdf#TestsManagerRole',
                ['ext' => 'taoQtiTestPreviewer', 'mod' => 'Previewer']
            ));
            AclProxy::applyRule(new AccessRule(
                AccessRule::GRANT,
                'http://www.tao.lu/Ontologies/TAOTest.rdf#TaoQtiTestPreviewerRole',
                ['ext' => 'taoQtiTestPreviewer', 'mod' => 'Previewer']
            ));
            OntologyUpdater::syncModels();
            $this->setVersion('0.2.0');
        }

        $this->skip('0.2.0', '1.0.0');
    }
}
