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
		
		$flag = $this->getAllItems($brand);		
		
		if ($flag == 1) 
			$data = "Items created successfully.";
		else
			$data = "Somthing went wrong.";
		return $twig->render('HelloWorld::content.importProduct',array('data' => $data));
	}
	public function getAllItems($brand){
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
	        $array = json_decode($json,TRUE); 
	      	   $flag = 0;   	      
	      if (is_array($array['items']['item'])) {
	        foreach ($array['items']['item'] as $items) {
	          
	             $arritem = $this->createItem($items);
	             // print_r($arritem);
	             if(empty($arritem['variationId'])) continue;
	             // Activate item
	              $status = $this->ActiveItem($arritem['itemId'], $arritem['variationId'], $items );

	             if($status == false) continue;
	             $salesPrice = $this->salesPrice($arritem['variationId'],$items);
	             $barcode = $this->linkingBarcode($arritem['itemId'], $arritem['variationId'], rand(10,1000000));

	             $discription = $this->ItemDiscription($arritem['itemId'], $arritem['variationId'], $items['name'], '');
	             $this->uploadImages($items);
	             $this->createSubVariation($arritem['itemId'], $arritem['variationId'], $items);
	             $flag = 1;
	             exit;
	        }
	        return $flag;

	      }else{

	      }			

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
	public function createItem($items){
    $login = $this->login();
    $login = json_decode($login, true);
    $access_token = $login['access_token'];
    $host = $_SERVER['HTTP_HOST'];

    $curl = curl_init();
    if(empty($items)) return "";
    $title = $items['name'];
    $itemId = $items['id'];
    $manufacturerId = $this->getManufacturer($items);
    $catId = $this->getCategory($items);
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://".$host."/rest/items",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 90000000,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\n\t\"id\":$itemId,\n\t\"title\": \"$title\",\n\t\"stockType\": 0,\n\t\"variations\": [{\n\t\t\"variationCategories\": [{\n\t\t\t\"categoryId\": $catId\n\t\t}],\n\t\t\"unit\": {\n\t\t\t\"unitId\": 1,\n\t\t\t\"content\": 1\n\t\t}\n\t}],\n\t\"manufacturerId\": $manufacturerId\n}",
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
      echo "cURL Error #:" . $err;
    } else {
      $response =(json_decode($response,true));
      $variationId = $response['mainVariationId'];
      if(empty($variationId)) return;
      // Activate the item and return to main function

      return array('itemId' => $itemId, 'variationId' => $variationId);
    }
  }
  	public function getManufacturer($items){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();
		$brand = $items['brand'];
		if(empty($brand))return;

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/manufacturers?name=".$brand,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  $result = json_decode($response, TRUE);
		  if(isset($result['entries'][0]['id'])){
		    return $result['entries'][0]['id'];
		  }else{
		    return $this->creatManufacturer($brand);
		  }
		}
  	}
  	public function creatManufacturer($brand){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/manufacturers",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n\t\"name\": \"$brand\"\n}",
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
		  echo "cURL Error #:" . $err;
		} else {
		  $response = json_decode($response,TRUE);
		  if(!empty($response)){
			    return $response['id'];
			}
		}
	}
	public function ActiveItem($itemId, $variationId, $items ){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

	    $curl = curl_init();
	    $model = isset($items['models']['model']['availability'])?$items['models']['model']:$items['models']['model'][0];
	    
	    //print_r($model);

	    $suggestedPrice = $items['suggestedPrice'];
	    $id = $model['barcode'];
	    $code = $model['code'];
	    $availability = $items['availability'];
	    $streetPrice = $items['streetPrice'];
	    $model = $model['model']; 
	    # get id of color
	    $purchasePrice = 0;
	    $avgPrice = 0;
	    $salePrice = $streetPrice;
	    if(!empty($suggestedPrice)){
	      $salePrice = $suggestedPrice;
	    }    
	    curl_setopt_array($curl, array(
	      CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationId."",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 90000000,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "PUT",
	      CURLOPT_POSTFIELDS => "{\n    \"isActive\": true,\n    \"purchasePrice\": $purchasePrice,\n    \"model\": \"$model\",\n    \"name\": \"$code\",\n    \"itemId\":\"$itemId\",\n    \"number\": \"$id\",\n    \"availability\": $availability,\n    \"movingAveragePrice\": $avgPrice,\n \"mainWarehouseId\": 104\n}",
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
	      echo $err;
	      //return "cURL Error #:" . $err;
	    } else {
	      //echo $response;
	      $response = json_decode($response, TRUE);
	      $isActive = $response['isActive'];
	      return $isActive;
	      
	      // return "true";
	    }
	    
	}
	public function salesPrice($variationId, $items){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();
		$salePrice = $items['streetPrice'];
	    if(!empty($items['suggestedPrice'])){
	      $salePrice = $items['suggestedPrice'];
	    }  
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/variations/variation_sales_prices",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 90000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "[{\n\t\"variationId\": $variationId,\n\t\"salesPriceId\": 2,\n\t\"price\": $salePrice\n}]",
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
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
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
	      CURLOPT_TIMEOUT => 90000000,
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
	      return $response;
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
	      return $response;
	    }
  	}
  	public function uploadImages($items){
	    $itemId = $items['id'];
	    $images = array();
	    if(isset($items['pictures']['image']['id'])) {
	      $images[] = $items['pictures']['image'];
	    }
	    else {
	      for($i=0; $i<count($items['pictures']['image']); $i++){

		      $images[] = $items['pictures']['image'][$i];
		    }

	    }

	    foreach($images as $image) {
	        $img = $this->uploadImage($itemId, $image['url'], $image['id']);
	    }
	}
   public function uploadImage($ItemId, $image, $imagevalue){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];
	    $img = $image;
	    $imgName = explode("/",$img);

	    $name[0] = array("lang" => "en","name" => "Stock product image");
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
	      CURLOPT_TIMEOUT => 90000000,
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
	      return $response;
	    }
  	}
  	public function createSubVariation($itemId, $variationId, $items){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

	    $models = array();
	    if(isset($items['models']['model']['availability'])) {
	      $models[] = $items['models']['model'];
	    }
	    else {
	      for($i=0; $i<count($items['models']['model']); $i++) {

	      $models[] = $items['models']['model'][$i];
	      }

	    }

	    if(empty($models)) return;
	    foreach($models as $model) {
	    $suggestedPrice = $model['suggestedPrice'];
	    $id = $model['id'];
	    $code = $model['code'];
	    $availability = $model['availability'];
	    $streetPrice = $model['streetPrice'];
	    $modelValue = $model['model'];
	    $barcode = $model['barcode'];
	    # get id of color
	    $purchasePrice = 0;
	    $avgPrice = 0;
	    $salePrice = $streetPrice;
	    if(!empty($suggestedPrice)){
	      $salePrice = $suggestedPrice;
	    }
	    $name_id = $this->searchAttributeName('Colour');
	    $colorValue = $this->searchAttributeValue($name_id,$model['color']);
	    $size_id = $this->searchAttributeName('Size');
	    $sizeValue = $this->searchAttributeValue($size_id,$model['size']);

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 900000000,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "POST",
	      CURLOPT_POSTFIELDS => "{\n    \"itemId\": $itemId,\n    \"isActive\": true,\n    \"purchasePrice\": $purchasePrice,\n    \"name\": \"$code\",\n    \"model\": \"$modelValue\",\n    \"number\": \"$id\",\n    \"availability\": $availability,\n    \"movingAveragePrice\": $avgPrice,\n    \"mainWarehouseId\": 104,\n    \"unit\": {\n        \"unitId\": 1,\n        \"content\": 1\n    },\n \"variationAttributeValues\": [\n        {\n            \"valueId\": $colorValue\n        },\n        {\n            \"valueId\": $sizeValue\n        }\n        ],\n   \"variationClients\": [\n        {\n            \"plentyId\": 42296\n        }\n  ],\n  \"variationBarcodes\": [{\n  \t\t\"barcodeId\":3,\n  \t\t\"code\": \"$barcode\"\n  \t}]\n}",
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
	      echo "cURL Error #: $id " . $err;
	    }
	    else {
	      $response = json_decode($response, TRUE);
		  $vid =  $response['id'];
		  if(!empty($id)) {
		  	$this->activateSubVariation($itemId, $vid);
			$this->bookIncomingStock($itemId, $vid, $items, $model);
		  }
	    }
	  }

  	}
  	public function searchAttributeName($name) {
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => "https://".$host."/rest/items/attributes",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 900000000,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "GET",
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Bearer $access_token",
	        "cache-control: no-cache",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if ($err) {
	      return "cURL Error #:" . $err;
	    } else {
	      	$response = json_decode($response, TRUE);
	      	$entries = $response['entries'];
	      	foreach ($entries as $entry) {
		        if($entry['backendName'] == $name) {
		            return $entry['id'];
		            break;
		        }
	        }
	        return '';
	    }      
  	}

  	public function searchAttributeValue($id,$value) {
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

    	$curl = curl_init();

	    curl_setopt_array($curl, array(
	      CURLOPT_URL => "https://".$host."/rest/items/attributes/".$id."/values",
	      CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	      CURLOPT_MAXREDIRS => 10,
	      CURLOPT_TIMEOUT => 900000000,
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	      CURLOPT_CUSTOMREQUEST => "GET",
	      CURLOPT_HTTPHEADER => array(
	        "authorization: Bearer $access_token",
	        "cache-control: no-cache",
	      ),
	    ));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);

	    curl_close($curl);

	    if ($err) {
	      return "cURL Error #:" . $err;
	    } else {
	      $response = json_decode($response, TRUE);
	      $entries = $response['entries'];
	      //print_r($entries); exit;
	      foreach ($entries as $entry) {
	        if($entry['backendName'] == "$value") {
	            return $entry['id'];
	            break;
	        }
	        }
	        // No match create attribute value
	        $valId = $this->createAttributeValue($id, $value);
	        return $valId;

	    }
	}
	public function createAttributeValue($id,$value) {

	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

	    $curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/attributes/".$id."/values",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n    \"attributeId\": $id,\n    \"backendName\": \"$value\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  $response = json_decode($response, TRUE);
		  $value_id =  $response['id'];
		  // set name of value
		  $this->setValueName($value_id, 'en', $value);
		  $this->setValueName($value_id, 'de', $value);
		  return $value_id;
		}
	}
	public function setValueName($valueId, $lang, $name) {
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

	    $curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/attribute_values/".$valueId."/names",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{\n    \"valueId\": $valueId,\n    \"lang\": \"$lang\",\n    \"name\": \"$name\"\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo $err;
		} else {
		  $response = json_decode($response, TRUE);
		  return $value_id =  $response['valueId'];
		  // set name of value
		}
	}
	public function getCategory($items){
	    $tags = array();
	    if(isset($items['tags']['tag'][0]['id']))
	      $tags = $items['tags']['tag'];
	    else
	      $tags[0] = $items['tags']['tag'];
	    if(empty($tags) || empty($tags[0]))return;
	    $catName = "";
	    foreach ($tags as $tag) {
	      if($tag['name'] == "category"){
	        $catName = $tag['value']['value'];
	        break;
	      }
	    }
	    if (empty($catName)) return;
	    	$catId =  $this->searchCategory($catName);
	    if(empty($catId)) {
			// Create category
			$catId = $this->createCategory($catName);
		}
		return $catId;
  	}
  	public function searchCategory($catName){
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/categories/?name=".$catName,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  $response = json_decode($response,TRUE);
		  if(empty($response['entries'])) return "";
		  return $response['entries'][0]['id'];
		}
  	}
  	public function createCategory($name) {
	    $login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/categories",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "[\n        {\n            \"parentCategoryId\": null,\n            \"type\": \"item\",\n            \"right\": \"all\",\n            \"details\": [\n                {\n                    \"plentyId\": 42296,\n                    \"lang\": \"en\",\n                    \"name\": \"$name\"\n                }\n            ],\n            \"clients\": [\n                {\n                    \"plentyId\": 42296\n                }\n            ]\n        }\n    ]",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  $response = json_decode($response,TRUE);
		   if(isset($response[0]['id']))
			return $response[0]['id'];
			else
			return "";
		}
	}
	public function bookIncomingStock($itemsId, $variationId, $items, $model) {
		    $login = $this->login();
		    $login = json_decode($login, true);
		    $access_token = $login['access_token'];
		    $host = $_SERVER['HTTP_HOST'];

			$curl = curl_init();
			$dt = date("Y-m-d")."T".date("G:i:s")."+01:00";
			$currency = $items['currency'];
			$purchasePrice = "0.00";
			$qty = $model['availability'];

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemsId."/variations/".$variationId."/stock/bookIncomingItems",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => "{\n    \"warehouseId\": 104,\n    \"deliveredAt\": \"$dt\",\n    \"currency\": \"$currency\",\n    \"quantity\": $qty,\n    \"purchasePrice\":$purchasePrice,\n    \"reasonId\": 101\n\n}",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Bearer $access_token",
		    "cache-control: no-cache",
		    "content-type: application/json",
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  echo $response;
		}
  	}
	public function activateSubVariation($itemId, $variationId){
		$login = $this->login();
	    $login = json_decode($login, true);
	    $access_token = $login['access_token'];
	    $host = $_SERVER['HTTP_HOST'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://".$host."/rest/items/".$itemId."/variations/".$variationId,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 900000000,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "PUT",
		  CURLOPT_POSTFIELDS => "{\n    \"isActive\": true\n   \n    \n}",
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
		  echo "cURL Error #:" . $err;
		} else {
		  // echo $response;
		}
	}
	
}

