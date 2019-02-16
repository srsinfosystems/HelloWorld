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
		


		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://www.brandsdistribution.com/restful/export/api/products.xml?Accept=application%2Fxml",
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
		  	$xml = $response; 
			$json = json_encode($xml);
			$array = json_decode($json,TRUE); 
		  //print_r(json_encode($array));exit;
		//return $twig->render('HelloWorld::content.importProduct',['name'=>$array]);

		// load template
		$tpl = $twig->loadTemplate('HelloWorld::content.importProduct');

		// render template with our data
		echo $tpl->render(array(
			'data' => $array
		));

	}
}
}