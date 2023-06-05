
cms3c.controller('blackListController', function ($scope, ApiServices,$filter,loginCheck, ApiUsers, ngTableParams, $window) {
	$scope.entity = {};
	var Token = loginCheck.getEntity().then(function (value) {
		$scope.entity = value.entity;
		$scope.authUser = value;
		$scope.lstBlackListNav = [
			{id: "black-list", active:true,  title: "Cấu hình Blacklist", permission: $scope.entity.BLACK_LIST ? true : false},
			{id: "white-list", title: "Cấu hình Whitelist", permission: $scope.entity.BLACK_LIST ? true : false},
		]
	});

	$scope.lstBlackListStatus=[
		{id:1,label:"Thêm"},
		{id:0,label:"Xóa"}
	]

	$scope.blackListTableCols=[
		{col:'phone_no',label:'Số điện thoại'},
		{col:'status',label:'Trạng thái'},
		{col:'updated_at',label:'Ngày cập nhật'},
		{col:'action',label:'Hành động'},
	]



	$scope.openAddBlackListForm= function () {

		$("#addBlackListForm").modal('show');
		$scope.newBlackList={'phone_no_list':'','status':1, datalength:0,successCount:0}

	}

	$scope.openAddWhitelistForm= function () {

		$("#addWhiteListForm").modal('show');
		$scope.newWhiteList={'hotline_numbers':'','white_list':1, datalength:0,successCount:0}

	}

	$scope.onSaveBlackList = function (data) {
		$("#loading").modal("show");
		$scope.uploading = true
		let reFormatData = reformatDataForm(data);
		data.datalength= reFormatData.length;
		if(data.datalength > 1499)
		{
			$.jGrowl("Số lượng bản ghi quá 1500");
			return;
		}

		AsyncDNC(reFormatData, 'form');
	}


	$scope.openImportModal= function () {
		$scope.newBlackList={'list':'', datalength:0,successCount:0}
		document.getElementById('file-input-uploadcsv').value = "";
		$("#importBlackListForm").modal('show');
	}

	$scope.onSaveWhiteList = function (data) {
		$("#loading").modal("show");
		$scope.uploading = true
		let postData = angular.copy(data);



		let lstPhoneNoOk = [];
		let lstPhoneNo = [];
		if (data.hotline_numbers.includes(",")) {
			lstPhoneNo = (data.hotline_numbers).split(",");
		}
		else {
			lstPhoneNo = [data.hotline_numbers];
		}
		lstPhoneNo.map(phoneNo => {
			if (phoneNo && validPhoneNo(phoneNo)) {
				lstPhoneNoOk.push(phoneNo);
			}
		})
		if (lstPhoneNoOk.length == 0) {
			$.jGrowl("Không có số hotline hợp lệ");
			$("#loading").modal("hide");
			$scope.uploading = false;
			return;
		}

		postData.hotline_numbers= lstPhoneNoOk.join();

		ApiServices.postDNCWhitelist(postData).then(result => {
			$.jGrowl("Cập nhật whitelist thành công ")
			$scope.getDNCWhitelist();
			$("#addWhiteListForm").modal('hide');
			$("#loading").modal("hide");
			$scope.uploading = false;
		}, reason => {
			$scope.uploading = false;

			$("#loading").modal("hide");
			$.jGrowl("Cập nhật whitelist không thành công")
		})
	}
	$scope.onRemoveWhiteList = function (data) {


		let confim= confirm(`Bạn muốn xóa hotline: ${data.hotline_number} khỏi whitelist`);
		if(confim)
		{
			$("#loading").modal("show");
			let postData = {hotline_numbers:data.hotline_number, white_list:0};
			ApiServices.postDNCWhitelist(postData).then(result => {
				$.jGrowl(`Đã xóa whitelist ${postData.hotline_numbers} thành công `)
				$scope.getDNCWhitelist();

				$("#loading").modal("hide");

			}, reason => {

				$("#loading").modal("hide");
				$.jGrowl(`Xóa whitelist  ${postData.hotline_numbers}  không thành công`)
			})

		}


	}



	$scope.openImportModal= function () {
		$scope.newBlackList={'list':'', datalength:0,successCount:0}
		document.getElementById('file-input-uploadcsv').value = "";
		$("#importBlackListForm").modal('show');
	}




	$scope.onSaveBlackListImport = function (data) {
		// console.log(data);
		$("#loading").modal("show");
		$scope.uploading = true
		AsyncDNC(data.list, 'file');
	}

	$scope.dncDetailCols=[
		{cssclass:'text-right','field':'id','title':'ID'},
		{cssclass:'','field':'phone_no','title':'Số thuê bao'},
		{cssclass:'','field':'type','title':'Loại'},
		{cssclass:'text-center','field':'created_at','title':'Ngày tạo'},
		{cssclass:'text-center','field':'updated_at','title':'Ngày cập nhật'},
		{cssclass:'','field':'action','title':'Hành động'},


	]

	$scope.dncWhitelistDetailCols=[
		{cssclass:'text-right','field':'id','title':'ID'},
		{cssclass:'','field':'hotline_number','title':'Hotline'},
		{cssclass:'','field':'brand_name','title':'Brand name'},

		{cssclass:'','field':'companyname','title':'Khách hàng'},
		{cssclass:'','field':'enterprise_number','title':'Số enterprise'},
		{cssclass:'text-center','field':'created_at','title':'Ngày tạo'},
		{cssclass:'text-center','field':'updated_at','title':'Ngày cập nhật'},
		{cssclass:'','field':'action','title':'Hành động'},


	]

	$scope.dncType={0:'Chặn tất cả',1:'DNC all', 2:'DNC/Brand', 3:'DNC/Brand Cskh', 4:'Sip thông thường'}
	$scope.dncDetailParams={
		q:""
	}
	$scope.dncWhitelistDetailParams={
		q:""
	}

	$scope.getDNCBlacklist = function () {
		// res = ApiUsers.viewAll()
		// res.then(function (data) {
		//     $scope.users = data.data;
		// })

		$("#loading").modal("show");
		if (!$scope.dncDetailTable) {
			$scope.dncDetailTable = new ngTableParams({
					page: 1, // show first page
					count:20   // count per page

				}, {
					counts: [20,50,100,200,300],
					getData: function ($defer, params) {
						$scope.dncDetailParams.page = params.page();
						$scope.dncDetailParams.count = params.count();
						$scope.dncDetailParams.sorting = $scope.dncDetailTable.orderBy().toString();
						$scope.dncDetailParams.tableGroupBy = $scope.dncDetailTable.tableGroupBy;
						ApiServices.getDNCBlacklist($scope.dncDetailParams).then(function (response) {
								$("#loading").modal("hide");
								// console.log(response)
								$scope.lstDncBlacklist = response.data.data;
								$scope.dncDetailParams.total = response.data.count;

								if (response.data.count <= $scope.dncDetailTable.parameters().count) {
									$scope.dncDetailTable.parameters().page = 1;
								}
								params.total(response.data.count);
								$defer.resolve(response.data.data);

							}, function (response) {
								$("#loading").modal("hide");
								$scope.lstDncBlacklist = [];
								$scope.lstDncBlacklistCount = -1;
							}
						);
					}
				}
			)
		}
		else {
			$scope.dncDetailTable.reload();
		}
	}

	$scope.getDNCWhitelist = function () {
			// res = ApiUsers.viewAll()
			// res.then(function (data) {
			//     $scope.users = data.data;
			// })

			$("#loading").modal("show");
			if (!$scope.dncWhitelistDetailTable) {
				$scope.dncWhitelistDetailTable = new ngTableParams({
						page: 1, // show first page
						count:20   // count per page

					}, {
						counts: [20,50,100,200,300],
						getData: function ($defer, params) {
							$scope.dncWhitelistDetailParams.page = params.page();
							$scope.dncWhitelistDetailParams.count = params.count();
							$scope.dncWhitelistDetailParams.sorting = $scope.dncWhitelistDetailTable.orderBy().toString();
							$scope.dncWhitelistDetailParams.tableGroupBy = $scope.dncWhitelistDetailTable.tableGroupBy;
							ApiServices.getDNCWhitelist($scope.dncWhitelistDetailParams).then(function (response) {
									$("#loading").modal("hide");
									// console.log(response)
									$scope.lstDncWhitelist = response.data.data;
									$scope.dncWhitelistDetailParams.total = response.data.count;

									if (response.data.count <= $scope.dncWhitelistDetailTable.parameters().count) {
										$scope.dncWhitelistDetailTable.parameters().page = 1;
									}
									params.total(response.data.count);
									$defer.resolve(response.data.data);

								}, function (response) {
									$("#loading").modal("hide");
									$scope.lstDncWhitelist = [];
									$scope.lstDncWhitelistCount = -1;
								}
							);
						}
					}
				)
			}
			else {
				$scope.dncWhitelistDetailTable.reload();
			}

		}


		$scope.getDNCBlacklist();
		$scope.removeDncBlacklist= (row)=>
		{
			let confim= confirm(`Bạn muốn xóa thuê bao: ${row.phone_no}`);
			if(confim)
			{
				let postData= {id:row.id};
				ApiServices.removeDNCBlacklist(postData).then(result=>{
					$.jGrowl("Xóa thành công thuê bao "+ row.phone_no)
					$scope.getDNCBlacklist();
				}, reason => {
					$.jGrowl("Xóa thuê bao không thành công")
				})
			}

		}
		let regexPhoneNo = /"/g;
		const reformatDataForm = (data) => {
			let lstPhoneNo = [];
			if (data.phone_no_list.includes(",")) {
				lstPhoneNo = (data.phone_no_list).split(",");
			}
			else {
				lstPhoneNo = [data.phone_no_list];
			}
			let returnData = []
			for (let i in lstPhoneNo) {
				let phoneNo = lstPhoneNo[i].replace(regexPhoneNo, '');
				if (lstPhoneNo[i] && validPhoneNo(phoneNo)) {
					returnData.push({'phone_no': phoneNo, status: data.status})
				}
			}
			return returnData;
		}


		$scope.lstWhitelistStatus=[
			{id:1,title:"Thêm vào whitelist"},
			{id:0,title:"Xóa khỏi whitelist"},
		]
		$scope.switchNavBlackList = function (nav) {
			$scope.lstBlackListNav.map(item => {
				item.active = false
			})
			nav.active = true;
			$scope.navBlacklist = nav.id;
			switch (nav.id) {
				case "black-list":
					$scope.getDNCBlacklist();
					break;
				case "white-list":
					$scope.getDNCWhitelist();
					$scope.newWhiteList={
						hotline_numbers:"",
						white_list:1,
					}
					break;
				default:
					$scope.getDNCBlacklist();
					break;
			}
		}




	function synDNC(data) {
		return new Promise((resolve, reject) => {
			ApiServices.postDNCBlacklist(data).then(result=>{
				return resolve(result);
			},reason => {
				return reject(reason)

			})
		});
	}
	const  resolve=result=>{
		// console.log(result.data);
		$scope.newBlackList.successCount += parseInt(result.data.successCount);
		$scope.$digest();
	}
	const  reject= reason=>{
		if(reason.status !=200)
		{
			console.log(reason);
			$.jGrowl("Có lỗi xảy ra")
			$scope.uploading=false;
		}

	}
	async function AsyncDNC(data, type = 'file') {
		if (data.length == 0) {
			$.jGrowl("Không có dữ liệu hợp lệ");
			$scope.uploading = false;
			$("#loading").modal("hide");
			return;
		}
		if (data.length > 500000) {
			$.jGrowl("Quá 500.000 bản ghi trong file. Vui lòng chia nhỏ file trước khi tiếp tục");
			$scope.uploading = false;
			$("#loading").modal("hide");
			return;
		}
		let range = makeRange(data.length, 500);
		for (let x in range) {
			let val = data.slice(range[x][0], range[x][1]);
			// console.log(val);
			const result = await synDNC({data: val});
			resolve(result);
			reject(result)
		}
		$("#loading").modal("hide");
		$scope.uploading = false;

		setTimeout(function () {
			switch (type) {
				case "file":
					$("#importBlackListForm").modal('hide');
					$.jGrowl(`Đã cập nhật ${$scope.newBlackList.datalength} thuê bao thành công`);
					break;
				case "form":
					$("#addBlackListForm").modal('hide');

					$.jGrowl(`Đã ${$scope.newBlackList.status==1?'thêm':'xóa'} ${$scope.newBlackList.datalength} thuê bao thành công`)
					break;
			}
		}, 500)
		$scope.getDNCBlacklist();
	}
	const makeRange = (total, limit) => {
		if (total < limit) {
			return [[0, total]];
		}
		else {
			let page = Math.ceil(total / limit);
			let rangeReturn = [];
			for (let i = 0; i < page; i++) {
				let nextpage = limit * (i + 1) < total ? limit * (i + 1) : total;
				rangeReturn.push([limit * i, nextpage]);
			}
			return rangeReturn;
		}
	}
	const validPhoneNo = (phoneNo) => {
		var pattern = /^0[0-9]{8,11}$/
		if (!pattern.test(phoneNo)) {
			return false;
		} else {
			return true;
		}
	}



	function readSingleFile(e) {
		var file = e.target.files[0];
		if (!file) {
			return;
		}
		var reader = new FileReader();
		reader.onload = function (e) {
			var contents = e.target.result;
			// displayContents(contents);
			let jsonData = displayParsed(contents);
			// jsonData.shift();
			$scope.newBlackList.list =jsonData;
			$scope.newBlackList.datalength = ($scope.newBlackList.list).length;
			$scope.$digest();
		};
		reader.readAsText(file);
	}
	function displayParsed(contents) {
		// console.log(contents);
		let temp = contents.split("\r\n");
		var ArrayX = [];
		for (let i in temp) {
			let r = temp[i].split(",");
			let phoneNo=r[0].replace(regexPhoneNo, '');

			if (r.length > 0 && validPhoneNo(phoneNo)) {
				let postData = {phone_no: phoneNo, status: r[1].replace(regexPhoneNo, '')};
				ArrayX.push(postData)
			}
		}
		return ArrayX;
	}

	document.getElementById('file-input-uploadcsv').addEventListener('change', readSingleFile, false);

});
