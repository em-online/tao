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
    'ui/component',
    'taoTaskQueue/component/listing/list'
], function($, _, componentFactory, taskListFactory) {
    'use strict';

    QUnit.module('API');

    QUnit.test('module', function(assert) {
        QUnit.expect(3);

        assert.equal(typeof taskListFactory, 'function', "The taskListFactory module exposes a function");
        assert.equal(typeof taskListFactory(), 'object', "The taskListFactory produces an object");
        assert.notStrictEqual(taskListFactory(), taskListFactory(), "The taskListFactory provides a different object on each call");
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
        var instance = taskListFactory();
        assert.equal(typeof instance[data.title], 'function', 'The list exposes the component method "' + data.title);
    });

    QUnit.cases([
        { title : 'on' },
        { title : 'off' },
        { title : 'trigger' },
        { title : 'before' },
        { title : 'after' },
    ]).test('Eventifier API ', function(data, assert) {
        var instance = taskListFactory();
        assert.equal(typeof instance[data.title], 'function', 'The list exposes the eventifier method "' + data.title);
    });

    QUnit.cases([
        { title : 'removeElement' },
        { title : 'insertElement' },
        { title : 'setDetail' },
        { title : 'hideDetail' },
        { title : 'scrollToTop' },
        { title : 'animateInsertion' },
    ]).test('Instance API ', function(data, assert) {
        var instance = taskListFactory();
        assert.equal(typeof instance[data.title], 'function', 'The list exposes the method "' + data.title);
    });

    QUnit.module('Methods');

    QUnit.asyncTest('insert and remove', function(assert) {
        var $container = $('#qunit-fixture');
        taskListFactory()
            .on('render', function(){
                var dummyListFactory = function dummyListFactory(){
                    var id;
                    if(!dummyListFactory.idCounter){
                        dummyListFactory.idCounter = 0;
                    }
                    dummyListFactory.idCounter++;
                    id = dummyListFactory.idCounter;

                    return componentFactory({
                        getId : function(){
                            return id;
                        }
                    }).setTemplate(function(){
                        return '<div class="dummy-element">DUMMY</div>';
                    }).init();
                };

                var first = dummyListFactory();
                this.insertElement(first);
                this.insertElement(dummyListFactory());
                this.insertElement(dummyListFactory());
                this.insertElement(dummyListFactory());

                assert.ok(this.getElement().find('.task-list').is(':visible'), 'list visible');
                assert.equal(this.getElement().find('.task-list li').length, 4, 'has four element rendered');
                assert.equal(this.getElement().find('.task-list li[data-id=1]').length, 1, 'found the element');
                assert.equal(this.getElement().find('.task-list li[data-id=2]').length, 1, 'found the element');
                assert.equal(this.getElement().find('.task-list li[data-id=3]').length, 1, 'found the element');
                assert.equal(this.getElement().find('.task-list li[data-id=4]').length, 1, 'found the element');

                this.removeElement(first);

                assert.equal(this.getElement().find('.task-list li').length, 3, 'has four element rendered');
                assert.equal(this.getElement().find('.task-list li[data-id=1]').length, 0, 'first element is gone');
                assert.equal(this.getElement().find('.task-list li[data-id=2]').length, 1, 'found the element');
                assert.equal(this.getElement().find('.task-list li[data-id=3]').length, 1, 'found the element');
                assert.equal(this.getElement().find('.task-list li[data-id=4]').length, 1, 'found the element');

                QUnit.start();
            })
            .render($container);
    });

    QUnit.asyncTest('set and hide details', function(assert) {
        var $container = $('#qunit-fixture');
        taskListFactory()
            .on('render', function(){
                var dummyListFactory = function dummyListFactory(){
                    var id;
                    if(!dummyListFactory.idCounter){
                        dummyListFactory.idCounter = 0;
                    }
                    dummyListFactory.idCounter++;
                    id = dummyListFactory.idCounter;

                    return componentFactory({
                        getId : function(){
                            return id;
                        }
                    }).setTemplate(function(){
                        return '<div class="dummy-element">DUMMY</div>';
                    }).init();
                };

                assert.ok(this.getElement().find('.task-list').is(':visible'), 'list visible');

                this.setDetail(dummyListFactory(), true);
                assert.ok(this.getElement().find('.view-detail').is(':visible'), 'viewing detail');
                assert.ok(!this.getElement().find('.task-list').is(':visible'), 'list hidden');

                this.hideDetail();
                assert.ok(!this.getElement().find('.view-detail').is(':visible'), 'detail hidden');
                assert.ok(this.getElement().find('.task-list').is(':visible'), 'list shown');

                QUnit.start();
            })
            .render($container);
    });

});
