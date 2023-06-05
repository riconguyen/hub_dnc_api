<?php
  /**
   * Created by IntelliJ IDEA.
   * User: nnghi
   * Date: 09/06/2018
   * Time: 9:01 PM
   */



  return [
    'app_log'=>true,
    'application_code'=>'VCONNECT',
    'ip_port_parent_node'=>"123.31.17.59:8088",
    'ip_port_current_node'=>"123.31.17.59:8088",

    'whitelist_write_content'=>[
    'getCustomersV2','check','getAuthUser','postSearchSip','postViewReportQuantity',
    'postViewReportFlow','postViewReportMonthlyAudit','postViewReportCustomer',
    'getRoles','getReport','getLogs'
    ]
    ,
    'whitelist_no_log_function'=>['check','postViewDashboardDailyFlow','getAuthUser','captcha','getHomePage','getServiceZoneQuantityType','getFeeByEntNumber','getConfigByCustomer']
  ];
