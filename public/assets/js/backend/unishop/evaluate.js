define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    // 合并格式化方法
    Table.api.formatter = $.extend(Table.api.formatter,
        {
            topTimeToggle: function (value, row, index) {
                var color = typeof this.color !== 'undefined' ? this.color : 'success';
                var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                var no = typeof this.no !== 'undefined' ? this.no : 0;
                var url = typeof this.url !== 'undefined' ? this.url : '';
                return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                    + row.id + "' " + (url ? "data-url='" + url + "'" : "") + " data-params='" + this.field + "=" + (value == no ? yes : no) + "'><i class='fa fa-toggle-on " + (value == no ? 'fa-flip-horizontal text-gray' : 'text-' + color ) + " fa-2x'></i></a>";
            }
        }
    );

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'unishop/evaluate/index' + location.search,
                    add_url: 'unishop/evaluate/add',
                    edit_url: 'unishop/evaluate/edit',
                    del_url: 'unishop/evaluate/del',
                    multi_url: 'unishop/evaluate/multi',
                    table: 'unishop_evaluate',
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
                        {field: 'user_id', title: __('User_id')},
                        {field: 'order_id', title: __('Order_id')},
                        {field: 'product_id', title: __('Product_id')},
                        {field: 'product.title', title: __('Product title')},
                        {field: 'spec', title: __('Spec')},
                        {field: 'comment', title: __('Comment')},
                        {field: 'rate', title: __('Rate'), searchList: {"1":__('Rate 1'),"2":__('Rate 2'),"3":__('Rate 3'),"4":__('Rate 4'),"5":__('Rate 5')}, formatter: Table.api.formatter.normal},
                        {field: 'anonymous', title: __('Anonymous'), searchList: {"0":__('Anonymous 0'),"1":__('Anonymous 1')}, formatter: Table.api.formatter.normal},
                        {field: 'toptime', title: __('Toptime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.topTimeToggle},
                        {field: 'toptime', title: __('Top time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'unishop/evaluate/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'unishop/evaluate/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'unishop/evaluate/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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
