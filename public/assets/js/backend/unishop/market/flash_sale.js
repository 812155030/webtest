define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'unishop/market/flash_sale/index' + location.search,
                    add_url: 'unishop/market/flash_sale/add',
                    edit_url: 'unishop/market/flash_sale/edit',
                    del_url: 'unishop/market/flash_sale/del',
                    multi_url: 'unishop/market/flash_sale/multi',
                    table: 'unishop_flash_sale',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'starttime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title')},
                        {field: 'introdution', title: __('Introdution')},
                        {field: 'product.length', title: __('Product number')},
                        {field: 'switch', title: __('Switch'), searchList: {"0":__('Switch off'),"1":__('Switch on')}, formatter: Table.api.formatter.toggle},
                        {field: 'status', title: __('Status'), searchList: {"0":__('No'),"1":__('Yes')}, formatter: Table.api.formatter.status},
                        {field: 'current_state', title: __('Current state')},
                        {field: 'starttime', title: __('Starttime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'endtime', title: __('Endtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'done',
                                    text: __('Flash done'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-flash-done',
                                    icon: 'fa fa-info',
                                    url: 'unishop/market/flash_sale/done',
                                    refresh: true
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
                url: 'unishop/market/flash_sale/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'title', title: __('Title'), align: 'left'},
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
                                    url: 'unishop/market/flash_sale/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'unishop/market/flash_sale/destroy',
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

            Form.events.faselect = function (form) {
                //绑定fachoose选择附件事件
                if ($(".fachoose", form).size() > 0) {
                    $(".fachoose", form).on('click', function () {

                        parent.Fast.api.open("unishop/product/select?&multiple=true", __('Choose'), {
                            callback: function (data) {

                                // 是否新增
                                let append = false;
                                if("undefined" == typeof dataProduct){
                                    dataProduct = [];
                                } else {
                                    append = true;
                                }

                                outer:
                                for(let i in data.data){
                                    //去掉重复
                                    for (let ii in dataProduct) {
                                        if (dataProduct[ii].id == data.data[i].id) {
                                            continue outer;
                                        }
                                    }

                                    //默认数量
                                    data.data[i].number = 1;
                                    if (append) {
                                        // 向表单增加一行
                                        $('#table_product').bootstrapTable('append',data.data[i]);
                                    }
                                    dataProduct.push(data.data[i]);
                                }

                                if (append) {
                                    return;
                                }

                                // 初始化表格参数配置
                                //Table.api.init();

                                var table = $('#table_product');

                                // 初始化表格
                                table_product = table.bootstrapTable({
                                    data: dataProduct,
                                    pk: 'id',
                                    pagination: false,
                                    search: false,
                                    commonSearch: false,
                                    showRefresh: false,
                                    showToggle: false,
                                    showColumns: false,
                                    showExport: false, //是否可导出数据
                                    columns: [
                                        [
                                            {field: 'id', title: __('Product id')},
                                            {field: 'title', title: __('Product title')},
                                            {field: 'image', title: __('Product image'),formatter: Table.api.formatter.image},
                                            {field: 'stock', title: __('Product stock')},
                                            {field: 'category.name', title: __('Category name')},
                                            {field: 'number', title: __('Flash number'),formatter: function(value, row, index) {
                                                //console.log(dataProduct);
                                                return '<input name="row[product]['+index+'][id]" value="'+row.id+'" type="hidden" />' +
                                                    '<input name="row[product]['+index+'][number]" type="number" style="width: 100px" class="form-control" min="1" value="' + value + '" onchange="javascript:dataProduct['+index+'].number = this.value" /> ';
                                                }},
                                            {field: 'introduction', title: __('Introduction'),formatter: function(value, row, index) {
                                                    //console.log(dataProduct);
                                                    return '<input name="row[product]['+index+'][introduction]" type="text" placeholder="至多输入20字" class="form-control" maxlength="20" value="" onchange="javascript:dataProduct['+index+'].introduction = this.value" /> ';
                                                }},
                                            {
                                                field: 'operate',
                                                title: __('Operate'),
                                                table: table,
                                                events: Table.api.events.operate,
                                                formatter: function (value, row, index) {
                                                    var html = '<a href="javascript:$(\'#table_product\').bootstrapTable(\'remove\',{field: \'id\', values: ['+row.id+']});dataProduct = dataProduct.filter(function(item){return item.id != '+row.id+';});" class="btn btn-xs btn-danger"  data-original-title="删除"><i class="fa fa-trash">删除</i></a>'
                                                    return html;
                                                }}
                                        ]
                                    ],

                                });



                                // 为表格绑定事件
                                //Table.api.bindevent(table);
                            }
                        });
                        return false;
                    });
                }
            };

            Controller.api.bindevent();
        },
        edit: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    edit_url: 'unishop/product/edit',
                    multi_url: 'unishop/market/flash_product/multi',
                }
            });

            var table = $('#table_product');

            Table.api.formatter.toggle = function (value, row, index) {
                var color = typeof this.color !== 'undefined' ? this.color : 'success';
                var yes = typeof this.yes !== 'undefined' ? this.yes : 1;
                var no = typeof this.no !== 'undefined' ? this.no : 0;
                var url = typeof this.url !== 'undefined' ? this.url : '';
                return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                    + row.flash_product_id + "' " + (url ? "data-url='" + url + "'" : "") + " data-params='" + this.field + "=" + (value == yes ? no : yes) + "'><i class='fa fa-toggle-on " + (value == yes ? 'text-' + color : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";
            };

            // 初始化表格
            table.bootstrapTable({
                url: 'unishop/market/flash_sale/product?flash_id=' + $('#flash_id').val(),
                pk: 'id',
                pagination: false,
                search: false,
                commonSearch: false,
                showRefresh: false,
                showToggle: false,
                showColumns: false,
                showExport: false, //是否可导出数据
                columns: [
                    [
                        {field: 'id', title: __('Product id'),visible:false},
                        {field: 'product_id', title: __('Product id')},
                        {field: 'title', title: __('Product title')},
                        {field: 'image', title: __('Product image'),formatter: Table.api.formatter.image},
                        {field: 'stock', title: __('Product stock')},
                        {field: 'category.name', title: __('Category name')},
                        {field: 'sold', title: __('Sold')},
                        {field: 'number', title: __('Flash number'),formatter: function(value, row, index) {
                                //console.log(dataProduct);
                                return '<input name="row[product]['+index+'][id]" value="'+row.product_id+'" type="hidden" />' +
                                    '<input name="row[product]['+index+'][number]" type="number" style="width: 100px" class="form-control" min="1" value="' + value + '" onchange="javascript:dataProduct['+index+'].number = this.value" /> ';
                            }},
                        {field: 'introduction', title: __('Introduction'),formatter: function(value, row, index) {
                                //console.log(dataProduct);
                                return '<input name="row[product]['+index+'][introduction]" type="text" placeholder="至多输入20字" class="form-control" maxlength="20" value="' + value + '" onchange="javascript:dataProduct['+index+'].introduction = this.value" /> ';
                            }},
                        {field: 'switch', title: __('Switch'), searchList: {"0":__('Switch off'),"1":__('Switch on')}, formatter: Table.api.formatter.toggle},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: function (value, row, index) {
                                //var html = Table.api.formatter.operate
                                var html = '<a href="javascript:$(\'#table_product\').bootstrapTable(\'remove\',{field: \'product_id\', values: ['+row.product_id+']});dataProduct = dataProduct.filter(function(item){return item.product_id != '+row.product_id+';});" class="btn btn-xs btn-danger"  data-original-title="删除"><i class="fa fa-trash">删除</i></a>'
                                html += ' <a href="/admin/unishop/product/edit?ids='+row.product_id+'" class="btn btn-xs btn-success btn-editone" data-toggle="tooltip" title="" data-table-id="table" data-field-index="6" data-row-index="'+index+'" data-button-index="1" data-original-title="编辑"><i class="fa fa-pencil">编辑</i></a>'
                                return html;
                            }
                        }
                    ]
                ]
            });
            // 为表格绑定事件 只是为了绑定上下架事件，其他多余了
            Table.api.bindevent(table);

            //重写添加产品事件
            Form.events.faselect = function (form) {
                //绑定fachoose选择附件事件
                if ($(".fachoose", form).size() > 0) {
                    $(".fachoose", form).on('click', function () {
                        parent.Fast.api.open("unishop/product/select?&multiple=true", __('Choose'), {
                            callback: function (data) {
                                //获取已存在的数据
                                dataProduct = $('#table_product').bootstrapTable('getData');
                                outer:
                                    for(let i in data.data){
                                        //去掉重复
                                        for (let ii in dataProduct) {
                                            if (dataProduct[ii].id == data.data[i].id) {
                                                continue outer;
                                            }
                                        }

                                        //默认数量
                                        data.data[i].number = 1;
                                        data.data[i].product_id = data.data[i].id;
                                        data.data[i].switch = 1;
                                        data.data[i].introduction = '';
                                        data.data[i].flash_product_id = null;

                                        // 向表单增加一行
                                        $('#table_product').bootstrapTable('append',data.data[i]);

                                        dataProduct.push(data.data[i]);
                                    }
                            }
                        });
                        return false;
                    });
                }
            };

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
