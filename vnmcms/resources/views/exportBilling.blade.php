
<?php
header('Content-Type: application/vnd-ms-excel');
header('Content-Disposition: attachment; filename=CuocThueBao_'.$enter.'_Tu_'.date('Y-m-d',strtotime($date['start_date'])).'_toi_'.date('Y-m-d',strtotime($date['end_date'])).'_page'.$current_page.'of'.$total_page.'.xls');


?>



<table  style="border:1px solid silver" width="100%" id="billing">
    <tr>
              <td colspan="4">
            Số bản ghi: {{($total)}}

            Số tiền: {{($sum)}}
            Số phút: {{(ceil($duration/60))}}


        </td>
        <td colspan="3">  Từ ngày: {{($date['start_date'])}}</td>
        <td colspan="2" >   Tới ngày: {{($date['end_date'])}}</td>
        <td colspan="2"> Số tính cước {{$enter}}
        </td>
        <td>

            Trang: {{$current_page}}/{{$total_page}}
        </td>

    </tr>
    <tr >
        <th style="border-top:1px solid silver" >No</th>
        <th style="border-top:1px solid silver" >ID</th>
        <th style="border-top:1px solid silver" >Ngày phát sinh</th>
        <th style="border-top:1px solid silver" >Loại</th>
        <th style="border-top:1px solid silver" ></th>
        <th style="border-top:1px solid silver" >Hotline</th>
        <th style="border-top:1px solid silver" >Số hiển thị</th>
        <th style="border-top:1px solid silver" >Số đích</th>
        <th style="border-top:1px solid silver" >Số tính cước</th>
        <th style="border-top:1px solid silver" >Nội dung</th>
        <th style="border-top:1px solid silver" >Số giây</th>
        <th style="border-top:1px solid silver" >Số tiền</th>


    </tr>

    @foreach ($data as $item)
    <tr><td style="border-top:1px solid silver"  data-cell-type="number">
            {{$i++}}
        </td>
        <td style="border-top:1px solid silver" data-cell-type="number">
            {{$item->id}}
        </td>
        <td style="border-top:1px solid silver" data-cell-type="text">{{$item->event_occur_time}}</td>
        <td style="border-top:1px solid silver" data-cell-type="text">
            @if($item->event_type=='000002')
            THOẠI
            @else
            SUB
            @endif

        </td>
        <td style="border-top:1px solid silver" data-cell-type="text">
            @foreach($prefix as $ite)
            @if($ite->id==$item->destination_type)
            {{$ite->name}}
            @break
            @endif
            @endforeach

        </td>
        <td style="border-top:1px solid silver" >{{$item->hotline_num}}</td>
        <td style="border-top:1px solid silver" >{{$item->display_num}}</td>
        <td style="border-top:1px solid silver" >{{$item->called_num}}</td>
        <td style="border-top:1px solid silver" >{{$item->enterprise_num}}</td>
        <td style="border-top:1px solid silver" >{{$item->charge_result}}</td>
        <td style="border-top:1px solid silver" >{{$item->count}}</td>
        <td style="border-top:1px solid silver">{{$item->amount}}</td>
    </tr>
    @endforeach



</table>
