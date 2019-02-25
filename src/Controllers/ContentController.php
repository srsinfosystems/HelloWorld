<?php
namespace HelloWorld\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;

/**
 * Class ContentController
 * @package HelloWorld\Controllers
 */
class ContentController extends Controller
{
	/**
	 * @param Twig $twig
	 * @return string
	 */
	public function getorder(Twig $twig):string
	{	
		$order_id = "101";
		$orderItemsData = $this->order($order_id);
		$orderItemsData = json_decode($orderData, TRUE);		

		$operationData = array();
		$OrderProducts = array();
		foreach ($orderItemsData['entries'] as  $value) {
			
			$getVariation = $this->getVariation($value['itemVariationId']);
			$getVariation = json_decode($getVariation, TRUE);

			$stock_id = $getVariation['entries']['model'];
			$qty = $value['quantity'];	

			if ($value['lockStatus']=="permanentlyLocked") {
				$operationData[] = array(
				"lock"=>array("stock_id"=>"$stock_id", "qty"=>"$qty"));
			}
			if ($value['lockStatus']=="unlocked") {
				$operationData[] = array(
				"unlock"=>array("stock_id"=>"$stock_id", "qty"=>"$qty"));
			}
			/*$operationData[] = array(				
				"set"=>array("stock_id"=>"$stock_id", "qty"=>"$qty"));*/

			$OrderProducts[] = array('modelId'=>"$stock_id", 'qty'=>"$qty");
			
		}
		
		$reserveOrder = $this->reserve($operationData);
		$lockedOrder = $this->lockedOrder();		
		$acquireOrder = $this->acquireOrder($OrderProducts);
		$customerDetail = $this->customerDetail($order_id);
		$customerDetail['order_number'] = $acquireOrder;
		foreach ($orderItemsData['entries'] as  $value) {
	
			$getVariation = $this->getVariation($value['itemVariationId']);
			$getVariation = json_decode($getVariation, TRUE);

			$stock_id = $getVariation['entries']['model'];
			$qty = $value['quantity'];	
			$SingleRecipientOrder = $this->SingleRecipientOrder($customerDetail, $stock_id, $qty);
			print_r($SingleRecipientOrder);

		}

		$orderStatusOrderId = $this->orderStatusOrderId($acquireOrder);

<<<<<<< HEAD
		return $twig->render('HelloWorld::content.getorder',array('data' => $acquireOrder));
	}

	public function reserve($operationData){
		$operationLock = '<operation type="lock">';
		foreach ($operationData['lock'] as $value) {
			$operationLock .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
		}
		$operationLock .= '</operation>';
		$operationUnlock = '<operation type="unlock">';
		foreach ($operationData['unlock'] as $value) {
			$operationUnlock .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
		}
		$operationUnlock .= '</operation>';
		$operationSet = '<operation type="set">';
		foreach ($operationData['set'] as $value) {
			$operationSet .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
		}
		$operationSet .= '</operation>';
		
		$requestData = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<root>
			$operationLock
			$operationUnlock
		</root>';	
=======
		$data = json_encode($Items);
		//$storeItemsToPlenty = $this->storeItemsToPlanty($Items, $access_token);
		return $twig->render('HelloWorld::content.importProduct',array('data' => $data));
	}
	public function getAllItems($brand){
>>>>>>> bff084d3594bba0d083e26ad5105d8439f069cf1
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/sold",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $requestData,
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/xml",
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
			$xml = simplexml_load_string($response); 
			$json = json_encode($xml);
			$arrayData = json_decode($json,TRUE); 
<<<<<<< HEAD
		  return $arrayData;
=======
			echo $arrayData['items']['item']['availability'];
			$i=0;
			if($arrayData['items']['item']){
				if($arrayData['items']['item']['availability']){
					echo "single";
					$ItemResponse = $this->createItem($arrayData['items']['item']['name']);           
	       			$ItemResponse = json_decode($ItemResponse,TRUE);
	       			$ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       			$ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];

	       			$linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,1000000));
	       			$linkingBarcode = json_decode($linkingBarcode,TRUE);
	       			$ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];

	       			$activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $arrayData['items']['item']['streetPrice']);
	      			$activeItem = json_decode($activeItem,TRUE);
	       			$ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];

	       			$no = 0;
	       				if ($arrayData['items']['item']['pictures']['image']) {
	       				
		       				if ($arrayData['items']['item']['pictures']['image']['id']) {
		       					$ImageResponse = $this->uploadImage($ItemResponse['id'],$arrayData['items']['item']['pictures']['image']['url'], $arrayData['items']['item']['pictures']['image']['id']);
		       						$ItemResponseArray[$i]['images'][$no]['id'] = $arrayData['items']['item']['pictures']['image']['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $arrayData['items']['item']['pictures']['image']['url'];
		       				}else{
					            foreach ($arrayData['items']['item']['pictures']['image'] as $picture) {                
					                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
					                // echo $ImageResponse;exit;
					                $ImageResponse = json_decode($ImageResponse,TRUE);
					               $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $no++;
					            }
					        }
				    	}else{
				    		$ItemResponseArray[$i]['images'][$no]['id'] = "not available";
					        $ItemResponseArray[$i]['images'][$no]['url'] = "not available";
				    	}
				    	return $ItemResponseArray;
		        } else{
					foreach ($arrayData['items']['item'] as $value) { 
						$ItemResponse = $this->createItem($value['name']);
	          
	          			$ItemResponse = json_decode($ItemResponse,TRUE);
	          			$ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       				$ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];

	       				$linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,1000000));
	       				$linkingBarcode = json_decode($linkingBarcode,TRUE);
	       				$ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];

	       				$activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $value['streetPrice']);
	       				$activeItem = json_decode($activeItem,TRUE);
	       				$ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];

	       				$no = 0;
	       				if ($value['pictures']['image']) {
	       				
		       				if ($value['pictures']['image']['id']) {
		       					$ImageResponse = $this->uploadImage($ItemResponse['id'],$value['pictures']['image']['url'], $value['pictures']['image']['id']);
		       						$ItemResponseArray[$i]['images'][$no]['id'] = $value['pictures']['image']['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $value['pictures']['image']['url'];
		       				}else{
					            foreach ($value['pictures']['image'] as $picture) {                
					                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
					                // echo $ImageResponse;exit;
					                $ImageResponse = json_decode($ImageResponse,TRUE);
					               $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $no++;
					            }
					        }
				    	}else{
				    		$ItemResponseArray[$i]['images'][$no]['id'] = "not available";
					        $ItemResponseArray[$i]['images'][$no]['url'] = "not available";
				    	}
						$i++;
					} 
					return $ItemResponseArray;         
		        } 
		    }else{
		    	echo "No product  found";
		    }
	        exit;
			

