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
    'taoTaskQueue/component/listing/report'
], function($, _, taskReportFactory) {
    'use strict';

    QUnit.module('API');

    QUnit.test('module', function(assert) {
        QUnit.expect(3);

        assert.equal(typeof taskReportFactory, 'function', "The taskReportFactory module exposes a function");
        assert.equal(typeof taskReportFactory(), 'object', "The taskReportFactory produces an object");
        assert.notStrictEqual(taskReportFactory(), taskReportFactory(), "The taskReportFactory provides a different object on each call");
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
        var instance = taskReportFactory();
        assert.equal(typeof instance[data.title], 'function', 'The report exposes the component method "' + data.title);
    });

    QUnit.cases([
        { title : 'on' },
        { title : 'off' },
        { title : 'trigger' },
        { title : 'before' },
        { title : 'after' },
    ]).test('Eventifier API ', function(data, assert) {
        var instance = taskReportFactory();
        assert.equal(typeof instance[data.title], 'function', 'The report exposes the eventifier method "' + data.title);
    });

    QUnit.cases([
        { title : 'update' },
    ]).test('Instance API ', function(data, assert) {
        var instance = taskReportFactory();
        assert.equal(typeof instance[data.title], 'function', 'The report exposes the method "' + data.title);
    });

    QUnit.module('Rendering');

    QUnit.asyncTest('without report', function(assert) {
        var $container = $('#qunit-fixture');
        var data = {
            id: 'rdf#i1508337970199318643',
            taskName: 'Task Name',
            taskLabel: 'Task label',
            status: 'in_progress',
            owner: 'userId',
            createdAt: '1512120107',
            updatedAt: '1512121107',
            createdAtElapsed : 601,
            updatedAtElapsed :26,
            hasFile: true,
            category: 'import',
            report : {
                type : 'success',
                message : 'completed task rdf#i1508337970199318643',
                data : null,
                children: []
            }
        };

        QUnit.expect(3);

        taskReportFactory({}, data)
            .on('render', function(){
                assert.equal(this.getElement().get(0), $container.find('.task-detail-element').get(0), 'component properly rendered');
                assert.equal(this.getElement().find('.detail-description .label').text(), data.taskLabel,'the title is correct');
                assert.ok(this.getElement().find('.no-detail').is(':visible'), 'the no-detail message is displayed');
                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('with report', function(assert) {
        var $container = $('#qunit-fixture');

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
        var data = {
            id: 'rdf#i1508337970190342',
            taskName: 'Task Name 2',
            taskLabel: 'Task label 2',
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
        };

        QUnit.expect(4);

        taskReportFactory({}, data)
            .on('render', function(){

                assert.equal(this.getElement().get(0), $container.find('.task-detail-element').get(0), 'component properly rendered');
                assert.ok(!this.getElement().find('.no-detail').is(':visible'), 'the no-detail message is hidden');
                assert.equal(this.getElement().find('.detail-description .label').text(), data.taskLabel,'the title is correct');
                assert.equal(this.getElement().find('.detail-body .component-report').length, 1, 'report generated');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('event', function(assert) {
        var $container = $('#qunit-fixture');
        var data = {
            id: 'rdf#i1508337970199318643',
            taskName: 'Task Name',
            taskLabel: 'Task label',
            status: 'in_progress',
            owner: 'userId',
            createdAt: '1512120107',
            updatedAt: '1512121107',
            createdAtElapsed : 601,
            updatedAtElapsed :26,
            hasFile: true,
            category: 'import',
            report : {
                type : 'success',
                message : 'completed task rdf#i1508337970199318643',
                data : null,
                children: []
            }
        };

        QUnit.expect(2);

        taskReportFactory({}, data)
            .on('close', function(){
                assert.ok(true, 'request close');
                QUnit.start();
            })
            .on('render', function(){
                assert.ok(this.getElement().find('[data-role="close"]').length, 'close button found');
                this.getElement().find('[data-role="close"]').click();
            })
            .render($container);
    });

    QUnit.module('Visual');

    QUnit.asyncTest('visual test', function(assert) {
        var $container = $('#visual');

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
        var data = {
            id: 'rdf#i1508337970190342',
            taskName: 'Task Name 2',
            taskLabel: 'Task label 2',
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
        };

        taskReportFactory({}, data)
            .on('render', function(){
                assert.ok(true, 'rendered');
                QUnit.start();
            })
            .render($container);
    });

});