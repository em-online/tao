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
    'taoTaskQueue/component/listing/element'
], function($, _, taskElementFactory) {
    'use strict';

    QUnit.module('API');

    QUnit.test('module', function(assert) {
        QUnit.expect(3);

        assert.equal(typeof taskElementFactory, 'function', "The taskElementFactory module exposes a function");
        assert.equal(typeof taskElementFactory(), 'object', "The taskElementFactory produces an object");
        assert.notStrictEqual(taskElementFactory(), taskElementFactory(), "The taskElementFactory provides a different object on each call");
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
        var instance = taskElementFactory();
        assert.equal(typeof instance[data.title], 'function', 'The element exposes the component method "' + data.title);
    });

    QUnit.cases([
        { title : 'on' },
        { title : 'off' },
        { title : 'trigger' },
        { title : 'before' },
        { title : 'after' },
    ]).test('Eventifier API ', function(data, assert) {
        var instance = taskElementFactory();
        assert.equal(typeof instance[data.title], 'function', 'The element exposes the eventifier method "' + data.title);
    });

    QUnit.cases([
        { title : 'getId' },
        { title : 'getStatus' },
        { title : 'getData' },
        { title : 'update' },
        { title : 'highlight' },
        { title : 'setStatus' },
        { title : 'getData' },
    ]).test('Instance API ', function(data, assert) {
        var instance = taskElementFactory();
        assert.equal(typeof instance[data.title], 'function', 'The element exposes the method "' + data.title);
    });

    QUnit.asyncTest('getId and getData', function(assert) {
        var $container = $('#qunit-fixture');
        var data = {
            id: 'rdf#i1508337970199318643',
            taskName: 'Task Name',
            taskLabel: 'Task label',
            status: 'created',
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

        taskElementFactory({}, data)
            .on('render', function(){
                assert.deepEqual(this.getData(), data, 'get data correct');
                assert.equal(this.getId(), data.id, 'get id correct');
                assert.equal(this.getStatus(), data.status, 'get status correct');
                QUnit.start();
            })
            .render($container);
    });

    QUnit.module('Behavior');

    QUnit.asyncTest('rendering and update', function(assert) {
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

        QUnit.expect(7);

        taskElementFactory({}, data)
            .on('render', function(){
                var $component = this.getElement();
                assert.ok(true);

                assert.equal($component.find('.label').text(), 'Task label', 'the task label is correct');
                assert.equal($component.find('.time').text(), 'Started 10 minutes ago', 'the task label is correct');
                assert.ok($component.find('.shape > span').hasClass('icon-import'), 'icon correct');

                assert.ok(!$component.find('[data-role="download"]').is(':visible'));
                assert.ok(!$component.find('[data-role="report"]').is(':visible'));
                assert.ok(!$component.find('[data-role="remove"]').is(':visible'));

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('render unknown category', function(assert) {
        var $container = $('#qunit-fixture');
        var data = {
            id: 'rdf#i1508337970199318643',
            taskName: 'Task Name',
            taskLabel: 'Task label',
            status: 'created',
            owner: 'userId',
            createdAt: '1512120107',
            updatedAt: '1512121107',
            createdAtElapsed : 601,
            updatedAtElapsed :26,
            hasFile: true,
            category: 'unknown',
            report : null
        };

        QUnit.expect(23);

        taskElementFactory({}, data)
            .on('render', function(){
                var $component = this.getElement();
                assert.ok(true);

                assert.equal($component.find('.label').text(), 'Task label', 'the task label is correct');
                assert.equal($component.find('.time').text(), 'Started 10 minutes ago', 'the task time is correct');
                assert.ok($component.find('.shape > span').hasClass('icon-property-advanced'), 'unknown icon correct');

                assert.ok(!$component.find('[data-role="download"]').is(':visible'));
                assert.ok(!$component.find('[data-role="report"]').is(':visible'));
                assert.ok(!$component.find('[data-role="remove"]').is(':visible'));

                this.update({
                    status : 'in_progress'
                });

                assert.equal($component.find('.label').text(), 'Task label', 'the task label is correct');
                assert.equal($component.find('.time').text(), 'Started 10 minutes ago', 'the task time is correct');
                assert.ok($component.find('.shape > span').hasClass('icon-property-advanced'), 'unknown icon correct');

                assert.ok(!$component.find('[data-role="download"]').is(':visible'));
                assert.ok(!$component.find('[data-role="report"]').is(':visible'));
                assert.ok(!$component.find('[data-role="remove"]').is(':visible'));

                this.update({
                    status : 'failed'
                });

                assert.ok($component.find('[data-role="download"]').is(':visible'));
                assert.ok($component.find('[data-role="report"]').is(':visible'));
                assert.ok($component.find('[data-role="remove"]').is(':visible'));
                assert.equal($component.find('.time').text(), 'Failed a few seconds ago', 'the task label is correct');
                assert.ok($component.find('.shape > span').hasClass('icon-result-nok'), 'icon correct');

                this.update({
                    status : 'completed'
                });

                assert.ok($component.find('[data-role="download"]').is(':visible'));
                assert.ok($component.find('[data-role="report"]').is(':visible'));
                assert.ok($component.find('[data-role="remove"]').is(':visible'));
                assert.equal($component.find('.time').text(), 'Completed a few seconds ago', 'the task label is correct');
                assert.ok($component.find('.shape > span').hasClass('icon-result-ok'), 'icon correct');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('Events', function(assert) {
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
            report : null
        };

        QUnit.expect(3);

        taskElementFactory({}, data)
            .on('download', function(){
                assert.ok(true, 'download requested');
            })
            .on('report', function(){
                assert.ok(true, 'report requested');
            })
            .on('report', function(){
                assert.ok(true, 'remove requested');
            })
            .on('render', function(){
                var $component = this.getElement();
                $component.find('[data-role="download"]').click();
                $component.find('[data-role="report"]').click();
                $component.find('[data-role="remove"]').click();

                QUnit.start();
            })
            .render($container);
    });

    QUnit.module('Visual');

    QUnit.asyncTest('visual test', function(assert) {
        var $container = $('#visual');
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

        taskElementFactory({}, data)
            .on('render', function(){
                assert.ok(true);
                QUnit.start();
            })
            .render($container);
    });

});
