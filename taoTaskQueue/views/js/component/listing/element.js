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
    'jquery',
    'lodash',
    'i18n',
    'moment',
    'ui/component',
    'ui/hider',
    'tpl!taoTaskQueue/component/listing/tpl/element',
    'css!taoTaskQueue/component/listing/css/element'
], function ($, _, __, moment, component, hider, elementTpl) {
    'use strict';

    var _defaults = {
    };

    var _allowedStatus = ['in_progress', 'failed', 'completed'];

    var _categoryMap = {
        import : 'import',
        export : 'export',
        delivery_comp : 'delivery',
        transfer : 'connect',
        create : 'magicwand',
        update : 'edit',
        delete : 'bin'
    };

    var _statusIcon = {
        in_progress : 'property-advanced',
        completed: 'result-ok',
        failed: 'result-nok',
    };

    /**
     * Get the task name to be displayed to the user
     * @param {Object} data - the standard task object
     * @returns {String}
     */
    var getLabelString = function getLabelString(data){
        return data.taskLabel;
    };

    /**
     * Get the formatted duration string
     * @param {Number} from - the start time in unix timestamp
     * @param {Number} elapsed - the duration in seconds
     * @returns {Number}
     */
    var getFormattedTime = function getFormattedTime(from, elapsed){
        return moment.unix(from).from(moment.unix(parseInt(from, 10)+parseInt(elapsed, 10)));
    };

    /**
     * Get the formatted time string according to the current task data
     * @param data - the standard task object
     * @returns {String}
     */
    var getTimeString = function getTimeString(data){
        switch(data.status){
            case 'created':
            case 'in_progress':
                return __('Started %s', getFormattedTime(data.createdAt, data.createdAtElapsed));
            case 'completed':
                return __('Completed %s', getFormattedTime(data.updatedAt, data.updatedAtElapsed));
            case 'failed':
                return __('Failed %s', getFormattedTime(data.updatedAt, data.updatedAtElapsed));
            default:
                return '';
        }
    };

    /**
     * Get the appropriate icon according to the task data
     * @param {Object} data - the standard task object
     * @returns {string}
     */
    var getIcon = function getIcon(data){
        var icon;
        if(!_.isPlainObject(data)){
            throw new Error('invalid data');
        }
        if(data.category && _categoryMap[data.category]){
            icon = _categoryMap[data.category];
        }else if(data.status && _statusIcon[data.status]){
            icon = _statusIcon[data.status];
        }else {
            icon = _statusIcon.in_progress;
        }
        return 'icon-'+icon;
    };

    var taskElementApi = {

        /**
         * Get the id of the task element
         * @returns {String}
         */
        getId : function getId(){
            if(this.data && this.data.id){
                return this.data.id;
            }
        },

        /**
         * Get the status of the task element
         * @returns {String}
         */
        getStatus : function getStatus(){
            if(this.data && this.data.status){
                return this.data.status;
            }
        },

        /**
         * Get the data of the task element
         * @returns {Object}
         */
        getData : function getData(){
            return this.data;
        },

        /**
         * Update the data and rendering of it
         * @param data
         * @returns {taskElement}
         */
        update : function update(data){
            var $container = this.getElement();

            _.assign(this.data || {}, data);

            $container.find('.shape > span').removeAttr('class').addClass(getIcon(this.data));
            $container.find('.label').html(getLabelString(this.data));
            $container.find('.time').html(getTimeString(this.data));

            this.setStatus(this.data.status);
            //bonus: check if there is any report and display the report button only when needed

            hider.toggle($container.find('.action-bottom [data-role="download"]'), this.data.hasFile);
            hider.toggle($container.find('.action-bottom [data-role="redirect"]'), !!this.data.redirectUrl);

            this.trigger('update');
            return this;
        },

        /**
         * Add transition to highlight the element (useful after an update for instance)
         * @returns {taskElement}
         */
        highlight : function highlight(){
            var $container = this.getElement();
            $container.addClass('highlight');
            _.delay(function(){
                $container.removeClass('highlight');
            }, 500);
            return this;
        },

        /**
         * Set the status of the task element
         * @param {String} status
         * @returns {taskElement}
         */
        setStatus : function setStatus(status){
            var self = this;
            if(!status){
                throw new Error('status should not be empty');
            }

            if(['created'].indexOf(status) !== -1){
                status = 'in_progress';
            }

            if(_allowedStatus.indexOf(status) === -1){
                throw new Error('unknown status '+status);
            }
            if(!this.is(status)){
                _.forEach(_.without(_allowedStatus, status), function(st){
                    self.setState(st, false);
                });
                this.setState(status, true);
            }
            return this;
        }
    };

    /**
     * Builds a task listing element
     *
     * @param {Object} config - the component config
     * @param {Array} data - the initial task data to be loaded from the server REST api call
     * @returns {taskElement} the component
     *
     * @event remove - Emitted when the element requests to be removed
     * @event download - Emitted when the element requests downloading its associated file
     * @event report - Emitted when a list element requests its related report to be displayed
     * @event update - Emitted when the display update is done
     */
    return function taskElementFactory(config, data) {
        var initConfig = _.defaults(config || {}, _defaults);

        /**
         * The component
         * @typedef {ui/component} taskElement
         */
        return component(taskElementApi)
            .setTemplate(elementTpl)
            .on('init', function(){
                this.data = data || {};
            })
            .on('render', function() {

                var self = this;
                var $component = this.getElement();

                this.update(data);

                $component.find('[data-role="download"]').click(function(){
                    self.trigger('download');
                });
                $component.find('[data-role="remove"]').click(function(){
                    self.trigger('remove');
                });
                $component.find('[data-role="report"]').click(function(){
                    self.trigger('report');
                });
                $component.find('[data-role="redirect"]').click(function(){
                    self.trigger('redirect');
                });

            })
            .init(initConfig);
    };

});