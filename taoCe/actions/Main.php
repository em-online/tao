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
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoCe\actions;

use oat\tao\helpers\TaoCe;
use oat\tao\model\mvc\DefaultUrlService;

/**
 * Just to override the paths and load the specific client side code
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 * @package taoCe

 * @license GPL-2.0
 *
 */
class Main extends \tao_actions_Main
{

    /**
     * Wrapper to the main action: update the first time property and redirect
     * @return void
     */
    public function index()
    {
        $this->defaultData();
        //redirect to the usual tao/Main/index
        if($this->hasRequestParameter('ext') || $this->hasRequestParameter('structure')){

            //but before update the first time property

            $user = $this->getServiceLocator()->get(\tao_models_classes_UserService::SERVICE_ID)->getCurrentUser();
            if($this->hasRequestParameter('nosplash')){
                TaoCe::becomeVeteran();
            }

            //@todo use forward on cross-extension forward is supported
            $this->redirect(_url('index', 'Main', 'tao', array(
                'ext' => $this->getRequestParameter('ext'),
                'structure' => $this->getRequestParameter('structure')
            )));

        } else {
            //render the index but with the taoCe URL used by client side routes
            parent::index();
        }
    }

    /**
     * Action used to redirect request made to root of tao.
     */
    public function rootEntry()
    {
        $this->defaultData();
        if (\common_session_SessionManager::isAnonymous()) {
            /* @var $urlRouteService DefaultUrlService */
            $urlRouteService = $this->getServiceLocator()->get(DefaultUrlService::SERVICE_ID);
            $this->redirect($urlRouteService->getLoginUrl());
        } else {
            $this->redirect(_url('entry', 'Main', 'tao'));
        }
    }
}
