cms3c.controller('userController', function ($scope, ApiUsers, loginCheck, ngTableParams, ApiServices) {


    var Token=loginCheck.getEntity().then(function (value) {

		$scope.entity=value.entity;
		$scope.authUser= value;
	});


    $scope.user= new User([])



    $scope.message = "Trang nay se duoc su dung de hien thi mot form de them sinh vien";

    $scope.userRole={'1':{'title':'admin'},'2':{'title':'Super Admin'}, '3':{"title":'Customer'},'0':{'title':'Guest'}};
    $scope.role= function(role){
        return $scope.userRole[role].title;
    }

    $scope.sbcUserParam={};
    $scope.userTableCols=[
        {field: "name", title: "Tên", sortable: "name", show: true},
        {field: "email", title: "Email", sortable: "email", show: true},
        {field: "role_name", title: "Vai trò", sortable: "role_name", show: true},
        {field: "updated_at", title: "Ngày cập nhật", sortable: "updated_at", show: true},
        {field: "action", title: "Hành động", sortable: "action", show: true},
    ]


    $scope.getRoles= function () {
        res= ApiUsers.getRoles()
        res.then(function (resData) {
            $scope.lstRoles= resData.data;

        })

    }
    $scope.viewUsers = function () {
        // res = ApiUsers.viewAll()
        // res.then(function (data) {
        //     $scope.users = data.data;
        // })

$("#loading").modal("show");
        if (!$scope.sbcUserTable) {
            $scope.sbcUserTable = new ngTableParams({
                    page: 1, // show first page
                    count:50   // count per page

                }, {
                    counts: [50,100,200,300],
                    getData: function ($defer, params) {
                        $scope.sbcUserParam.page = params.page();
                        $scope.sbcUserParam.count = params.count();
                        $scope.sbcUserParam.sorting = $scope.sbcUserTable.orderBy().toString();
                        $scope.sbcUserParam.tableGroupBy = $scope.sbcUserTable.tableGroupBy;
                        ApiUsers.viewAll($scope.sbcUserParam).then(function (response) {
							$("#loading").modal("hide");
                                $scope.lstUser = response.data.data;
                                $scope.lstUserCount = response.data.count;

                                if (response.data.count <= $scope.sbcUserTable.parameters().count) {
                                    $scope.sbcUserTable.parameters().page = 1;
                                }
                                params.total($scope.lstUserCount);
                                $defer.resolve($scope.lstUser);
                            }, function (response) {
							$("#loading").modal("hide");
                                $scope.lstUser = [];
                                $scope.lstUserCount = -1;
                            }
                        );
                    }
                }
            )
        }
        else {
            $scope.sbcUserTable.reload();
        }



    }
    $scope.addUser = function () {
        var res = ApiUsers.addUser($scope.user).then(function (data) {

            if(data.status==200)
            {
				$.jGrowl("Tạo mới thành công")
				$scope.user=  new User([]);
				$scope.userAddError={};
               $scope.viewUsers();
            }
        }, function (reason) {
        	$.jGrowl("Thông tin còn thiếu")
			if(reason.status==422) {
				$scope.userAddError = reason.data.errors;
			}
		})
    };

    $scope.addUserForm=function () {
        $scope.user= new User([]);

    }

    $scope.saveUser=function () {
    	console.log($scope.user);
        if($scope.user.id)
        {
            res= ApiUsers.updateUser($scope.user, $scope.user.id)
            res.then(function (data) {
                if(data.status==200)
                {	$.jGrowl("Cập nhật thành công")
                    $scope.sbcUserParam.q= $scope.user.email;
                    $scope.viewUsers(); // reload user
                    $scope.user=null;
                    $scope.userAddError=null;
                }


            },
                function (error) {
                if(error.status==422) {
                    $scope.userAddError = error.data.errors;
                }


                })
        }

    }



    $scope.viewUser=function (data, index) {
    	console.log(data)
        $scope.user=new User(data);
        $scope.user.index= index;

    }
    // Load user
    $scope.viewUsers();
    $scope.getRoles();
    $scope.getRoles();

    $scope.switchNav= function (nav) {
        $scope.navUser= nav;
        if(nav=='roles')
        {
			$scope.getAllRoles();
        }

	}

	$scope.getAllRoles = function (data) {

		ApiServices.getAllRoles(data).then(function (result) {

			$scope.lstAllRoles = result.data ? result.data.lstRoles : [];

		})
	}


	$scope.onClearRole= function(role)
	{
		$scope.newRole= new Role();
	}

	$scope.onDeleteRole= function(role)
	{
		var confim= confirm("Bạn chắc chắn muốn xóa vai trò này này. Việc này sẽ set các tài khoản đang dùng vai trò này về vai trò GUEST");
		if(confim)
		{
			ApiServices.setRemoveRole(role).then(function (value) {
				$.jGrowl("Đã xóa thành công");
				$scope.getAllRoles();
			}, function (reason) {

				$.jGrowl("Lỗi không xóa được role");

			})
		}
	}
	$scope.onAddRole = function (role) {

		ApiServices.setRole(role).then(function (result) {


			var data = result.data.roles;

			if (data.edit) {
				$.jGrowl("Cập nhật thành công")
				for (var i = 0; i < $scope.lstAllRoles.length; i++) {
					if ($scope.lstAllRoles[i].id == data.id) {
						$scope.lstAllRoles[i].name = data.name;
						$scope.lstAllRoles[i].description = data.description;
						$scope.lstAllRoles[i].role_key = data.role_key;
						break;
					}
				}
			}
			else {

				$.jGrowl("Thêm mới thành công")
				$scope.getAllRoles();
			}

			$scope.newRole = new Role();
			$scope.newRoleError= {}; 

		}, function (reason) {

			if(reason.status==422) {
				$.jGrowl("Thông tin còn thiếu")
				$scope.newRoleError = reason.data.errors;
			}
			else
			{
				$.jGrowl("Có lỗi xảy ra")
			}

		})

	}

	$scope.editRole = function (role) {
		$scope.newRole = new Role();


		if (role.role_key == 'GUEST') {
			alert("Quyền của khách, không chỉnh sửa");
			return;
		}
		$scope.newRole.id = role.id;
		$scope.newRole.description = role.description;
		$scope.newRole.name = role.name;
		$scope.newRole.role_key = role.role_key;



	}

	$scope.getEntity = function () {
		ApiServices.getEntity().then(function (result) {
			$scope.lstEntities = result.data ? result.data.lst : [];

		}, function (reason) {
			alert("errors");

		})

	}

	$scope.viewRoleEntity = function (role) {

		$("#viewEntityToRole").modal('show');
		$scope.currentRole = angular.copy(role);
		if (!$scope.lstEntities) {
			$scope.getEntity();
		}

	}


	$scope.onAddNewEntityToRole = function (entity) {

		if (entity.entity.id) {

			entity.entity_id = entity.entity.id;
			entity.entity_name = entity.entity.entity_name;
			entity.role_id = angular.copy($scope.currentRole.id);
		}

		if (entity.role_id) {
			ApiServices.setEntityRole(entity).then(function (result) {

				$scope.currentRole.entity.push(result.data.roleEntity)
				$scope.newEntityRole = new RoleEntity();

			}, function (reason) {

				if(reason.status==422)
				{
					var mss;
					if(reason.data.roleEntity)
					{
						mss= "Đã tồn tại quyền trong vai trò";
					}

					$.jGrowl("Có lỗi xảy ra:<br>"+ mss)
					return;
				}


				$.jGrowl("Có lỗi xảy ra!")
				return;
			})
		}

	}


	$scope.onRemoveEntityRole = function (entity) {

    	var conf= confirm("Bạn muốn xóa quyền "+entity.entity_name+" của vai trò này này")

		if (conf){

			var postData = {id: entity.id};
			ApiServices.removeEntityRole(postData).then(function (result) {
				$.jGrowl("Xóa quyền thành công")
				if (result.data && result.data.status) {
					for (var i = 0; i < $scope.currentRole.entity.length; i++) {
						if ($scope.currentRole.entity[i].id == result.data.role_entity_id) {
							($scope.currentRole.entity).splice(i, 1);
							break;
						}
					}
				}

			}, function (reason) {
				if(reason.status==402)
				{
					$.jGrowl("Bạn không được xóa quyền mặc định của Admin")
				}


				else if  (reason.status == 403) {
					$location.path('/login');
				}
				else
				{
					$.jGrowl("Xóa quyền không thành công")
				}
			});
		}

// Xoa entity ủe
	}



});




var Role = function () {

	this.id = null;
	this.name = "";
	this.description = "";
	this.role_key = "";

}

var RoleEntity = function () {
	this.entity_name = "";
	this.entity_key = "";
	this.id = null;
}


var User= function (data) {
	this.name=data?data.name:"";
	this.role=data?data.role:"";
	this.email=data?data.email:"";
	this.id= data.id?data.id:null;
	if(data)
	{
		this.password= null;
	}


}