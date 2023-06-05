
var LIMIT_CHARGING_ACCOUNT= 10;

var Charging= function () {
	this.step=1;
	this.data="";
}

var ChargingEnterprise= function (enterprise, number) {
	this.enterprise_number=enterprise?enterprise:null;
	this.value=number?number:null


}


cms3c.controller('chargingController', function ($scope, ApiServices,$filter,loginCheck, ApiUsers, NgTableParams, $window) {
	var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});


	$scope.charging= new Charging();

	$scope.getDynamicRows = function() {
		var ele = (angular.element(document.querySelector("#chargingArea")));
		return ele.val().split(/\r|\r\n|\n/);
	}

	$scope.onSearchCharge = function () {
		var data = $scope.getDynamicRows();
		var dataToCheck ={data:[], lstEnterprise:[]};
		for (var i = 0; i < data.length; i++) {
			if (data[i] && data[i].includes("|")) {

				var newData = data[i].split('|');
				dataToCheck.data.push(new ChargingEnterprise(newData[0].trim(), newData[1].trim()));
				dataToCheck.lstEnterprise.push(newData[0].trim());

			}

		}

		if(dataToCheck.data.length> LIMIT_CHARGING_ACCOUNT)
		{
			$.jGrowl("Số lượng thuê bao cần kiểm tra quá "+LIMIT_CHARGING_ACCOUNT+"  vui lòng giảm bớt để tránh sai sót")
			return;
		}


		ApiServices.postRequestCheckCharging(dataToCheck).then(function (value) {

			$scope.charging.reviewData= value.data&& value.data.data?value.data.data:[];
			$scope.charging.step=2;
			console.log(value.data);
		}, function (reason) {
			$.jGrowl("Có lỗi xảy ra, không thể kiểm tra được cước phát sinh")
		})


	}


	$scope.onSendCharging= function () {
		var data={"data":$scope.charging.reviewData};
		ApiServices.postRequestCharge(data).then(function (value) {
			$.jGrowl("Đã gửi charge thành công, xem trong lịch sử tác động các giá trị thay đổi")
			$scope.charging.result= value.data&& value.data.data?value.data.data:[];
			$scope.charging.step=3;
			$scope.charging.resultDate=moment().format("YYYY-MM-DD HH:mm:ss");
		},function (reason) {
			$.jGrowl("Gửi charge không thành công, có lỗi xảy ra")
		})

	}


});
