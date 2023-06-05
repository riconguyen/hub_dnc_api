cms3c.controller('logController', function ($scope, ApiServices,$filter,loginCheck, ApiUsers, NgTableParams, $window,$sce) {

	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});

	loginCheck.getRole().then(function (result) {
		$scope.userLogin = result.data ? result.data : {};
		$scope.userLogin.role = result.data.id;
	})



	$scope.logParams={};
	$scope.logTableCols=[
		{field: "created_at", title: "Ngày ", sortable: "created_at", show: true},
		{field: "username", title: "Người tác động", sortable: "username", show: true},
		{field: "action", title: "Hành động", sortable: "action", show: true},
		{field: "enterprise_number", title: "Số đại diện", sortable: "enterprise_number", show: true},
		{field: "hotline_number", title: "Hotline", sortable: "hotline_number", show: true},
		// {field: "data_table", title: "Table", sortable: "data_table", show: true},
		// {field: "data_id", title: "ID chính", sortable: "data_id", show: true},



		// {field: "level", title: "Level", sortable: "level", show: true},
		{field: "description", title: "Ghi chú", sortable: "description", show: true},

	]




	$scope.getLogs = function () {
		// res = ApiUsers.viewAll()
		// res.then(function (data) {
		//     $scope.users = data.data;
		// })


		$("#loading").modal("show");
		if (!$scope.tableLogs) {
			$scope.tableLogs = new NgTableParams({
					page: 1, // show first page
					count:20   // count per page

				}, {
					counts: [20,50,100,200,300],
					getData: function ($defer, params) {
						$scope.logParams.page = params.page();
						$scope.logParams.count = params.count();
						$scope.logParams.sorting = $scope.tableLogs.orderBy().toString();
						$scope.logParams.tableGroupBy = $scope.tableLogs.tableGroupBy;
						ApiServices.getLogs($scope.logParams).then(function (response) {
							$("#loading").modal("hide");
								$scope.lstLogs = response.data.data;
								$scope.totalLogs = response.data.count;
								$scope.lstActions= response.data.action;

									for(var i=0; i< $scope.lstLogs.length; i++)
									{
										if($scope.lstLogs[i].description && $scope.lstLogs[i].description.indexOf("[R]"))
										{
											$scope.lstLogs[i].description=  $sce.trustAsHtml($scope.lstLogs[i].description.replace("[R]","<br><b>Lý do</b>: "));
										}



									}



								if (response.data.count <= $scope.tableLogs.parameters().count) {
									$scope.tableLogs.parameters().page = 1;
								}
								params.total($scope.totalLogs);
								$defer.resolve($scope.lstLogs);
							}, function (response) {
							$("#loading").modal("hide");
								$scope.lstLogs = [];
								$scope.totalLogs = -1;
							}
						);
					}
				}
			)
		}
		else {
			$scope.tableLogs.reload();
		}



	}


	$scope.getLogs();
	});
