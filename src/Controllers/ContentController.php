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
		$Items = $this->getAllItems($brand);
		//$Item = "{\"2\":{\"id\":\"98084\",\"name\":\"5526\",\"categories\":[{\"categoryId\":33}]}}";

		
		//$storeItemsToPlenty = $this->storeItemsToPlanty($Items, $access_token);
		return $twig->render('HelloWorld::content.importProduct',array('data' => $Items));
	}
	public function getAllItems($brand){		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/export/api/products.xml?Accept=application%2Fxml&tag_1=".$brand,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		  CURLOPT_TIMEOUT=> 900000000
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
			
			$arrCheck = $arrayData;
		  $i=0;
	      $products = array(); 
	      $ItemResponseArray = array(); 

	       if (!empty($arrCheck['items']['item']['availability'])) {
           $ItemResponse = $this->createItem($arrCheck['items']['item']['name']);
           
	       $ItemResponse = json_decode($ItemResponse,TRUE);
	       $ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       $ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];

	       $linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,1000000));
	       $linkingBarcode = json_decode($linkingBarcode,TRUE);
	       $ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];

	       $activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $arrCheck['items']['item']['streetPrice']);
	       $activeItem = json_decode($activeItem,TRUE);
	       $ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];
		   
		   $no = 0;
            foreach ($arrCheck['items']['item']['pictures']['image'] as $picture) {                
                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
                // echo $ImageResponse;exit;
                $ImageResponse = json_decode($ImageResponse,TRUE);
               $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
                $ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
                $no++;
            }
			return json_encode($ItemResponseArray);exit;
	    }
	      echo "else == ";
	       echo "no response  == ";
	      foreach ($arrayData as $value) {  
	      echo "else loop == ";

	        $sr = $i;
	        foreach ($value['item'] as $item) { 
	      echo "else item loop";

	        	echo $products[$sr]['id'] = $item['id'];
            	$products[$sr]['name'] = $item['name'];
            	$categories = array("categoryId"=>155);
            	$products[$sr]['categories'][] = $categories;
	          //$products[$sr]['name'] = $item['name'];
            	
	          $ItemResponse = $this->createItem($item['name']);
	          echo $ItemResponse;exit;
	          $ItemResponse = json_decode($ItemResponse,TRUE);

	          $ItemResponseArray[$i]['Item']['id'] = $ItemResponse['id'];
	       	  $ItemResponseArray[$i]['variation']['VariationId'] = $ItemResponse['mainVariationId'];
	          /*$variation = $this->createVariation($ItemResponse['id']);
	          $variationResponse = json_decode($variation,TRUE);*/

	          $activeItem = $this->ActiveItem($ItemResponse['id'], $ItemResponse['mainVariationId'], $item['streetPrice']);
	          $activeItem = json_decode($activeItem,TRUE);	          
	          $ItemResponseArray[$i]['variation']['activeItem'] = $activeItem['isActive'];

	          $linkingBarcode = $this->linkingBarcode($ItemResponse['id'], $ItemResponse['mainVariationId'], rand(10,100000));
	          $linkingBarcode = json_decode($linkingBarcode,TRUE);
	          $ItemResponseArray[$i]['variation']['barcode'] = $linkingBarcode['code'];
	         $no = 0;
	          foreach ($item['pictures']['image'] as $picture) {
	                /*$products[$sr]['image_url'][] = "https://www.brandsdistribution.com".$picture['url'];*/
	                $ImageResponse = $this->uploadImage($ItemResponse['id'],$picture['url'], $picture['id']);
                	$ImageResponse = json_decode($ImageResponse,TRUE);
	                $ItemResponseArray[$i]['images'][$no]['id'] = $ImageResponse['id'];
                	$ItemResponseArray[$i]['images'][$no]['url'] = $ImageResponse['url'];
	            } echo "after image";
	            $no++;
	         
	          $sr++;
	        }       echo "after item"; 
	        
	        $i++;
	      } echo "after array";
	      // echo json_encode($ItemResponseArray);exit;
	 
	  
	      return(json_encode($ItemResponseArray));
	      // return(json_encode($ItemResponse));

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
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n\t\"title\": \"$title\",\n\t\"variations\": [{\n\t\t\"variationCategories\": [{\n\t\t\t\"categoryId\": 155\n\t\t}],\n\t\t\"unit\": {\n\t\t\t\"unitId\": 1,\n\t\t\t\"content\": 1\n\t\t}\n\t}]\n}",
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/json",
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		  CURLOPT_TIMEOUT=> 900000000
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
		 // CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $requestdata,
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		  CURLOPT_TIMEOUT=> 900000000
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
	public function storeItemsToPlanty($Items, $access_token){
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/item_sets",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => $Items,
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
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
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer ".$access_token,
		    "cache-control: no-cache",
		    "content-type: application/json",
		    "postman-token: 8a2c3500-dd2e-6ac7-cc50-637991e0222e"
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

	public function getOrder($access_token){
		$host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://".$host."/rest/orders",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  //CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer $access_token",
    "cache-control: no-cache",
    "postman-token: e35ffc09-3b07-9c0a-11dd-5853d81af683"
  ),
  CURLOPT_TIMEOUT=> 90000000
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
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\"itemId\": \"$ItemId\",\"uploadFileName\": \"stock_product_image_97783_673693377.jpg\", \"uploadUrl\": \"https://www.brandsdistribution.com/prod/stock_product_image_97783_673693377.jpg\",\"names\": [{\"lang\": \"en\",\"name\": \"Red plentymarkets tee\"}],\"availabilities\": [{\"type\": \"mandant\",\"value\": 42296}]}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
		  ),
		  CURLOPT_TIMEOUT=> 90000000
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
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache"
		  ),
		  CURLOPT_TIMEOUT=> 90000000
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
		  //CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n    \"barcodeId\": 3,\n    \"code\": \"$code\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json"
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

	public function ActiveItem($itemId, $variationId, $purchasePrice){
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
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => "{\n    \"isActive\": true,\n    \"purchasePrice\": $purchasePrice \n}",
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
		  // return $response;
		  return "true";
		}
	}
}