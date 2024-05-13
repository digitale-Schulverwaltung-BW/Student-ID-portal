<?php
// Ubuntu install php-http-request2

require('config.php');
require_once 'HTTP/Request2.php';

if (!isset($_REQUEST['id']) || empty($_REQUEST['id'])) return "";
$id=$_REQUEST['id'];

$request = new HTTP_Request2();
$request->setUrl('https://cloud.kortpress.io/rest/v1/pass?templateId=1628');
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setConfig(array(
  'follow_redirects' => TRUE
));
$request->setHeader(array(
  'Authorization' => 'Bearer ' . KORTPRESS_TOKEN,
  'Cookie' => 'INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw'
));
$request->setBody('{ "details": { "name": "Schülerausweis", "tags": "student ID card", "passType": "pass.type.loyalty.card", 
 "thirdPartyId": "'.$id.'", "platformTypeList": [ "GOOGLE", "PDF" ], "scanEventWebhookUrl": "https://cmp.letsdev.de/letsdev/webhook-test/?PHPSESSID=9b3e0d071138b22bf770ab2dc7d8b7f7", "scanEventWebhookAuthentication": "string", "transactionWebhookUrl": "https://cmp.letsdev.de/letsdev/webhook-test/?PHPSESSID=9b3e0d071138b22bf770ab2dc7d8b7f7", "transactionWebhookAuthentication": "string", 
 "plannedVoidedDate": "2050-01-01T13:30:45.678Z" }, "items": { 
 "expirationDate": { "update": true, "value": "2024-09-30T08:08:03.118Z" }, 
 "relevantDate": { "update": true, "value": "2050-01-20T13:05:10+01:00" }, 
 "barcodeMessage": { "update": true, "value": "https://www.hhs.karlsruhe.de/ID/v.php?id='.$id.'" }, 
 "barcodeAltText": { "update": true, "value": "Gültigkeitsprüfung" }, "barcodeFormat": { "update": true, "value": "PKBarcodeFormatQR" }, 
 "backField1": { "update": true, "value": "9/2024" }, 
 "backField1Label": { "update": true, "value": "Gültig bis:" }, 
 "backUrl1Title": { "update": true, "value": "Heinrich-Hertz-Schule Karlsruhe" }, 
 "backUrl1": { "update": true, "value": "https://www.hhs.karlsruhe.de" }, 
 "logoImageUrlGooglePasses": { "update": true, "value": "https://hhs.karlsruhe.de/ID/templates/GoogleLogo-660x660.png" }, 
 "heroImageUrlGooglePasses": { "update": true, "value": "https://hhs.karlsruhe.de/ID/templates/Hero-1030x336.png" }, 
 "iconImageUrl3X": { "update": true, "value": "https://hhs.karlsruhe.de/ID/templates/AppleLogo-480x150.png" }, 
 "rewardsTier": { "update": true, "value": "03.01.1991" }, 
 "rewardsTierLabel": { "update": true, "value": "Geburtsdatum" }, 
 "accountName": { "update": true, "value": "Max Muster" }, 
 "accountNameLabel": { "update": true, "value": " Name" }, 
 "accountId": { "update": true, "value": "'.substr($id,-4).'" }, 
 "accountIdLabel": { "update": true, "value": "Ausweis ID" } }}');

try {
  $response = $request->send();
  if ($response->getStatus() == 200) {
    $data = json_decode($response->getBody(), true);

    $returnval = array("apple" => $data['urls']['platforms']['APPLE'], 
                       "google" => $data['urls']['platforms']['GOOGLE'], 
                       "pdf" => $data['urls']['platforms']['PDF'],
                       "pass_id" =>$data['details']['serialNumber']);
    header("Content-Type: application/json");
    echo json_encode($returnval);
  }
  else {
    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
    $response->getReasonPhrase();
  }
}
catch(HTTP_Request2_Exception $e) {
  echo 'Error: ' . $e->getMessage();
}   



