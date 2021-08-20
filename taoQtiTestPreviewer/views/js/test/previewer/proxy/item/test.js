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
    'jquery',
    'lodash',
    'util/url',
    'core/communicator',
    'taoTests/runner/proxy',
    'taoQtiTestPreviewer/previewer/proxy/item',
    'lib/jquery.mockjax/jquery.mockjax'
], function($, _, urlUtil, communicatorFactory, proxyFactory, itemProxy) {
    'use strict';

    QUnit.module('itemProxy');


    // prevent the AJAX mocks to pollute the logs
    $.mockjaxSettings.logger = null;
    $.mockjaxSettings.responseTime = 1;

    // restore AJAX method after each test
    QUnit.testDone(function () {
        $.mockjax.clear();
    });


    QUnit.test('module', function(assert) {
        QUnit.expect(6);
        assert.equal(typeof itemProxy, 'object', "The itemProxy module exposes an object");
        assert.equal(typeof proxyFactory, 'function', "The proxyFactory module exposes a function");
        assert.equal(typeof proxyFactory.registerProvider, 'function', "The proxyFactory module exposes a registerProvider method");
        assert.equal(typeof proxyFactory.getProvider, 'function', "The proxyFactory module exposes a getProvider method");

        proxyFactory.registerProvider('itemProxy', itemProxy);

        assert.equal(typeof proxyFactory('itemProxy'), 'object', "The proxyFactory factory has registered the itemProxy definition and produces an instance");
        assert.notStrictEqual(proxyFactory('itemProxy'), proxyFactory('itemProxy'), "The proxyFactory factory provides a different instance of itemProxy on each call");
    });


    QUnit
        .cases([
            { title : 'install' },
            { title : 'init' },
            { title : 'destroy' },
            { title : 'callTestAction' },
            { title : 'getItem' },
            { title : 'submitItem' },
            { title : 'callItemAction' }
        ])
        .test('proxy API ', function(data, assert) {
            QUnit.expect(1);
            assert.equal(typeof itemProxy[data.title], 'function', 'The itemProxy definition exposes a "' + data.title + '" function');
        });


    QUnit
        .cases([{
            title: 'success',
            token: '1234',
            response: {
                token: '4567',
                success: true
            },
            ajaxSuccess: true,
            success: true
        }, {
            title: 'failing data',
            token: '1234',
            response: {
                token: '4567',
                success: false
            },
            ajaxSuccess: true,
            success: false
        }, {
            title: 'failing request',
            token: '1234',
            response: "error",
            ajaxSuccess: false,
            success: false
        }])
        .asyncTest('itemProxy.init ', function(caseData, assert) {
            var initConfig = {
                serviceCallId: 'foo',
                bootstrap: {
                    serviceController: 'MockRunner',
                    serviceExtension: 'MockExtension'
                }
            };

            var expectedUrl = urlUtil.route('init', initConfig.bootstrap.serviceController, initConfig.bootstrap.serviceExtension, {
                serviceCallId: initConfig.serviceCallId
            });

            var proxy, result;

            QUnit.expect('object' !== typeof caseData.response ? 6 : 7);

            proxyFactory.registerProvider('itemProxy', itemProxy);

            $.mockjax({
                url: '/*',
                status: caseData.ajaxSuccess ? 200 : 500,
                responseText: caseData.response,
                response: function(settings) {
                    assert.equal(settings.url, expectedUrl, 'The proxy has called the right service');
                }
            });

            proxy = proxyFactory('itemProxy', initConfig);

            proxy.install();

            proxy.getTokenHandler().setToken(caseData.token);

            proxy.on('init', function(promise, config) {
                assert.ok(true, 'The proxy has fired the "init" event');
                assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "init" event');
                assert.equal(config, initConfig, 'The proxy has provided the config object through the "init" event');
            });

            result = proxy.init();

            assert.equal(typeof result, 'object', 'The proxy.init method has returned a promise');

            result
                .then(function(data) {
                    if (caseData.success) {
                        assert.deepEqual(data, caseData.response, 'The proxy has returned the expected data');
                    } else {
                        assert.ok(false, 'The proxy must throw an error!');
                    }

                    if (data.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), data.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                })
                .catch(function(err) {
                    assert.ok(!caseData.success, 'The proxy has thrown an error! #' + err);

                    if (err.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), err.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                });
        });


    QUnit.asyncTest('itemProxy.destroy', function(assert) {
        var initConfig = {
            serviceCallId: 'foo',
            bootstrap: {
                serviceController: 'MockRunner',
                serviceExtension: 'MockExtension'
            }
        };

        var proxy, result;

        QUnit.expect(5);

        proxyFactory.registerProvider('itemProxy', itemProxy);

        $.mockjax([{
            url: '/init*',
            responseText: {
                success: true
            }
        }, {
            url: '/*',
            status: 500,
            response: function() {
                assert.ok(false, 'The proxy must not use an ajax request to destroy the instance!');
            }
        }]);

        proxy = proxyFactory('itemProxy', initConfig);

        proxy.install();

        proxy.init().then(function () {
            proxy.on('destroy', function(promise) {
                assert.ok(true, 'The proxyFactory has fired the "destroy" event');
                assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "destroy" event');
            });

            result = proxy.destroy();

            assert.equal(typeof result, 'object', 'The proxy.destroy method has returned a promise');

            result
                .then(function() {
                    assert.ok(true, 'The proxy has resolved the promise provided by the "destroy" method!');

                    proxy.getTestContext()
                        .then(function() {
                            assert.ok(false, 'The proxy must be initialized');
                            QUnit.start();
                        })
                        .catch(function() {
                            assert.ok(true, 'The proxy must be initialized');
                            QUnit.start();
                        });
                })
                .catch(function() {
                    assert.ok(false, 'The proxy cannot reject the promise provided by the "destroy" method!');
                    QUnit.start();
                });
        }).catch(function () {
            assert.ok(false, 'The proxy has not been properly initialized!');
            QUnit.start();
        });

    });


    QUnit
        .cases([{
            title: 'success',
            token: '1234',
            action: 'move',
            params: {
                type: 'forward'
            },
            response: {
                token: '4567',
                success: true
            },
            ajaxSuccess: true,
            success: true
        }, {
            title: 'failing data',
            token: '1234',
            action: 'move',
            params: {
                type: 'forward'
            },
            response: {
                token: '4567',
                success: false
            },
            ajaxSuccess: true,
            success: false
        }, {
            title: 'failing request',
            token: '1234',
            action: 'move',
            params: {
                type: 'forward'
            },
            response: "error",
            ajaxSuccess: false,
            success: false
    }]).asyncTest('itemProxy.callTestAction ', function(caseData, assert) {
            var initConfig = {
                serviceCallId: 'foo',
                bootstrap: {
                    serviceController: 'MockRunner',
                    serviceExtension: 'MockExtension'
                }
            };

            var expectedUrl = urlUtil.route(caseData.action, initConfig.bootstrap.serviceController, initConfig.bootstrap.serviceExtension, {
                serviceCallId: initConfig.serviceCallId
            });

            var proxy, result;

            QUnit.expect('object' !== typeof caseData.response ? 8 : 9);

            proxyFactory.registerProvider('itemProxy', itemProxy);

            $.mockjax([{
                url: '/init*',
                responseText: {
                    success: true
                }
            }, {
                url: '/*',
                status: caseData.ajaxSuccess ? 200 : 500,
                responseText: caseData.response,
                response: function(settings) {
                    assert.equal(settings.url, expectedUrl, 'The proxy has called the right service');
                }
            }]);

            proxy = proxyFactory('itemProxy', initConfig);

            proxy.install();

            proxy.getTokenHandler().setToken(caseData.token);

            proxy.callTestAction(caseData.action, caseData.params)
                .then(function() {
                    assert.ok(false, 'The proxy must be initialized');
                })
                .catch(function() {
                    assert.ok(true, 'The proxy must be initialized');
                });

            proxy.init().then(function () {

                proxy.on('callTestAction', function(promise, action, params) {
                    assert.ok(true, 'The proxy has fired the "callTestAction" event');
                    assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "callTestAction" event');
                    assert.equal(action, caseData.action, 'The proxy has provided the action through the "callTestAction" event');
                    assert.deepEqual(params, caseData.params, 'The proxy has provided the params through the "callTestAction" event');
                });

                result = proxy.callTestAction(caseData.action, caseData.params);

                assert.equal(typeof result, 'object', 'The proxy.callTestAction method has returned a promise');

                result.then(function(data) {
                    if (caseData.success) {
                        assert.deepEqual(data, caseData.response, 'The proxy has returned the expected data');
                    } else {
                        assert.ok(false, 'The proxy must throw an error!');
                    }

                    if (data.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), data.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                }).catch(function(err) {
                    assert.ok(!caseData.success, 'The proxy has thrown an error! #' + err);

                    if (err.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), err.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                });
            });
        });


    QUnit
        .cases([{
            title: 'success',
            uri: 'http://tao.dev/mockItemDefinition#123',
            token: '1234',
            response: {
                itemData: {
                    interactions: [{}]
                },
                itemState: {
                    response: [{}]
                },
                token: '4567',
                success: true
            },
            ajaxSuccess: true,
            success: true
        }, {
            title: 'failing data',
            uri: 'http://tao.dev/mockItemDefinition#123',
            token: '1234',
            response: {
                token: '4567',
                success: false
            },
            ajaxSuccess: true,
            success: false
        }, {
            title: 'failing request',
            uri: 'http://tao.dev/mockItemDefinition#123',
            token: '1234',
            response: "error",
            ajaxSuccess: false,
            success: false
        }])
        .asyncTest('itemProxy.getItem ', function(caseData, assert) {
            var initConfig = {
                serviceCallId: 'foo',
                bootstrap: {
                    serviceController: 'MockRunner',
                    serviceExtension: 'MockExtension'
                }
            };

            var expectedUrl = urlUtil.route('getItem', initConfig.bootstrap.serviceController, initConfig.bootstrap.serviceExtension, {
                serviceCallId: initConfig.serviceCallId,
                itemUri : caseData.uri
            });

            var proxy, result;

            QUnit.expect('object' !== typeof caseData.response ? 7 : 8);

            proxyFactory.registerProvider('itemProxy', itemProxy);

            $.mockjax([{
                url: '/init*',
                responseText: {
                    success: true
                }
            }, {
                url: '/*',
                status: caseData.ajaxSuccess ? 200 : 500,
                responseText: caseData.response,
                response: function(settings) {
                    assert.equal(settings.url, expectedUrl, 'The proxy has called the right service');
                }
            }]);

            proxy = proxyFactory('itemProxy', initConfig);

            proxy.install();

            proxy.getTokenHandler().setToken(caseData.token);

            proxy.getItem(caseData.uri)
                .then(function() {
                    assert.ok(false, 'The proxy must be initialized');
                })
                .catch(function() {
                    assert.ok(true, 'The proxy must be initialized');
                });

            proxy.init().then(function () {

                proxy.on('getItem', function(promise, uri) {
                    assert.ok(true, 'The proxy has fired the "getItem" event');
                    assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "getItem" event');
                    assert.equal(uri, caseData.uri, 'The proxy has provided the URI through the "getItem" event');
                });

                result = proxy.getItem(caseData.uri);

                assert.equal(typeof result, 'object', 'The proxy.getItem method has returned a promise');

                result.then(function(data) {
                    if (caseData.success) {
                        assert.deepEqual(data, caseData.response, 'The proxy has returned the expected data');
                    } else {
                        assert.ok(false, 'The proxy must throw an error!');
                    }

                    if (data.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), data.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                }).catch(function(err) {
                    assert.ok(!caseData.success, 'The proxy has thrown an error! #' + err);

                    if (err.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), err.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                });
            });
        });


    QUnit
        .cases([{
            title: 'success',
            uri: 'http://tao.dev/mockItemDefinition#123',
            itemState: {response: [{}]},
            itemResponse: {response: [{}]},
            token: '1234',
            response: {
                token: '4567',
                success: true
            },
            ajaxSuccess: true,
            success: true
        }, {
            title: 'failing data',
            uri: 'http://tao.dev/mockItemDefinition#123',
            itemState: {response: [{}]},
            itemResponse: {response: [{}]},
            token: '1234',
            response: {
                token: '4567',
                success: false
            },
            ajaxSuccess: true,
            success: false
        }, {
            title: 'failing request',
            uri: 'http://tao.dev/mockItemDefinition#123',
            itemState: {response: [{}]},
            itemResponse: {response: [{}]},
            token: '1234',
            response: "error",
            ajaxSuccess: false,
            success: false
        }])
        .asyncTest('itemProxy.submitItem ', function(caseData, assert) {
            var initConfig = {
                serviceCallId: 'foo',
                bootstrap: {
                    serviceController: 'MockRunner',
                    serviceExtension: 'MockExtension'
                }
            };

            var expectedUrl = urlUtil.route('submitItem', initConfig.bootstrap.serviceController, initConfig.bootstrap.serviceExtension, {
                serviceCallId: initConfig.serviceCallId,
                itemUri : caseData.uri
            });

            var proxy, result;

            QUnit.expect('object' !== typeof caseData.response ? 9 : 10);

            proxyFactory.registerProvider('itemProxy', itemProxy);

            $.mockjax([{
                url: '/init*',
                responseText: {
                    success: true
                }
            }, {
                url: '/*',
                status: caseData.ajaxSuccess ? 200 : 500,
                responseText: caseData.response,
                response: function(settings) {
                    assert.equal(settings.url, expectedUrl, 'The proxy has called the right service');
                }
            }]);

            proxy = proxyFactory('itemProxy', initConfig);

            proxy.install();

            proxy.getTokenHandler().setToken(caseData.token);

            proxy.submitItem(caseData.uri, caseData.itemState, caseData.itemResponse)
                .then(function() {
                    assert.ok(false, 'The proxy must be initialized');
                })
                .catch(function() {
                    assert.ok(true, 'The proxy must be initialized');
                });

            proxy.init().then(function () {

                proxy.on('submitItem', function(promise, uri, state, response) {
                    assert.ok(true, 'The proxy has fired the "submitItem" event');
                    assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "submitItem" event');
                    assert.equal(uri, caseData.uri, 'The proxy has provided the URI through the "submitItem" event');
                    assert.deepEqual(state, caseData.itemState, 'The proxy has provided the state through the "submitItem" event');
                    assert.deepEqual(response, caseData.itemResponse, 'The proxy has provided the response through the "submitItem" event');
                });

                result = proxy.submitItem(caseData.uri, caseData.itemState, caseData.itemResponse);

                assert.equal(typeof result, 'object', 'The proxy.submitItem method has returned a promise');

                result.then(function(data) {
                    if (caseData.success) {
                        assert.deepEqual(data, caseData.response, 'The proxy has returned the expected data');
                    } else {
                        assert.ok(false, 'The proxy must throw an error!');
                    }

                    if (data.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), data.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                }).catch(function(err) {
                    assert.ok(!caseData.success, 'The proxy has thrown an error! #' + err);

                    if (err.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), err.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                });
            });
        });


    QUnit
        .cases([{
            title: 'success',
            uri: 'http://tao.dev/mockItemDefinition#123',
            action: 'comment',
            params: {
                text: 'lorem ipsum'
            },
            token: '1234',
            response: {
                token: '4567',
                success: true
            },
            ajaxSuccess: true,
            success: true
        }, {
            title: 'failing data',
            uri: 'http://tao.dev/mockItemDefinition#123',
            action: 'comment',
            params: {
                text: 'lorem ipsum'
            },
            token: '1234',
            response: {
                token: '4567',
                success: false
            },
            ajaxSuccess: true,
            success: false
        }, {
            title: 'failing request',
            uri: 'http://tao.dev/mockItemDefinition#123',
            action: 'comment',
            params: {
                text: 'lorem ipsum'
            },
            token: '1234',
            response: "error",
            ajaxSuccess: false,
            success: false
        }])
        .asyncTest('itemProxy.callItemAction ', function(caseData, assert) {
            var initConfig = {
                serviceCallId: 'foo',
                bootstrap: {
                    serviceController: 'MockRunner',
                    serviceExtension: 'MockExtension'
                }
            };

            var expectedUrl = urlUtil.route(caseData.action, initConfig.bootstrap.serviceController, initConfig.bootstrap.serviceExtension, {
                serviceCallId: initConfig.serviceCallId,
                itemUri : caseData.uri
            });

            var proxy, result;

            QUnit.expect('object' !== typeof caseData.response ? 9 : 10);

            proxyFactory.registerProvider('itemProxy', itemProxy);

            $.mockjax([{
                url: '/init*',
                responseText: {
                    success: true
                }
            }, {
                url: '/*',
                status: caseData.ajaxSuccess ? 200 : 500,
                responseText: caseData.response,
                response: function(settings) {
                    assert.equal(settings.url, expectedUrl, 'The proxy has called the right service');
                }
            }]);

            proxy = proxyFactory('itemProxy', initConfig);

            proxy.install();

            proxy.getTokenHandler().setToken(caseData.token);

            proxy.callItemAction(caseData.uri, caseData.action, caseData.params)
                .then(function() {
                    assert.ok(false, 'The proxy must be initialized');
                })
                .catch(function() {
                    assert.ok(true, 'The proxy must be initialized');
                });

            proxy.init().then(function () {

                proxy.on('callItemAction', function(promise, uri, action, params) {
                    assert.ok(true, 'The proxy has fired the "callItemAction" event');
                    assert.equal(typeof promise, 'object', 'The proxy has provided the promise through the "callItemAction" event');
                    assert.equal(uri, caseData.uri, 'The proxy has provided the URI through the "callItemAction" event');
                    assert.equal(action, caseData.action, 'The proxy has provided the action through the "callItemAction" event');
                    assert.deepEqual(params, caseData.params, 'The proxy has provided the params through the "callItemAction" event');
                });

                result = proxy.callItemAction(caseData.uri, caseData.action, caseData.params);

                assert.equal(typeof result, 'object', 'The proxy.callItemAction method has returned a promise');

                result.then(function(data) {
                    if (caseData.success) {
                        assert.deepEqual(data, caseData.response, 'The proxy has returned the expected data');
                    } else {
                        assert.ok(false, 'The proxy must throw an error!');
                    }

                    if (data.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), data.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                }).catch(function(err) {
                    assert.ok(!caseData.success, 'The proxy has thrown an error! #' + err);

                    if (err.token) {
                        assert.equal(proxy.getTokenHandler().getToken(), err.token, 'The proxy must update the security token');
                    }

                    QUnit.start();
                });
            });
        });

});
