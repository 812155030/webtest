require.config({
    paths : {
        "delivery" : "../addons/unishop/js/delivery"
    }
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form','delivery'], function ($, undefined, Backend, Table, Form,undefined) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'unishop/delivery/index' + location.search,
                    add_url: 'unishop/delivery/add',
                    edit_url: 'unishop/delivery/edit',
                    del_url: 'unishop/delivery/del',
                    multi_url: 'unishop/delivery/multi',
                    table: 'unishop_delivery',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'min', title: __('Min buy')},
                        {field: 'type', title: __('Type'), searchList: {"quantity":__('Quantity'),"weight":__('Weight')}, formatter: Table.api.formatter.normal},
                        {field: 'switch', title: __('Switch'), searchList: {"1":__('Yes'),"0":__('No')}, formatter: Table.api.formatter.toggle},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'weigh', title: __('Weigh')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            // 配送区域表格
            new Delivery({
                table: '.regional-table',
                regional: '.regional-choice',
                datas: datas
            });
            Controller.api.bindevent();
        },
        edit: function () {
            // 配送区域表格
            new Delivery({
                table: '.regional-table',
                regional: '.regional-choice',
                datas: datas
            });
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
