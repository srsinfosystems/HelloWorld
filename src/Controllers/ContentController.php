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
	public function home(Twig $twig):string
	{	
		$message = $_GET['message'];
		if (!empty($message)) {
			return $twig->render('HelloWorld::content.mainView',array('data' => "success"));
		}else{
			return $twig->render('HelloWorld::content.mainView');
		}
		

	}
	public function sayHello(Twig $twig):string
	{
		return $twig->render('HelloWorld::content.mainView');
	}

	public function importProduct(Twig $twig):string
	{
		//echo $_REQUEST;
		
		 $brand = $_GET['brand'];		
		
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];

		$modelNoArray = $this->getAllModelNo();
		$Items = $this->getAllItems($brand, $modelNoArray);
		//$Item = "{\"2\":{\"id\":\"98084\",\"name\":\"5526\",\"categories\":[{\"categoryId\":33}]}}";

		$data = json_encode($Items);
		//$storeItemsToPlenty = $this->storeItemsToPlanty($Items, $access_token);
		return $twig->render('HelloWorld::content.importProduct',array('data' => $data));
	}
	public function getAllItems($brand, $modelNoArray){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/export/api/products.xml?Accept=application%2Fxml&tag_1=".$brand,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  )
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
			//echo $arrayData['items']['item']['availability'];
			$i=0;
			if($arrayData['items']['item']){
				if($arrayData['items']['item']['availability']){
					// echo "single";
					$modelNo = $arrayData['items']['item']['models']['model']['id'];
					if (!in_array($modelNo, $modelNoArray)) {
					
					$ItemResponse = $this->createItem($arrayData['items']['item']['name']);    
	       			
	       			$ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       			$ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];

	       			$linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,1000000));
	       			
	       			$ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];

	       			if ($arrayData['items']['item']['models']['model']) {
	      				if ($arrayData['items']['item']['models']['model']['id']) {
	      					$modelId = $arrayData['items']['item']['models']['model']['id'];
	      				}else{
	      					$modelId = $arrayData['items']['item']['models']['model'][0]['id'];
	      				}

	      			}

	       			$activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $arrayData['items']['item']['streetPrice'], $modelId);
	       			$ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];
	       			$ItemResponseArray[$i]['variation']['purchasePrice'] = $activeItem['purchasePrice'];
            		$ItemResponseArray[$i]['variation']['model'] = $activeItem['model'];
            		$setSKU = $this->setSKU($ItemResponse['id'], $ItemResponse['mainVariationId']);
	      			
	      			$ItemResponseArray[$i]['sku']['initialSku'] = $setSKU['initialSku'];
	      			$ItemResponseArray[$i]['sku']['sku'] = $setSKU['sku'];

	      			$ItemDiscription = $this->ItemDiscription($ItemResponse['id'], $ItemResponse['mainVariationId'], $arrayData['items']['item']['name'], $arrayData['items']['item']['description']);
	      			
	      			$ItemResponseArray[$i]['Item']['name'] = $ItemDiscription['name'];
	      			$ItemResponseArray[$i]['Item']['discription'] = $ItemDiscription['description'][0];

	       			$no = 0;
	       				if ($arrayData['items']['item']['pictures']['image']) {
	       				
		       				if ($arrayData['items']['item']['pictures']['image']['id']) {
		       					$ImageResponse = $this->uploadImage($ItemResponse['id'],$arrayData['items']['item']['pictures']['image']['url'], $arrayData['items']['item']['pictures']['image']['id']);
		       						
		       						 $islink = $this->LinkImageTOVariation($ItemResponse['id'], $ItemResponse['mainVariationId'],$ImageResponse['id']);
		       						$ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $ItemResponseArray[$i]['images'][$no]['link'] = $islink;
		       				}else{
					            foreach ($arrayData['items']['item']['pictures']['image'] as $picture) {                
					                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
					                // echo $ImageResponse;exit;
					                $islink = $this->LinkImageTOVariation($ItemResponse['id'], $ItemResponse['mainVariationId'],$ImageResponse['id']);
					               $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $ItemResponseArray[$i]['images'][$no]['link'] = $islink;
					                $no++;
					            }
					        }
				    	}else{
				    		$ItemResponseArray[$i]['images'][$no]['id'] = "not available";
					        $ItemResponseArray[$i]['images'][$no]['url'] = "not available";
				    	}
				    	return $ItemResponseArray;
				    }
		        } else{

					foreach ($arrayData['items']['item'] as $value) { 

						$modelNo = $value['models']['model']['id'];
					if (!in_array($modelNo, $modelNoArray)) {

						$ItemResponse = $this->createItem($value['name']);	          
	          			
	          			$ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       				$ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];

	       				$linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,1000000));
	       				
	       				$ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];
	       				if ($arrayData['items']['item']['models']['model']) {
		      				if ($arrayData['items']['item']['models']['model']['id']) {
		      					$modelId = $arrayData['items']['item']['models']['model']['id'];
		      				}else{
		      					$modelId = $arrayData['items']['item']['models']['model'][0]['id'];
		      				}

		      			}
	       				$activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $value['streetPrice'], $modelId);
	       				
	       				$ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];
	       				$ItemResponseArray[$i]['variation']['purchasePrice'] = $activeItem['purchasePrice'];
            			$ItemResponseArray[$i]['variation']['model'] = $activeItem['model'];

            			$setSKU = $this->setSKU($ItemResponse['id'], $ItemResponse['mainVariationId']);
	      				$ItemResponseArray[$i]['sku']['initialSku'] = $setSKU['initialSku'];
	      				$ItemResponseArray[$i]['sku']['sku'] = $setSKU['sku'];

	      				$ItemDiscription = $this->ItemDiscription($ItemResponse['id'], $ItemResponse['mainVariationId'], $value['name'], $value['description']);
	      				$ItemResponseArray[$i]['Item']['name'] = $ItemDiscription['name'];
	      				$ItemResponseArray[$i]['Item']['discription'] = $ItemDiscription['description'][0];
	       				$no = 0;
	       				if ($value['pictures']['image']) {
	       				
		       				if ($value['pictures']['image']['id']) {
		       					$ImageResponse = $this->uploadImage($ItemResponse['id'],$value['pictures']['image']['url'], $value['pictures']['image']['id']);
		       						$islink = $this->LinkImageTOVariation($ItemResponse['id'], $ItemResponse['mainVariationId'],$ImageResponse['id']);
		       						$ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $ItemResponseArray[$i]['images'][$no]['link'] = $islink;
		       				}else{
					            foreach ($value['pictures']['image'] as $picture) {                
					                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
					                // echo $ImageResponse;exit;
					                $islink = $this->LinkImageTOVariation($ItemResponse['id'], $ItemResponse['mainVariationId'],$ImageResponse['id']);
					               $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
					                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
					                $ItemResponseArray[$i]['images'][$no]['link'] = $islink;
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
					return $ItemResponseArray;         
		        } 
		    }else{
		    	echo "No product  found";
		    }
	        exit;
			

		}
}
	public function createItem($title){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n\t\"title\": \"$title\",\n\t\"variations\": [{\n\t\t\"variationCategories\": [{\n\t\t\t\"categoryId\": 155\n\t\t}],\n\t\t\"unit\": {\n\t\t\t\"unitId\": 1,\n\t\t\t\"content\": 1\n\t\t}\n\t}]\n}",
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/json",
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  )
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return (json_decode($response,TRUE));
		}
	}
	public function uploadImage($ItemId, $image, $imagevalue){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];
		$img = $image;
		$imgName = explode("/",$img);
		$name[0] = array("lang" => "en","name" => "Red plentymarkets tee");
    	$availabilities[0] = array("type" => "mandant","value" => "$imagevalue");
    	$requestdata = Array(
		    "itemId" => "$ItemId",
		    "uploadFileName" => "$imgName[2]",
		    "uploadUrl" => "https://www.brandsdistribution.com".$image,
		    $name,
		    $availabilities
		);
		  $requestdata = json_encode($requestdata); 
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$ItemId."/images/upload",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		 CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $requestdata,
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  )
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return (json_decode($response,TRUE));
		}
	}
	public function storeItemsToPlanty($Items, $access_token){
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/item_sets",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $Items,
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  )
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
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "username=API-USER&password=%5BnWu%3Bx%3E8Eny%3BbSs%40",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded",
		    "postman-token: 49a8d541-073c-8569-b3c3-76319f67e552"
		  )
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
	public function stockManagement(Twig $twig):string
	{
		$pageNo = 1;
		$records = array();
		$response = $this->updateStock();
		$array = json_decode($response,TRUE); 
		$pageNo = $array['page'] + 1;
		$lastPageNumber = $array['lastPageNumber'];
		$isLastPage = $array['isLastPage'];
		$records = $array['entries'];
		// array_push($records,$array['entries']);
		/*if ($pageNo > 1) {
			for ($i=$pageNo; $i < $lastPageNumber; $i++) { 
				$response = $this->updateStock($i);
				$array2 = json_decode($response,TRUE); 
				$pageNo = $array2['page'] + 1;
				$lastPageNumber = $array2['lastPageNumber'];
				$isLastPage = $array2['isLastPage'];
				array_push($records,$array2['entries']);
			}
			
		}else{

		}*/
		return $twig->render('HelloWorld::content.stockManagement',array('data' => $array));
		
	}
	public function updateStock($pageNo=null){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$curl = curl_init();
		if (!empty($pageNo)) {
			$pageNoString = "page=".$pageNo."&";
		}else{
			$pageNoString = '';
		}
		$host = $_SERVER['HTTP_HOST'];
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/stockmanagement/stock?".$pageNoString."warehouseId=104",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "content-type: application/json",
		    "postman-token: 8a2c3500-dd2e-6ac7-cc50-637991e0222e"
		  )
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

	public function getOrder($access_token){
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://".$host."/rest/orders",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 900000000,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer $access_token",
    "cache-control: no-cache",
    "postman-token: e35ffc09-3b07-9c0a-11dd-5853d81af683"
  )
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
  return "cURL Error #:" . $err;
} else {
	$xml = simplexml_load_string($response);
}
	}

	public function uploadItemImage($ItemId,$imgUrl){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$ItemId."/images/upload",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\"itemId\": \"$ItemId\",\"uploadFileName\": \"stock_product_image_97783_673693377.jpg\", \"uploadUrl\": \"https://www.brandsdistribution.com/prod/stock_product_image_97783_673693377.jpg\",\"names\": [{\"lang\": \"en\",\"name\": \"Red plentymarkets tee\"}],\"availabilities\": [{\"type\": \"mandant\",\"value\": 42296}]}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  )
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return json_decode($response,TRUE);
		}
	}
	public function createVariation($ItemId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$ItemId."/variations",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache"
		  )
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return json_decode($response,TRUE);
		}
	}

	public function linkingBarcode($ItemId, $variationId, $code){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$ItemId."/variations/".$variationId."/variation_barcodes",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n    \"barcodeId\": 3,\n    \"code\": \"$code\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  )
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return (json_decode($response,TRUE));
		}
	}

	public function ActiveItem($itemId, $variationId, $purchasePrice, $modelid){
		//echo $itemId." ".$variationId;exit;
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationId."",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => "{\n    \"isActive\": true,\n    \"purchasePrice\": $purchasePrice,\n    \"model\": \"$modelid\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {		  
		  return (json_decode($response,TRUE));
		}
	}
	public function setSKU($itemId, $variationNo){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationNo."/variation_skus",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n\t\"variationId\": $variationNo,\n\t\"marketId\": 2,\n\t\"accountId\": 2,\n\t\"initialSku\": $variationNo,\n\t\"sku\": $variationNo,\n\t\"isActive\": true,\n\t\"status\": \"ACTIVE\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return (json_decode($response,TRUE));
		}
	}
	public function getAllModelNo(){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
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
		  foreach ($response['entries'] as $value) {
		  	$modelNos = $this->getVariationModelNos($value['id']);		  	
		  }
		  return $modelNos;
		}
	}
	public function getVariationModelNos($itemId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
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
		  $modelNos = array();
		  foreach ($response['entries'] as $value) {
		  	if (!empty($value['model'])) {
		  		array_push($modelNos, $value['model']);
		  	}		  	
		  }
		  return $modelNos;
		}
	}
	public function ItemDiscription($itemId, $variationId, $ItemName, $discription){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationId."/descriptions",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\"itemId\": $itemId,\"lang\": \"en\",\"name\": \"$ItemName\",\"description\": \"$discription\"}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return (json_decode($response,TRUE));
		}
	}
	public function LinkImageTOVariation($itemId, $variationId, $imageId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		$host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationId."/variation_images",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\"variationId\": $variationId,\"itemId\": $itemId,\"imageId\": $imageId}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  return "Image linked with variation";
		}
	}
}