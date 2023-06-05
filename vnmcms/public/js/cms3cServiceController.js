$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})


var ServicePrefix=function()
{
    this.id=null;
    this.service_config_id=null;
    this.prefix_type_id=null;
    this.description=null;
    this.prefix_caller="";
    this.prefix_called="";
    this.prefix_caller_match_switch=0;
    this.prefix_called_match_switch=0;
    this.prefix_match_constraint=0;
    this.charge_block_type= 0;

}

cms3c.controller('servicesController', function ($scope, ApiServices,$filter,  $location, dataShare, loginCheck,ngTableParams,$filter) {
	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
	});


	loginCheck.getRole().then(function (result) {
		$scope.userLogin = result.data ? result.data : {};
		$scope.userLogin.role = result.data.id;
	})

    var path= $location.path();

    if(path=="/services")
    {
        initServices();


    }


    function initServices() {

        var res= ApiServices.getServiceZoneQuantityType();
        res.then(function (value) {

            $scope.quantityTypes=value.data.prefix;
            $scope.quantityGroup=value.data.group?value.data.group:[];

            $scope.quantityTypes.push({id:-1,name:" - Thêm mới",prefix_group:""});



            $scope.quantityTypeString ={};
            for(var i=0; i< value.data.prefix.length; i++)
            {
                $scope.quantityTypeString[value.data.prefix[i].id]= value.data.prefix[i].name;
            }


        }, function (reason) {
            $.jGrowl("Không thể tải được dịch vu, vui lòng đăng ại")
        })

    }
    // Mảng Loại dịch vụ gọi
    $scope.quantityType = {0:'Nội mạng', 1:'Ngoại mạng'};
    //$scope.callSmsType = {1: $filter(translate)('SERVICE.CALL_TYPE.INBOUND'), 2: $filter(translate)('SERVICE.CALL_TYPE.OUTBOUND'), 3: "INT"};
    // 1: extension, 2: call_record, 3: data_storage, 4: api, 5: softphone_3c
    $scope.optionType = {1: "EXTENSION", 2: "CALL_RECORD", 3: "DATA_STORAGE", 4: "API", 5: "SOFTPHONE_3C"};
    // Update list
    $scope.lstSmsType=[
        {id:1, title:$filter('translate')('SERVICE.CALL_TYPE.1')},
        {id:2, title:$filter('translate')('SERVICE.CALL_TYPE.2')}

    ];

    $scope.lstCallType=[
        {id:0, title:$filter('translate')('Ra ngoài')},
        {id:1, title:$filter('translate')('Nội bộ')}
    ]


    $scope.lstMatchSwitch=[
        {
            id:0,name:"Bình thường"
        },{
            id:1,name:"Phủ định"
        }
    ];
    $scope.lstMatchSwitchConstraint=[
        {
            id:0,name:"Không có"
        },{
            id:1,name:"Prefix hotline giống prefix số bị gọi"
        },{
            id:2,name:"Prefix hotline khác prefix số bị gọi"
        }
    ];

     $scope.lstChargeBlockType=[
        {
            id:0,name:"1s + 1"
        },{
            id:1,name:"6s+1"
        },{
            id:2,name:"1 phút + 1"
        }
    ];


    $scope.addService = {};

	$scope.servicesParam={};
	$scope.serviceTableCols=[
		{field: "service_name", title: "Tên", sortable: "service_name", show: true},
		{field: "status", title: "Trạng thái", sortable: "status", show: true},
		{field: "product_code", title: "Mã gói cước", sortable: "product_code", show: true},
		{field: "type", title: "Loại cước", sortable: "type", show: true},
		{field: "ocs_charge_name", title: "Tính  cước qua OCS", sortable: "ocs_charge", show: true},
		{field: "created_at", title: "Ngày tạo", sortable: "created_at", show: true},
		{field: "updated_at", title: "Ngày cập nhật", sortable: "updated_at", show: true},
		{field: "action", title: "Hành động", sortable: "action", show: true},
	]

	$scope.lstOCS={
		0:"Không",
		1:"Tính cước qua OSC"
	}



	$scope.getAllServices = function () {
        // var res = ApiServices.getServiceConfig({});
        // res.then(function (data) {
        //     $scope.lstServices = data.data.data;
        // })

		if (!$scope.serviceTableParams) {
			$scope.serviceTableParams = new ngTableParams({
					page: 1, // show first page
					count:20   // count per page

				}, {
					counts: [10,20,50,100],
					getData: function ($defer, params) {
						$scope.servicesParam.page = params.page();
						$scope.servicesParam.count = params.count();
						$scope.servicesParam.sorting = $scope.serviceTableParams.orderBy().toString();
						$scope.servicesParam.tableGroupBy = $scope.serviceTableParams.tableGroupBy;
						ApiServices.getServiceConfig($scope.servicesParam).then(function (response) {
								$("#loading").modal("hide");
								$scope.lstServices = response.data.data;
									$scope.lstServices.map(item=>{
										item.ocs_charge_name = $scope.lstOCS[item.ocs_charge]
									})


								$scope.lstServicesCount = response.data.count;

								if (response.data.count <= $scope.serviceTableParams.parameters().count) {
									$scope.serviceTableParams.parameters().page = 1;
								}
								params.total($scope.lstServicesCount);
								$defer.resolve($scope.lstServices);
							}, function (response) {
								$("#loading").modal("hide");
								$scope.lstServices = [];
								$scope.lstServicesCount = -1;
							}
						);
					}
				}
			)
		}
		else {
			$scope.serviceTableParams.reload();
		}

    }
    $scope.getAllServices();
    $scope.viewServiceConfig = function (data) {
        $scope.currentService = data;
        $scope.currentService.lstServicePrefix=[];
        if (data.id) {
            res = ApiServices.getServiceConfigById(data.id);
            res.then(function (data) {

                $scope.serviceConfig = data.data;
                $scope.currentService.lstServicePrefix=data.data.lstServicePrefix;


            })
        }
    }
    $scope.editService = function () {
        dataShare.data = ($scope.currentService);
        $location.path("/addService");
    }

    $scope.editServiceTable = function (data) {
        dataShare.data = angular.copy(data);
        $location.path("/addService");
    }


    /** Config Price --
     * ===============================================================
     */


    $scope.activeConfigPriceZone= function()
    {

    }


    $scope.addNewPrefixType= function(){
        $scope.serviceConfigPrefixError=null;
        $("#addPrefixType").modal("show");
        var Framedata = new ServicePrefix();
        Framedata.service_config_id= $scope.currentService.id;
        $scope.zone = (Framedata);
    }

    $scope.editPrefixType= function(data)
    {
		$scope.zone=data;
        for(var i=0; i< $scope.quantityTypes.length; i++)
        {
            if($scope.quantityTypes[i].id== data.prefix_type_id)
            {
				$scope.zone.prefix_group= angular.copy($scope.quantityTypes[i].prefix_group);
                break;
            }
        }
        $scope.serviceConfigPrefixError=null;
        // console.log(data);
        $("#addPrefixType").modal("show");


    }

    $scope.changePrefixGroup= function(zone)
    {
		for(var i=0; i< $scope.quantityTypes.length; i++)
		{
			if($scope.quantityTypes[i].id== zone.prefix_type_id)
			{
				$scope.zone.prefix_group=angular.copy($scope.quantityTypes[i].prefix_group);
				break;
			}
		}
    }



    $scope.deletePrefixType= function(data)
    {
        var cfm = confirm("Bạn muốn xóa dữ liệu này");
        if(cfm)
        {
            /// Do Delete
            var dataPost={id:data.id};
            var res= ApiServices.deleteServicePrefixType(dataPost);
            res.then(function (value) {
                $.jGrowl("Xóa thành công")
				// initServices();
				for(var i=0; i<$scope.lstServices.length; i++)
				{
					if(data.service_config_id== $scope.lstServices[i].id)
					{
						$scope.viewServiceConfig( $scope.lstServices[i]);

						break;
					}
				}


			}, function (reason) {
                alert("Lỗi xóa prefix")
            })
        }
        else
        {
            // Do nothing
        }

    }
    $scope.savePrefixType= function(data)
    {

        var res= ApiServices.postServicePrefixType(data);
        res.then(function (value) {

            $.jGrowl("Cập nhật thành công")


            // $scope.viewServiceConfig({id:data.service_config_id});

			$("#addPrefixType").modal("hide");
			  initServices();
			  for(var i=0; i<$scope.lstServices.length; i++)
              {
                  if(data.service_config_id== $scope.lstServices[i].id)
                  {
					  $scope.viewServiceConfig( $scope.lstServices[i]);


                      break;
                  }
              }

        }, function (reason) {
            $scope.serviceConfigPrefixError=reason.data;
            console.warn(reason);
        })
    }

    $scope.activeConfigPrice = function () {
        $scope.serviceConfig.config_price = [];
        $scope.addConfigPrice();
    }
    $scope.addConfigPrice = function () {
        // Validate data for stepped billing
        var TotalConfigPrice=$scope.serviceConfig.config_price.length;
        if(TotalConfigPrice==0)
        {
            $scope.serviceConfig.config_price.push({
                "edit": true,
                "service_config_id": $scope.currentService.id,
                "from_user": "0",
                "to_user": "1",
                "price": "0"
            })
        }
        else
        {

            $scope.serviceConfig.config_price.push({
                "edit": true,
                "service_config_id": $scope.currentService.id,
                "from_user": parseInt($scope.serviceConfig.config_price[TotalConfigPrice-1].to_user)+1,
                "to_user":parseInt($scope.serviceConfig.config_price[TotalConfigPrice-1].to_user)+2,
                "price": "0"
            })

        }



    }
    $scope.editConfigPrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveConfigPrice = function (data) {

        // Set to service
        res = ApiServices.postServiceConfigPrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {
                delete data.edit;
                //  data.updated_at = rdata.data.data.updated_at;
                data.id = rdata.data.id;
                if (rdata.data.data.created_at) {
                    data.created_at = rdata.data.data.created_at;
                }

				$.jGrowl("Cập nhật thành công")
            }
        })
    }
    /** Config Hotline Price --
     * ===============================================================
     */
    $scope.activeConfigHotlinePrice = function () {
        $scope.serviceConfig.hotline_price = [];
        $scope.addConfigHotlinePrice();
    }
    $scope.addConfigHotlinePrice = function () {
        // $scope.serviceConfig.hotline_price.push({
        //     "edit": true,
        //     "service_config_id": $scope.currentService.id,
        //     "from_hotline_num": "",
        //     "to_hotline_num": "",
        //     "price": ""
        // })



        var TotalConfigHotline=$scope.serviceConfig.hotline_price.length;
        if(TotalConfigHotline==0)
        {
            $scope.serviceConfig.hotline_price.push({
                "edit": true,
                "service_config_id": $scope.currentService.id,
                "from_hotline_num": "0",
                "to_hotline_num": "1",
                "price": ""
            })
        }
        else
        {

            $scope.serviceConfig.hotline_price.push({
                // "edit": true,
                // "service_config_id": $scope.currentService.id,
                // "from_user": parseInt($scope.serviceConfig.config_price[TotalConfigPrice-1].to_user)+1,
                // "to_user":parseInt($scope.serviceConfig.config_price[TotalConfigPrice-1].to_user)+2,
                // "price": "0"

                "edit": true,
                "service_config_id": $scope.currentService.id,
                "from_hotline_num":  parseInt($scope.serviceConfig.hotline_price[TotalConfigHotline-1].to_hotline_num)+1,
                "to_hotline_num": parseInt($scope.serviceConfig.hotline_price[TotalConfigHotline-1].to_hotline_num)+2,
                "price": ""
            })

        }


    }
    $scope.editConfigHotlinePrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveConfigHotlinePrice = function (data) {

        // Set to service
        res = ApiServices.postServiceConfigHotlinePrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {
                delete data.edit;

                // data.id = rdata.data.id;

                $scope.viewServiceConfig($scope.currentService);

				$.jGrowl("Cập nhật thành công")
            }
        })
    };
    /** Config Call price Price --
     * ===============================================================
     */
    $scope.activeCallPrice = function () {
        $scope.serviceConfig.call_price = [];
        $scope.addCallPrice();
    }
    $scope.addCallPrice = function () {
        $scope.serviceConfig.call_price.push({
            "edit": true,
            "service_config_id": $scope.currentService.id,
            "from_min": "",
            "to_min": "",
            "call_fees": "",
            "call_type": null
        })
    }
    $scope.editCallPrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveCallPrice = function (data) {

        // Set to service
        res = ApiServices.postServiceCallPrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {

                $scope.viewServiceConfig($scope.currentService);

				$.jGrowl("Cập nhật thành công")
            }
        }, function (e) {
            if(e.status==422) {
                data.error = e.data.errors;
            }

        })
    };
    /** Config SMS price Price --
     * ===============================================================
     */
    $scope.activeSmsPrice = function () {
        $scope.serviceConfig.sms_price = [];
        $scope.addSmsPrice();
    }
    $scope.addSmsPrice = function () {
        $scope.serviceConfig.sms_price.push({
            "edit": true,
            "service_config_id": $scope.currentService.id,
            "from_sms": "",
            "to_sms": "",
            "sms_fees": "",
            "sms_type": ""
        })
    }
    $scope.editSmsPrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveSmsPrice = function (data) {

        // Set to service
        res = ApiServices.postServiceSmsPrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {


				$.jGrowl("Cập nhật thành công");

                $scope.viewServiceConfig($scope.currentService);
            }
        }, function (reason) {
			$.jGrowl("Có lỗi xảy ra")
        })
    };
    /** Config Quantity price Price --
     * ===============================================================
     */
    $scope.activeQuantityPrice = function () {
        $scope.serviceConfig.quantity_price = [];
        $scope.addQuantityPrice();
    }
    $scope.addQuantityPrice = function () {
        $scope.serviceConfig.quantity_price.push({
            "edit": true,
            "service_config_id": $scope.currentService.id,
            "min": "",
            "description": "",
            "price": "",
            "type": ""
        })
    }
    $scope.editQuantityPrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveQuantityPrice = function (data) {

        // Set to service
        res = ApiServices.postServiceQuantityPrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {


				$.jGrowl("Cập nhật thành công")
				$scope.viewServiceConfig($scope.currentService);
            }
        })
    };
    /** Config Option Price --
     * ===============================================================
     */
    $scope.activeOptionPrice = function () {
        $scope.serviceConfig.option_price = [];
        $scope.addOptionPrice();
    }
    $scope.addOptionPrice = function () {
        $scope.serviceConfig.option_price.push({
            "edit": true,
            "service_config_id": $scope.currentService.id,
            "from": "",
            "to": "",
            "type": "",
            "description": ""
        })
    }
    $scope.editOptionPrice = function (data) {
        data.edit = true;
        data.service_config_id = angular.copy($scope.currentService.id);
        return data;
    }
    $scope.saveOptionPrice = function (data) {

        // Set to service
        res = ApiServices.postServiceOptionPrice(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {

				$.jGrowl("Cập nhật thành công")
				$scope.viewServiceConfig($scope.currentService);

            }
        })
    };

    $scope.deleteConfigPrice= function (data) {

		if(!confirm("Bạn muốn xóa thông tin"))
		{
			return;
		}
        res=ApiServices.deleteConfigPrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);
			$.jGrowl("Xóa thành công")
        })

    }
    $scope.deleteQuantityPrice= function (data) {

		if(!confirm("Bạn muốn xóa thông tin"))
		{
			return;
		}
        res=ApiServices.deleteQuantityPrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);

			$.jGrowl("Xóa thành công")

        })

    }

    $scope.deleteOptionPrice= function (data) {
		if(!confirm("Bạn muốn xóa thông tin"))
		{
			return;
		}

        res=ApiServices.deleteOptionPrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);
			$.jGrowl("Xóa thành công")
        })

    }

    $scope.deleteSmsPrice= function (data) {

		if(!confirm("Bạn muốn xóa thông tin"))
		{
			return;
		}
        res=ApiServices.deleteSmsPrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);
			$.jGrowl("Xóa thành công")
        })

    }

    $scope.deleteConfigHotlinePrice= function (data) {

		if(!confirm("Bạn muốn xóa thông tin"))
		{
			return;
		}
        res=ApiServices.deleteConfigHotlinePrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);
			$.jGrowl("Xóa thành công")
        })

    }

    $scope.deleteCallPrice= function (data) {

        if(!confirm("Bạn muốn xóa thông tin"))
        {
            return;
        }


        res=ApiServices.deleteCallPrice({id:data.id})
        res.then(function(rs){
            $scope.viewServiceConfig($scope.currentService);
			$.jGrowl("Xóa thành công")
        })

    }



});