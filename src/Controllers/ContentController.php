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
		$storeItemsToPlenty = $this->storeItemsToPlanty($Items, $access_token);
		// header('Location: /home?message=success');
		return $twig->render('HelloWorld::content.mainView',array('data' => $storeItemsToPlenty));
	}
	public function getAllItems($brand){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/export/api/products.xml?Accept=application%2Fxml&tag_1=".$brand,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
		    "cache-control: no-cache",
		    "content-type: application/xml"
		  ),
		  CURLOPT_TIMEOUT=> 90000000
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
		  	
			$xml = simplexml_load_string($response); 
			$json = json_encode($xml);
			$array = json_decode($json,TRUE); 
			$categoryArray = array("men", "women");
			
		  $i=0;
	      $products = array();      
	      foreach ($array as  $value) {  
	        $sr = $i;
	        foreach ($value['item'] as $item) {
	          $products[$sr]['id'] = $item['id'];
	          $products[$sr]['name'] = $item['name'];
	          foreach ($item['tags']['tag'] as $category) {
	          	if ($category['name'] == 'gender') {
	          		if ($category['value']['value'] == 'men') {
	          			$categoryId = 74;
	          		}else if ($category['value']['value'] == 'women') {
	          			$categoryId = 33;
	          		}else{
	          			$categoryId = 154; //other category
	          		}
	          	}
	            if ($category['name'] == 'category') {
	            	
	             //$products[$sr]['categories'][] = $category['value']['value'];
	              $categories = array("categoryId"=>$categoryId);
	             $products[$sr]['categories'][] = $categories;
	            }
	            
	          }
	          
	          $sr++;
	        }        
	        
	        $i++;
	      } 
	      return (json_encode($products));

		}
	}
	public function storeItemsToPlanty($Items, $access_token){
	
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://07adb5fd142e0c1c833bd912a158fa7a8750ee4e.plentymarkets-cloud-ie.com/rest/item_sets",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
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
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://07adb5fd142e0c1c833bd912a158fa7a8750ee4e.plentymarkets-cloud-ie.com/rest/login",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "username=API-USER&password=%5BnWu%3Bx%3E8Eny%3BbSs%40",
		  CURLOPT_HTTPHEADER => array(
		    "cache-control: no-cache",
		    "content-type: application/x-www-form-urlencoded",
		    "postman-token: 49a8d541-073c-8569-b3c3-76319f67e552"
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
}