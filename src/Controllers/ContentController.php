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
		// echo $login;exit;
		//$Items = $this->getAllItems();
		// $Item = "{\"2\":{\"id\":\"98084\",\"name\":\"5526\",\"categories\":[{\"categoryId\":33}]}}";
		// $storeItemsToPlenty = $this->storeItemsToPlanty($Item);
		return $twig->render('HelloWorld::content.importProduct',array('data' => $login));
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
	public function storeItemsToPlanty($Items){
	
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
		    "authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjU5Y2EzOGZkN2M2YWQwYzAxMWE5OTE3YmFkNWM0OTE3ZGU3MjcwZWIyYmIzZjA2ZWFkNmY2MzQ4ODAzNTY1MmExNjJhYWZmMGZjMzk5MTdlIn0.eyJhdWQiOiIxIiwianRpIjoiNTljYTM4ZmQ3YzZhZDBjMDExYTk5MTdiYWQ1YzQ5MTdkZTcyNzBlYjJiYjNmMDZlYWQ2ZjYzNDg4MDM1NjUyYTE2MmFhZmYwZmMzOTkxN2UiLCJpYXQiOjE1NTA0NzIwMDMsIm5iZiI6MTU1MDQ3MjAwMywiZXhwIjoxNTUwNTU4NDAzLCJzdWIiOiI2Iiwic2NvcGVzIjpbIioiXX0.ummFx9nvLbmbfRcKmi-G7dIXVxKpQH5MhAXXKhqVv4mXbMYlLxCjeBgxKcYewbAatomCcEz4twDAPU30AmLkA2iOyGehfGgL1I8w8kX1xr8-Xelz7ISYe9dBfzRf25zhrYrAe7emDbAgk-TVn6NOF_2Ic7zBNIiFKoaYM18G7-WEEZUZ6AKut1KSNineyUELsA69QQRDP0iG7QbfHvgOhx5XMg0KIUhG7l28oBv693SjSXr85ELI6gWJeVifnIY7T2mr7-qDfgVjj0mQw5H_u0-zO_R6tKfC_809KVASBXCBemGlGpLn8NMyfXGqFTPQi-myBgs2yOeSmBs0YUvTpOQFJAMT9gX0gMJARytDOTeVk_YO1siNppFrpL95fTEh8TpAVp9fvOP2uUyfWFV2DXgQR2_6i7shgjW-URAFGc6ZBGztk84sn_M38gCzaDDiLYHRDGf8ZpxGCRYreXOwNSzVaOPOIW0oV5VSbbZB29oKb5RIo4CVcFS6aka7jmr9yV9q2I2EbU6KV17Elm4wRbhbfIN1y75Gtosw77rESry3z8qk4F1R4evG_5m3kgmtK4PDx5Sr052I8_MdVYgO0Qi9Gr8evjcpZ718FYaBNGpwbIfyw8OYzXE7torJCkKL_6QmW3ht1Applr4YB-nEbK80lE7lnYqQ-JiAeU6gIxs",
		    "cache-control: no-cache",
		    "content-type: application/json",
		    "postman-token: 1e6ea49e-1d39-d2e4-bdd6-ae2cdbd08c04"
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