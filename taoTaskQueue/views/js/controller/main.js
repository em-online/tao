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

/**
 * @author Sam <sam@taotesting.com>
 */
define([
    'jquery',
    'taoTaskQueue/component/manager/manager',
    'core/taskQueue/taskQueue'
],
function ($, taskQueueManagerFactory, taskQueue) {
    'use strict';

    /**
     * This controller initialize all the task queue component globally for tao backoffice use
     * @exports taoTaskQueue/controller/main
     */
    return {
        start: function () {

            var taskManager = taskQueueManagerFactory({
                    replace: true
                })
                .on('remove', function (taskId) {
                    taskQueue.archive(taskId);
                })
                .on('report', function (taskId) {
                    taskQueue.get(taskId).then(function (task) {
                        //show report in popup
                        taskManager.showDetail(task);
                    });
                })
                .on('download', function (taskId) {
                    taskQueue.download(taskId);
                })
                .on('redirect', function (taskId) {
                    taskQueue.redirect(taskId);
                })
                .on('listclearfinished', function (){
                    taskQueue
                        .pollAllStop()
                        .archive('all')
                        .then(function(){
                            taskQueue.pollAll();
                        });
                })
                .render($('#taskqueue').parent())
                .hide();//start hidden to prevent blinking effect

            //listen to events started by the task queue model
            taskQueue.on('taskcreated', function(data){
                if(taskManager.list.is('hidden')){
                    taskManager.absorbBurst(data.sourceDom, [0, 300, 600]).then(function(){
                        taskManager.addNewTask(data.task);
                        taskQueue.pollAll();
                    });
                }else{
                    taskManager.addNewTask(data.task, true);
                    taskQueue.pollAll();
                }
            }).on('multitaskstatuschange', function(){
                taskManager.pulse();
            }).on('pollAll', function (tasks) {
                if(taskManager.is('hidden')){
                    taskManager.show();
                }
                taskManager.loadData(tasks);
            }).pollAll(true);//start polling immediately on load
        }
    };
});