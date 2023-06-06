cms3c.controller('customerController', function ($filter, $scope, ApiServices, ApiV1, $location, dataShare, loginCheck,ngTableParams, $window) {
	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});

	$scope.SERVER_PROFILE= SERVER_PROFILE;

    var path= $location.path();
    $scope.getLstOperatorTelco= function () {
        ApiServices.getOperatorTelco().then(result=>{
            $scope.lstOperatorTelco= result.data?result.data.data:[];
            $scope.lstOperatorTelco.unshift({'id':"",'description':"Chọn"})
        }, reason => {

        })

    }
    $scope.lstOperatorTelco=[];
    if(path=="/accounts")
    {
        initServices();
    }
    function initServices() {

        var res= ApiServices.getServiceZoneQuantityType();
        res.then(function (value) {

            $scope.quantityTypes=value.data;



            $scope.quantityTypeString ={};
            for(var i=0; i< value.data.length; i++)
            {
                $scope.quantityTypeString[value.data[i].id]= value.data[i].name;
            }


        }, function (reason) {
          $.jGrowl("Không tải được dịch vụ",{themes:"error"})
        })




        $scope.getLstOperatorTelco({});

    }

    $scope.newCustomer={};
    $scope.searchCustomer='';
    $scope.sbcCusParam = {};

    $scope.status={0:"STATUS.0",1:"STATUS.1",2:"STATUS.2"}
    // Init list customer
    $scope.accountCols = [

        {field: "companyname", title: "CUSTOMER.COMPANYNAME", sortable: "cus_name", show: true},
        {field: "enterprise_number", title: "CUSTOMER.ENTERPRISE_NUMBER", sortable: "enterprise_number", show: true},
        {field: "service_name", title: "CUSTOMER.SERVICE_NAME", sortable: "service_name", show: true},
        // {field: "send_statistics", title: "CUSTOMER.SEND_STATISTICS", sortable: "id", show: false},
        {field: "created_at", title: "Ngày tạo", sortable: "created_at", show: true},
        {field: "updated_at", title: "Cập nhật", sortable: "updated_at", show: true},
        {field: "action", title: "CRUD.TITLE", sortable: "action", show: true},
    ];
    // FIX 20180905
    $scope.customerCols = [

        {field: "companyname", title: "CUSTOMER.COMPANYNAME", sortable: "cus_name", show: true},
        {field: "status", title: "LBL.STATUS.TITLE", sortable: "status", show: true},
        {field: "charge_result", title: "T/t cước", sortable: "charge_result", show: true},
        {field: "enterprise_number", title: "CUSTOMER.ENTERPRISE_NUMBER", sortable: "enterprise_number", show: true},
        {field: "service_name", title: "CUSTOMER.SERVICE_NAME", sortable: "service_name", show: true},

        {field: "created_at", title: "Ngày tạo", sortable: "created_at", show: true},
        {field: "updated_at", title: "Cập nhật", sortable: "updated_at", show: true},
        {field: "total", title: "Lũy kế tháng", sortable: "total", show: true},
        {field: "action", title: "CRUD.TITLE", sortable: "action", show: true},
    ];

    $scope.lstBlockState=[

        {id:"0", title:$filter('translate')('LBL.STATUS.0')},
        {id:"1", title:$filter('translate')('LBL.STATUS.1')},
        {id:"2", title:$filter('translate')('LBL.STATUS.2')}
    ]


    $scope.lstSipProfile=[
        {id:2, title:"Public Internet"},
        {id:3, title:"Office WAN"},
        {id:4, title:"MPBN"}

    ]

	$scope.cssCustomerTableCols= function(col)
	{
		switch (col.field) {
			case "total":
				return "text-right";
				break;
			case "companyname":
				return "w-25";
				break
			default:
				return;
		}
	}


    $scope.addNewCustomerForm = function () {
        $location.path('addCustomer');
        // Get Service
    }

    $scope.editCustomerForm=function (data) {
        $scope.editCustomer=angular.copy(data);
        $scope.editCustomer.operator_telco_id= angular.copy(data.operator_telco_id?data.operator_telco_id:"");
        $("#editCustomerForm").modal('show');

    }

    $scope.onEditCustomer= function (data) {
        var res=ApiV1.editCustomer(data)
        res.then(function (resData) {
            if(resData.data.status==true)
            {
                $("#editCustomerForm").modal('hide');
				$scope.currentCustomer= data;
				$.jGrowl("Cập nhật thành công");
				return;
            }
			$.jGrowl("Cập nhật lỗi")
        }, function (e) {
            if(e.status==422)
            {
                $scope.currentCustomer.error=e.data.errors;
            }

        })

    }


    $scope.closeCustomer=function()
	{
		$scope.currentCustomer= null;
		$scope.CusHotlineTable.settings().dataset = [];
		// $scope.CusHotlineTable.reload();
	}

    $scope.editCustomerEnterprise= function (data) {
        $scope.editCustomerEnterPrise={'enterprise_number':data};
        $("#editCustomerEnterpriseNumberForm").modal('show');

    }
    $scope.onEditCustomerEnterprise = function (data) {
		var  res = ApiV1.changeCustomerIdentity(data)
        res.then(function (resData) {
                if (resData.data.status == true) {
                    $("#editCustomerEnterpriseNumberForm").modal('hide');
                    $scope.currentCustomer.enterprise_number = data.new_enterprise_number;
                    $.jGrowl("Cập nhật thành công");
                    return;
                }
			$.jGrowl("Cập nhật không thành công");

            }, function (error) {
                $scope.editCustomerEnError = error.data.errors;

            }
        )
    }
    $scope.getListService = function (data) {
       var res = ApiV1.getServices(data);
        res.then(function (resData) {
                $scope.lstServices = resData.data.data;
            }
        );
    }

    $scope.openQuantityLimit= function () {
        if($scope.userAgent.role!=1 && $scope.userAgent.role!=6)
        {
            return;
        }
        $("#quantityLimitForm").modal('show');

    }

    $scope.saveFeeLimit= function (data) {
        var postData={'enterprise_number':$scope.currentCustomer.enterprise_number,
        'limit_amount':data.limit_amount
        };

		var res= ApiServices.saveFeeLimit(postData);
       res.then(function (result) {
           $("#quantityLimitForm").modal('hide');
           $.jGrowl("Cập nhật hạn mức thành công");
           $scope.viewCustomer($scope.currentCustomer);

       }, function (error) {
           $scope.feeLimitError= error.data.errors;

       })

    }
    $scope.editCustomerService = function (data) {
        // $scope.editCustomerEnterPrise={'enterprise_number':data};
        $("#editCustomerServiceCodeForm").modal('show');
        $scope.getListService({});
    };
    $scope.onEditCustomerService = function (data) {
        data.enterprise_number = $scope.currentCustomer.enterprise_number;
		var res = ApiV1.changeCustomerProductCode(data)
        res.then(function (resData) {
                if (resData.data.status == true) {
                    $("#editCustomerServiceCodeForm").modal('hide');
                    $scope.currentCustomer.product_code = data.new_product_code;
                    $scope.editCustomerSCError = null;
                    $scope.getCustomerServiceConfig(data.new_product_code);
					$.jGrowl("Cập nhật  thành công");
					return;
                }
				$.jGrowl("Cập nhật không thành công");

			}, function (error) {
                if (error.status == 422) {
                    $scope.editCustomerSCError = error.data.errors;

                }
                else if(error.status==500)
                {
					$.jGrowl(error.data.message);
                }
            }
        )
    }

    $scope.onRemoveCustomer= function(data)
    {

		var dataToremove = angular.copy(data);
		var res = ApiV1.removeCustomer(dataToremove);
		res.then(function (resData) {

			$.jGrowl("Hủy khách hàng thành công")
			$("#removeCustomerModal").modal("hide");
			$scope.viewCustomer($scope.currentCustomer);
		}, function (e) {
			$.jGrowl("Hủy khách hàng không thành công", {themes: "error"})
		});


    }
    $scope.changeCfuStatus= function(customer,status)
    {

		var flagStatus=  status==1?'Mở':'Tắt';
    	var confirmCfu= confirm('Bạn muốn '+ flagStatus+' gọi call forward, voicemail cho khách hàng này');

    	if(!confirmCfu)
		{
			return;
		}


		var dataToChange = {'enterprise_number':customer.enterprise_number, cfu:status};
		var res = ApiServices.postChangeCfu(dataToChange);
		res.then(function (resData) {

			if(resData.data && resData.data.status)
			{
				customer.cfu= status;
				$.jGrowl(flagStatus+" gọi call forward, voicemail thành công")
			}


			$scope.viewCustomer($scope.currentCustomer);
		}, function (e) {
			$.jGrowl(flagStatus+" gọi call forward, voicemail không thành công.Vui lòng thử lại", {themes: "error"})
		});


    }

	$scope.removeCustomer = function () {

		$scope.removeCustomerModalData = {};
		$scope.removeCustomerModalData.enterprise_number = $scope.currentCustomer.enterprise_number;
		$scope.removeCustomerModalData.reason = "";

		$("#removeCustomerModal").modal("show");

	}


	$scope.onChangeStatus= function(data)
    {

		var   dataPost =angular.copy(data);

		var    res = ApiV1.changeCustomersStatus(dataPost);
		res.then(function (resData) {

			$.jGrowl("Thay đổi trạng thái thành công")
			$("#changeCustomerStatusModal").modal("hide");
			$scope.viewCustomer($scope.currentCustomer);

		}, function (e) {
			if(e.status==422)
			{


				if(e.data.errors.reason)
				{
					$.jGrowl("Đổi trạng thái khách hàng không thành công. <br> Lý do không được chứa ký tự đặc biệt hoặc quá dài ",{themes:"error"})
				}
				else
				{
					$.jGrowl("Đổi trạng thái khách hàng không thành công<br> Dữ liệu không hợp lệ",{themes:"error"})
				}


				return;
			}
			$.jGrowl("Đổi trạng thái khách hàng không thành công",{themes:"error"})
		})

    }

    $scope.changeStatus = function (data) {

        $scope.changeCustomerStatusData={};

        if(data==1)
        {
            var newStatus=0;
        }
        else
        {
            var newStatus=1;
        }

		$scope.changeCustomerStatusData.new_status= newStatus;
		$scope.changeCustomerStatusData.enterprise_number= $scope.currentCustomer.enterprise_number;

		$("#changeCustomerStatusModal").modal("show");


    }

    $scope.getCustomerServiceConfig=function (data) {
		var    res = ApiV1.getServiceByCode({'product_code':data});
        res.then(function (data) {
            $scope.serviceConfig = data.data.data;

        })

    }


    // V1.2 Model ===============================================================
    $scope.getListCustomers = function () {


        if (!$scope.sbcCusTable) {
            $scope.sbcCusTable = new ngTableParams({
                    page: 1, // show first page
                    count:50   // count per page

                }, {
                    counts: [50,100,200,300],
                    getData: function ($defer, params) {
                        $scope.sbcCusParam.page = params.page();
                        $scope.sbcCusParam.count = params.count();
                        $scope.sbcCusParam.sorting = $scope.sbcCusTable.orderBy().toString();
                        $scope.sbcCusParam.tableGroupBy = $scope.sbcCusTable.tableGroupBy;
						$("#loading").modal("show");
                        ApiV1.getCustomersV2($scope.sbcCusParam).then(function (response) {
                            $("#loading").modal("hide");

                            $scope.lstCustomer = response.data.data.data;
                            $scope.lstCustomerCount = response.data.data.count;
                            $scope.userAgent= response.data.data.user;

                            if (response.data.data.count <= $scope.sbcCusTable.parameters().count) {
                                $scope.sbcCusTable.parameters().page = 1;
                            }
                            params.total($scope.lstCustomerCount);
                            $defer.resolve($scope.lstCustomer);
                        }, function (response) {
								$("#loading").modal("hide");
                            $scope.lstCustomer = [];
                            $scope.lstCustomerCount = -1;
                        }
                        );
                    }
                }
            )
        }
        else {
            $scope.sbcCusTable.reload();
        }



        // END=======================================

    };

    /** Load
     *
     */
    $scope.getListCustomers();

    $scope.viewPage= function (page) {

        $scope.getListCustomers();
    }

    $scope.searchCus= function () {
        $scope.sbcCusTable.parameters().page = 1;
        $scope.getListCustomers();

    }

    $scope.downCus= function () {

        $scope.sbcCusParam.download=1;
        $window.open('/exportCustomer?'+ $.param($scope.sbcCusParam));


    }

    $scope.sortValue =function (data) {

        $scope.sbcCusParam.col= data;

        $scope.getListCustomers();

    }


    /** View customer and configuration
     *
     * @param data
     */

    $scope.sipConfig = function (data) {

        console.log(data);
        $scope.sip={};
        $scope.sip.hotline=data.hotline_number;
        $scope.sip.cus_id=data.cus_id;
        $scope.sip.caller_group_master=data.caller_group_master;


        // alert(data.hotline_number )
       var  res = ApiServices.getSipConfigByCaller(data.hotline_number)
        res.then(function (rs) {

            $scope.lstVendor= rs.data.vendor;

            if(rs.data.current_vendor && rs.data.current_vendor.vendor_id>0 )
            {
                $scope.sip.vendor_id=rs.data.current_vendor.vendor_id;
            }
            else
            {
                $scope.sip.vendor_id= null;
            }

            $scope.sip.ip_auth=rs.data.acl.ip_auth;

            $scope.sip.ip_proxy=rs.data.acl.ip_proxy;
			if(rs.data.acl_backup)
			{
				$scope.sip.ip_auth_backup=rs.data.acl_backup.ip_auth;
				$scope.sip.ip_proxy_backup=rs.data.acl_backup.ip_proxy;

			}

            $scope.sip.description=rs.data.acl.description;
            $scope.sip.block_regex_callee=rs.data.acl.block_regex_callee;
            $scope.sip.allow_regex_callee=rs.data.acl.allow_regex_callee;
			$scope.sip.caller_group=rs.data.group?1:0;
            var  callerGroup = rs.data.group ? rs.data.group : {};

            console.log("CALLER GROUP",callerGroup)
            $scope.sip.callee_regex= callerGroup.callee_regex;
            // $scope.sip.caller_group_master= callerGroup.caller_group_master;

            console.log("SIP", $scope.sip)
			var sipCallin=rs.data.routing.length==2?rs.data.routing[1]:{}
            var sipCallOut=rs.data.routing.length>0?rs.data.routing[0]:{}


            $scope.sip.destination=sipCallin.destination;
            $scope.sip.telco_destination=sipCallOut.destination;
            $scope.sip.profile_id_backup=parseInt(sipCallin.i_sip_profile);
			$("#sipConfigForm").modal('show');


        }, function (er) {

        $.jGrowl("Lỗi. Không tải được cấu hình hotline")
        })

        // $scope.sip = {'hotline': data.hotline_number, 'cus_id': data.cus_id, 'sip_config': $scope.sip_config};

    }
    $scope.openModalChangeSite= function(data)
    {

        if(data.server_profile != SERVER_PROFILE)
        {
            $.jGrowl("Khách hàng đã hoạt động trên server "+ data.server_profile +". ")
            return;

        }

        $scope.changeServer={};
        $scope.changeServer.site_id=null;
        $scope.changeServer.cus_id=data.id;
        $scope.changeServer.cus_name=data.cus_name;
        $scope.changeServer.enterprise_number=data.enterprise_number;

        $scope.lstServers=[{id:SERVER_PROFILE_BACKUP,server_name:" SERVER " + SERVER_PROFILE_BACKUP}];


        $("#changeSiteCustomerForm").modal("show");
    }


    $scope.onChangeServerCustomer= function(data)
    {


        var isError=false;
        if(!data.site_id)
        {
            isError= true;
        }
        if(isError)
        {
           $.jGrowl("Thông tin còn thiếu. Bạn chưa chọn server");
            return;
        }

        var res= ApiServices.saveMoveCustomerServer(data);
        res.then(function (value) {
            // alert("Thay đổi thành công, khách hàng sẽ tạm ngưng dịch vụ trên hệ thống này")

			if(value.data && value.data.status==false)
			{
				$.jGrowl(value.data.message);
				return;
			}

			$.jGrowl("Chuyển đổi cụm máy chủ cho khách hàng thành công. Khách hàng sẽ tạm ngưng trên cụm máy chủ "+ SERVER_PROFILE);
			$("#changeSiteCustomerForm").modal("hide");
			$scope.currentCustomer=null;
			$scope.sbcCusTable.reload();

        }, function (reason) {

			$.jGrowl("Chuyển cụm máy chủ không thành công. Có lỗi xảy ra");
			// alert("Change error")
        })


    }


    $scope.onChangePauseState=function(data)
    {

		var dataPost = angular.copy(data)

		var res = ApiV1.postChangePauseState(dataPost);
		res.then(function (value) {

			// $scope.currentCustomer.pause_state = value && value.data ? value.data.data : "10";

			$scope.viewCustomer($scope.currentCustomer);

			$("#changePauseStateModal").modal("hide");
			$.jGrowl("Thay đổi trạng thái thành công");

		}, function (e) {
			if(e.status==422)
			{


				if(e.data.errors.reason)
				{
					$.jGrowl("Đổi trạng thái khách hàng không thành công. <br> Lý do không được chứa ký tự đặc biệt hoặc quá dài ",{themes:"error"})
				}
				else
				{
					$.jGrowl("Đổi trạng thái khách hàng không thành công<br> Dữ liệu không hợp lệ",{themes:"error"})
				}


				return;
			}


			$.jGrowl("Không thực hiện lệnh được, có lỗi xảy ra", {theme: "errors"});
		})
    }

	$scope.changePauseState = function (direction, action) {

		$("#changePauseStateModal").modal("show");
		$scope.changePauseStateCustomer = {};
		$scope.changePauseStateCustomer.direction = direction;
		$scope.changePauseStateCustomer.action = action;
		$scope.changePauseStateCustomer.enterprise_number = $scope.currentCustomer.enterprise_number;
		$scope.changePauseStateCustomer.reason = "";

	}


	$scope.onChangePauseStateHotline= function(data)
    {
		var dataPost= angular.copy(data);

		var res= ApiV1.postChangePauseStateHotline(dataPost);
		res.then(function (value) {

			$("#changePauseStateHotlineModal").modal("hide");

			$scope.viewCustomer($scope.currentCustomer);

			$.jGrowl("Thay đổi trạng thái  thành công")
		}, function (e) {
			if(e.status==422)
			{
				if(e.data.errors.reason)
				{
					$.jGrowl("Đổi trạng thái  không thành công. <br> Lý do không được chứa ký tự đặc biệt hoặc quá dài ",{themes:"error"})
				}
				else
				{
					$.jGrowl("Đổi trạng thái không thành công<br> Dữ liệu không hợp lệ",{themes:"error"})
				}


				return;
			}

		$.jGrowl("Thay đổi trạng thái không thành công")
		})
    }


    $scope.changePauseStateHotline= function(line, direction, action)
    {
        $scope.changePauseStateHotlineData= {};
        $scope.changePauseStateHotlineData.enterprise_number = $scope.currentCustomer.enterprise_number;
        $scope.changePauseStateHotlineData.hotline_number= line.hotline_number;
        $scope.changePauseStateHotlineData.direction= direction;
        $scope.changePauseStateHotlineData.action= action;
        $scope.changePauseStateHotlineData.reason="";

        $("#changePauseStateHotlineModal").modal("show");



    }



    $scope.saveSip = function (data) {

        if(data.caller_group==1 &&!data.caller_group_master)
        {
            $.jGrowl("Bạn chưa chọn số chủ nhóm")
            return;
        }

        if(data.caller_group==1 && data.hotline==data.caller_group_master)
        {
            $.jGrowl("Số chủ nhóm không được trùng với số nhánh")
            return;
        }
        data.enterprise_number=$scope.currentCustomer.enterprise_number;

        if (data.enterprise_number) {
         var   res = ApiServices.postSipRouting(data);
            res.then(function (rs) {
                if (rs.data.status == true) {

                    $scope.viewCustomer($scope.currentCustomer);

                    $("#sipConfigForm").modal('hide');

					$.jGrowl("Cập nhật thành công")

                }
            },function (reason) {
            	if(reason.status==422)
				{


					$.jGrowl(reason.data.message)
					return;
				}

				$.jGrowl("Cập nhật thành công")

			});

        }
    }
    $scope.activeCustomerHotline = function () {
        alert('hotline');
    }
    $scope.activeCustomerOption = function () {
        alert('Option');
    }
    $scope.activeCustomerQuantity = function () {
        alert('quantity');
    }
    $scope.viewLogCustomer = function (id) {
        var data = {'table': 'customers', 'id': id};
        res = ApiServices.getActivity(data)
        res.then(function (r) {
            $scope.currentCustomer.log = r.data;
        })
    }
    $scope.viewBilling = function (data) {
        dataShare.data.billing = data;
        $location.path("/billing");
    }
    $scope. viewCustomer = function (data) {
    	$scope.newHotlines_show=false;
        console.log("current data ", data)

        $scope.currentCustomer = angular.copy(data); // reset before loaded
        // $scope.viewLogCustomer(data.id);
        if ($scope.currentCustomer.product_code  ) {
            $scope.getCustomerServiceConfig($scope.currentCustomer.product_code);
        }
        // Get Sip and Trunking service
        if ($scope.currentCustomer.enterprise_number && $scope.currentCustomer.status !=2 ) {
            res = ApiServices.getConfigByCustomer($scope.currentCustomer.enterprise_number);
            res.then(function (data) {
                $scope.currentCustomer.cog = data.data;

                if(data.data &&data.data.customer)
                {
					$scope.currentCustomer.status= data.data.customer.blocked;
					$scope.currentCustomer.pause_state= data.data.customer.pause_state;
                }
				$scope.fee={limit_amount:0};
                if(data.data.fee_limit)
                {

                    $scope.fee.limit_amount= data.data.fee_limit.limit_amount;
                }
            })



        }
        // if ($scope.currentCustomer.enterprise_number) {
        //     res = ApiServices.getFeeByEntNumber($scope.currentCustomer.enterprise_number);
        //     res.then(function (data) {
        //         $scope.currentCustomer.fee = data.data;
        //         var call = 0, sub = 0, sms = 0;
        //         if (data.data.sub.length > 0) {
        //             sub = parseFloat(data.data.sub[0].sum_sub);
        //         }
        //         if (data.data.sms.length > 0) {
        //             sms = parseFloat(data.data.sms[0].sum_sms);
        //         }
        //         if (data.data.call.length > 0) {
        //             call = parseFloat(data.data.call[0].sum_call);
        //         }
        //         $scope.currentCustomer.fee.total_amount = call + sub + sms;
        //     })
        // }


		$scope.getListHotline();
    };

    $scope.sbcHotlineTableParam={};

	$scope.getListHotline=function()
	{


		if (!$scope.CusHotlineTable) {
			$scope.CusHotlineTable = new ngTableParams({
					page: 1, // show first page
					count:10   // count per page

				}, {
					counts: [20,50,100],
					getData: function ($defer, params) {
						$scope.sbcHotlineTableParam.page = params.page();
						$scope.sbcHotlineTableParam.count = params.count();
						$scope.sbcHotlineTableParam.enterprise_number = $scope.currentCustomer.enterprise_number;
						// $scope.sbcHotlineTableParam.sorting = $scope.sbcHotlineTable.orderBy().toString();
						$scope.sbcHotlineTableParam.tableGroupBy = $scope.CusHotlineTable.tableGroupBy;
						var postData=  angular.copy($scope.sbcHotlineTableParam);



						ApiServices.getListHotLinesByCustomers(postData).then(function (response) {


								var dataRes= response.data.data;
								$scope.listHotlines=[];

								for (var i = 0; i < dataRes.length ; i++) {
									dataRes[i].created_at= moment(dataRes[i].created_at).format("DD/MM/YYYY HH:mm:ss")
									$scope.listHotlines.push(dataRes[i]);
								}



								$scope.listHotlinesCount = response.data.count;


								if (response.data.count <= $scope.CusHotlineTable.parameters().count) {
									$scope.CusHotlineTable.parameters().page = 1;
								}
								params.total($scope.listHotlinesCount);
								$defer.resolve($scope.listHotlines);
							}, function (response) {
								$scope.listHotlines = [];
								$scope.listHotlinesCount = 0;
								$.jGrowl("Điều kiện tìm kiếm không hợp lệ hoặc có lỗi xảy ra")


							}
						);
					}
				}
			)
		}
		else {
			$scope.CusHotlineTable.reload();
		}

	}

    $scope.viewCustomerFee = function (tag) {
        $scope.selectedFee = [];
        if (tag == "sms") {
            $scope.selectedFee = $scope.currentCustomer.fee.sms;
        }
        else if (tag == "call") {
            $scope.selectedFee = $scope.currentCustomer.fee.call;
        }
        else if (tag == "sub") {
            $scope.selectedFee = $scope.currentCustomer.fee.sub;
        }
    }
    $scope.activeAccountHotline = function () {
        $scope.addHotline();
    }
    $scope.addHotline = function () {

		$scope.newHotlines_show= true
		$scope.newHotlines=new newHotlines($scope.currentCustomer)


		console.log($scope.newHotlines);

    };
    $scope.editHotline = function (data) {
        data.edit = true;
        data.cus_id = angular.copy($scope.currentCustomer.id);
        return data;
    }

    $scope.onRemoveHotline =function(data)
    {

		var datatodelete = angular.copy(data);

		res = ApiV1.removeHotline(datatodelete);
		res.then(function (rs) {

		    $scope.viewCustomer($scope.currentCustomer);


			$("#removeHotlineModal").modal("hide");
			$.jGrowl("Xóa hotline thành công")
		}, function (error) {

		})

	}


    $scope.deleteHotline=function (data) {

        $scope.removeHotlineModalData={};
        $scope.removeHotlineModalData.hotline_number=data.hotline_number;
        $scope.removeHotlineModalData.enterprise_number=$scope.currentCustomer.enterprise_number;
        $scope.removeHotlineModalData.reason="";

        $("#removeHotlineModal").modal("show");




    }


    $scope.onChangeHotlineStatus= function(data)
    {
        var dataSend= angular.copy(data);



		var res=ApiV1.changeHotlineStatus(dataSend);
		res.then(function (resData) {
			$("#changeHotlineStatusModal").modal("hide");
			$scope.viewCustomer($scope.currentCustomer);

			$.jGrowl("Thay đổi trạng thái thành công")

		}, function (e) {
			if(e.status==422)
			{
				if(e.data.errors.reason)
				{
					$.jGrowl("Đổi trạng thái  không thành công. <br> Lý do không được chứa ký tự đặc biệt hoặc quá dài ",{themes:"error"})
				}
				else
				{
					$.jGrowl("Đổi trạng thái không thành công<br> Dữ liệu không hợp lệ",{themes:"error"})
				}


				return;
			}


			$.jGrowl("Có lỗi xảy ra ")
        })


	}



    $scope.changeHotlineStatus=function (data) {
 
		var dataSend={};
		if(data.status==0) {
			dataSend.status = 1;
		}
		else  {
			dataSend.status = 0;
		}
		dataSend.enterprise_number=$scope.currentCustomer.enterprise_number;
		dataSend.hotline_number= data.hotline_number;

		$scope.changeHotlineStatusData=dataSend;

		$("#changeHotlineStatusModal").modal("show");




    }



    $scope.onSaveHotline= function (data) {
        res= ApiV1.addCustomerHotlines(data)
        res.then(function (resData) {
            if(resData.data.status)
            {

				$.jGrowl("Cập nhật  thành công");


				$scope.viewCustomer($scope.currentCustomer);

				data.hotline_numbers=""
				data.error= null;
				return;
			}
			$.jGrowl("Cập nhật không thành công");
        },function (e) {
            if(e.status==422)
            {
                data.error=e.data.errors;
                $.jGrowl("Cập nhật không thành công")

            }

        })

    }
    $scope.saveHotline = function (data) {

        // Set to service
        if(data.id) {
            res = ApiServices.updateServiceCustomerHotline(data.hotline_number, data)
        }
        else
        {
            res = ApiServices.postServiceCustomerHotline(data)
        }
        res.then(function (rdata) {
            if (rdata.status == 200) {
                delete data.edit;
                data.updated_at = rdata.data.data.updated_at;
                data.id = rdata.data.id;
                if (rdata.data.data.created_at) {
                    data.created_at = rdata.data.data.created_at;
                }
            }
        })
    };
    $scope.activeAccountQuantity = function () {
        //  $scope.currentCustomer.cog.quantities = [];
        $scope.addQuantity($scope.currentCustomer);
    }
    $scope.addQuantity = function (data) {
        if (!$scope.currentCustomer.cog.quantities) {
            $scope.currentCustomer.cog.quantities = [];
        }
        $scope.currentCustomer.cog.quantities.push({
            "edit": true,
            "cus_id": data.id,
            "quantity_config_id": null,
            "description": "",
            "begin_use_date":  $filter('date')(new Date(),'yyyy-MM-dd', 'GMT+07'),
            "status": "",
            "init_charge": "",
            "resub": "0",
            "type": ""
        });
    };
    $scope.editQuantity = function (data) {
        data.edit = true;
        data.cus_id = angular.copy($scope.currentCustomer.id);
        return data;
    }
    $scope.cancelQuantity= function (data,index) {

        data.edit=false;
        if(!data.quantity_config_id)
        {
            $scope.currentCustomer.cog.quantities.splice(index,1)
        }



        
    }

    $scope.saveQuantity = function (data) {
        data.enterprise_number = angular.copy($scope.currentCustomer.enterprise_number);
        // Set to service
        res = ApiServices.postServiceCustomerQuantity(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {
                delete data.edit;
                data.updated_at = rdata.data.data.updated_at;
                data.id = rdata.data.id;
                if (rdata.data.data.created_at) {
                    for (var i = 0; i < $scope.serviceConfig.quantity_price.length; i++) {
                        if ($scope.serviceConfig.quantity_price[i].id == data.quantity_config_id) {
                            data.description = $scope.serviceConfig.quantity_price[i].description;
                        }
                    }
                    data.created_at = rdata.data.data.created_at;
                }
            }
        })
    };
    $scope.activeAccountOption = function () {
        //  $scope.currentCustomer.cog.quantities = [];
        $scope.addOption($scope.currentCustomer);
    }
    $scope.addOption = function (data) {
        $scope.currentCustomer.cog.options = [];
        $scope.currentCustomer.cog.options.push({
            "edit": true,
            "cus_id": data.id,
            "begin_charge_date": new Date(),
            "status": "",
            "last_charge_date": "",
            "last_try_charge_date": "",
            "last_charge_sub_status": "",
            "extension_count": "",
            "call_record_storage_count": "",
            "data_storage_count": "",
            "api_count": "",
            "api_rpm_count": "0",
            "softphone_3c_count": "0"
        });
        $("#0_begin_charge_date").focus();
    };
    $scope.editOption = function (data) {
        data.edit = true;
        data.cus_id = angular.copy($scope.currentCustomer.id);
        return data;
    }
    $scope.saveOption = function (data) {
        data.service_config_id=$scope.currentCustomer.service_id;
        data.enterprise_number =$scope.currentCustomer.enterprise_number;


        // Set to service
        res = ApiServices.postServiceCustomerOption(data)
        res.then(function (rdata) {
            if (rdata.status == 200) {
                delete data.edit;
                data.updated_at = rdata.data.data.updated_at;
                data.id = rdata.data.id;
                if (rdata.data.data.created_at) {
                    data.created_at = rdata.data.data.created_at;
                }

                $.jGrowl("Cập nhật thành công");
                $scope.viewCustomer($scope.currentCustomer);
            }
        })
    };
    $scope.blockAccount = function (row, next) {
        var data = {enterprise_number: row.enterprise_number, blocked: next};
        res = ApiServices.postAccountBlock(data);
        res.then(function (data) {
            row.blocked = data.data;
        })
    }
    /** Add Sip trunk
     *
     * @param data
     */
    $scope.addSipRouting = function (id) {
        dataShare.data.customerID = id;
        $location.path("/addSip");
    }
    $scope.editSipRouting = function (data) {
        $scope.addSipRouting(data);
    }
    $scope.editAccount = function (id) {
        dataShare.data.customer = id;
        $location.path("/addAccount");
    }

    $scope.changeRedAlert= function (customer) {



        var action=1;
        if(customer && customer.baodo==1)
        {

            action=0;

        }

        var postData= {'action':action,'enterprise_number':customer.customer.enterprise_number};

        customer.baodo_pending= true;
        ApiServices.saveRedWarning(postData).then(function (value) {
            if(value.data.status)
            {
                customer.baodo= action;
				customer.baodo_pending= false;
			}
        })


	}

    $scope.tosServiceLinked=function () {
		$scope.getListService({});
        $("#tosServiceAdded").modal("show");
        $scope.tosService={active:1, service_key:"",cus_id:$scope.currentCustomer.id, enterprise_number:$scope.currentCustomer.enterprise_number};



	}

	$scope.deActiveApps=function (apps) {
		var postData= {active:0, service_key:apps.service_key,cus_id:apps.cus_id, enterprise_number:$scope.currentCustomer.enterprise_number};
		$scope.onAddTosService(postData);

	};

    $scope.activeApps= function (apps) {

        var postData= {active:1, service_key:apps.service_key,cus_id:apps.cus_id, enterprise_number:$scope.currentCustomer.enterprise_number};
		$scope.onAddTosService(postData);

	}

	$scope.onAddTosService= function (apps) {

		if(apps.service_key !="")
		{
			var res= ApiServices.postServiceAdded(apps);
			res.then(function (value) {
				$("#tosServiceAdded").modal("hide");
				$scope.viewCustomer ($scope.currentCustomer);
				$.jGrowl("Cập nhật thành công")

			}, function (reason) {
			    alert("Error");
            });

		}


	}

	$scope.sendRechargeCustomer= function (customer) {

        var x= confirm("Bạn muốn gửi lệnh charge cước lại");
        if(x)
        {
			var postData={};
			postData.enterprise_number= customer.enterprise_number;
			ApiServices.setRechargeCustomer(postData).then(function (value) {
				if(value.data.status)
				{
					alert("Gửi lệnh charge bù thành công");
					customer.recharge_send= true;
				}
				else
				{
					alert("Gửi lệnh thất bại"+ value.data.message);
				}
			}, function (reason) {
				alert("Gửi lệnh lỗi");
			})
        }


	}



});


