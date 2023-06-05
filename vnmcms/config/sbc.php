<?php

return [

    'limitLog'=>50000,
    'limitCustomerDownload'=>10000,
    'totalUserPerPage'=>100,
    'usingCaptcha'=>false,
  'profile_id_backup'=>2,
  'action'=>[
    'create_customer'=>"Tạo mới khách hàng",
    'update_customer'=>"Cập nhật khách hàng",

    'add_hotline'=>"Thêm mới hotline",
    'pause_call_out'=>"Chặn/mở gọi ra",
    'pause_call_in'=>"Chặn/mở gọi vào",

    'pause_state_hotline'=>"Chặn/mở hotline",
    'pause_state_customer'=>"Chặn/mở khách hàng",
//    'resume_customer'=>"Khôi phục khách hàng",
//    'pause_all'=>"Chặn 2 chiều",
    'cancel_customer'=>"Hủy khách hàng",
    'set_fee_limit'=>'Cài đặt hạn mức',
    'change_product_code'=>"Đổi gói cước",
    'change_enterprise_number'=>"Đổi số đại diện",
    'cancel_hotline'=>"Hủy hotline",
    'update_sip_config'=>"Cập nhật cấu hình sip",
    'change_site'=>"Chuyển khách",
    'recharge'=>"Charge bù cước",
    'added_service_code'=>'Dịch vụ GTGT',
    'set_quantity_subscriber'=>'Gói sản lượng',
    'charge_hotline'=>'Charge cước hotline',
    'move_customer'=>'Chuyển cụm server',
    'update_cycle_charge'=>"Cập nhật cước",
    'sync_customer'=>'Đồng bộ khách hàng'

  ],
  'action_cdr'=>[
    'pause_call_out_cdr'=>1,
    'pause_call_in_cdr'=>1,
  ]
  ,
//  'backup_site'=>true,
//  'server_profile'=>10,
//  'server_profile_backup'=>11,

  'customer_status'=>['pause'=>1,'active'=>0,'cancelled'=>2],
  'CDR'=>[
    'ACTIVE'=>0,
    'PAUSE'=>1,
    'CANCEL'=>2,
    'CHANGE'=>3
  ],
  'limit_charge_amount'=>3000000,
  'delay_quantity_charge_in_minutes'=>60,
  'delay_sub_charge_in_minutes'=>60,
  'auto_quantity_charge_add'=>true,
  'row_per_file_download'=>500

      ];
