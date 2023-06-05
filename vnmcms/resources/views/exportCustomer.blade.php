<?php
/**
 * Created by IntelliJ IDEA.
 * User: Rico Nguyen
 * Date: 09/05/2018
 * Time: 3:13 AM
 */

header('Content-Type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=DanhSachKhachHang-'.date('Y-m-d').'-.xls');
?>

<table class="table table-striped table-sm small table-bordered" id="table1" border="1" width="100%">

    <tr>
        <th>TT</th>
        <th>Khách hàng</th>
        <th>Số điện thoại</th>
        <th>Mã tính cước</th>
        <th>Tình trạng</th>
        <th>Gói cước</th>
        <th>Ngày tạo</th>
        <th>Ngày cập nhật</th>
        <th>Cước lũy kế trong tháng</th>




    </tr>


    @foreach ($data['data']  as $item)
    <tr><td>
            {{$i++}}
        </td>
        <td>{{$item->companyname}}</td>
        <td>{{$item->phone1}}</td>
        <td>{{$item->enterprise_number}}</td>
        <td>  @if($item->status==0)

            Đang hoạt động
            @endif


            @if($item->status==1)
            Tạm ngưng
            @endif
               @if($item->status==2)
            Hủy
            @endif

        </td>
        <td>{{$item->service_name}}</td>

        <td>{{$item->created_at}}</td>
        <td>{{$item->updated_at}}</td>
        <td>{{$item->total}}</td>

    </tr>
    @endforeach

</table>
