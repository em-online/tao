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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien.conan@vesperiagroup.com>
 * @author Sam <sam@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'core/eventifier',
    'core/promise',
    'core/logger',
    'core/providerRegistry',
    'taoTests/runner/dataHolder',
    'tpl!taoTests/template/finalReview',
    'taoTests/runner/finalReview'
], function ($, _, __, eventifier, Promise, logger, providerRegistry, dataHolderFactory, finalReviewTpl, finalReviewFactory) {
    'use strict';

    /**
     * Builds an instance of the QTI test runner
     *
     * @param {String} providerName
     * @param {Object} config
     * @param {String|DOMElement|JQuery} config.contentContainer - the dom element that is going to holds the test content (item, rubick, etc)
     * @param {Array} [config.plugins] - the list of plugin instances to be initialized and bound to the test runner
     * @returns {runner|_L28.testRunnerFactory.runner}
     */
    function testRunnerFactory(providerName, pluginFactories, config){

        /**
         * @type {Object} The test runner instance
         */
        var runner;

        /**
         * @type {Map} Contains the test runner data
         */
        var dataHolder = dataHolderFactory();

        /**
         * @type {Object} the registered plugins
         */
        var plugins = {};

        /**
         * @type {Object} the test of the runner
         */
        var states = {
            'init':    false,
            'ready':   false,
            'render':  false,
            'finish':  false,
            'destroy': false
        };

        /**
         * @type {Object} keeps the states of the items
         */
        var itemStates = {};

        /**
         * The selected test runner provider
         */
        var provider  = testRunnerFactory.getProvider(providerName);

        /**
         * Keep the area broker instance
         * @see taoTests/runner/areaBroker
         */
        var areaBroker;

        /**
         * Keep the proxy instance
         * @see taoTests/runner/proxy
         */
        var proxy;


        /**
         * Keep the instance of the probes overseer
         * @see taoTests/runner/probeOverseer
         */
        var probeOverseer;

        /**
         * Keep the instance of a testStore
         * @see taoTests/runner/testStore
         */
        var testStore;

        /**
         * Run a method of the provider (by delegation)
         *
         * @param {String} method - the method to run
         * @param {...} args - rest parameters given to the provider method
         * @returns {Promise} so provider can do async stuffs
         */
        function providerRun(method){
            var args = [].slice.call(arguments, 1);
            return new Promise(function(resolve){
                if(!_.isFunction(provider[method])){
                    return resolve();
                }
                return resolve(provider[method].apply(runner, args));
            });
        }

        /**
         * Run a method in all plugins
         *
         * @param {String} method - the method to run
         * @returns {Promise} once that resolve when all plugins are done
         */
        function pluginRun(method){
            var execStack = [];

            _.forEach(runner.getPlugins(), function (plugin){
                if(_.isFunction(plugin[method])){
                    execStack.push(plugin[method]());
                }
            });

            return Promise.all(execStack);
        }

        /**
         * Trigger error event
         * @param {Error|String} err - the error
         * @fires runner#error
         */
        function reportError(err){
            runner.trigger('error', err);
        }


        config = config || {};

        /**
         * Defines the test runner
         *
         * @type {runner}
         */
        runner = eventifier({

            /**
             * Initialize the runner
             *  - instantiate the plugins
             *  - provider init
             *  - plugins init
             *  - call render
             * @fires runner#init
             * @returns {runner} chains
             */
            init : function init(){
                var self = this;

                //instantiate the plugins first
                _.forEach(pluginFactories, function(pluginFactory){
                    var plugin = pluginFactory(runner, self.getAreaBroker());
                    plugins[plugin.getName()] = plugin;
                });

                providerRun('install')
                    .then(_.partial(providerRun, 'loadPersistentStates'))
                    .then(_.partial(pluginRun, 'install'))
                    .then(_.partial(providerRun, 'init'))
                    .then(_.partial(pluginRun, 'init'))
                    .then(function() {
                        self.setState('init', true)
                            .off('init.internal')
                            .after('init.internal', function initDone(){
                                this.render();
                            })
                            .trigger('init');
                    })
                    .catch(reportError);

                return this;
            },

            /**
             * Render the runner
             *  - provider render
             *  - plugins render
             * @fires runner#render
             * @fires runner#ready
             * @returns {runner} chains
             */
            render : function render(){
                var self = this;

                providerRun('render').then(function(){
                    pluginRun('render').then(function(){
                        self.setState('ready', true)
                            .trigger('render')
                            .trigger('ready');
                    }).catch(reportError);
                }).catch(reportError);
                return this;
            },

            /**
             * Load an item
             *  - provider loadItem, resolve or return the itemData
             *  - plugins loadItem
             *  - call renderItem
             * @param {*} itemRef - something that let you identify the item to load
             * @fires runner#loaditem
             * @returns {runner} chains
             */
            loadItem : function loadItem(itemRef){
                var self = this;

                providerRun('loadItem', itemRef).then(function(itemData){
                    self.setItemState(itemRef, 'loaded', true)
                        .off('loaditem.internal')
                        .after('loaditem.internal', function loadItemDone(){
                            this.renderItem(itemRef, itemData);
                        })
                        .trigger('loaditem', itemRef, itemData);
                }).catch(reportError);
                return this;
            },

            /**
             * Render an item
             *  - provider renderItem
             *  - plugins renderItem
             * @param {Object} itemData - the loaded item data
             * @fires runner#renderitem
             * @returns {runner} chains
             */
            renderItem : function renderItem(itemRef, itemData){
                var self = this;

                if(runner.getTestContext().isLast && !runner.getTestContext().isLinear){
	                var $testMap = runner.getTestMap();
	                var navigator = finalReviewFactory(runner.getTestData().config.review, $testMap, runner.getTestContext());
	                var $treeHTML = finalReviewTpl(navigator.getScopedMap($testMap, runner.getTestContext()));
			itemData.content.data.body.body = $treeHTML;
                }

                providerRun('renderItem', itemRef, itemData).then(function(){
                    self.setItemState(itemRef, 'ready', true)
                        .trigger('renderitem', itemRef, itemData);
                }).catch(reportError);
                return this;
            },

            /**
             * Unload an item (for example to destroy the item)
             *  - provider unloadItem
             *  - plugins unloadItem
             * @param {*} itemRef - something that let you identify the item to unload
             * @fires runner#unloaditem
             * @returns {runner} chains
             */
            unloadItem : function unloadItem(itemRef){
                var self = this;

                providerRun('unloadItem', itemRef).then(function(){
                    itemStates = _.omit(itemStates, itemRef);
                    self.trigger('unloaditem', itemRef);
                }).catch(reportError);
                return this;
            },

            /**
             * Disable an item
             *  - provider disableItem
             * @param {*} itemRef - something that let you identify the item
             * @fires runner#disableitem
             * @returns {runner} chains
             */
            disableItem : function disableItem(itemRef){
                var self = this;

                if(!this.getItemState(itemRef, 'disabled')){

                    providerRun('disableItem', itemRef).then(function(){
                        self.setItemState(itemRef, 'disabled', true)
                            .trigger('disableitem', itemRef);
                    }).catch(reportError);
                }
                return this;
            },

            /**
             * Enable an item
             *  - provider enableItem
             * @param {*} itemRef - something that let you identify the item
             * @fires runner#disableitem
             * @returns {runner} chains
             */
            enableItem : function enableItem(itemRef){
                var self = this;

                if(this.getItemState(itemRef, 'disabled')){
                    providerRun('enableItem', itemRef).then(function(){
                        self.setItemState(itemRef, 'disabled', false)
                            .trigger('enableitem', itemRef);
                    }).catch(reportError);
                }
                return this;
            },

            /**
             * When the test is terminated
             *  - provider finish
             *  - plugins finsh
             * @fires runner#finish
             * @returns {runner} chains
             */
            finish : function finish(){
                var self = this;

                providerRun('finish').then(function(){
                    pluginRun('finish').then(function(){
                        self.setState('finish', true)
                            .trigger('finish');
                    }).catch(reportError);
                }).catch(reportError);
                return this;
            },

            /**
             * Flushes the runner
             *  - provider flush
             *  - plugins flush
             * @fires runner#flush
             * @returns {runner} chains
             */
            flush : function flush(){
                var self = this;

                providerRun('flush').then(function(){
                    pluginRun('flush').then(function(){
                        self.setState('flush', true)
                            .trigger('flush');
                    }).catch(reportError);
                }).catch(reportError);
                return this;
            },

            /**
             * Destroy
             *  - provider destroy
             *  - plugins destroy
             * @fires runner#destroy
             * @returns {runner} chains
             */
            destroy : function destroy(){
                var self = this;

                providerRun('destroy').then(function(){
                    pluginRun('destroy').then(function(){
                        var destroyed;

                        if (proxy) {
                            destroyed = proxy.destroy();
                        } else {
                            destroyed = Promise.resolve();
                        }

                        return destroyed.then(function() {
                            self.setTestContext({})
                                .setTestMap({})
                                .setState('destroy', true)
                                .trigger('destroy');
                        });
                    }).catch(reportError);
                }).catch(reportError);
                return this;
            },

            /**
             * Get the runner pugins
             * @returns {plugin[]} the plugins
             */
            getPlugins : function getPlugins(){
                return plugins;
            },

            /**
             * Get a plugin
             * @param {String} name - the plugin name
             * @returns {plugin} the plugin
             */
            getPlugin : function getPlugin(name){
                return plugins[name];
            },

            /**
             * Get the config
             * @returns {Object} the config
             */
            getConfig : function getConfig(){
                return config;
            },

            /**
             * Get the area broker, load it if not present
             *
             * @returns {areaBroker} the areaBroker
             */
            getAreaBroker : function getAreaBroker(){
                if(!areaBroker){
                    areaBroker = provider.loadAreaBroker.call(this);
                }
                return areaBroker;
            },


            /**
             * Get the proxy, load it if not present
             *
             * @returns {proxy} the proxy
             */
            getProxy : function getProxy(){
                var self = this;
                if(!proxy){
                    if(!_.isFunction(provider.loadProxy)){
                        throw new Error('The provider does not have a loadProxy method');
                    }
                    proxy = provider.loadProxy.call(this);

                    proxy.on('error', function (error) {
                        self.trigger('error', error);
                    });

                    proxy.install(dataHolder);
                }
                return proxy;
            },

            /**
             * Get the probeOverseer, and load it if not present
             *
             * @returns {probeOverseer} the probe overseer
             */
            getProbeOverseer : function getProbeOverseer(){
                if(!probeOverseer && _.isFunction(provider.loadProbeOverseer)){
                    probeOverseer = provider.loadProbeOverseer.call(this);
                }

                return probeOverseer;
            },

            /**
             * Get the testStore, and load it if not present
             *
             * @returns {testStore} the testStore instance
             */
            getTestStore : function getTestStore(){

                if(!testStore && _.isFunction(provider.loadTestStore)){
                    testStore = provider.loadTestStore.call(this);
                }
                return testStore;
            },

            /**
             * Get a plugin store.
             * It's a convenience method that calls testStore.getStore
             * @param {String} name - the name of store, usually the plugin name.
             *
             * @returns {Promise<storage>} the plugin store
             */
            getPluginStore : function getPluginStore(name){
                var loadedStore = this.getTestStore();
                if(!loadedStore || !_.isFunction(loadedStore.getStore)){
                    return Promise.reject(new Error('Please configure a testStore via loadTestStore to be able to get a plugin store'));
                }
                return this.getTestStore().getStore(name);
            },


            /**
             * Check a runner state
             *
             * @param {String} name - the state name
             * @returns {Boolean} if active, false if not set
             */
            getState : function getState(name){
                return !!states[name];
            },

            /**
             * Define a runner state
             *
             * @param {String} name - the state name
             * @param {Boolean} active - is the state active
             * @returns {runner} chains
             * @throws {TypeError} if the state name is not a valid string
             */
            setState : function setState(name, active){
                if(!_.isString(name) || _.isEmpty(name)){
                    throw new TypeError('The state must have a name');
                }
                states[name] = !!active;

                return this;
            },

            /**
             * Checks a runner persistent state
             *  - provider getPersistentState
             *
             * @param {String} name - the state name
             * @returns {Boolean} if active, false if not set
             */
            getPersistentState : function getPersistentState(name) {
                var state;

                if(_.isFunction(provider.getPersistentState)){
                    state = provider.getPersistentState.call(runner, name);
                }

                return !!state;
            },

            /**
             * Defines a runner persistent state
             *  - provider setPersistentState
             *
             * @param {String} name - the state name
             * @param {Boolean} active - is the state active
             * @returns {Promise} Returns a promise that:
             *                      - will be resolved once the state is fully stored
             *                      - will be rejected if any error occurs or if the state name is not a valid string
             */
            setPersistentState : function setPersistentState(name, active) {
                var stored;

                if (!_.isString(name) || _.isEmpty(name)) {
                    stored = Promise.reject(new TypeError('The state must have a name'));
                } else {
                    stored = providerRun('setPersistentState', name, !!active);
                }

                stored.catch(reportError);

                return stored;
            },

            /**
             * Check an item state
             *
             * @param {*} itemRef - something that let you identify the item
             * @param {String} name - the state name
             * @returns {Boolean} if active, false if not set
             *
             * @throws {TypeError} if there is no itemRef nor name
             */
            getItemState : function getItemState(itemRef, name){
                if( _.isEmpty(itemRef) || _.isEmpty(name)){
                    throw new TypeError('The state is identified by an itemRef and a name');
                }
                return !!(itemStates[itemRef] && itemStates[itemRef][name]);
            },

            /**
             * Check an item state
             *
             * @param {*} itemRef - something that let you identify the item
             * @param {String} name - the state name
             * @param {Boolean} active - is the state active
             * @returns {runner} chains
             *
             * @throws {TypeError} if there is no itemRef nor name
             */
            setItemState : function setItemState(itemRef, name, active){
                if( _.isEmpty(itemRef) || _.isEmpty(name)){
                    throw new TypeError('The state is identified by an itemRef and a name');
                }
                itemStates[itemRef] = itemStates[itemRef] || {
                    'loaded' : false,
                    'ready'  : false,
                    'disabled': false
                };

                itemStates[itemRef][name] = !!active;

                return this;
            },

            /**
             * Get the test data/definition
             * @returns {Object} the test data
             */
            getTestData : function getTestData(){
                return dataHolder && dataHolder.get('testData');
            },

            /**
             * Set the test data/definition
             * @param {Object} testData - the test data
             * @returns {runner} chains
             */
            setTestData : function setTestData(testData){
                if(dataHolder && _.isPlainObject(testData)){
                    dataHolder.set('testData', testData);
                }
                return this;
            },

            /**
             * Get the test context/state
             * @returns {Object} the test context
             */
            getTestContext : function getTestContext(){
                return dataHolder && dataHolder.get('testContext');
            },

            /**
             * Set the test context/state
             * @param {Object} testContext - the context to set
             * @returns {runner} chains
             */
            setTestContext : function setTestContext(testContext){
                if(dataHolder && _.isPlainObject(testContext)){
                    dataHolder.set('testContext', testContext);
                }
                return this;
            },

            /**
             * Get the test items map
             * @returns {Object} the test map
             */
            getTestMap : function getTestMap(){
                return dataHolder && dataHolder.get('testMap');
            },

            /**
             * Set the test items map
             * @param {Object} testMap - the map to set
             * @returns {runner} chains
             */
            setTestMap : function setTestMap(testMap){
                if(dataHolder && _.isPlainObject(testMap)){
                    dataHolder.set('testMap', testMap);
                }
                return this;
            },

            /**
             * Get the data holder
             * @returns {dataHolder}
             */
            getDataHolder : function getDataHolder(){
                return dataHolder;
            },

            /**
             * Move next alias
             * @param {String|*} [scope] - the movement scope
             * @fires runner#move
             * @returns {runner} chains
             */
            next : function next(scope){
                this.trigger('move', 'next', scope);
                return this;
            },

            /**
             * Move previous alias
             * @param {String|*} [scope] - the movement scope
             * @fires runner#move
             * @returns {runner} chains
             */
            previous : function previous(scope){
                this.trigger('move', 'previous', scope);
                return this;
            },

            /**
             * Move to alias
             * @param {String|Number} position - where to jump
             * @param {String|*} [scope] - the movement scope
             * @fires runner#move
             * @returns {runner} chains
             */
            jump : function jump(position, scope){
                this.trigger('move', 'jump', scope, position);
                return this;
            },

            /**
             * Skip alias
             * @param {String|*} [scope] - the movement scope
             * @fires runner#move
             * @returns {runner} chains
             */
            skip : function skip(scope){
                this.trigger('skip', scope);
                return this;
            },

            /**
             * Exit the test
             * @param {String|*} [why] - reason the test is exited
             * @fires runner#exit
             * @returns {runner} chains
             */
            exit : function exit(why){
                this.trigger('exit', why);
                return this;
            },

            /**
             * Pause the current execution
             * @fires runner#pause
             * @returns {runner} chains
             */
            pause : function pause(){
                if(!this.getState('pause')){
                    this.setState('pause', true)
                        .trigger('pause');
                }
                return this;
            },

            /**
             * Resume a paused test
             * @fires runner#pause
             * @returns {runner} chains
             */
            resume : function resume(){
                if(this.getState('pause') === true){
                    this.setState('pause', false)
                        .trigger('resume');
                }
                return this;
            },

            /**
             * Notify a test timeout
             * @param {String} scope - The scope where the timeout occurred
             * @param {String} ref - The reference to the place where the timeout occurred
             * @param {Object} [timer] - The timer's descriptor, if any
             * @fires runner#timeout
             * @returns {runner} chains
             */
            timeout : function timeout(scope, ref, timer){
                this.trigger('timeout', scope, ref, timer);
                return this;
            }
        });

        runner
            .on('move', function move(type){
                this.trigger.apply(this, [type].concat([].slice.call(arguments, 1)));
            })
            .after('destroy', function destroyCleanUp(){
                if (dataHolder) {
                    dataHolder.clear();
                }
                areaBroker = null;
                proxy = null;
                probeOverseer = null;
                testStore = null;
            });
            $(document).on('click', '.qti-final-review-item', function(e) {
                runner.jump(parseInt(e.currentTarget.dataset.position), 'item');
            });
            $(document).on('click', '.qti-final-review-part>span.qti-final-review-label', function(e) {
                var panel = $(this).closest('li.qti-final-review-part')
                var collapsed = panel.hasClass('collapsed');
                if (collapsed) {
                    panel.removeClass('collapsed');
                } else {
                    panel.addClass('collapsed');
                }
            });
            $(document).on('click', '.qti-final-review-section>span.qti-final-review-label', function(e) {
                var panel = $(this).closest('li.qti-final-review-section')
                var collapsed = panel.hasClass('collapsed');
                if (collapsed) {
                    panel.removeClass('collapsed');
                } else {
                    panel.addClass('collapsed');
                }
            });
        return runner;
    }

    //bind the provider registration capabilities to the testRunnerFactory
    return providerRegistry(testRunnerFactory, function validateProvider(provider){

        //mandatory methods
        if(!_.isFunction(provider.loadAreaBroker)){
            throw new TypeError('The runner provider MUST have a method that returns an areaBroker');
        }
        return true;
    });
});
