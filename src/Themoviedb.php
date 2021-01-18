<?php 

namespace hkalman\Themoviedb;

class Themoviedb
{

	public function get_toprated_movies($ApiKey='', $movie_count=20, $version=0) {
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Paraméterek összeállítása
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$params = array(
			'api_key' => $ApiKey,
			'language' => 'en-US'
		);

		$movies=array();

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Toplista lekérdezése
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		for ($page=1; $page<=(ceil($movie_count/20)); $page++) {
			$params['page']=$page;
			$ret=$this->api_request('https://api.themoviedb.org/3/movie/top_rated',$params);
			if ($ret <> false) {
				if (isset($ret['results'])) {
					for ($i=0; $i<count($ret['results']); $i++) {
						if (count($movies)<$movie_count) {
							//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------
							// Bár mindegyik mező opcionális a dokumentáció szerint, de id nélkül nem kérdezhetők le a film adatai
							//
							// A place_no kiegészítést azért rakom mellé, mert frissítés esetén a sorrend helyes reprodukálásához a kapott adatok nem elegendők.
							//-----------------------------------------------------------------------------------------------------------------------------------------------------------------------
							if (is_int($ret['results'][$i]['id'])) {
								
								$movie = array (
									'place_no' => count($movies)+1,
									'movie_id' => $ret['results'][$i]['id'],
									'version' => $version
								);
			
								$movies[]=$movie;
							}
						}
					}
				}
			}
		}
		
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Darabszám ellenőrzése
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if (count($movies)<>$movie_count) {
			$this->email_alert('Nem '.$movie_count.' film adatai töltődtek le, kérem ellenőrizze a scriptet!');
			return false;
		} else {
			return $movies;
		}
	
	}
	
	

	public function get_genres($ApiKey='') {
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Paraméterek összeállítása
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$params = array(
			'api_key' => $ApiKey,
			'language' => 'en-US'
		);

		$genres=array();

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Műfajok lekérdezése
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		$ret=$this->api_request('https://api.themoviedb.org/3/genre/movie/list',$params);
		if ($ret <> false) {
			if (isset($ret['genres']) and is_array($ret['genres'])) {
				return $ret['genres'];
			} else {
				$this->email_alert('Nem helyesen töltődtek le a műfajok, kérem ellenőrizze a scriptet!');
				return false;
			}
		} else {
			$this->email_alert('Nem töltődtek le a műfajok, kérem ellenőrizze a scriptet!');
			return false;
		}
			
	}
	

	public function get_movie_details($ApiKey='', $movie_id=0) {
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Paraméterek összeállítása
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$params = array(
			'api_key' => $ApiKey,
			'append_to_response' => 'credits',
			'language' => 'en-US'
		);

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Filmadatlap lekérdezése
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		$ret=$this->api_request('https://api.themoviedb.org/3/movie/'.$movie_id,$params);
		if ($ret <> false) {
			if (isset($ret) and is_array($ret)) {
				return $ret;
			} else {
				$this->email_alert('Nem helyesen töltődött le a filmadatlap, kérem ellenőrizze a scriptet!');
				return false;
			}
		} else {
			$this->email_alert('Nem töltődött le a filmadatlap, kérem ellenőrizze a scriptet!');
			return false;
		}
			
	}

	

	public function get_director($ApiKey='', $director_id=0) {
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Paraméterek összeállítása
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$params = array(
			'api_key' => $ApiKey,
			'language' => 'en-US'
		);

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Rendező adatlapjának lekérdezése
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		$ret=$this->api_request('https://api.themoviedb.org/3/person/'.$director_id,$params);
		if ($ret <> false) {
			if (isset($ret) and is_array($ret)) {
				return $ret;
			} else {
				$this->email_alert('Nem helyesen töltődött le a rendező adatlapja, kérem ellenőrizze a scriptet!');
				return false;
			}
		} else {
			$this->email_alert('Nem töltődött le a rendező adalapja, kérem ellenőrizze a scriptet!');
			return false;
		}
			
	}


	private function email_alert($message='') {
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Ide lehetne egy email ertesítőt készíteni, hogy a hibáról értesüljünk is
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	}



	private function api_request($endpoint='', $params = array()) {

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// API URL összeállítása
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

		$url = $endpoint.(empty($params) ? '' : '?'.http_build_query($params));

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// API request
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Accept: application/json'
					));		
		$response=curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Ellenőrzizzük, hogy sikerült-e lekérdezni
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if ($response === FALSE) {

			$error_msg='API request hiba: '.$url.'; curl_errno: '.$errno.'; curl_error:'.$error;
			$this->email_alert($error_msg);
			log_message('error',$error_msg);
			return false;

		}

		$json = json_decode($response, true);

		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		// Ellenőrzizzük, hogy JSON válasz jött-e vissza
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
		if (json_last_error() != false) {

			$error_msg='JSON dekódolási hiba: '.json_last_error();
			$this->email_alert($error_msg);
			log_message('error',$error_msg);
			return false;

		} else {

			return $json;

		}
	}
	
}
