define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    // 合并格式化方法
    Table.api.formatter = $.extend(Table.api.formatter,
        {
            statusCustom : function (value, row, index) {
                let number = value == 0 ? 0 : 1;
                let display = value == 0 ? '否' : '是';
                let color = value == 0 ? 'primary' : 'success';
                var html = '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + display + '</span>';
                if (value != 0){
                    html = '<a href="javascript:;" class="searchit" data-operate="=" data-field="' + this.field + '" data-value="' + number + '" data-toggle="tooltip" title="' + __('Time: %s', Moment(parseInt(value) * 1000).format('YYYY-MM-DD HH:mm:ss')) + '" >' + html + '</a>';
                } else {
                    html = '<a href="javascript:;" class="searchit" data-operate="=" data-field="' + this.field + '" data-value="' + number + '" data-toggle="tooltip" title="' + __('Click to search %s', display) + '" >' + html + '</a>';
                }
                return html;
            }
        }
    );

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'unishop/order/index' + location.search,
                    add_url: 'unishop/order/add',
                    edit_url: 'unishop/order/edit',
                    del_url: 'unishop/order/del',
                    multi_url: 'unishop/order/multi',
                    delivere_url: 'unishop/order/delivery',
                    product_url: 'unishop/order/product',
                    refund_url: 'unishop/order/refund',
                    table: 'unishop_order',
                }
            });

            // 合并操作方法
            Table.api.events.operate = $.extend(Table.api.events.operate,
                {
                    'click .btn-delivere': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.delivere_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('Delivere'), $(this).data() || {});
                    }
                },
                {
                    'click .btn-product': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.product_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('Product'), $(this).data() || {});
                    }
                },
                {
                    'click .btn-refund': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.refund_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('Refund'), $(this).data() || {});
                    }
                }
            );

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),visible:false},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.username', title: __('User name')},
                        {field: 'out_trade_no', title: __('Out_trade_no')},
                        {field: 'order_price', title: __('Order_price'), operate:'BETWEEN'},
                        {field: 'discount_price', title: __('Discount_price'), operate:'BETWEEN'},
                        {field: 'delivery_price', title: __('Delivery_price'), operate:'BETWEEN'},
                        {field: 'total_price', title: __('Total_price'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: {"-1":__('Refund'),"0":__('Cancel'),"1":__('Normal')}, formatter: Table.api.formatter.status},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"1":__('Online'),"2":__('Offline'),"3":__('wxPay'),"4":__('aliPay')}, formatter: Table.api.formatter.normal},
                        {field: 'ip', title: __('Ip'), visible:false},
                        {field: 'have_paid_status', title: __('Have_paid'), searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_delivered_status', title: __('Have_delivered'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_received_status', title: __('Have_received'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_commented_status', title: __('Have_commented'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'refund_status', title: __('Refund status'),searchList: {"0":__('None'),"1":__('Apply'),"2":__('Waiting for shipment'),"3":__('Pass'),"4":__('Refuse')},  formatter: Table.api.formatter.status},
                        {field: 'have_paid', title: __('Pay time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_delivered', title: __('Delivered time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_received', title: __('Received time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_commented', title: __('Commented time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE %...%', placeholder: '模糊搜索，*表示任意字符'},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'refund',
                                    text: __('Refund'),
                                    classname: 'btn btn-xs btn-info btn-refund',
                                    extend: 'data-toggle="tooltip"',
                                    icon: 'fa fa-handshake-o'
                                },
                                {
                                    name: 'delivere',
                                    text: __('Delivere'),
                                    classname: 'btn btn-xs btn-info btn-delivere',
                                    extend: 'data-toggle="tooltip"',
                                    icon: 'fa fa-plane'
                                },
                                {
                                    name: 'product',
                                    text: __('Product'),
                                    classname: 'btn btn-xs btn-info btn-product',
                                    extend: 'data-toggle="tooltip"',
                                    icon: 'fa fa-star-half'
                                },
                                {
                                    name: 'edit',
                                    icon: 'fa fa-pencil',
                                    text: __('Edit'),
                                    extend: 'data-toggle="tooltip"',
                                    classname: 'btn btn-xs btn-success btn-editone',
                                    url: $.fn.bootstrapTable.defaults.extend.edit_url
                                }
                            ]
                        }
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
                url: 'unishop/order/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),visible:false},
                        {field: 'user_id', title: __('User_id')},
                        {field: 'user.username', title: __('User name')},
                        {field: 'out_trade_no', title: __('Out_trade_no')},
                        {field: 'order_price', title: __('Order_price'), operate:'BETWEEN'},
                        {field: 'discount_price', title: __('Discount_price'), operate:'BETWEEN'},
                        {field: 'delivery_price', title: __('Delivery_price'), operate:'BETWEEN'},
                        {field: 'total_price', title: __('Total_price'), operate:'BETWEEN'},
                        {field: 'pay_type', title: __('Pay_type'), searchList: {"1":__('Online'),"2":__('Offline'),"3":__('wxPay'),"4":__('aliPay')}, formatter: Table.api.formatter.normal},
                        {field: 'ip', title: __('Ip'), visible:false},
                        {field: 'status', title: __('Status'), searchList: {"-1":__('Refund'),"0":__('Cancel'),"1":__('Normal')}, formatter: Table.api.formatter.status},
                        {field: 'have_paid_status', title: __('Have_paid'), searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_delivered_status', title: __('Have_delivered'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_received_status', title: __('Have_received'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'have_commented_status', title: __('Have_commented'),searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.statusCustom},
                        {field: 'refund_status', title: __('Refund status'),searchList: {"0":__('None'),"1":__('Apply'),"2":__('Waiting for shipment'),"3":__('Pass'),"4":__('Refuse')},  formatter: Table.api.formatter.status},
                        {field: 'have_paid', title: __('Pay time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_delivered', title: __('Delivered time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_received', title: __('Received time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'have_commented', title: __('Commented time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,visible:false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'remark', title: __('Remark'), operate: 'LIKE %...%', placeholder: '模糊搜索，*表示任意字符'},
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
                                    url: 'unishop/order/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'unishop/order/destroy',
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
        delivery: function(){
            Controller.api.bindevent();
        },
        product: function(){
            Controller.api.bindevent();
        },
        refund: function(){
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
