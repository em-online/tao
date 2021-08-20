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
use oat\taoQtiTestPreviewer\scripts\update\Updater;
use oat\taoQtiTestPreviewer\scripts\install\RegisterPreviewers;

return [
    'name' => 'taoQtiTestPreviewer',
    'label' => 'extension-tao-testqti-previewer',
    'description' => 'extension that provides QTI test previewer',
    'license'     => 'GPL-2.0',
    'version' => '1.0.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => [
        'tao'          => '>=21.0.0',
        'taoTests'     => '>=8.0.0',
        'taoItems'     => '>=6.0.0',
        'taoQtiTest'   => '>=29.0.0',
        'taoOutcomeUi' => '>=6.0.0'
    ],
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOTest.rdf#TaoQtiTestPreviewerRole',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/TAOTest.rdf#TaoQtiTestPreviewerRole', ['ext' => 'taoQtiTestPreviewer']],
    ],
    'install' => [
        'php' => [
            RegisterPreviewers::class
        ],
        'rdf' => [
            __DIR__ . '/install/ontology/previewerRole.rdf',
        ],
    ],
    'update' => Updater::class,
    'uninstall' => [
    ],
    'routes' => [
        '/taoQtiTestPreviewer' => 'oat\\taoQtiTestPreviewer\\controller'
    ],
    'constants' => [
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL . 'taoQtiTestPreviewer/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];
