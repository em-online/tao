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
    'ui/component',
    'taoTaskQueue/component/listing/element',
    'tpl!taoTaskQueue/component/listing/tpl/list',
    'tpl!taoTaskQueue/component/listing/tpl/elementWrapper',
    'css!taoTaskQueue/component/listing/css/list'
], function ($, _, __, component, listElementFactory, listTpl, elementWrapperTpl) {
    'use strict';

    var _defaults = {
        title : 'Task List',
        emptyText : __('The list is currently empty.')
    };

    var listApi = {
        /**
         * Remove a list element
         * @param {taoTaskQueue/component/listing/element} listElement
         * @returns {taskList}
         */
        removeElement : function removeElement(listElement){
            listElement.destroy();
            this.getElement().find('ul li[data-id="'+listElement.getId()+'"]').remove();
            return this;
        },

        /**
         * Insert a list element
         * @param {taoTaskQueue/component/listing/element} listElement
         * @returns {taskList}
         */
        insertElement : function insertElement(listElement){
            var id = listElement.getId();
            var $li = $(elementWrapperTpl({
                id : id
            }));
            this.getElement().find('ul').prepend($li);
            listElement.render($li);
            return this;
        },

        /**
         * Show the detail
         * @param {taoTaskQueue/component/listing/report} detailElement - the detail element to be shown
         * @param {Booleam} [show] - should the detail of an element be immediately shown or not
         * @returns {taskList}
         */
        setDetail : function setDetail(detailElement, show){
            detailElement.render(this.getElement().find('.view-detail'));
            if(show){
                this.setState('detail-view', true);
            }
            return this;
        },

        /**
         * Hide the detail panel and display the default list view again
         * @returns {taskList}
         */
        hideDetail : function hideDetail(){
            this.setState('detail-view', false);
            return this;
        },

        /**
         * Scroll to the top of the list
         * @returns {taskList}
         */
        scrollToTop : function scrollToTop(){
            this.getElement().find('.task-list').get(0).scrollTo(0, 0);
            return this;
        },

        /**
         * Animate the insertion tset emphasis on it
         * @param listElement
         * @returns {taskList}
         */
        animateInsertion : function animateInsertion(listElement){
            var $listElement = listElement.getElement();
            var $container = $listElement.parent();
            $container.addClass('inserting');
            $listElement.addClass('new-element');
            _.delay(function(){
                $container.removeClass('inserting');
                _.delay(function(){
                    $listElement.removeClass('new-element');
                }, 400);
            },100);
            return this;
        }
    };

    /**
     * Builds a simple task list component
     *
     * @param {Object} config - the component config
     * @returns {taskList} the component
     */
    return function taskListFactory(config) {
        var initConfig = _.defaults(config || {}, _defaults);

        /**
         * The component
         * @typedef {ui/component} taskList
         */
        return component(listApi)
            .setTemplate(listTpl)
            .on('render', function(){
                var self = this;
                this.getElement().find('.clear-all').on('click', function(e){
                    e.preventDefault();
                    self.trigger('clearall');
                });
            })
            .init(initConfig);
    };

});