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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 */



define([
    'lodash',
    'i18n',
    'ui/component',
    'tpl!taoTests/template/finalReview',
], function(_, __, component, finalReviewTpl) {
    'use strict';

    /**
     * Some default values
     * @type {Object}
     * @private
     */
    var _defaults = {
        scope: 'test',
        canCollapse: false,
        preventsUnseen: true,
        hidden: false
    };

    /**
     * Gets an empty stats record
     * @returns {itemStats}
     */
    function getEmptyStats() {
        return {
            questions: 0,
            answered: 0,
            flagged: 0,
            viewed: 0,
            total: 0,
            questionsViewed: 0,
            skipped: 0,
        };
    };
    
    /**
     *
     * @type {Object}
     */
    var finalReviewApi = {

        /**
         * Gets the parts table
         * @param {Object} map - The assessment test map
         * @returns {Object}
         */
        getParts: function getParts(map) {
            return map && map.parts;
        },

        /**
         * Gets a test part by its identifier
         * @param {Object} map - The assessment test map
         * @param {String} partName - The identifier of the test part
         * @returns {Object}
         */
        getPart: function getPart(map, partName) {
            var parts = this.getParts(map);
            return parts && parts[partName];
        },

        
        /**
         * Gets a test section by its identifier
         * @param {Object} map - The assessment test map
         * @param {String} sectionName - The identifier of the test section
         * @returns {Object}
         */
        getSection: function getSection(map, sectionName) {
            var parts = this.getParts(map);
            var section = null;
            _.forEach(parts, function (part) {
                var sections = part.sections;
                if (sections && sections[sectionName]) {
                    section = sections[sectionName];
                    return false;
                }
            });
            return section;
        },

        /**
         * Gets the jump at a particular position
         * @param {Object} map - The assessment test map
         * @param {Number} position - The position of the item
         * @returns {Object}
         */
        getJump: function getJump(map, position) {
            var jumps = this.getJumps(map);
            return jumps && jumps[position];
        },

        /**
         * Gets the jumps table
         * @param {Object} map - The assessment test map
         * @returns {Object}
         */
        getJumps: function getJumps(map) {
            return map && map.jumps;
        },

        /**
         * Gets a test item by its identifier
         * @param {Object} map - The assessment test map
         * @param {String} itemName - The identifier of the test item
         * @returns {Object}
         */
        getItem: function getItem(map, itemName) {
            var jump = _.find(this.getJumps(map), {identifier: itemName});
            return this.getItemAt(map, jump && jump.position);
        },

        /**
         * Gets the item located at a particular position
         * @param {Object} map - The assessment test map
         * @param {Number} position - The position of the item
         * @returns {Object}
         */
        getItemAt: function getItemAt(map, position) {
            var jump = this.getJump(map, position);
            var part = this.getPart(map, jump && jump.part);
            var sections = part && part.sections;
            var section = sections && sections[jump && jump.section];
            var items = section && section.items;
            return items && items[jump && jump.identifier];
        },

        /**
         * Computes the stats for a list of items
         * @param {Object} items
         * @returns {itemStats}
         */
        computeItemStats: function computeItemStats(items) {
            return _.reduce(items, function accStats(acc, item) {
                if (!item.informational) {
                    acc.questions++;

                    if (item.answered) {
                        acc.answered++;
                    }else{
                        acc.skipped++;
                    }

                    if (item.viewed) {
                        acc.questionsViewed++;
                    }
                }
                if (item.flagged) {
                    acc.flagged++;
                }
                if (item.viewed) {
                    acc.viewed++;
                }
                acc.total++;
                return acc;
            }, getEmptyStats());
        },

        /**
         * Computes the global stats of a collection of stats
         * @param {Object} collection
         * @returns {itemStats}
         */
        computeStats: function computeStats(collection) {
            return _.reduce(collection, function accStats(acc, item) {
                acc.questions += item.stats.questions;
                acc.answered += item.stats.answered;
                acc.flagged += item.stats.flagged;
                acc.viewed += item.stats.viewed;
                acc.total += item.stats.total;
                acc.questionsViewed += item.stats.questionsViewed;
                return acc;
            }, getEmptyStats());
        },

        /**
         * Applies a callback on each item of the provided map
         * @param {Object} map - The assessment test map
         * @param {Function} callback(item, section, part, map) - A callback to apply on each item
         * @returns {Object}
         */
        each: function each(map, callback) {
            if (_.isFunction(callback)) {
                _.forEach(map && map.parts, function(part) {
                    _.forEach(part && part.sections, function(section) {
                        _.forEach(section && section.items, function(item) {
                            callback(item, section, part, map);
                        });
                    });
                });
            }
            return map;
        },
        
        /**
         * Gets the scoped map
         * @param {Object} map The current test map
         * @param {Object} context The current test context
         * @returns {object} The scoped map
         */
        getScopedMap: function getScopedMap(map, context) {
            var scopedMap = this.getScopeMapFromContext(map, context, this.config.scope);
            var testPart = this.getPart(scopedMap, context.testPartId) || {};
            var section = this.getSection(scopedMap, context.sectionId) || {};
            var item = this.getItem(scopedMap, context.itemIdentifier) || {};

            // set the active part/section/item
            testPart.active = true;
            section.active = true;
            item.active = true;

            // adjust each item with additional meta
            return this.each(scopedMap, function(itm) {
                var cls = [];
                var icon = '';

                if (itm.active) {
                    cls.push('active');
                }
                if (itm.informational) {
                    cls.push('info');
                    icon = icon || 'info';
                }
                if (itm.flagged) {
                    cls.push('flagged');
                    icon = icon || 'flagged';
                }
                if (itm.answered) {
                    cls.push('answered');
                    icon = icon || 'answered';
                }
                if (itm.viewed) {
                    cls.push('viewed');
                    icon = icon || 'viewed';
                } else {
                    cls.push('unseen');
                    icon = icon || 'unseen';
                }
                if (itm.position==scopedMap.stats.total-1){
                    cls.push('hidden');
                    cls.push('potato');
                }
                itm.cls = cls.join(' ');
                itm.icon = icon;
            });
        },

        /**
         * Gets the map of a particular scope from a current context
         * @param {Object} map - The assessment test map
         * @param {Object} context - The current session context
         * @param {String} [scope] - The name of the scope. Can be: test, part, section (default: test)
         * @returns {object} The scoped map
         */
        getScopeMapFromContext: function getScopeMapFromContext(map, context, scope) {
            // need a clone of the map as we will change some properties
            var scopeMap = _.cloneDeep(map || {});
            var part;
            var section;

            // gets the current part and section
            if (context && context.testPartId) {
                part = this.getPart(scopeMap, context.testPartId);
            }
            if (context && context.sectionId) {
                section = this.getSection(scopeMap, context.sectionId);
            }

            // reduce the map to the scope part
            if (scope && scope !== 'test') {
                scopeMap.parts = {};
                if (part) {
                    scopeMap.parts[context.testPartId] = part;
                }
            }

            // reduce the map to the scope section
            if (part && (scope === 'section' || scope === 'testSection')) {
                part.sections = {};
                if (section) {
                    part.sections[context.sectionId] = section;
                }
            }

            // update the stats to reflect the scope
            if (section) {
                section.stats = this.computeItemStats(section.items);
            }
            if (part) {
                part.stats = this.computeStats(part.sections);
            }
            scopeMap.stats = this.computeStats(scopeMap.parts);

            return scopeMap;
        }
    };

    function finalReviewFactory(config, map, context) {

        var finalReview;

        finalReview = component(finalReviewApi, _defaults)
            .setTemplate(finalReviewTpl)

        // set default filter
        finalReview.currentFilter = 'all';

        // the component will be ready
        return finalReview.init(config);
    };

    return finalReviewFactory;
});
