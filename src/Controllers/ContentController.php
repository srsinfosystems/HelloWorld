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
	public function sayHello(Twig $twig):string
	{
		return $twig->render('HelloWorld::content.hello');
	}

	public function importProduct(Twig $twig):string
	{
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		
		$Items = $this->getAllItems();
		$Item = "{\"2\":{\"id\":\"98084\",\"name\":\"5526\",\"categories\":[{\"categoryId\":33}]}}";
		$storeItemsToPlenty = $this->storeItemsToPlanty($Item, $access_token);
		return $twig->render('HelloWorld::content.importProduct',array('data' => $storeItemsToPlenty));
	}
	public function getAllItems(){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://www.brandsdistribution.com/restful/export/api/products.xml?Accept=application%2Fxml&tag_26=women",
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
			$categoryArray = array("74"=>"Men", "33"=>"Women", "32"=>"Accessories", "31"=>"Top Trending", "29"=>"Sales", "154"=>"Other", "118"=>"By Brand");
			//return $twig->render('HelloWorld::content.importProduct',array('data' => $json));

		  $i=0;
	      $products = array();      
	      // echo "<pre>";
	      foreach ($array as  $value) {  
	        // print_r($value['item']);
	        $sr = $i;
	        foreach ($value['item'] as $item) {
	          $products[$sr]['id'] = $item['id'];
	          $products[$sr]['name'] = $item['name'];
	          foreach ($item['tags']['tag'] as $category) {
	            if ($category['name'] == 'category') {
	             //$products[$sr]['categories'][] = $category['value']['value'];
	              $categories = array("categoryId"=>33);
	             $products[$sr]['categories'][] = $categories;
	            }
	            
	          }
	          
	          $sr++;
	        }        
	        
	        $i++;
	      } //exit;
	      return (json_encode($products));

		}
	}
	public function storeItemsToPlanty($Items, $access_token){
	
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://f0d711a79f1ef14d0aa44fada04b3451c8d936d2.plentymarkets-cloud-ie.com/rest/item_sets",
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
		  CURLOPT_URL => "https://f0d711a79f1ef14d0aa44fada04b3451c8d936d2.plentymarkets-cloud-ie.com/rest/login",
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