>>>>>>> bff084d3594bba0d083e26ad5105d8439f069cf1
		}
	}

	public function lockedOrder(){

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/dropshipping/locked/",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/xml",
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  $xml = simplexml_load_string($response); 
			$json = json_encode($xml);
			$arrayData = json_decode($json,TRUE); 
		  return $arrayData;
		}
	}
	public function acquireOrder($productArray){
		$productTag = ""; 
		foreach ($productArray as  $value) { //print_r($value);
			 $productTag .= ' <product stock_id="'.$value['modelId'].'" quantity="'.$value['qty'].'" />';
		} 
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/supplierorder/acquire",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 9000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><supplierorder><products>'.$productTag.'</products></supplierorder>',,
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {		  
		  return $response;
		}
	}

	public function SingleRecipientOrder($recipientData, $stock_id, $qty){
		$requestData = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<root>
	<order_list>
		<order>
			<key>".$recipientData['order_number']."</key>
			<date>".$recipientData['date']."</date>
			<recipient_details>
				<recipient>".$recipientData['recipient']."</recipient>
				<careof />
				<cfpiva></cfpiva>
				<customer_key></customer_key>
				<notes></notes>
				<address>
					<street_type></street_type>
					<street_name>".$recipientData['street_name']."</street_name>
					<address_number>".$recipientData['address_number']."</address_number>
					<zip>".$recipientData['zip']."</zip>
					<city>".$recipientData['city']."</city>
					<province></province>
					<countrycode>".$recipientData['countrycode']."</countrycode>
				</address>
				<phone>
					<prefix>".$recipientData['prefix']."</prefix>
					<number>".$recipientData['number']."</number>
				</phone>
			</recipient_details>
			<item_list>
				<item>
					<stock_id>".$stock_id."</stock_id>
					<quantity>".$qty."</quantity>
				</item>
			</item_list>			
		</order>
	</order_list>
</root>";
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/0/dropshipping",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 9000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $requestData,
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/xml",
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  $xml = simplexml_load_string($response); 
			$json = json_encode($xml);
			$arrayData = json_decode($json,TRUE); 
		  return $arrayData;
		}
	}
	public function order($orderId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/orders/".$orderId."/items",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "postman-token: 77b15284-d14b-3b3f-c085-904253595e91"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}
	}
	public function login(){
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/login",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "username=API-USER&password=%5BnWu%3Bx%3E8Eny%3BbSs%40",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded",
		    "postman-token: 49a8d541-073c-8569-b3c3-76319f67e552"
		  ),
		  CURLOPT_TIMEOUT=> 90000000
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}
	}
	public function getVariation($id){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/variations?id=".$id,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return $response;
		}
	}
	public function customerDetail($orderId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/orders/".$orderId."?with[]=addresses&with[]=relation",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "postman-token: 416bc02e-dffa-1fb1-b443-9fa00dc4c675"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  	$response = json_decode($response, TRUE);
			$detailArray = array();
			$detailArray['date'] = date('Y/m/d h:i:s')." +0000";
			$detailArray['recipient'] = $response['addresses'][0]['name1'];			
			$detailArray['street_name'] = $response['addresses'][0]['address1'];
			$detailArray['address_number'] = $response['addresses'][0]['address2'];
			$detailArray['zip'] = $response['addresses'][0]['postalCode'];
			$detailArray['city'] = $response['addresses'][0]['town'];
			$countryId = $response['addresses'][0]['countryId'];
			$countryCode = $this->getCountryCode($countryId);
			$detailArray['countrycode'] = $countryCode;
			$prefix = (explode(" ",$response['relations'][1]['contactReceiver']['privatePhone']));
			$detailArray['prefix'] = $prefix[0];
			$number = '';
			foreach ($prefix as $value) {
				if ($value != $prefix[0]) {
					$number .= $value;
				}
			}
			$detailArray['number'] = $number;	

		  return $detailArray;
		}
	}

	public function getCountryCode($countryId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/orders/shipping/countries",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
			$response = json_decode($response, TRUE);
			foreach ($response as $value) {
				if ($value['id'] == "$countryId") {
					$result = $value['isoCode2'];
					break;
				}
			}
		  return $result;
		}
	}

	public function orderStatusOrderId($orderNumber){

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/clientorders/serverkey/".$orderNumber,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 9000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
<<<<<<< HEAD
		  $xml = simplexml_load_string($response); 
			$json = json_encode($xml);
			$arrayData = json_decode($json,TRUE); 
		  return $arrayData;
=======
		  // return $response;
		  return $response;
>>>>>>> bff084d3594bba0d083e26ad5105d8439f069cf1
		}
	}
}