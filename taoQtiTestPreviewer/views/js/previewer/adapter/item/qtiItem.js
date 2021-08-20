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
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'lodash',
    'core/logger',
    'taoQtiTestPreviewer/previewer/runner',
    'css!taoQtiTestPreviewer/previewer/provider/item/css/item'
], function (_, loggerFactory, previewerFactory) {
    'use strict';

    var logger = loggerFactory('taoQtiTest/previewer');

    /**
     * List of default providers.
     * @type {Object[]}
     */
    var defaultProviders = [{
        id: 'qtiItemPreviewer',
        module: 'taoQtiTestPreviewer/previewer/provider/item/item',
        bundle: 'taoQtiTestPreviewer/loader/qtiPreviewer.min',
        category: 'previewer'
    }];

    /**
     * List of required plugins that should be loaded in order to make the previewer work properly
     * @type {Object[]}
     */
    var defaultPlugins = [{
        module: 'taoQtiTestPreviewer/previewer/plugins/controls/close',
        bundle: 'taoQtiTestPreviewer/loader/qtiPreviewer.min',
        category: 'controls'
    }, {
        module: 'taoQtiTestPreviewer/previewer/plugins/navigation/submit/submit',
        bundle: 'taoQtiTestPreviewer/loader/qtiPreviewer.min',
        category: 'navigation'
    }, {
        module: 'taoQtiTest/runner/plugins/tools/itemThemeSwitcher/itemThemeSwitcher',
        bundle: 'taoQtiTest/loader/testPlugins.min',
        category: 'tools'
    }];

    /**
     * Wraps the legacy item previewer in order to be loaded by the taoItems previewer factory
     */
    return {
        name: 'qtiItem',

        /**
         * Builds and shows the legacy item previewer
         *
         * @param {String} uri - The URI of the item to load
         * @param {Object} state - The state of the item
         * @param {Object} [config] - Some config entries
         * @param {String} [config.serviceCallId='previewer'] - The service call Id to send to the server
         * @param {String} [config.fullPage] - Force the previewer to occupy the full window.
         * @param {String} [config.readOnly] - Do not allow to modify the previewed item.
         * @returns {Object}
         */
        init: function init(uri, state, config) {
            config = _.defaults(config || {}, {
                provider: 'qtiItemPreviewer',
                serviceCallId: 'previewer'
            });

            // ensure required providers and plugins will be loaded
            config.providers = defaultProviders.concat(config.providers || []);
            config.plugins = defaultPlugins.concat(config.plugins || []);

            return previewerFactory(config)
                .on('error', function (err) {
                    logger.error(err);
                })
                .on('ready', function (runner) {
                    runner
                        .on('renderitem', function () {
                            if (state) {
                                runner.itemRunner.setState(state);
                            }
                        })
                        .loadItem(uri);
                });
        }
    };
});