cms3c.controller('customerControllerAdd', function ($scope, ApiServices, ApiV1, $location, dataShare, loginCheck) {
	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});



	$scope.lstSipProfile=[
		{id:2, title:"Public Internet"},
		{id:3, title:"Office WAN"},
		{id:4, title:"MPBN"}

	]



    $scope.newCustomer= new Customer();

	$scope.customer = {};



    $scope.getListService= function (data) {
        res = ApiV1.getServices(data);
        res.then(function (resData) {
                $scope.lstServices = resData.data.data;

            }
        );

    }


    $scope.lstOperatorTelco=[];
    $scope.getLstOperatorTelco= function () {
       ApiServices.getOperatorTelco().then(result=>{
       	$scope.lstOperatorTelco= result.data?result.data.data:[];
           $scope.lstOperatorTelco.unshift({'id':"",'description':"Chọn"})
	   }, reason => {

	   })

    }

    $scope.getLstOperatorTelco({});

    $scope.getListService({});


    // V1.2 Model ===============================================================
    $scope.closeFormAdd= function () {
        $location.path('/accounts');

    }
    $scope.onAddCustomer= function () {

        if(!$scope.entity.ADD_CUSTOMER)
        {
			$.jGrowl("Bạn không có quyền tạo khách hàng")
        }
       var res= ApiV1.addCustomer($scope.newCustomer);
        res.then(function (data) {

            $scope.newCustomerError=null;
            $.jGrowl("Tạo khách hàng thành công")
            $location.path('/accounts')

        }, function (error) {
            $scope.newCustomerError=(error.data.errors);

        })

    }



});


function Customer() {
    this.profile_id_backup= 2;
    this.status            = "0";
    this.fee_limit= null;
    this.split_contract= null;
    this.hotline_numbers= "";
    this.destination="";
    this.ip_proxy= "";
    this.ip_auth="";
    this.product_code="";
    this.email= "";
    this.phone1= "";
    this.addr="";
    this.companyname="";
    this.enterprise_number="";
    this.cus_name=""
    this.baodo=0;
    this.operator_telco_id="";
    this.telco_destination="";


}


function  newHotlines(data) {
	this.enterprise_number=data.enterprise_number;
	this.hotline_numbers= "";
    this.operator_telco_id=data.operator_telco_id?data.operator_telco_id:"";

}