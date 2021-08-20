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
    'taoTaskQueue/component/manager/manager'
], function($, _, taskQueueManagerFactory) {
    'use strict';

    var _sampleReport = {
        "type": "warning",
        "message": "<em>Data not imported. All records are <strong>invalid.</strong></em>",
        "data": null,
        "children": [{
            "type": "error",
            "message": "Row 1 Student Number Identifier: Duplicated student \"92001\"",
            "data": null,
            "children": [{
                "type": "error",
                "message": "This is but a sub-report Z",
                "data": null,
                "children": []
            }]
        }, {
            "type": "success",
            "message": "Row 2 Student Number Identifier OK",
            "data": null,
            "children": [{
                "type": "success",
                "message": "This is but a sub-report A",
                "data": null,
                "children": []
            }, {
                "type": "info",
                "message": "This is but a sub-report B",
                "data": null,
                "children": []
            }]
        },{
            "type": "error",
            "message": "Row 1 Student Number Identifier: Duplicated student \"92001\"",
            "data": null,
            "children": [{
                "type": "error",
                "message": "This is but a sub-report Z",
                "data": null,
                "children": []
            }]
        }, {
            "type": "success",
            "message": "Row 2 Student Number Identifier OK",
            "data": null,
            "children": [{
                "type": "success",
                "message": "This is but a sub-report A",
                "data": null,
                "children": []
            }, {
                "type": "info",
                "message": "This is but a sub-report B",
                "data": null,
                "children": []
            }]
        },{
            "type": "error",
            "message": "Row 1 Student Number Identifier: Duplicated student \"92001\"",
            "data": null,
            "children": [{
                "type": "error",
                "message": "This is but a sub-report Z",
                "data": null,
                "children": []
            }]
        }, {
            "type": "success",
            "message": "Row 2 Student Number Identifier OK",
            "data": null,
            "children": [{
                "type": "success",
                "message": "This is but a sub-report A",
                "data": null,
                "children": []
            }, {
                "type": "info",
                "message": "This is but a sub-report B",
                "data": null,
                "children": []
            }]
        }]
    };
    var _sampleLogCollection = [
        {
            id: 'rdf#i1508337970199318643',
            taskName: 'Task Name',
            taskLabel: 'Task label',
            status: 'completed',
            owner: 'userId',
            createdAt: '1512120107',
            updatedAt: '1512121107',
            createdAtElapsed : 601,
            updatedAtElapsed :26,
            hasFile: false,
            category: 'import',
            redirectUrl: 'http://tao.local/taoBackOffice/Redirector/redirectTaskToInstance?taskId=http%3A%2F%2Ftao%2Ftao.rdf%23i15366738451441107',
            report : {
                type : 'success',
                message : 'completed task rdf#i1508337970199318643',
                data : null,
                children: []
            }
        },
        {
            id: 'rdf#i15083379701993186432222',
            taskName: 'Task Name 2',
            taskLabel: 'Task label 2',
            status: 'in_progress',
            owner: 'userId',
            createdAt: '1512122107',
            updatedAt: '1512123107',
            createdAtElapsed : 41,
            updatedAtElapsed :626,
            hasFile: false,
            category: 'publish',
            report : {
                type : 'info',
                message : 'running task rdf#i15083379701993186432222',
                data : null,
                children: []
            }
        },
        {
            id: 'rdf#i1508337970190342',
            taskName: 'Task Name 3',
            taskLabel: 'Task label 3',
            status: 'failed',
            owner: 'userId',
            createdAt: '1512124107',
            updatedAt: '1512125107',
            createdAtElapsed : 61,
            updatedAtElapsed :101,
            hasFile: true,
            category: 'export',
            report : {
                type : 'error',
                message : 'running task rdf#i1508337970190342',
                data : null,
                children: [_sampleReport]
            }
        }
    ];

    QUnit.module('API');

    QUnit.test('module', function(assert) {
        QUnit.expect(3);

        assert.equal(typeof taskQueueManagerFactory, 'function', "The taskQueueManagerFactory module exposes a function");
        assert.equal(typeof taskQueueManagerFactory(), 'object', "The taskQueueManagerFactory produces an object");
        assert.notStrictEqual(taskQueueManagerFactory(), taskQueueManagerFactory(), "The taskQueueManagerFactory provides a different object on each call");
    });

    QUnit.cases([
        { title : 'init' },
        { title : 'destroy' },
        { title : 'render' },
        { title : 'show' },
        { title : 'hide' },
        { title : 'enable' },
        { title : 'disable' },
        { title : 'is' },
        { title : 'setState' },
        { title : 'getContainer' },
        { title : 'getElement' },
        { title : 'getTemplate' },
        { title : 'setTemplate' },
    ]).test('Component API ', function(data, assert) {
        var instance = taskQueueManagerFactory();
        assert.equal(typeof instance[data.title], 'function', 'The taskQueueManager exposes the component method "' + data.title);
    });

    QUnit.cases([
        { title : 'on' },
        { title : 'off' },
        { title : 'trigger' },
        { title : 'before' },
        { title : 'after' },
    ]).test('Eventifier API ', function(data, assert) {
        var instance = taskQueueManagerFactory();
        assert.equal(typeof instance[data.title], 'function', 'The taskQueueManager exposes the eventifier method "' + data.title);
    });

    QUnit.cases([
        { title : 'getTaskElements' },
        { title : 'showDetail' },
        { title : 'addNewTask' },
        { title : 'selfUpdateBadge' },
        { title : 'loadData' },
        { title : 'pulse' },
        { title : 'showList' },
        { title : 'hideList' },
    ]).test('Instance API ', function(data, assert) {
        var instance = taskQueueManagerFactory();
        assert.equal(typeof instance[data.title], 'function', 'The taskQueueManager exposes the method "' + data.title);
    });

    QUnit.module('Behavior');

    QUnit.asyncTest('rendering', function(assert) {

        var $container = $('#qunit-fixture');

        taskQueueManagerFactory({}, _sampleLogCollection)
            .on('render', function(){

                assert.equal(this.getElement().get(0), $container.find('.task-manager-container').get(0), 'component container found');

                assert.equal(this.getElement().find('.badge-component').length, 1, 'badge component found');
                assert.ok(this.getElement().find('.badge-component .loader').is(':visible'), 'the loader is on');
                assert.ok(this.getElement().find('.badge-component .badge').hasClass('badge-warning'), 'the badge is displaying a warning');
                assert.equal(this.getElement().find('.badge-component .badge').text(), '3', 'the badge value is correct');

                assert.equal(this.getElement().find('.task-listing').length, 1, 'list component found');
                assert.ok(!this.getElement().find('.task-listing').is(':visible'), 'list starts hidden');
                assert.equal(this.getElement().find('.task-listing .task-list li').length, 3, 'list has 3 elements');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('show list & report', function(assert) {

        var $container = $('#qunit-fixture');

        taskQueueManagerFactory({}, _sampleLogCollection)
            .on('render', function(){
                this.getElement().click();
            })
            .on('listshow', function(){

                assert.ok(true, 'list shown');

                this.showDetail(_sampleLogCollection[2]);

                assert.ok(!this.getElement().find('.task-listing .task-list').is(':visible'), 'the list is hidden');
                assert.ok(this.getElement().find('.task-listing .view-detail').is(':visible'), 'the detail is displayed');

                //hide the list
                this.getElement().click();
            })
            .on('listhide', function(){

                assert.ok(true, 'list hidden');
                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('show / hide list', function(assert) {

        var $container = $('#qunit-fixture');

        QUnit.expect(5);

        taskQueueManagerFactory({}, _sampleLogCollection)
            .on('render', function(){
                assert.ok(!this.getElement().find('.task-listing .task-list').is(':visible'), 'the list is hidden by default');

                this.showList();
            })
            .on('listshow', function(){
                assert.ok(true, 'listshown event has been triggered');
                assert.ok(this.getElement().find('.task-listing .task-list').is(':visible'), 'the list is visible');

                this.hideList();
            })
            .on('listhide', function(){
                assert.ok(true, 'listhidden event has been triggered');
                assert.ok(!this.getElement().find('.task-listing .task-list').is(':visible'), 'the list has been hidden');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('removeAllFinished', function(assert) {

        var $container = $('#qunit-fixture');

        taskQueueManagerFactory({}, _sampleLogCollection)
            .on('render', function(){

                assert.equal(this.getElement().get(0), $container.find('.task-manager-container').get(0), 'component container found');

                assert.equal(this.getElement().find('.badge-component').length, 1, 'badge component found');
                assert.ok(this.getElement().find('.badge-component .loader').is(':visible'), 'the loader is on');
                assert.ok(this.getElement().find('.badge-component .badge').hasClass('badge-warning'), 'the badge is displaying a warning');
                assert.equal(this.getElement().find('.badge-component .badge').text(), '3', 'the badge value is correct');

                assert.equal(this.getElement().find('.task-listing').length, 1, 'list component found');
                assert.ok(!this.getElement().find('.task-listing').is(':visible'), 'list starts hidden');
                assert.equal(this.getElement().find('.task-listing .task-list li').length, 3, 'list has 3 elements');

                this.removeAllFinished();

                assert.ok(this.getElement().find('.badge-component .badge').hasClass('badge-info'), 'the badge is displaying a info');
                assert.equal(this.getElement().find('.badge-component .badge').text(), '1', 'the badge value is correct');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('clearAll', function(assert) {

        var $container = $('#qunit-fixture');

        taskQueueManagerFactory({}, _sampleLogCollection)
            .on('render', function(){

                assert.equal(this.getElement().get(0), $container.find('.task-manager-container').get(0), 'component container found');

                assert.equal(this.getElement().find('.badge-component').length, 1, 'badge component found');
                assert.ok(this.getElement().find('.badge-component .loader').is(':visible'), 'the loader is on');
                assert.ok(this.getElement().find('.badge-component .badge').hasClass('badge-warning'), 'the badge is displaying a warning');
                assert.equal(this.getElement().find('.badge-component .badge').text(), '3', 'the badge value is correct');

                assert.equal(this.getElement().find('.task-listing').length, 1, 'list component found');
                assert.ok(!this.getElement().find('.task-listing').is(':visible'), 'list starts hidden');
                assert.equal(this.getElement().find('.task-listing .task-list li').length, 3, 'list has 3 elements');

                assert.equal(this.getElement().find('.clear-all').length, 1, 'clear all button found');

                //trigger cleanup
                this.getElement().find('.clear-all').click();

                assert.ok(this.getElement().find('.badge-component .badge').hasClass('badge-info'), 'the badge is displaying a info');
                assert.equal(this.getElement().find('.badge-component .badge').text(), '1', 'the badge value is correct');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.module('Visual');

    QUnit.asyncTest('playground', function(assert) {

        var taskManager;
        var $container = $('#visual');
        var $controls = $container.find('#controls');
        var $list = $controls.find('ul');

        var getRandomValue = function getRandomValue(arr){
            return arr[Math.floor(Math.random()*arr.length)];
        };

        var updateTestTask = function updateTestTask(id, status){
            var elements = taskManager.getTaskElements();
            if(id && elements[id]){
                elements[id].update({
                    status: status,
                    updatedAt : Math.floor(Date.now() / 1000),
                    updatedAtElapsed : 0
                });
            }
            taskManager.trigger('listchange');
        };

        var updateTaskList = function updateTaskList(){
            $list.empty();
            _.forEach(taskManager.getTaskElements(), function(task){
                if(task.getStatus() === 'in_progress'){
                    $list.append('<li data-id="'+task.getId()+'"><span class="name">'+task.getData().taskLabel+'</span><a class="task-complete">complete it</a><a class="task-fail">fail it</a> </li>');
                }
            });
        };

        var createTask = function createTask(){
            var timestamp = Math.floor(Date.now() / 1000);
            var type = getRandomValue(['import', 'export', 'publish', 'transfer', 'create', 'update', 'delete']);

            return {
                id: 'rdf#i'+(Math.floor(Math.random() * 123456789) +  123456789) ,
                taskName: 'php/class/for/task/'+type,
                taskLabel: 'Async ' +  type + ' ' + (Math.floor(Math.random() * 99) +  1),
                status: 'in_progress',
                owner: 'userId',
                createdAt: timestamp,
                updatedAt: timestamp,
                updatedAtElapsed : 0,
                createdAtElapsed : 0,
                file: getRandomValue([true, false]),
                category: type,
                report : null
            };
        };

        $list.on('click', '.task-complete', function(){
            var id = $(this).closest('li').data('id');
            updateTestTask(id, 'completed');
            updateTaskList();

        }).on('click', '.task-fail', function(){
            var id = $(this).closest('li').data('id');
            updateTestTask(id, 'failed');
            updateTaskList();
        });

        taskManager = taskQueueManagerFactory({}, _sampleLogCollection)
            .on('report', function(){
                //fetch report
                this.showDetail(_sampleLogCollection[2]);
            })
            .on('render', function(){
                var self = this;
                assert.ok(true);

                $controls.find('.add-task').click(function(){
                    self.addNewTask(createTask(), true);
                    updateTaskList();
                });

                QUnit.start();
            })
            .render($container);

        updateTaskList();
    });
});