/*
<?php
require_once 'HTTP/Request2.php';
$request = new HTTP_Request2();
$request->setUrl('https://cloud.kortpress.io/rest/v1/pass?templateId=1628');
$request->setMethod(HTTP_Request2::METHOD_POST);
$request->setConfig(array(
  'follow_redirects' => TRUE
));
$request->setHeader(array(
  'Content-Type' => 'application/json',
  'Authorization' => 'Bearer eyJraWQiOiJzc28ta2V5LWlkIiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiJkZmIyMGY4Yy0wMTUzLTExZWYtYjI2Ny05NjVhOWNiMDk3MmMiLCJhdWQiOiJrb3J0cHJlc3NDbG91ZCIsIm5iZiI6MTcxNTAwMjU4OSwic2NvcGUiOlsiYXBpOndyaXRlIiwiYXBpOmRlbGV0ZSIsImFwaTpyZWFkIl0sImlzcyI6Imh0dHBzOi8vc3NvLmxldHNkZXYuZGUiLCJleHAiOjE3MTUwODg5ODksImlhdCI6MTcxNTAwMjU4OSwianRpIjoiMGE2MzlmZDgtZDgzNS00MGI5LWE3MGUtZWMzNzM0YjNlZDFkIn0.EqWhml3r072IfZpHUZVzMR2AR3gc9JYiMm8XbtEmJe80AoCRdGEPw55unwyRWfxz62i4gBN_31gkS3METS14h-CFSqvOdTlJpQidBNFLi2Ikqb3oPC2Q1M6m0Uq4EBaTo1DJduqgSyK8YERpgGSHzLFHYGN4pSFsEIHtCOMIBotxUsXHwek2T01Pi6djtdCmcRLeRwd_Ymf46Nb5zpeSIcMW7JY-CnXI6NSv04bTB9yhht2HkRG44PYI3xbfb9RQnOlGzQBq1vPMm1bswnsQs25c8AUsPxbQ9ITq3QwWRQPCt_G5RtSYy9xhi5uvg3Dl7nTdNZEyRSpZxLp1w4HMnw',
  'Cookie' => 'INGRESSCOOKIE=e04b23e90c3fcb130cc9f602b360d97f; SESSION=YWM4OTk3MTctZTEzOC00NjMyLTkyMTYtZjBlNDAwMmRmMmIw'
));
$request->setBody('{  "details": {    "name": "Schülerausweis",    "tags": "student ID card",    "passType": "pass.type.loyalty.card",    "thirdPartyId": "8a9041a0-89622cd6-0189-62b5c570-06eb",    "platformTypeList": [      "GOOGLE",      "PDF"    ],    "scanEventWebhookUrl": "https://cmp.letsdev.de/letsdev/webhook-test/?PHPSESSID=9b3e0d071138b22bf770ab2dc7d8b7f7",    "scanEventWebhookAuthentication": "string",    "transactionWebhookUrl": "https://cmp.letsdev.de/letsdev/webhook-test/?PHPSESSID=9b3e0d071138b22bf770ab2dc7d8b7f7",    "transactionWebhookAuthentication": "string",    "plannedVoidedDate": "2050-01-01T13:30:45.678Z"  },  "items": {    "expirationDate": {      "update": true,      "value": "2024-09-30T08:08:03.118Z"    },    "relevantDate": {      "update": true,      "value": "2050-01-20T13:05:10+01:00"    },    "barcodeMessage": {      "update": true,      "value": "https://www.hhs.karlsruhe.de/ID/v.php?id=8a9041a0-89622cd6-0189-62b5c570-06eb"    },    "barcodeAltText": {      "update": true,      "value": "Gültigkeitsprüfung"    },    "barcodeFormat": {      "update": true,      "value": "PKBarcodeFormatQR"    },    "backField1": {      "update": true,      "value": "9/2024"    },    "backField1Label": {      "update": true,      "value": "Gültig bis:"    },    "backUrl1Title": {      "update": true,      "value": "String"    },    "backUrl1": {      "update": true,      "value": "https://www.hhs.karlsruhe.de"    },    "backUrl2Title": {      "update": true,      "value": "Heinrich-Hertz-Schule Karlsruhe"    },    "logoImageUrlGooglePasses": {      "update": true,      "value": "https://hhs.karlsruhe.de/ID/templates/GoogleLogo-660x660.png"    },    "heroImageUrlGooglePasses": {      "update": true,      "value": "https://hhs.karlsruhe.de/ID/templates/Hero-1030x336.png"    },    "iconImageUrl3X": {      "update": true,      "value": "https://hhs.karlsruhe.de/ID/templates/AppleLogo-480x150.png"    },    "rewardsTier": {      "update": true,      "value": "03.01.1991"    },    "rewardsTierLabel": {      "update": true,      "value": "Geburtsdatum"    },    "accountName": {      "update": true,      "value": "Max Muster"    },    "accountNameLabel": {      "update": true,      "value": " Name"    },    "accountId": {      "update": true,      "value": "06E3"    },    "accountIdLabel": {      "update": true,      "value": "Ausweis ID"    }  }}');
try {
  $response = $request->send();
  if ($response->getStatus() == 200) {
    echo $response->getBody();
  }
  else {
    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
    $response->getReasonPhrase();
  }
}
catch(HTTP_Request2_Exception $e) {
  echo 'Error: ' . $e->getMessage();
}
*/
?>