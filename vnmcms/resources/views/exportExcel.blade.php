
<?php
header('Content-Type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=Log_'.date('Y-m',strtotime($data['date']->end_date)).'-'.$enter.'.xls');
?>

<table class="table table-striped table-sm small table-bordered" id="table1" border="1" width="100%">
    <tr>
        <td colspan="3">
            Số bản ghi: {{number_format($data['page']['count'])}}
            Số tiền: {{number_format($data['sum'])}}
            Số phút: {{number_format(ceil($data['duration']/60))}}

        </td>
        <td colspan="3">  Từ ngày: {{($data['date']->start_date)}}</td>
        <td colspan="2" >   Tới ngày: {{($data['date']->end_date)}}</td>
        <td colspan="3"> Số tính cước {{$enter}}</td>

    </tr>
    <tr>
        <th>No</th>
        <th>Ngày phát sinh</th>
        <th>Loại</th>
        <th></th>
        <th>Hotline</th>
        <th>Số hiển thị</th>
        <th>Số đích</th>
        <th>Số tính cước</th>


        <th>Nội dung</th>
        <th>Số giây</th>
        <th>Số tiền</th>


    </tr>


    @foreach ($data["data"] as $item)
    <tr><td>
            {{$i++}}
        </td>
        <td>{{$item->event_occur_time}}</td>
        <td>
            @if($item->event_type=='000002')

            THOẠI



            @else
            SUB
            @endif

        </td>
        <td>

            @if($item->destination_type=='1')
            Nội mạng
            @else
            Ngoại mạng
            @endif
        </td>
        <td>{{$item->hotline_num}}</td>
        <td>{{$item->display_num}}</td>
        <td>{{$item->called_num}}</td>

        <td>{{$item->enterprise_num}}</td>
        <td>{{$item->charge_description}}</td>
        <td>{{$item->count}}</td>
        <td>{{$item->amount}}</td>
    </tr>
    @endforeach


</table>