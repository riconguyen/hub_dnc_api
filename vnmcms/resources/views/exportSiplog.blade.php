
<?php

if(!$data || !$data['client'])
{

    echo("No record found");
    die;
}

header('Content-Type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=Siplog_'.date('Y-m-d').'-'.$data['client']->enterprise_number.'-'.$data['client']->hotline_number.'.xls');
?>

<table class="table table-striped table-sm small table-bordered" id="table1" border="1" width="100%">

    <tr>

        <td>Số bản ghi: {{count($data['call_history'])}}</td>
        <td>Số Hotline: {{$data['client']->hotline_number}}</td>

        <td>Số Enterprise: {{$data['client']->enterprise_number}}</td>
        <td> </td>
        <td colspan="2">Từ ngày: {{$data['start_date']}}</td>
        <td colspan="2">Tới ngày: {{$data['end_date']}}</td>
        <td >Ngày xuất: {{date('Y-m-d H:i:s')}}</td>
    </tr>
    <tr>
        <th>Thời gian</th>
        <th>Người nhận</th>
        <th>Số gọi</th>
        <th>Thời điểm kết nối</th>
        <th>Thời điểm ngừng kết nối</th>
        <th>Lý do ngừng</th>
        <th>Tình trạng tính phí</th>
        <th>Tình trạng cuộc gọi</th>
        <th>Thời lượng</th>
        <th>Mã lỗi</th>
        <th>Bandname</th>
    </tr>

    @foreach ($data['call_history'] as $call)

    <tr>
        <td>{{$call->setup_time}}</td>
        <td>{{$call->CLD}}</td>
        <td>{{$call->CLI}}</td>
        <td>{{$call->connect_time}}</td>
        <td>{{$call->disconnect_time}}</td>
        <td>{{$call->disconnect_cause}}</td>
        <td>{{$call->charge_status}}</td>
        <td>{{$call->state}}</td>
        <td>{{$call->duration}}</td>
        <td>{{$call->reject_cause}}</td>
        <td>{{$call->call_brandname}}</td>

    </tr>

    @endforeach

</table>
